# Server Setup & Runbook — `server1`

Production runbook for the Linux server. It records what is already verified working, then the
exact commands to close the remaining gaps. Every command is copy-paste ready for
`rusel@server1:/var/www/maternal-health-hub`.

| | |
|---|---|
| Host | `server1` |
| App path | `/var/www/maternal-health-hub` |
| LAN address | `192.168.1.11` (private — no public IP yet) |
| Stack | nginx 1.28.3 · PHP 8.5.4 (FPM + CLI) · MySQL · Laravel 13.38 · Supervisor |
| App timezone | `Asia/Manila` |
| Last full check | 2026-11-25 |

---

## 1. Verified working (2026-11-25)

Do not "fix" these — they were tested end to end.

| Item | Evidence |
|---|---|
| nginx serving the app | `curl -sI http://192.168.1.11` → `HTTP/1.1 200 OK` |
| nginx → PHP-FPM → Laravel | Same request renders the login page |
| Vhost enabled, `default` removed | `sites-enabled/maternal-health-hub` only |
| PHP-FPM socket | `/run/php/php8.5-fpm.sock`, `php -v` = 8.5.4 (CLI matches FPM) |
| Supervisor | `maternal-health-hub-reverb`, `-queue_00`, `-queue_01`, `-scheduler` all **RUNNING** |
| Scheduler actually firing | Real backup written by the scheduler: `storage/app/backups/backup-2026-11-25_141500.zip` |
| Backup encryption | Zip test returns "incorrect password" → AES-256 password applied |
| `BACKUP_PASSWORD` / `BACKUP_COPY_PATH` | Set in `.env`, go-live check passes both |
| Reverb keys | Changed from `local-reverb-key` / `local-reverb-secret` (go-live check passes) |
| `APP_KEY`, `APP_DEBUG=false`, session encryption | All pass the go-live check |
| FPM upload limits | `upload_max_filesize=26M`, `post_max_size=28M` |
| Drivers | database session / cache / queue, `reverb` broadcast, `public/storage` linked |

`php artisan app:go-live-check` → **1 blocking item** (demo accounts, section 4) plus recommended items.

---

## 2. Permissions — `rusel` cannot write to `storage` or `bootstrap/cache`

`storage` and `bootstrap/cache` are owned by `www-data:www-data` (775). `rusel` is not in that
group, so manual artisan commands that write — `backup:run`, `config:cache`, `view:cache` — fail
with *permission denied*. Proof: the only backup on disk is owned by `www-data` (written by the
scheduler); the manual `backup:run` in the setup history left no file.

```bash
sudo usermod -aG www-data rusel
# Group membership only applies to new sessions:
exit          # then SSH back in  (or: newgrp www-data)
id            # should now list www-data
```

Make new files inherit the group (setgid) so this does not regress:

```bash
sudo find /var/www/maternal-health-hub/storage \
          /var/www/maternal-health-hub/bootstrap/cache \
          -type d -exec chmod 2775 {} +
sudo find /var/www/maternal-health-hub/storage \
          /var/www/maternal-health-hub/bootstrap/cache \
          -type f -exec chmod 664 {} +
```

Test it:

```bash
cd /var/www/maternal-health-hub
php artisan backup:run          # must succeed and print "Backup written: ..."
```

Restrict the environment file (it holds the DB password, Reverb secret and backup password):

```bash
sudo chgrp www-data /var/www/maternal-health-hub/.env
sudo chmod 640 /var/www/maternal-health-hub/.env
ls -l /var/www/maternal-health-hub/.env   # -rw-r----- www-data/rusel group
```

---

## 3. CLI `php.ini` upload limits (last "Recommended" item that is not a policy choice)

The go-live check runs in **CLI**, which still reads the 2M/8M defaults. FPM (the path real chat
uploads take) is already correct.

```bash
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 26M/' /etc/php/8.5/cli/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 28M/'             /etc/php/8.5/cli/php.ini
php -r 'echo ini_get("upload_max_filesize"), " / ", ini_get("post_max_size"), PHP_EOL;'
# expect: 26M / 28M   (no service restart needed for CLI)
```

---

## 4. Demo accounts — the only blocking go-live item

`migrate --seed` created:

| Email | Password | Role |
|---|---|---|
| `health@example.com` | `password` | admin |
| `patient@example.com` | `password` | patient (linked to demo patient Elena Dela Cruz) |

