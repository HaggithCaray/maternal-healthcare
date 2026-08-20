# Deployment Notes

## Local Development
See [[Local Development Setup]] for running locally with XAMPP/Laragon.

## Cloudflare Tunnel
- Guide: `cloudflare_tunnel_setup.md`
- Exposes local Laravel server to public internet
- HTTPS termination at Cloudflare edge
- Command: `cloudflared tunnel --url http://localhost:8000`

## Production Considerations

### Security
- HTTPS forced via AppServiceProvider (X-Forwarded-Proto)
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
- Chat attachments in `storage/app/public/`

### Scaling
- Reverb supports Redis-backed scaling (disabled by default)
- Database queue for background jobs
- Multiple server instances behind load balancer

## Environment Variables for Production
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
DB_CONNECTION=mysql
DB_HOST=your-mysql-host
DB_PORT=3306
DB_DATABASE=healthcare_db
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
