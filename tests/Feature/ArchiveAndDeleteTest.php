<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\ChildRecord;
use App\Models\MaternalCheckup;
use App\Models\MaternalRecord;
use App\Models\Patient;
use App\Models\PostpartumVisit;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ArchiveAndDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Midwife',
            'email' => 'admin@maternal.com',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
        ]);
    }

    protected function patient(array $overrides = []): Patient
    {
        return Patient::create(array_merge([
            'first_name' => 'Elena',
            'last_name' => 'Cruz',
            'dob' => '1995-01-01',
            'gender' => 'Female',
            'phone' => '09171234567',
            'address' => 'Purok 1',
            'barangay' => 'Bicao',
            'emergency_contact_name' => 'Juan Cruz',
            'emergency_contact_phone' => '09222222222',
            'registration_type' => 'Maternal',
            'status' => 'Active',
        ], $overrides));
    }

    protected function archivedPatient(array $overrides = []): Patient
    {
        $patient = $this->patient($overrides);
        $patient->update(['status' => 'Archived']);

        return $patient;
    }

    // ---------- Hiding and showing ----------

    public function test_archive_hides_patient_from_records_by_default(): void
    {
        $this->patient(['first_name' => 'Zenobia']);
        $this->archivedPatient(['first_name' => 'Archibald', 'last_name' => 'Santos']);

        $response = $this->actingAs($this->admin)->get('/records');

        $response->assertOk();
        $response->assertSee('Zenobia');
        $response->assertDontSee('Archibald');
        $response->assertViewHas('totalPatients', 1);
    }

    public function test_show_archived_flag_lists_archived_patient(): void
    {
        $this->patient(['first_name' => 'Zenobia']);
        $this->archivedPatient(['first_name' => 'Archibald']);

        $response = $this->actingAs($this->admin)->get('/records?archived=1');

        $response->assertOk();
        $response->assertSee('Zenobia');
        $response->assertSee('Archibald');
        $response->assertViewHas('showArchived', true);
    }

    public function test_search_does_not_find_archived_patient(): void
    {
        $this->archivedPatient(['first_name' => 'Archibald', 'last_name' => 'Santos']);

        // The search box itself echoes the term, so the row is checked via the last name.
        $this->actingAs($this->admin)
            ->get('/records?search=Archibald')
            ->assertOk()
            ->assertSee('No patient records found.');

        $this->actingAs($this->admin)
            ->get('/records?search=Archibald&archived=1')
            ->assertOk()
            ->assertSee('Santos');
    }

    public function test_records_kpis_exclude_archived_patients(): void
    {
        $this->patient();
        $this->patient(['first_name' => 'Maria']);
        $this->archivedPatient(['first_name' => 'Archibald']);

        $response = $this->actingAs($this->admin)->get('/records');

        $response->assertOk();
        $response->assertViewHas('totalPatients', 2);
        $response->assertViewHas('maternalCases', 2);
    }

    public function test_dashboard_counts_exclude_archived_patients(): void
    {
        $this->patient();
        $this->archivedPatient(['first_name' => 'Archibald']);
        $this->patient(['registration_type' => 'Child', 'first_name' => 'Baby', 'dob' => '2026-01-01', 'gender' => 'Male']);
        $this->archivedPatient(['registration_type' => 'Child', 'first_name' => 'Ghost', 'dob' => '2026-02-01', 'gender' => 'Male']);

        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertOk();
        $response->assertViewHas('totalMothers', 1);
        $response->assertViewHas('totalChildren', 1);
    }

    public function test_masterlist_export_excludes_archived_by_default(): void
    {
        $this->patient(['first_name' => 'Zenobia', 'last_name' => 'Reyes']);
        $this->archivedPatient(['first_name' => 'Archibald', 'last_name' => 'Santos']);

        $content = $this->actingAs($this->admin)->get('/records/export')->assertOk()->streamedContent();
        $this->assertStringContainsString('Reyes', $content);
        $this->assertStringNotContainsString('Santos', $content);

        $withArchived = $this->actingAs($this->admin)->get('/records/export?archived=1')->assertOk()->streamedContent();
        $this->assertStringContainsString('Santos', $withArchived);
    }

    // ---------- Archive / restore lifecycle ----------

    public function test_reports_page_excludes_archived_patients(): void
    {
        $this->patient();
        $this->archivedPatient(['first_name' => 'Archibald']);

        $response = $this->actingAs($this->admin)->get('/reports');

        $response->assertOk();
        $response->assertViewHas('totalPatients', 1);
        $response->assertViewHas('maternalCases', 1);
    }

    public function test_archive_and_restore_roundtrip(): void
    {
        $patient = $this->patient();

        $this->actingAs($this->admin)
            ->from('/records')
            ->patch("/patients/{$patient->id}/archive")
            ->assertRedirect(route('records'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'status' => 'Archived']);

        $this->actingAs($this->admin)
            ->from('/records')
            ->patch("/patients/{$patient->id}/restore")
            ->assertRedirect(route('records'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'status' => 'Active']);
    }

    public function test_non_admin_cannot_archive_restore_or_delete(): void
    {
        $patient = $this->archivedPatient();
        $member = User::create([
            'name' => 'Portal Member',
            'email' => 'member@maternal.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
        ]);

        $this->actingAs($member)->patch("/patients/{$patient->id}/archive")->assertForbidden();
        $this->actingAs($member)->patch("/patients/{$patient->id}/restore")->assertForbidden();
        $this->actingAs($member)->delete("/patients/{$patient->id}")->assertForbidden();

        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'status' => 'Archived']);
    }

    public function test_archive_and_restore_are_audit_logged(): void
    {
        $patient = $this->patient();

        $this->actingAs($this->admin)->patch("/patients/{$patient->id}/archive");
        $this->actingAs($this->admin)->patch("/patients/{$patient->id}/restore");

        $this->assertTrue(AuditLog::where('action', 'archive_patient')->where('model_id', $patient->id)->exists());
        $this->assertTrue(AuditLog::where('action', 'restore_patient')->where('model_id', $patient->id)->exists());
    }

    public function test_archived_patient_can_still_log_in(): void
    {
        $login = User::create([
            'name' => 'Archibald Santos',
            'email' => 'archibald@maternal.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
        ]);
        $this->archivedPatient(['user_id' => $login->id, 'first_name' => 'Archibald']);

        $response = $this->from('/')->post('/', [
            'email' => 'archibald@maternal.com',
            'password' => 'secret123',
            'role' => 'user',
        ]);

        $response->assertRedirect('/portal');
    }

    // ---------- Permanent delete ----------

    public function test_delete_requires_archive_first(): void
    {
        $patient = $this->patient();

        $this->actingAs($this->admin)
            ->from('/records')
            ->delete("/patients/{$patient->id}")
            ->assertRedirect(route('records'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('patients', ['id' => $patient->id]);
    }

    public function test_mother_with_linked_children_cannot_be_deleted(): void
    {
        $mother = $this->archivedPatient(['first_name' => 'Martha']);
        $child = $this->patient(['registration_type' => 'Child', 'first_name' => 'Baby', 'dob' => '2026-01-01', 'gender' => 'Male']);
        ChildRecord::create(['patient_id' => $child->id, 'mother_id' => $mother->id]);

        $this->actingAs($this->admin)
            ->from('/records')
            ->delete("/patients/{$mother->id}")
            ->assertRedirect(route('records'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('patients', ['id' => $mother->id]);
        $this->assertDatabaseHas('patients', ['id' => $child->id]);
    }

    public function test_admin_can_delete_archived_patient(): void
    {
        $patient = $this->archivedPatient(['first_name' => 'Archibald']);

        $this->actingAs($this->admin)
            ->delete("/patients/{$patient->id}")
            ->assertRedirect(route('records'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
    }

    public function test_delete_cascades_clinical_records_and_sms(): void
    {
        $patient = $this->archivedPatient();
        $record = MaternalRecord::create(['patient_id' => $patient->id, 'lmp' => '2026-03-01']);
        MaternalCheckup::create(['maternal_record_id' => $record->id, 'visit_number' => 1, 'date' => '2026-08-20', 'weight_kg' => 60, 'bp' => '110/70']);
        PostpartumVisit::create(['maternal_record_id' => $record->id, 'date' => '2026-08-20']);
        SmsMessage::create(['patient_id' => $patient->id, 'phone_number' => '09171234567', 'message' => 'Hi', 'status' => 'Sent', 'sent_at' => now(), 'type' => 'Manual']);

        $this->actingAs($this->admin)->delete("/patients/{$patient->id}");

        $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
        $this->assertDatabaseMissing('maternal_records', ['id' => $record->id]);
        $this->assertDatabaseMissing('maternal_checkups', ['visit_number' => 1]);
        $this->assertDatabaseMissing('postpartum_visits', ['maternal_record_id' => $record->id]);
        $this->assertDatabaseMissing('sms_messages', ['message' => 'Hi']);
    }

    public function test_delete_removes_linked_login_and_chat_history(): void
    {
        $login = User::create([
            'name' => 'Archibald Santos',
            'email' => 'archibald@maternal.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
        ]);
        $patient = $this->archivedPatient(['user_id' => $login->id]);
        ChatMessage::create(['sender_id' => $login->id, 'receiver_id' => $this->admin->id, 'message' => 'Hello', 'is_read' => false]);
        ChatMessage::create(['sender_id' => $this->admin->id, 'receiver_id' => $login->id, 'message' => 'Hi there', 'is_read' => false]);

        $this->actingAs($this->admin)->delete("/patients/{$patient->id}");

        $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
        $this->assertDatabaseMissing('users', ['id' => $login->id]);
        $this->assertDatabaseMissing('chat_messages', ['sender_id' => $login->id]);
        $this->assertDatabaseMissing('chat_messages', ['receiver_id' => $login->id]);
    }

    public function test_delete_removes_the_patients_chat_attachment_files(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::fake('public');
        $login = User::create(['name' => 'Archibald Santos', 'email' => 'archibald@maternal.com', 'password' => Hash::make('secret123'), 'role' => 'user']);
        $other = User::create(['name' => 'Bea Cruz', 'email' => 'bea@maternal.com', 'password' => Hash::make('secret123'), 'role' => 'user']);
        $patient = $this->archivedPatient(['user_id' => $login->id]);

        foreach ([
            ['attachments/sent.pdf', $login->id, $this->admin->id, 'local'],
            ['attachments/reply.jpg', $this->admin->id, $login->id, 'local'],
            ['attachments/old-public.pdf', $login->id, $this->admin->id, 'public'],
            ['attachments/someone-else.pdf', $other->id, $this->admin->id, 'local'],
        ] as [$path, $from, $to, $disk]) {
            \Illuminate\Support\Facades\Storage::disk($disk)->put($path, 'file contents');
            ChatMessage::create(['sender_id' => $from, 'receiver_id' => $to, 'message' => null, 'is_read' => false,
                'attachment_path' => $path, 'attachment_name' => basename($path), 'attachment_type' => 'application/pdf']);
        }

        $this->actingAs($this->admin)->delete("/patients/{$patient->id}");

        \Illuminate\Support\Facades\Storage::disk('local')->assertMissing(['attachments/sent.pdf', 'attachments/reply.jpg']);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('attachments/old-public.pdf');
        // Another patient's file stays.
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists('attachments/someone-else.pdf');
        $this->assertSame(3, AuditLog::where('action', 'delete_patient')->sole()->details['deleted_attachments']);
    }

    public function test_delete_writes_final_audit_entry(): void
    {
        $patient = $this->archivedPatient(['first_name' => 'Archibald', 'last_name' => 'Santos']);

        $this->actingAs($this->admin)->delete("/patients/{$patient->id}");

        $log = AuditLog::where('action', 'delete_patient')->first();
        $this->assertNotNull($log, 'The permanent delete must leave its own audit entry');
        $this->assertStringContainsString('Archibald Santos', json_encode($log->details));
        $this->assertNull($log->model_id);
    }
}