**Preferred — through the UI:** sign in at `http://192.168.1.11` as `health@example.com` /
`password`, then:

1. **Admin → Users → edit `health@example.com`** → set a real password (or deactivate it and create
   your own admin account first — do not leave zero admins).
2. **Admin → Users → edit `patient@example.com`** → deactivate (demo patient records should not
   stay reachable).

**Or by SQL** (deactivate both at once):

```bash
sudo mysql healthcare_db -e \
  "UPDATE users SET is_active = 0 WHERE email IN ('health@example.com','patient@example.com');"
```

Re-check:

```bash
cd /var/www/maternal-health-hub && php artisan app:go-live-check
# expect: no "FIX FIRST" rows
```

---

## 5. Production `.env` (IP for now, domain later)

Current: `Environment: local`, `URL: 127.0.0.1:8000`, `LOG_LEVEL=debug`. Change:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.1.11
LOG_LEVEL=warning

# Leave these as they are until section 8 (HTTPS):
# SESSION_SECURE_COOKIE=true
```

Then clear the compiled config and rebuild it:

```bash
cd /var/www/maternal-health-hub
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan about | grep -E 'Environment|Debug|URL|Cached'
```

> Every later `.env` edit needs `php artisan config:cache` again (or `config:clear` to undo).
> While config is cached, `env()` outside `config/` returns `null` — put new settings in `config/`.

Also confirm Reverb points at something a browser can reach. `.env` should have the LAN address
(not `localhost`, which would mean "the patient's own phone"):

```ini
REVERB_HOST=192.168.1.11
REVERB_PORT=80
REVERB_SCHEME=http
```

If you changed any `REVERB_*` value: `npm run build` (VITE values are baked in at build time),
then `php artisan config:cache` and `sudo supervisorctl restart maternal-health-hub-reverb`.

Chat check: open the app from another device on the LAN, send a message, and confirm it appears
without a refresh; the browser console must not say "Real-time chat is off".

---

## 6. Stop exposing Reverb directly (port 8080)

nginx already proxies `location /app` → `127.0.0.1:8080`, but Supervisor starts Reverb on
`0.0.0.0`, so anything on the network can bypass nginx.

```bash
grep -n 'reverb:start' /etc/supervisor/conf.d/maternal-health-hub.conf   # confirm the line first
sudo sed -i 's|^command=php artisan reverb:start --host=0.0.0.0 --port=8080|command=php artisan reverb:start --host=127.0.0.1 --port=8080|' \
  /etc/supervisor/conf.d/maternal-health-hub.conf
