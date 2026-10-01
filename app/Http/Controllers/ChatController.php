<?php

namespace App\Http\Controllers;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\StaffInboxUpdated;
use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    /**
     * Display messaging screen and handle sending messages/attachments.
     */
    public function messaging(Request $request)
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            return $this->adminMessaging($request, $user);
        } else {
            return $this->patientMessaging($request, $user);
        }
    }

    /**
     * The health station's shared inbox: every staff member sees every patient's conversation.
     */
    protected function adminMessaging(Request $request, User $user)
    {
        // Patient logins that belong to a patient record (e.g. not a login left over from a child),
        // waiting conversations first, then the most recently active.
        $patients = User::where('role', 'user')
            ->whereHas('patient')
            ->withCount(['sentMessages as unread_count' => fn ($q) => $q->where('is_read', false)])
            ->withMax('sentMessages as last_message_at', 'created_at')
            ->get()
            ->sortBy([['unread_count', 'desc'], ['last_message_at', 'desc'], ['name', 'asc']])
            ->values();

        if ($request->isMethod('post')) {
            $request->validate([
                'message' => 'required_without:file|nullable|string|max:2000',
                // Staff write to patients only.
                'receiver_id' => ['required', Rule::exists('users', 'id')->where('role', 'user')],
                'file' => self::ATTACHMENT_RULES,
            ]);

            $patientId = (int) $request->receiver_id;
            $newMessage = $this->storeMessage($request, $user, $patientId, $patientId);

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $newMessage]);
            }

            return redirect()->route('messaging', ['chat_user_id' => $patientId]);
        }

        // Conversations are with patient logins only, never another staff account.
        $activeChatUser = $request->query('chat_user_id')
            ? User::where('role', 'user')->find((int) $request->query('chat_user_id'))
            : $patients->first();

        $messages = collect();
        if ($activeChatUser) {
            $messages = ChatMessage::thread($activeChatUser->id)->with('sender:id,name,role')->orderBy('created_at')->orderBy('id')->get();

            $this->markReadByStaff($activeChatUser->id, $user);
        }

        return view('messaging', compact('patients', 'activeChatUser', 'messages'));
    }

    /**
     * A patient's conversation with the health station (all staff, not one person).
     */
    protected function patientMessaging(Request $request, User $user)
    {
        // Recorded as the receiver; any staff member can read and answer.
        $station = User::careTeamContact();

        if ($request->isMethod('post')) {
            $request->validate([
                'message' => 'required_without:file|nullable|string|max:2000',
                'file' => self::ATTACHMENT_RULES,
            ]);

            if (! $station) {
                $error = 'Messaging is unavailable: the health station has no active staff account.';

                return $request->expectsJson()
                    ? response()->json(['message' => $error], 503)
                    : redirect()->route('messaging')->with('error', $error);
            }

            $newMessage = $this->storeMessage($request, $user, $station->id, $user->id);

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $newMessage]);
            }

            return redirect()->route('messaging');
        }

        $messages = ChatMessage::thread($user->id)->with('sender:id,name,role')->orderBy('created_at')->orderBy('id')->get();

        $this->markReadByPatient($user);

        return view('patient.messaging', compact('station', 'messages'));
    }

    /**
     * Mark the open conversation read when messages arrive live (the page is already showing them).
     */
    public function markRead(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $data = $request->validate([
                'patient_id' => ['required', Rule::exists('users', 'id')->where('role', 'user')],
            ]);
            $this->markReadByStaff((int) $data['patient_id'], $user);
        } else {
            $this->markReadByPatient($user);
        }

        return response()->json(['success' => true, 'unread' => $user->unreadChatCount()]);
    }

    /**
     * A staff member saw the patient's messages: they are read for the whole team.
     */
    protected function markReadByStaff(int $patientId, User $staff): void
    {
        $read = ChatMessage::where('sender_id', $patientId)->where('is_read', false)->update(['is_read' => true]);

        if ($read > 0) {
            $this->broadcastSafely(fn () => event(new MessageRead($patientId, $staff->id)));
            $this->announceInbox($patientId);
        }
    }

    protected function markReadByPatient(User $patient): void
    {
        $read = ChatMessage::where('receiver_id', $patient->id)->where('is_read', false)->update(['is_read' => true]);

        if ($read > 0) {
            $this->broadcastSafely(fn () => event(new MessageRead($patient->id, $patient->id)));
        }
    }

    /**
     * Update the unread badges on every open staff page.
     */
    protected function announceInbox(int $patientId): void
    {
        $this->broadcastSafely(fn () => event(new StaffInboxUpdated(
            $patientId,
            ChatMessage::where('sender_id', $patientId)->where('is_read', false)->count(),
            ChatMessage::unreadFromPatientsCount(),
        )));
    }

    private const ATTACHMENT_RULES = 'nullable|file|max:25600|mimes:jpeg,jpg,png,webp,gif,pdf,doc,docx,xls,xlsx,mp4,mov';

    /**
     * Save a message (and its attachment) in the patient's conversation and announce it live.
     */
    protected function storeMessage(Request $request, User $sender, int $receiverId, int $patientId): ChatMessage
    {
        $attachment = ['attachment_path' => null, 'attachment_name' => null, 'attachment_type' => null];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $attachment = [
                'attachment_path' => $file->storeAs('attachments', Str::uuid() . '.' . $this->safeExtension($file), 'local'),
                'attachment_name' => $file->getClientOriginalName(),
                'attachment_type' => $file->getMimeType(),
            ];
        }

        $message = ChatMessage::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiverId,
            'message' => $request->message,
            'is_read' => false,
        ] + $attachment)->load('sender:id,name,role');

        $this->broadcastSafely(fn () => event(new MessageSent($message, $patientId)));
        if ($sender->id === $patientId) {
            $this->announceInbox($patientId);
        }

        AuditLog::log('send_chat_message', $message, [
            'has_attachment' => $attachment['attachment_path'] !== null,
            'receiver_id' => $receiverId,
            'patient_user_id' => $patientId,
        ]);

        return $message;
    }

    /**
     * Stream a chat attachment to one of the conversation's participants (or staff).
     * Attachments live on the private disk so they are never reachable by bare URL.
     */
    public function attachment(ChatMessage $message)
    {
        $this->authorize('view', $message);
        abort_unless($message->attachment_path, 404);

        // Uploads made before attachments went private may still be on the public disk.
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($message->attachment_path)) {
                $name = str_replace(['/', '\\'], '_', $message->attachment_name ?: basename($message->attachment_path));
                $fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', Str::ascii($name));

                $response = response()->file(Storage::disk($disk)->path($message->attachment_path), [
                    'Content-Type' => $message->attachment_type ?: 'application/octet-stream',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
                $response->setContentDisposition('inline', $name, $fallback);

                return $response;
            }
        }

        abort(404);
    }

    /**
     * Derive a safe, non-executable extension from the file's content-derived MIME type
     * rather than from the client-supplied filename, preventing RCE via polyglot uploads.
     */
    protected function safeExtension($file): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
        ];

        return $map[$file->getMimeType()] ?? 'bin';
    }

    /**
     * Live updates are a bonus: if the Reverb server is down, the message is still saved (the other
     * person sees it on refresh) and the failure is logged, instead of the chat failing.
     */
    protected function broadcastSafely(callable $broadcast): void
    {
        rescue($broadcast);
    }
}
