<?php

namespace App\Http\Controllers;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

    protected function adminMessaging(Request $request, User $user)
    {
        $patients = User::where('role', 'user')->get();
        $activePatientId = $request->query('chat_user_id') ?? ($patients->first()?->id ?? null);
        $activeChatUser = $activePatientId ? User::find($activePatientId) : null;

        $messages = collect();
        if ($activeChatUser) {
            $messages = ChatMessage::where(function ($q) use ($user, $activeChatUser) {
                $q->where('sender_id', $user->id)->where('receiver_id', $activeChatUser->id);
            })->orWhere(function ($q) use ($user, $activeChatUser) {
                $q->where('sender_id', $activeChatUser->id)->where('receiver_id', $user->id);
            })->orderBy('created_at', 'asc')->get();

            $unreadCount = ChatMessage::where('sender_id', $activeChatUser->id)
                       ->where('receiver_id', $user->id)
                       ->where('is_read', false)
                       ->update(['is_read' => true]);

            if ($unreadCount > 0) {
                $conversationId = min($user->id, $activeChatUser->id) . '-' . max($user->id, $activeChatUser->id);
                event(new MessageRead($conversationId, $user->id));
            }
        }

        if ($request->isMethod('post')) {
            $request->validate([
                'message' => 'required_without:file|nullable|string|max:2000',
                'receiver_id' => 'required|exists:users,id',
                'file' => 'nullable|file|max:25600|mimes:jpeg,jpg,png,webp,gif,pdf,doc,docx,xls,xlsx,mp4,mov',
            ]);

            $attachmentPath = null;
            $attachmentName = null;
            $attachmentType = null;

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $safeName = Str::uuid() . '.' . $this->safeExtension($file);
                $attachmentPath = $file->storeAs('attachments', $safeName, 'public');
                $attachmentName = $file->getClientOriginalName();
                $attachmentType = $file->getMimeType();
            }

            $newMessage = ChatMessage::create([
                'sender_id' => $user->id,
                'receiver_id' => $request->receiver_id,
                'message' => $request->message,
                'is_read' => false,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
                'attachment_type' => $attachmentType,
            ]);

            event(new MessageSent($newMessage, min($user->id, $request->receiver_id) . '-' . max($user->id, $request->receiver_id)));

            AuditLog::log('send_chat_message', $newMessage, [
                'has_attachment' => $attachmentPath !== null,
                'receiver_id' => $request->receiver_id,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $newMessage,
                ]);
            }

            return redirect()->route('messaging', ['chat_user_id' => $request->receiver_id]);
        }

        return view('messaging', compact('patients', 'activeChatUser', 'messages'));
    }

    protected function patientMessaging(Request $request, User $user)
    {
        $midwife = User::where('role', 'admin')->first();

        $messages = collect();
        if ($midwife) {
            $messages = ChatMessage::where(function ($q) use ($user, $midwife) {
                $q->where('sender_id', $user->id)->where('receiver_id', $midwife->id);
            })->orWhere(function ($q) use ($user, $midwife) {
                $q->where('sender_id', $midwife->id)->where('receiver_id', $user->id);
            })->orderBy('created_at', 'asc')->get();

            $unreadCount = ChatMessage::where('sender_id', $midwife->id)
                       ->where('receiver_id', $user->id)
                       ->where('is_read', false)
                       ->update(['is_read' => true]);

            if ($unreadCount > 0) {
                $conversationId = min($user->id, $midwife->id) . '-' . max($user->id, $midwife->id);
                event(new MessageRead($conversationId, $user->id));
            }
        }

        if ($request->isMethod('post')) {
            $request->validate([
                'message' => 'required_without:file|nullable|string|max:2000',
                'file' => 'nullable|file|max:25600|mimes:jpeg,jpg,png,webp,gif,pdf,doc,docx,xls,xlsx,mp4,mov',
            ]);

            if ($midwife) {
                $attachmentPath = null;
                $attachmentName = null;
                $attachmentType = null;

                if ($request->hasFile('file')) {
                    $file = $request->file('file');
                    $safeName = Str::uuid() . '.' . $this->safeExtension($file);
                    $attachmentPath = $file->storeAs('attachments', $safeName, 'public');
                    $attachmentName = $file->getClientOriginalName();
                    $attachmentType = $file->getMimeType();
                }

                $newMessage = ChatMessage::create([
                    'sender_id' => $user->id,
                    'receiver_id' => $midwife->id,
                    'message' => $request->message,
                    'is_read' => false,
                    'attachment_path' => $attachmentPath,
                    'attachment_name' => $attachmentName,
                    'attachment_type' => $attachmentType,
                ]);

                event(new MessageSent($newMessage, min($user->id, $midwife->id) . '-' . max($user->id, $midwife->id)));

                AuditLog::log('send_chat_message', $newMessage, [
                    'has_attachment' => $attachmentPath !== null,
                    'receiver_id' => $midwife->id,
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $newMessage,
                    ]);
                }
            }

            return redirect()->route('messaging');
        }

        return view('patient.messaging', compact('midwife', 'messages'));
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
}