sudo supervisorctl reread && sudo supervisorctl update
sudo supervisorctl status maternal-health-hub-reverb
ss -ltnp | grep 8080        # must show 127.0.0.1:8080, not 0.0.0.0:8080
```

Re-test chat from the LAN afterwards (it must still work through nginx on port 80).

---

## 7. Hardening

### 7.1 Firewall (LAN-only for now)

```bash
sudo apt install -y ufw
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow from 192.168.1.0/24 to any port 80 proto tcp
sudo ufw enable
sudo ufw status verbose
```

Port 8080 is **not** opened — Reverb is reached only through nginx. When the public domain is
live (section 8) replace the LAN rule with `sudo ufw allow 80,443/tcp`.

### 7.2 fail2ban (SSH brute force)

```bash
sudo apt install -y fail2ban
sudo systemctl enable --now fail2ban
sudo fail2ban-client status sshd
```

### 7.3 Automatic security updates

```bash
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure --priority=low unattended-upgrades   # answer Yes
```

### 7.4 Log rotation (Laravel + Supervisor logs never rotate)

```bash
sudo tee /etc/logrotate.d/maternal-health-hub > /dev/null <<'EOF'
/var/www/maternal-health-hub/storage/logs/*.log {
    weekly
    rotate 8
    missingok
    notifempty
    compress
    delaycompress
    copytruncate
    su www-data www-data
}
EOF
sudo logrotate -d /etc/logrotate.d/maternal-health-hub   # dry run: no errors
```

### 7.5 Credentials

- The MySQL password for `maternal_user` appears in plaintext in `~/.bash_history` (setup line 35).
  Rotate it once: create the new password, update `.env`, then
  `sudo mysql -e "ALTER USER 'maternal_user'@'localhost' IDENTIFIED BY '<new>'; FLUSH PRIVILEGES;"`,
  `php artisan config:cache`, and test the app.
- Consider `history -c` afterwards on shared accounts.

---

## 8. When the domain arrives (HTTPS)

Prerequisites — these are network tasks, not Laravel ones:

1. DNS `A` record `hub.example.com` → the **public** IP of the router in front of `server1`.
2. Router port-forward `80/TCP` and `443/TCP` → `192.168.1.11`.
3. Verify from outside: `curl -I http://hub.example.com` reaches nginx. Certbot cannot validate
   until this works.

Then:

```bash
# 1. Vhost name
sudo sed -i 's/server_name your-domain.com;/server_name hub.example.com;/' \
  /etc/nginx/sites-available/maternal-health-hub

# 2. Let Laravel know the request is HTTPS (nginx terminates TLS; the app trusts only
#    127.0.0.1 in bootstrap/app.php and REMOTE_ADDR is the client, so X-Forwarded-Proto
#    is deliberately ignored — this single param is the correct fix instead).
sudo sed -i 's|^\(\s*\)include fastcgi_params;|\1fastcgi_param HTTPS $https if_not_empty;\n\1include fastcgi_params;|' \
  /etc/nginx/sites-available/maternal-health-hub
grep -A1 'fastcgi_param SCRIPT_FILENAME' /etc/nginx/sites-available/maternal-health-hub

sudo nginx -t && sudo systemctl reload nginx

# 3. Certificate (also installs the HTTP→HTTPS redirect)
sudo certbot --nginx -d hub.example.com
sudo certbot renew --dry-run

# 4. Point the app at HTTPS and rebuild the front end
cd /var/www/maternal-health-hub
# .env edits:
#   APP_URL=https://hub.example.com
#   REVERB_HOST=hub.example.com
#   REVERB_PORT=443
#   REVERB_SCHEME=https
#   SESSION_SECURE_COOKIE=true
npm run build
php artisan config:cache
sudo supervisorctl restart maternal-health-hub-reverb

# 5. Open only what is needed
sudo ufw delete allow from 192.168.1.0/24 to any port 80 proto tcp
sudo ufw allow 80,443/tcp

# 6. Final gate
php artisan app:go-live-check     # expect exit 0, no FIX FIRST
```

Sign-in test over HTTPS is mandatory after flipping `SESSION_SECURE_COOKIE=true`: if the login
loop appears, `fastcgi_param HTTPS` is missing or `.env` still says `http://`.

---

## 9. Verification checklist (run after any change)

```bash
# Services
sudo systemctl is-active nginx php8.5-fpm mysql supervisor
sudo supervisorctl status                 # 4 lines, all RUNNING (supervisorctl has no --no-pager)

# HTTP end to end
curl -sI http://192.168.1.11 | head -3    # HTTP/1.1 200 OK

# Scheduler knows its jobs
cd /var/www/maternal-health-hub && php artisan schedule:list

# Backups (encrypted, fresh)
php artisan backup:run
unzip -l storage/app/backups/backup-*.zip | tail -3
sudo ls -l storage/app/backups/

# Go-live gate
php artisan app:go-live-check
```

| Symptom | Cause / fix |
|---|---|
| `backup:run` → permission denied | Section 2 (group membership lost by re-login?) |
| Admin page says no backup in days | Scheduler stopped or MySQL down → `supervisorctl status`, `systemctl status mysql` |
| Login loop right after enabling HTTPS | Section 8 step 2 (`fastcgi_param HTTPS`) not applied |
| Chat needs refresh to show messages | `BROADCAST_CONNECTION=reverb`, `REVERB_*` values, `npm run build`, Reverb running |
| Upload says file too large | FPM limits (already 26M/28M) — check `php-fpm8.5 -i \| grep -E 'upload_max|post_max'` |

---

## 10. Deploying updates

```bash
cd /var/www/maternal-health-hub
git pull                       # needs a token/credential for the private repo
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force    # README upgrade notes may add: assessments:recalculate, attachments:make-private
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
sudo supervisorctl restart all
php artisan app:go-live-check
```

Run these from a session where `id` shows `www-data` (section 2), otherwise the cache commands
fail.

---

## Related docs

- `README.md` — setup, scheduled jobs, backups, "Before using it with real patients"
- `maternal-healthcare/Deployment Notes.md` — production considerations
- `cloudflare_tunnel_setup.md` — alternative to port-forwarding (tunnel instead of DNS + certbot)
