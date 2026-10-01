# Configuration

## Key Config Files

### config/app.php: timezone
- **Timezone:** `Asia/Manila`. "Today" for a visit, measurement or vaccine dose is the clinic's day. Under the old `UTC` setting, anything entered before 8am was dated the previous day
- Date-times (`created_at`, `sent_at`, `last_login_at`, …) are stored as Philippine wall-clock time. Date-only fields (visit date, dose date, birthday, LMP) are calendar days
- Migration `2026_10_01_000000_store_timestamps_in_philippine_time` moved date-times written under UTC forward 8 hours (Manila has no daylight saving). On MySQL it only touches this app's database. Run `php artisan migrate` right after updating, before entering new data
- Visit or dose dates recorded before the change are not corrected: an entry made between midnight and 8am may show the previous day

### config/database.php
- **Default:** MySQL (env: `DB_CONNECTION`)
- **MySQL:** host 127.0.0.1, port 3306, charset utf8mb4, strict mode enabled
- **Sessions:** database driver, 120min lifetime

### config/broadcasting.php
- **Default:** `reverb` (env: `BROADCAST_CONNECTION`)
- **Reverb:** Uses REVERB_APP_KEY/SECRET/ID
- **Pusher:** Standard config (backup)

### config/reverb.php
- Host: 0.0.0.0, port: 8080
- Max request size: 10,000
- Ping interval: 60s
- Activity timeout: 30s
- Rate limiting: disabled

### config/auth.php
- Guard: web (session-based)
- Provider: eloquent (User model)
- Password reset: 60min expiry

## Environment Variables
| Variable | Default | Purpose |
|----------|---------|---------|
| `DB_CONNECTION` | mysql | Database driver |
| `DB_HOST` | 127.0.0.1 | MySQL host |
| `DB_PORT` | 3306 | MySQL port |
| `DB_DATABASE` | healthcare_base_db / healthcare_db | Database name (base / advanced) |
| `DB_USERNAME` | root | DB user |
| `DB_PASSWORD` | (empty) | DB password |
| `SESSION_DRIVER` | database | Session storage |
| `BROADCAST_CONNECTION` | reverb | Broadcasting driver |
| `QUEUE_CONNECTION` | database | Queue driver |
| `CACHE_STORE` | database | Cache driver |
| `REVERB_HOST` | 127.0.0.1 | Reverb server host |
| `REVERB_PORT` | 8080 | Reverb server port |
| `SMS_GATEWAY_URL` | (empty) | SMS gateway URL |
| `SMS_GATEWAY_USER` | (empty) | SMS gateway username |
| `SMS_GATEWAY_PASSWORD` | (empty) | SMS gateway password |

## SMS Settings Storage
Runtime-configured via admin panel, stored in:
```
storage/app/sms_settings.json
```

## AppServiceProvider
Forces HTTPS when behind reverse proxy:
```php
if ($request->header('HTTP_X_FORWARDED_PROTO') === 'https') {
    $url->forceScheme('https');
}
```

## Related Pages
- [[Setup Guide]]
- [[Local Development Setup]]
