<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Patient;
use App\Models\SmsMessage;
use App\Services\SmsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SmsGatewayController extends Controller
{
    /**
     * Display SMS management dashboard and outbound message log.
     */
    public function sms(Request $request, SmsService $smsService)
    {
        if ($request->isMethod('post') && auth()->user()->role === 'admin') {
            return $this->send($request, $smsService);
        }

        $smsMessages = SmsMessage::with('patient')->latest()->take(50)->get();
        $patients = Patient::whereNotNull('phone')->orderBy('last_name')->orderBy('first_name')->get();

        $stats = [
            'sent' => SmsMessage::where('status', 'Sent')->count(),
            'sentThisMonth' => SmsMessage::where('status', 'Sent')->where('created_at', '>=', Carbon::now()->startOfMonth())->count(),
            'failed' => SmsMessage::where('status', 'Failed')->count(),
            'reachableMothers' => $patients->where('registration_type', 'Maternal')->count(),
            'reachableChildren' => $patients->where('registration_type', 'Child')->count(),
        ];

        $gatewaySettings = $smsService->getSettings();
        $gatewayStatus = $smsService->checkStatus();

        return view('sms', compact('smsMessages', 'patients', 'stats', 'gatewaySettings', 'gatewayStatus'));
    }

    /**
     * Send manual SMS message to patient via gateway.
     */
    public function send(Request $request, SmsService $smsService)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'message' => 'required|string|max:1000',
        ]);

        $patient = Patient::findOrFail($request->patient_id);
        $result = $smsService->sendSms($patient->phone, $request->message);

        $smsLog = SmsMessage::create([
            'patient_id' => $patient->id,
            'phone_number' => $patient->phone,
            'message' => $request->message,
            'status' => $result['success'] ? 'Sent' : 'Failed',
            'sent_at' => $result['success'] ? Carbon::now() : null,
            'type' => 'Manual',
        ]);

        AuditLog::log('send_sms', $smsLog, [
            'recipient' => $patient->phone,
            'success' => $result['success'],
        ]);

        if ($result['success']) {
            return back()->with('success', 'SMS message sent successfully!');
        }

        return back()->with('error', 'Failed to send SMS: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Update Capcom6 SMS Gateway configuration.
     */
    public function updateSmsSettings(Request $request, SmsService $smsService)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'url' => 'required|url',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
        ]);

        $success = $smsService->saveSettings(
            $request->url,
            $request->username ?? '',
            $request->password ?? ''
        );

        AuditLog::log('update_sms_gateway_settings', null, [
            'url' => $request->url,
            'success' => $success,
        ]);

        if ($success) {
            return back()->with('success', 'SMS gateway settings updated successfully!');
        }

        return back()->with('error', 'Failed to update SMS gateway settings.');
    }

    /**
     * Test live connection to Capcom6 Android SMS Gateway.
     */
    public function testSmsGatewayConnection(SmsService $smsService)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $status = $smsService->checkStatus();

        return response()->json([
            'success' => true,
            'status' => $status ? 'online' : 'offline',
        ]);
    }
}
