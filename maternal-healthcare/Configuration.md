# Configuration

## Key Config Files

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
| `DB_DATABASE` | healthcare_db | Database name |
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
