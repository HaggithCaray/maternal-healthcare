# Deployment Notes

## Local Development
See [[Local Development Setup]] for running locally with XAMPP.

## Cloudflare Tunnel
- Guide: `cloudflare_tunnel_setup.md`
- Exposes local Laravel server to public internet
- HTTPS termination at Cloudflare edge
- Command: `cloudflared tunnel --url http://localhost:8000`

## Production Considerations

### Before going live
Run `php artisan app:go-live-check`: it lists the settings that must change before real patient data is
entered (debug mode, demo accounts, example real-time keys) and the recommended ones (HTTPS, secure and
encrypted session cookies, log level, config cache). See the README section "Before using it with real patients".

### Security
- HTTPS links behind the Cloudflare tunnel come from the trusted-proxy setting (`bootstrap/app.php`), which
  only believes `X-Forwarded-Proto` from the tunnel on this machine
- Rate limits on login, password change, chat and SMS sending
- CSRF tokens on all forms
- Session-based auth with database driver
- Role-based access control (CheckRole middleware)
- Password hashing (bcrypt)

### Performance
- OPcache enabled for PHP
- Database sessions (120min lifetime)
- Vite-compiled assets (CSS/JS)
- Cache stored in database

### Backup
- MySQL database dumps
- SMS settings in `storage/app/sms_settings.json`
- Chat attachments in `storage/app/private/attachments/` (not publicly served)

### Scaling
- Reverb supports Redis-backed scaling (disabled by default)
- Database queue for background jobs
- Multiple server instances behind load balancer

## Environment Variables for Production
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
LOG_LEVEL=warning
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
REVERB_APP_KEY=<random>
REVERB_APP_SECRET=<random>
DB_CONNECTION=mysql
DB_HOST=your-mysql-host
DB_PORT=3306
DB_DATABASE=healthcare_db  # or healthcare_base_db for base version
DB_USERNAME=your-user
DB_PASSWORD=<secure-password>
SESSION_DRIVER=database
BROADCAST_CONNECTION=reverb
QUEUE_CONNECTION=database
CACHE_STORE=database
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
```

## Related Pages
- [[Setup Guide]]
- [[Local Development Setup]]
- [[Configuration]]
