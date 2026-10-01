# Troubleshooting

## Common Issues

### Signed Out When Another User Signs In
**Symptom:** Signing in as one user signs out another user who is already signed in
**Causes:**
- **Same browser:** a browser holds one sign-in per site, so a second login in another tab or window replaces the first. Use a different browser or a private window for the second account
- **Another Laravel app on localhost:** browsers share `localhost` cookies across ports. Apps using the default `laravel-session` cookie used to overwrite this app's sign-in. This app now uses its own cookie, `maternal-health-session` (`config/session.php`, `SESSION_COOKIE`)
- **Intended sign-outs:** a password change, a staff password reset, or deactivating the account signs that user out everywhere
**Note:** after the cookie name changed, everyone has to sign in once more.

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
1. MySQL is running in XAMPP Control Panel
2. `.env` has `DB_HOST=127.0.0.1` (not `db`)
3. Port is `3306`
4. Database exists (`healthcare_base_db` for base, `healthcare_db` for advanced)

### WebSocket Not Connecting / Chat Only Updates After Refresh
**Symptom:** New chat messages appear only after reloading the page. The browser console says "Real-time chat is off"
**Check:**
1. `BROADCAST_CONNECTION=reverb` in `.env`. With `log`, messages are written to the log file instead of being sent
2. `.env` has the `REVERB_*` and `VITE_REVERB_*` block from `.env.example` (app id, key, secret, `REVERB_HOST=localhost`, `REVERB_PORT=8080`, `REVERB_SCHEME=http`)
3. Run `npm run build` after changing any `REVERB_*` value. The browser only gets these settings at build time
4. Reverb is running in its own terminal: `php artisan reverb:start`
5. No other process using port 8080
**Note:** if Reverb is not running, chat still works: messages are saved and appear after a refresh, and the failure is logged.

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

### Apache Shows PHP 8.2 Instead of 8.4
**Symptom:** Apache log says `PHP/8.2.12` instead of `PHP/8.4.24`
**Cause:** You downloaded the NTS (Non Thread Safe) version of PHP 8.4. Apache requires the TS (Thread Safe) version.
**Note:** The TS version filename does NOT have `nts-` prefix. e.g. `php-8.4.24-Win32-vs17-x64.zip` is TS, `php-8.4.24-nts-Win32-vs17-x64.zip` is NTS.
**Fix:**
1. Stop Apache
2. Delete `C:\xampp\php`
3. Download the TS version: `https://downloads.php.net/~windows/releases/php-8.4.24-Win32-vs17-x64.zip`
4. Extract to `C:\xampp\php`
5. Copy `php.ini` from `C:\xampp\php8.2_backup`
6. Comment out browscap in `php.ini`
7. Restart Apache

### Apache Shutdown Unexpectedly
**Cause:** Port 80 blocked by another service (e.g. Windows IIS).
**Fix:**
1. Stop the conflicting service: `net stop W3SVC` (run as Administrator)
2. Or change Apache to port 8080 in `httpd.conf`: `Listen 80` → `Listen 8080` and `ServerName localhost:80` → `ServerName localhost:8080`

### phpMyAdmin 404 Not Found
**Cause:** Apache not running or wrong port.
**Fix:**
1. Start Apache in XAMPP Control Panel
2. Access `http://localhost/phpmyadmin`

### curl Extension: "libssh2_crypto_engine" Not Found
**Symptom:** `The procedure entry point libssh2_crypto_engine could not be located in the dynamic link library php_curl.dll`
**Cause:** Apache's `bin\` folder has an older `libssh2.dll` (v1.10.0 from 2023) but PHP 8.4's `php_curl.dll` requires v1.11.1+. Apache loads DLLs from its own `bin\` first before PHP's folder.
**Fix:** Copy these DLLs from `C:\xampp\php` to `C:\xampp\apache\bin\`:
```powershell
Copy-Item "C:\xampp\php\libssh2.dll" "C:\xampp\apache\bin\libssh2.dll" -Force
Copy-Item "C:\xampp\php\brotlidec.dll" "C:\xampp\apache\bin\brotlidec.dll" -Force
Copy-Item "C:\xampp\php\brotlicommon.dll" "C:\xampp\apache\bin\brotlicommon.dll" -Force
```
Then restart Apache.

### Apache: "Speaking plain HTTP to an SSL-enabled server port"
**Symptom:** Browser shows `Bad Request — You're speaking plain HTTP to an SSL-enabled server port`
**Cause:** `httpd-ssl.conf` is loaded and forces HTTPS on port 443, which conflicts with plain HTTP on port 80.
**Fix:** Comment out the SSL include in `C:\xampp\apache\conf\httpd.conf`:
```
#Include conf/extra/httpd-ssl.conf
```
Then restart Apache.

## Related Pages
- [[Setup Guide]]
- [[Configuration]]
