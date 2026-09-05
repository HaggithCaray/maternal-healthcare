<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $settingsPath;

    public function __construct()
    {
        $this->settingsPath = storage_path('app/sms_settings.json');
    }

    /**
     * Get the current SMS gateway settings.
     *
     * @return array
     */
    public function getSettings(): array
    {
        if (File::exists($this->settingsPath)) {
            $encrypted = File::get($this->settingsPath);

            try {
                $settings = json_decode(Crypt::decryptString($encrypted), true);
                if (is_array($settings)) {
                    return $this->normalizeSettings($settings);
                }
            } catch (\Exception $e) {
                Log::error('Failed to decrypt SMS gateway settings: ' . $e->getMessage());
            }

            // Backward compatibility: a pre-encryption plaintext file may still exist.
            $legacy = json_decode($encrypted, true);
            if (is_array($legacy)) {
                Log::warning('Migrating legacy plaintext SMS gateway settings to encrypted storage.');
                $this->saveSettings(
                    $legacy['url'] ?? '',
                    $legacy['username'] ?? '',
                    $legacy['password'] ?? ''
                );
                return $this->normalizeSettings($legacy);
            }
        }

        return $this->normalizeSettings([]);
    }

    /**
     * Save the SMS gateway settings (encrypted at rest).
     *
     * @param string $url
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function saveSettings(string $url, string $username, string $password): bool
    {
        try {
            // Normalize URL to have no trailing slash
            $url = rtrim($url, '/');

            $settings = [
                'url' => $url,
                'username' => $username,
                'password' => $password,
            ];

            // Ensure parent directory exists
            $dir = dirname($this->settingsPath);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            File::put($this->settingsPath, Crypt::encryptString(json_encode($settings)));
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to save SMS gateway settings: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Merge stored settings with environment fallbacks.
     *
     * @param array $stored
     * @return array
     */
    protected function normalizeSettings(array $stored): array
    {
        return [
            'url' => $stored['url'] ?? env('SMS_GATEWAY_URL', ''),
            'username' => $stored['username'] ?? env('SMS_GATEWAY_USER', ''),
            'password' => $stored['password'] ?? env('SMS_GATEWAY_PASSWORD', ''),
        ];
    }

    /**
     * Check the status of the SMS gateway.
     *
     * @return bool
     */
    public function checkStatus(): bool
    {
        $settings = $this->getSettings();
        $url = $settings['url'];

        if (empty($url)) {
            return false;
        }

        try {
            // Unauthenticated healthcheck endpoint in local server mode
            $response = Http::timeout(3)->get($url . '/health');

            if ($response->successful()) {
                $status = $response->json('status');
                // The gateway usually returns status: "pass", but we can also accept "ok" or simply check for HTTP success
                return $status === 'pass' || $status === 'ok' || is_null($status);
            }
        } catch (\Exception $e) {
            Log::warning('SMS gateway health check failed: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Send an SMS via the gateway.
     *
     * @param string $to
     * @param string $message
     * @return array
     */
    public function sendSms(string $to, string $message): array
    {
        $settings = $this->getSettings();
        $url = $settings['url'];
        $username = $settings['username'];
        $password = $settings['password'];

        if (empty($url)) {
            return [
                'success' => false,
                'error' => 'SMS Gateway URL is not configured.',
            ];
        }

        $formattedTo = $this->formatPhoneNumber($to);

        try {
            $response = Http::timeout(5)
                ->withBasicAuth($username, $password)
                ->post($url . '/message', [
                    'textMessage' => [
                        'text' => $message,
                    ],
                    'phoneNumbers' => [$formattedTo],
                ]);

            if ($response->successful()) {
                return ['success' => true];
            }

            $errorMsg = $response->json('message') ?? $response->body() ?: 'HTTP error ' . $response->status();
            return [
                'success' => false,
                'error' => $errorMsg,
            ];
        } catch (\Exception $e) {
            Log::error('SMS Gateway send error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Network error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Helper to format phone number to international E.164 format (+639171234567).
     *
     * @param string $phone
     * @return string
     */
    public function formatPhoneNumber(string $phone): string
    {
        // Strip out non-numeric characters except for leading '+'
        $clean = preg_replace('/[^\d+]/', '', $phone);

        // If it already starts with '+', assume it's formatted
        if (str_starts_with($clean, '+')) {
            return $clean;
        }

        // Handle Philippine local formats
        // 09171234567 -> +639171234567
        if (str_starts_with($clean, '09') && strlen($clean) === 11) {
            return '+63' . substr($clean, 1);
        }

        // 9171234567 -> +639171234567
        if (str_starts_with($clean, '9') && strlen($clean) === 10) {
            return '+63' . $clean;
        }

        // 639171234567 -> +639171234567
        if (str_starts_with($clean, '639') && strlen($clean) === 12) {
            return '+' . $clean;
        }

        // Fallback: prepend '+' if it doesn't have it, or return clean number
        return $clean;
    }
}
