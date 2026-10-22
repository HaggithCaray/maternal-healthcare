<?php

namespace App\Services;

use App\Models\Immunization;
use App\Models\MaternalCheckup;
use App\Models\PostpartumVisit;
use App\Models\SmsMessage;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * The daily text-message reminders: a vaccine dose coming due, and the next prenatal and
 * postpartum visits. Each reminder carries a dedupe key (vaccine:{id}, nextvisit:{id},
 * postpartum:{id}) so a run never messages twice about the same thing — a key whose message
 * already went out as "Sent" is skipped, while a failed one is retried on the next run.
 *
 * Nothing sends unless the admin turned reminders on in the SMS settings. Archived patients
 * are never texted, a mother who has delivered gets no prenatal reminders, and a visit missed
 * longer ago than OVERDUE_WINDOW_DAYS is left to the staff rather than texted out of the blue.
 */
class ReminderService
{
    /** A missed visit is reminded about only within this many days of its due date. */
    public const OVERDUE_WINDOW_DAYS = 14;

    public function __construct(protected SmsService $sms) {}

    /**
     * Send every reminder that is due now.
     *
     * @return array{enabled: bool, sent: int, failed: int, skipped: int}
     */
    public function run(): array
    {
        $counts = ['enabled' => true, 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        if (! $this->sms->remindersEnabled()) {
            Log::info('reminders:send skipped: automated SMS reminders are off in the SMS settings.');
            $counts['enabled'] = false;

            return $counts;
        }

        foreach ($this->due() as $reminder) {
            $existing = SmsMessage::where('dedupe_key', $reminder['dedupe_key'])->first();

            if ($existing?->status === 'Sent') {
                $counts['skipped']++;

                continue;
            }

            $result = $this->sms->sendSms($reminder['phone'], $reminder['message']);
            $sent = (bool) $result['success'];

            try {
                if ($existing) {
                    // A failed send is retried by updating its own row, never by adding another.
                    $existing->update([
                        'message' => $reminder['message'],
                        'status' => $sent ? 'Sent' : 'Failed',
                        'sent_at' => $sent ? Carbon::now() : null,
                    ]);
                } else {
                    SmsMessage::create([
                        'patient_id' => $reminder['patient_id'],
                        'phone_number' => $reminder['phone'],
                        'message' => $reminder['message'],
                        'status' => $sent ? 'Sent' : 'Failed',
                        'sent_at' => $sent ? Carbon::now() : null,
                        'type' => 'Reminder',
                        'dedupe_key' => $reminder['dedupe_key'],
                    ]);
                }
            } catch (UniqueConstraintViolationException) {
                // An overlapping run claimed this key in between; it is being handled.
                $counts['skipped']++;

                continue;
            }

            $counts[$sent ? 'sent' : 'failed']++;
        }

        return $counts;
    }

    /**
     * The reminders that are due, in message order.
     *
     * @return iterable<int, array{patient_id: int, phone: string, message: string, dedupe_key: string}>
     */
    protected function due(): iterable
    {
        yield from $this->dueVaccines();
        yield from $this->duePrenatalVisits();
        yield from $this->duePostpartumVisits();
    }

    /**
     * Vaccine doses scheduled today or within the next two days that are not yet given.
     * The window is deliberately short: only what is actually coming up is messaged.
     */
    protected function dueVaccines(): iterable
    {
        $today = Carbon::today();

        $doses = Immunization::with(['childRecord.mother', 'childRecord.patient'])
            ->where('status', 'Scheduled')
            ->whereHas('childRecord.patient', fn ($q) => $q->where('status', '!=', 'Archived'))
            ->whereDate('scheduled_date', '>=', $today)
            ->whereDate('scheduled_date', '<=', $today->copy()->addDays(2))
            ->orderBy('id')
            ->lazy(500);

        foreach ($doses as $dose) {
            $child = $dose->childRecord?->patient;

            if (! $child) {
                continue;
            }

            // A child's contact details always resolve to its mother's, so the text reaches her.
            $phone = $dose->childRecord->mother?->phone ?? $child->phone;

            if (! filled($phone)) {
                continue;
            }

            yield [
                'patient_id' => $child->id,
                'phone' => $phone,
                'dedupe_key' => "vaccine:{$dose->id}",
                'message' => $this->vaccineMessage($child->first_name, $dose),
            ];
        }
    }

    /**
     * The newest prenatal checkup of each mother, when its next visit is today, tomorrow or
     * already overdue. Only the newest counts: an older checkup's visit has since happened.
     */
    protected function duePrenatalVisits(): iterable
    {
        $checkups = MaternalCheckup::with('maternalRecord.patient')
            ->whereIn('id', $this->latestPerRecord('maternal_checkups'))
            ->whereNotNull('next_visit_date')
            ->whereDate('next_visit_date', '<=', Carbon::tomorrow())
            ->whereDate('next_visit_date', '>=', $this->overdueCutoff())
            // Once she has delivered, prenatal visits are over; postpartum reminders take over.
            ->whereHas('maternalRecord', fn ($q) => $q->whereNull('delivery_date'))
            ->whereHas('maternalRecord.patient', fn ($q) => $q->where('status', '!=', 'Archived'))
            ->orderBy('id')
            ->lazy(500);

        foreach ($checkups as $checkup) {
            $mother = $checkup->maternalRecord?->patient;

            if (! $mother || ! filled($mother->phone)) {
                continue;
            }

            yield [
                'patient_id' => $mother->id,
                'phone' => $mother->phone,
                'dedupe_key' => "nextvisit:{$checkup->id}",
                'message' => $this->visitMessage($mother->first_name, 'prenatal checkup', $checkup->next_visit_date),
            ];
        }
    }

    /**
     * The newest postpartum visit of each mother, on the same today/tomorrow/overdue rule.
     */
    protected function duePostpartumVisits(): iterable
    {
        $visits = PostpartumVisit::with('maternalRecord.patient')
            ->whereIn('id', $this->latestPerRecord('postpartum_visits'))
            ->whereNotNull('next_visit_date')
            ->whereDate('next_visit_date', '<=', Carbon::tomorrow())
            ->whereDate('next_visit_date', '>=', $this->overdueCutoff())
            ->whereHas('maternalRecord.patient', fn ($q) => $q->where('status', '!=', 'Archived'))
            ->orderBy('id')
            ->lazy(500);

        foreach ($visits as $visit) {
            $mother = $visit->maternalRecord?->patient;

            if (! $mother || ! filled($mother->phone)) {
                continue;
            }

            yield [
                'patient_id' => $mother->id,
                'phone' => $mother->phone,
                'dedupe_key' => "postpartum:{$visit->id}",
                'message' => $this->visitMessage($mother->first_name, 'postpartum checkup', $visit->next_visit_date),
            ];
        }
    }

    /**
     * The earliest missed next-visit date still worth a text (see OVERDUE_WINDOW_DAYS).
     */
    protected function overdueCutoff(): Carbon
    {
        return Carbon::today()->subDays(self::OVERDUE_WINDOW_DAYS);
    }

    /**
     * A subquery for whereIn(): the newest row of each mother's visit history, which is the
     * only entry whose "next visit" date is still the one that counts.
     */
    protected function latestPerRecord(string $table): \Closure
    {
        return function ($query) use ($table) {
            $query->selectRaw('MAX(id)')->from($table)->groupBy('maternal_record_id');
        };
    }

    protected function vaccineMessage(string $childName, Immunization $dose): string
    {
        $due = match (true) {
            $dose->scheduled_date->isToday() => 'today',
            $dose->scheduled_date->isTomorrow() => 'tomorrow',
            default => 'on ' . $dose->scheduled_date->format('M d, Y'),
        };

        return "Barangay Bicao Health Station: Reminder that {$childName} is due for "
            . "{$dose->vaccine_name} dose {$dose->dose_number} {$due}. Please visit the health station. Thank you.";
    }

    protected function visitMessage(string $motherName, string $what, CarbonInterface $date): string
    {
        $timing = match (true) {
            $date->isToday() => 'is today',
            $date->isTomorrow() => 'is tomorrow',
            $date->isPast() => 'was due on ' . $date->format('M d, Y'),
            default => 'is on ' . $date->format('M d, Y'),
        };

        return "Barangay Bicao Health Station: Hi {$motherName}, your {$what} {$timing}. "
            . 'Please visit the health station. Thank you.';
    }
}
