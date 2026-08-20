# Troubleshooting

## Common Issues

### View Cache Not Updating
**Symptom:** Blade template changes not reflected in browser
**Fix:**
```bash
php artisan view:clear
php artisan cache:clear
```

### Test Error: "Call to a member function format() on string"
**Cause:** Date fields (created_at, dob, lmp) are strings in test context, not Carbon instances
**Fix:** Wrap with `\Carbon\Carbon::parse()`:
```blade
{{ \Carbon\Carbon::parse($patient->created_at)->format('Y') }}
{{ \Carbon\Carbon::parse($patient->dob)->format('Y-m-d') }}
```

### Test Error: Pusher Class Not Found
**Symptom:** `Failed to create broadcaster for connection "reverb" with error: Class "Pusher\Pusher" not found`
**Cause:** Pre-existing issue — Reverb test environment doesn't have Pusher class
**Status:** Known issue, does not affect functionality

### MySQL Connection Refused
**Check:**
1. MySQL is running in XAMPP/Laragon
2. `.env` has `DB_HOST=127.0.0.1` (not `db`)
3. Port is `3306`
4. Database `healthcare_db` exists

### WebSocket Not Connecting
**Check:**
1. Reverb is running: `php artisan reverb:start`
2. `.env` has `REVERB_HOST=127.0.0.1` and `REVERB_PORT=8080`
3. `BROADCAST_CONNECTION=reverb` in `.env`
4. No other process using port 8080

### SMS Gateway Connection Failed
1. Check gateway URL in admin panel `/sms`
2. Test connection button in SMS settings
3. Verify gateway is accessible from your network

### CSS/Tailwind Not Loading
**Cause:** Vite dev server not running or compiled assets stale
**Fix:**
```bash
npm run build
# or for dev:
npm run dev
```

### Port 8000 Already in Use
```bash
# Find process using port 8000
netstat -ano | findstr :8000
# Kill it or use a different port
php artisan serve --port=8001
```

### Port 8080 Already in Use (Reverb)
```bash
# Find process using port 8080
netstat -ano | findstr :8080
# Kill it or use a different port
php artisan reverb:start --port=8081
```
If using a different Reverb port, update `REVERB_PORT` and `VITE_REVERB_PORT` in `.env`.

## Related Pages
- [[Setup Guide]]
- [[Configuration]]
