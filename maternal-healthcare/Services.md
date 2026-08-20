# Services

## SmsService
**File:** `app/Services/SmsService.php`

Handles communication with an external SMS gateway via HTTP API.

### Methods

| Method | Signature | Description |
|--------|-----------|-------------|
| `getSettings()` | `(): array` | Reads config from `storage/app/sms_settings.json` or `.env` fallback |
| `saveSettings()` | `(string $url, string $username, string $password): bool` | Persists gateway config to JSON file |
| `checkStatus()` | `(): bool` | HTTP GET to `{url}/health`, checks for "pass"/"ok" response |
| `sendSms()` | `(string $to, string $message): array` | HTTP POST to `{url}/message` with basic auth |
| `formatPhoneNumber()` | `(string $phone): string` | Converts PH local formats to E.164 (+639171234567) |

### Phone Number Formats
| Input | Output |
|-------|--------|
| `09171234567` | `+639171234567` |
| `9171234567` | `+639171234567` |
| `639171234567` | `+639171234567` |
| `+639171234567` | `+639171234567` (unchanged) |

### Gateway Settings Storage
```json
// storage/app/sms_settings.json
{
    "url": "http://gateway.example.com",
    "username": "admin",
    "password": "secret"
}
```

### SMS Send Payload
```json
{
    "textMessage": "Your message here",
    "phoneNumbers": ["+639171234567"]
}
```

## Related Pages
- [[Controllers]]
- [[Routes Map]]
