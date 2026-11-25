# Server Setup & Runbook — `server1`

Production runbook for the Linux server. It records what is already verified working, every
command used to get there, how the site is reached today (Tailscale), and the exact steps to
move to a real domain later. Every command is copy-paste ready for
`rusel@server1:/var/www/maternal-health-hub` unless a heading says it runs on your own PC.

| | |
|---|---|
| Host | `server1` (Ubuntu 26.04) |
| App path | `/var/www/maternal-health-hub` |
| LAN address | `192.168.1.11` (private) |
| Tailscale address | `100.110.30.36` · `https://server1.<tailnet>.ts.net` (HTTPS, section 8) |
| Public access | Tailscale Funnel on `https://server1.<tailnet>.ts.net` (section 8.3) — no domain yet |
| Stack | nginx 1.28.3 · PHP 8.5 (FPM + CLI) · MySQL · Laravel 13 · Supervisor · Node 20 |
| App timezone | `Asia/Manila` |
| Last full check | 2026-11-25 |

> `<tailnet>` is your Tailscale network name (shown by `tailscale status` and in the Tailscale
> admin console under DNS). Replace it wherever it appears.

---

## 1. Verified working (2026-11-25)

Do not "fix" these — they were tested end to end.

| Item | Evidence |
|---|---|
| nginx serving the app | `curl -sI http://localhost` → `HTTP/1.1 200 OK` |
| nginx → PHP-FPM → Laravel | Same request renders the login page |
| Vhost enabled, `default` removed | `sites-enabled/maternal-health-hub` only |
| PHP-FPM socket | `/run/php/php8.5-fpm.sock` |
| Supervisor | `maternal-health-hub-reverb`, `-queue_00`, `-queue_01`, `-scheduler` all **RUNNING** |
| Scheduler actually firing | Backup written by the scheduler without any manual run |
| Backup encryption | AES-256 password applied (`BACKUP_PASSWORD` set) |
| `mysqldump`, zip AES support | `/usr/bin/mysqldump`, `ZipArchive::EM_AES_256` supported |
| HTTPS | `https://server1.<tailnet>.ts.net` returns `200` (Tailscale serve, section 8) |
| Built assets | Page links `/build/assets/*.css`, no `public/hot` file (section 10) |
| MySQL | Listens on `127.0.0.1:3306` only |
| `.env` | Mode `640` (`rusel:www-data`), blocked by nginx (`.env`, `.git`, `.sql`, `.bak`) |
| Demo data | Removed (section 4); one admin account remains |

`php artisan app:go-live-check` is the gate; run it after any change (section 11).

---

## 2. Permissions — `rusel` cannot write to `storage` or `bootstrap/cache`

`storage` and `bootstrap/cache` are owned by `www-data:www-data` (775). If `rusel` is not in that
group, manual artisan commands that write — `backup:run`, `config:cache`, `view:cache` — fail
with *permission denied*.

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
ls -l /var/www/maternal-health-hub/.env   # -rw-r----- rusel www-data
```

---

## 3. CLI `php.ini` upload limits

The go-live check runs in **CLI**, which still reads the 2M/8M defaults. FPM (the path real chat
uploads take) is already correct.

```bash
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 26M/' /etc/php/8.5/cli/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 28M/'             /etc/php/8.5/cli/php.ini
php -r 'echo ini_get("upload_max_filesize"), " / ", ini_get("post_max_size"), PHP_EOL;'
# expect: 26M / 28M   (no service restart needed for CLI)
```

---

## 4. Demo accounts and seeded data (DONE 2026-11-25)

`migrate --seed` creates `health@example.com` / `patient@example.com`, **both with the password
`password`**, plus demo patients, records, SMS and chat. All of it was removed:

- `patient@example.com` and every demo patient, maternal/child record, checkup, immunization,
  growth measurement, SMS and chat message were deleted.
- The remaining admin account was given a real password and its email was changed to the real
  admin address (the password was **not** touched).
- The audit log entries were kept.

Do **not** run `php artisan migrate:fresh --seed` or `db:seed` on this server: it recreates the
`password` accounts.

How it was done, in case it has to be repeated. Take a backup first (section 9). Use the app's own
database connection through a small script (no password is ever typed in a command line):

```bash
cd /var/www/maternal-health-hub
php artisan backup:run

php artisan tinker --execute='
DB::transaction(function () {
    DB::table("chat_messages")->delete();
    DB::table("sms_messages")->delete();
    DB::table("patients")->delete();            // cascades to records, checkups, immunizations, growth
    DB::table("sessions")->where("user_id", "!=", 1)->delete();
    DB::table("users")->where("id", "!=", 1)->delete();   // keep ONLY the admin (id 1)
});
'
```

Change the admin email (password untouched):

```bash
php artisan tinker --execute='DB::table("users")->where("id", 1)->update(["email" => "you@example.com"]);'
```

Change the admin password through the app (profile page) or:

```bash
php artisan tinker
>>> $u = App\Models\User::find(1); $u->password = Hash::make('NEW-STRONG-PASSWORD'); $u->save();
```

Check what is in the database (counts and account emails only):

```bash
php artisan tinker --execute='
foreach (DB::table("users")->get(["id","email","role"]) as $u) echo "$u->id $u->email $u->role\n";
foreach (["patients","maternal_records","child_records","audit_logs"] as $t) echo "$t: ".DB::table($t)->count()."\n";
'
```

Re-check the gate:

```bash
php artisan app:go-live-check     # expect: no "FIX FIRST" rows
```

---

## 5. Production `.env` (Tailscale HTTPS)

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://server1.<tailnet>.ts.net
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning

# Server side (Laravel -> Reverb, inside the machine):
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# Browser side (baked into the JavaScript at build time):
VITE_REVERB_HOST="server1.<tailnet>.ts.net"
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

Browsers reach Reverb through nginx (`location /app`) on port 443; nginx forwards to
`127.0.0.1:8080`. Never put `localhost`/`127.0.0.1` in a `VITE_` value: it means "the visitor's own
device".

After **any** `.env` change:

```bash
cd /var/www/maternal-health-hub
npm run build                # only needed when a VITE_ value changed
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart all
```

> While config is cached, `env()` outside `config/` returns `null` — put new settings in `config/`.

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

Re-test chat afterwards (it must still work through nginx).

---

## 7. Hardening

### 7.1 Firewall

With Tailscale (and Funnel) nothing needs to be opened to the internet: connections reach the
server through the outbound Tailscale connection. Allow SSH and the web port on the LAN only:

```bash
sudo apt install -y ufw
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow from 192.168.1.0/24 to any port 80 proto tcp
sudo ufw allow in on tailscale0
sudo ufw enable
sudo ufw status verbose
```

Port 8080 is **not** opened. If you later forward ports from a router for a normal domain
(section 8.5) replace the LAN rule with `sudo ufw allow 80,443/tcp`.

### 7.2 fail2ban (SSH brute force)

```bash
sudo apt install -y fail2ban
sudo systemctl enable --now fail2ban
sudo fail2ban-client status sshd
```

### 7.3 SSH keys only (after your key works — test in a second window first)

```bash
# on your PC (PowerShell), once:
ssh-keygen -t ed25519
type $env:USERPROFILE\.ssh\id_ed25519.pub | ssh rusel@100.110.30.36 "mkdir -p ~/.ssh && cat >> ~/.ssh/authorized_keys"

# on the server, once a key login works:
sudo sed -i 's/^#\?PasswordAuthentication .*/PasswordAuthentication no/' /etc/ssh/sshd_config
sudo sshd -t && sudo systemctl reload ssh
```

> Run the `ssh-keygen` / `type ... | ssh` lines in a **local** PowerShell, not inside an SSH
> session. Run in the SSH session they create a key on the server by mistake (delete it with
> `rm ~/.ssh/id_ed25519 ~/.ssh/id_ed25519.pub`). Some Ubuntu images also set
> `PasswordAuthentication` in `/etc/ssh/sshd_config.d/*.conf`; check that folder too.

### 7.4 Automatic security updates and the pending reboot

```bash
sudo apt install -y unattended-upgrades
sudo dpkg-reconfigure --priority=low unattended-upgrades   # answer Yes
ls /var/run/reboot-required && cat /var/run/reboot-required.pkgs   # "System restart required"
sudo reboot                                                        # at a quiet time
```

After a reboot confirm everything came back on its own (section 11).

### 7.5 Log rotation (Laravel + Supervisor logs never rotate)

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

### 7.6 Credentials

- If the MySQL password for `maternal_user` was ever typed on a command line it is in
  `~/.bash_history`. Rotate it once: create the new password, update `.env`, then
  `sudo mysql -e "ALTER USER 'maternal_user'@'localhost' IDENTIFIED BY '<new>'; FLUSH PRIVILEGES;"`,
  `php artisan config:cache`, and test the app. Then `history -c`.
- Never paste passwords, `.env` contents or backup files into chat tools or tickets.

---

## 8. How the site is reached

### 8.1 Today: Tailscale HTTPS (private, free, permanent name)

`https://server1.<tailnet>.ts.net` is permanent and has an auto-renewing certificate. It changes
only if you rename the machine or the tailnet. Only devices signed in to your tailnet can open it
(each phone/PC needs the Tailscale app, **connected**, and must use Chrome/Safari — in-app browsers
such as Messenger's do not use the VPN and show `ERR_NAME_NOT_RESOLVED`).

One-time, in a browser: <https://login.tailscale.com/admin/dns> → **HTTPS Certificates** → **Enable
HTTPS**.

On the server:

```bash
tailscale status                       # shows the machine name and the tailnet
sudo tailscale serve --bg 80           # HTTPS 443 -> nginx on port 80, tailnet only
tailscale serve status
sudo tailscale serve reset             # undo
```

Because `tailscale serve` connects to nginx from `127.0.0.1`, which Laravel already trusts in
`bootstrap/app.php`, the `X-Forwarded-Proto: https` header is honoured and links come out as
`https://`. No nginx change is needed.

### 8.2 Sharing with other people

| Who | How |
|---|---|
| Staff you trust | Tailscale admin console → **Users** → invite by email, or **Machines** → `server1` → **Share** (that machine only) |
| Limit what they can reach | Tailscale access rules (ACLs): allow `tcp:443` only, not SSH |
| Anyone with the link | Tailscale Funnel (8.3) or a domain (section 9) |

### 8.3 Public access: Tailscale Funnel

Funnel publishes the same address to the whole internet. HTTPS is terminated on the server, so
Tailscale relays encrypted traffic. Do the cleanup (section 4), the `.env` changes (section 5) and
the build (section 10) **before** turning it on.

One-time, in the Tailscale admin console → **Access controls → JSON editor**: add this section
(after `"ssh": [...],`), then **Save**:

```json
	"nodeAttrs": [
		{
			"target": ["autogroup:member"],
			"attr":   ["funnel"],
		},
	],
```

On the server:

```bash
sudo tailscale funnel --bg 80
tailscale funnel status                # must say "(Funnel on)"
```

Test from a device **not** on the tailnet (Tailscale off or mobile data) in Chrome.

Turn it off (also clears serve; re-run `tailscale serve --bg 80` for private HTTPS):

```bash
sudo tailscale funnel reset
sudo tailscale serve --bg 80
```

Before real patients use a public site: strong admin password, no demo accounts, `fail2ban` on,
backups copied off the server (section 9), a quiet-hour reboot, and login throttling confirmed.

### 8.4 Temporary: Cloudflare quick tunnel (testing only)

A quick tunnel gives a random public `https://<words>.trycloudflare.com` address with no account.
It is public, changes every restart and has no uptime guarantee: use fake data only and stop it
when done. See also `cloudflare_tunnel_setup.md`.

```bash
curl -L --output cloudflared.deb \
  https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb
sudo dpkg -i cloudflared.deb
cloudflared tunnel --url http://localhost:80        # prints the trycloudflare.com address; Ctrl+C stops it
```

While it runs set `APP_URL` and `VITE_REVERB_HOST` to that address and rebuild (section 5). After
stopping it, set them back to the Tailscale name — a stale `VITE_REVERB_HOST` breaks live chat.

---

## 9. Backups

The scheduler (`schedule:work` under Supervisor) writes one encrypted zip a day after 02:00
Manila time (database dump, `storage/app`, `.env`) to `storage/app/backups` and deletes zips older
than 30 days. Nothing needs to be scheduled by hand.

```bash
cd /var/www/maternal-health-hub
php artisan schedule:list                 # backup:run and reminders:send listed
php artisan backup:run                    # take one now
ls -la storage/app/backups
grep -c "Backup written" storage/logs/laravel.log
grep "copying it to" storage/logs/laravel.log | tail -3   # copy failures show up here
```

**Keep `BACKUP_PASSWORD` somewhere other than this server** (password manager). Without it a
backup cannot be restored. Each backup contains every patient record and the `.env`.

### 9.1 Off-server copy (REQUIRED: backups on the same disk are not enough)

`BACKUP_COPY_PATH=/mnt/backup-drive` points at a folder that does not exist, so the second copy
fails on every run (logged as `copying it to /mnt/backup-drive failed`). Pick one:

**A. Pull the backups to your Windows PC (no extra hardware).** Run in local PowerShell:

```powershell
mkdir C:\MaternalBackups
scp rusel@100.110.30.36:/var/www/maternal-health-hub/storage/app/backups/backup-*.zip C:\MaternalBackups\
```

Make it daily with Task Scheduler:

```powershell
$action  = New-ScheduledTaskAction -Execute "scp.exe" -Argument "rusel@100.110.30.36:/var/www/maternal-health-hub/storage/app/backups/backup-*.zip C:\MaternalBackups\"
$trigger = New-ScheduledTaskTrigger -Daily -At 6:00AM
Register-ScheduledTask -TaskName "Pull Maternal Hub backups" -Action $action -Trigger $trigger
```

(This needs the SSH key login from section 7.3 so no password is requested; the PC must be on and
on Tailscale at that time.)

**B. External drive on the server:**

```bash
lsblk -f                                  # find the drive, e.g. /dev/sdb1
sudo mkdir -p /mnt/backup-drive
sudo mount /dev/sdb1 /mnt/backup-drive
sudo chown www-data:www-data /mnt/backup-drive
# permanent: add the drive's UUID to /etc/fstab, then `sudo mount -a`
```

**C. Cloud folder** with `rclone` (set `BACKUP_COPY_PATH` to the synced folder).

### 9.2 Prove a backup restores (do this once)

```bash
cd /tmp && mkdir restore-test && cd restore-test
cp /var/www/maternal-health-hub/storage/app/backups/backup-<latest>.zip .
unzip -P '<BACKUP_PASSWORD>' backup-*.zip            # needs `sudo apt install unzip`; 7-Zip on Windows
zcat database.sql.gz | head -20                      # a readable SQL dump
# restore into a SCRATCH database, never the live one:
sudo mysql -e "CREATE DATABASE restore_test;"
zcat database.sql.gz | sudo mysql restore_test
sudo mysql restore_test -e "SELECT COUNT(*) FROM users;"
sudo mysql -e "DROP DATABASE restore_test;"
cd / && rm -rf /tmp/restore-test
```

---

## 10. Front-end build (never run `npm run dev` on the server)

`npm run dev` creates `public/hot`. While that file exists Laravel loads CSS/JS from
`http://127.0.0.1:5173` — the *visitor's* own machine — so the page appears unstyled for everyone
(a page that "looks fine" only because your PC runs a dev server is a false positive). Production
uses the built files:

```bash
cd /var/www/maternal-health-hub
rm -f public/hot
npm ci && npm run build
php artisan config:clear && php artisan config:cache
```

Verify the served page uses built assets over HTTPS:

```bash
curl -sL https://server1.<tailnet>.ts.net/ | grep -oE '(href|src)="[^"]+"' | head
# expect .../build/assets/...css  and NO 127.0.0.1:5173
```

---

## 11. Verification checklist (run after any change)

```bash
# Services
sudo systemctl is-active nginx php8.5-fpm mysql supervisor ssh fail2ban
systemctl --failed --no-pager
sudo supervisorctl status                 # 4 lines, all RUNNING

# Listening ports (3306 and 8080 should be 127.0.0.1 only)
ss -tlnp

# Firewall and Tailscale
sudo ufw status verbose
tailscale status
tailscale serve status
tailscale funnel status

# HTTP end to end
curl -sI http://localhost | head -3                       # HTTP/1.1 200 OK
curl -s -o /dev/null -w '%{http_code}\n' https://server1.<tailnet>.ts.net/

# Scheduler and backups
cd /var/www/maternal-health-hub && php artisan schedule:list
ls -la storage/app/backups

# Go-live gate
php artisan app:go-live-check
```

| Symptom | Cause / fix |
|---|---|
| `backup:run` → permission denied | Section 2 (group membership lost by re-login?) |
| Backup log says copy to `/mnt/backup-drive` failed | Section 9.1 — the folder does not exist |
| Admin page says no backup in days | Scheduler stopped or MySQL down → `supervisorctl status`, `systemctl status mysql` |
| Page loads but is unstyled / plain text | `public/hot` exists or a `npm run dev` is running → section 10 |
| `ERR_NAME_NOT_RESOLVED` on a phone | Device not on the tailnet, or opened inside an in-app browser → install Tailscale, connect, use Chrome; or use Funnel (8.3) |
| `tailscale funnel` refuses | Funnel not allowed in the access rules (8.3) or HTTPS not enabled in DNS settings |
| Login loop right after enabling HTTPS | `APP_URL` still `http://`, `SESSION_SECURE_COOKIE` set before HTTPS works, or (direct certbot only) `fastcgi_param HTTPS` missing |
| Chat needs refresh to show messages | `BROADCAST_CONNECTION=reverb`, `REVERB_*` / `VITE_REVERB_*` values, `npm run build`, Reverb running |
| Upload says file too large | FPM limits (already 26M/28M) — check `php-fpm8.5 -i \| grep -E 'upload_max|post_max'` |
| `sudo` over a script/SSH command says "interactive authentication is required" | Run it in an interactive session where you can type the password |

---

## 12. Deploying updates

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
fail. Never run `migrate:fresh` or `db:seed` here.

---

## 13. When you have a domain

Pick the route that matches who needs to reach the site. All three keep the same app code; only
DNS, the front door, `.env` and a rebuild change.

| | Cloudflare named tunnel (**recommended**) | Port-forward + certbot | Keep Tailscale |
|---|---|---|---|
| Router changes | none | forward 80/443 | none |
| Public IP needed | no | yes | no |
| HTTPS certificate | Cloudflare handles it | certbot (Let's Encrypt) | Tailscale handles it |
| Login in front of the site | yes, Cloudflare Access | no | tailnet membership |
| Reachable by patients/outside staff | yes | yes | no (Funnel for public) |

### 13.1 Cloudflare named tunnel (recommended)

Needs a domain whose DNS is on Cloudflare (free plan). No inbound ports are opened.

```bash
# On the server (cloudflared installed as in section 8.4)
cloudflared tunnel login                       # opens a URL: authorise the domain
cloudflared tunnel create maternal-hub          # prints the tunnel ID and writes credentials
cloudflared tunnel route dns maternal-hub hub.example.com
```

`/etc/cloudflared/config.yml`:

```yaml
tunnel: maternal-hub
credentials-file: /etc/cloudflared/<TUNNEL-ID>.json
ingress:
  - hostname: hub.example.com
    service: http://localhost:80
  - service: http_status:404
```

```bash
sudo mkdir -p /etc/cloudflared && sudo cp ~/.cloudflared/<TUNNEL-ID>.json /etc/cloudflared/
sudo cloudflared service install
sudo systemctl enable --now cloudflared
systemctl status cloudflared --no-pager
```

Optional but advised for patient data: Cloudflare dashboard → **Zero Trust → Access →
Applications → Add → Self-hosted** for `hub.example.com`, allow only your staff emails. Cloudflare
then asks for a login before anyone reaches the app.

Cloudflare connects to nginx from `127.0.0.1`, so Laravel trusts the forwarded HTTPS header (as
with Tailscale). No nginx change is needed.

### 13.2 Router port-forward + certbot (no tunnel)

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

# 2. Let Laravel know the request is HTTPS (nginx terminates TLS directly here; the app trusts
#    only 127.0.0.1 and REMOTE_ADDR is the client, so X-Forwarded-Proto is deliberately ignored —
#    this single param is the correct fix instead).
sudo sed -i 's|^\(\s*\)include fastcgi_params;|\1fastcgi_param HTTPS $https if_not_empty;\n\1include fastcgi_params;|' \
  /etc/nginx/sites-available/maternal-health-hub
grep -A1 'fastcgi_param SCRIPT_FILENAME' /etc/nginx/sites-available/maternal-health-hub

sudo nginx -t && sudo systemctl reload nginx

# 3. Certificate (also installs the HTTP->HTTPS redirect)
sudo certbot --nginx -d hub.example.com
sudo certbot renew --dry-run

# 4. Open only what is needed
sudo ufw delete allow from 192.168.1.0/24 to any port 80 proto tcp
sudo ufw allow 80,443/tcp
```

Then do the common steps (13.4). Optionally add HSTS inside the 443 `server {}` block:
`add_header Strict-Transport-Security "max-age=31536000" always;`

### 13.3 Keep Tailscale only

No change: staff install Tailscale and use the `ts.net` name. Use this when only a small trusted
team needs the site.

### 13.4 Common steps when switching to the domain (all routes)

1. Edit `.env`:

   ```ini
   APP_URL=https://hub.example.com
   VITE_REVERB_HOST="hub.example.com"
   VITE_REVERB_PORT=443
   VITE_REVERB_SCHEME=https
   SESSION_SECURE_COOKIE=true
   ```

   (Server-side `REVERB_HOST=127.0.0.1`, `REVERB_PORT=8080`, `REVERB_SCHEME=http` stay as they
   are.)

2. Rebuild and refresh caches, restart Reverb and the queue:

   ```bash
   cd /var/www/maternal-health-hub
   rm -f public/hot
   npm run build
   php artisan config:clear && php artisan config:cache
   php artisan route:cache && php artisan view:cache
   sudo supervisorctl restart all
   ```

3. Turn off the temporary doors you no longer need:

   ```bash
   sudo tailscale funnel reset                   # stop the public ts.net address
   sudo tailscale serve --bg 80                  # optional: keep a private ts.net address for admins
   # stop a quick tunnel with Ctrl+C in its terminal
   ```

4. Test: log in over `https://hub.example.com` (a login loop means `APP_URL` is still `http://`
   or, for 13.2, `fastcgi_param HTTPS` is missing), send a chat message and confirm it appears
   without a refresh.

5. Final gate:

   ```bash
   php artisan app:go-live-check     # expect exit 0, no FIX FIRST
   ```

Existing sessions end at the switch; everyone signs in again. Old bookmarks to the `ts.net` name
keep working for tailnet users while `tailscale serve` is on.

---

## Related docs

- `README.md` — setup, scheduled jobs, backups, "Before using it with real patients"
- `maternal-healthcare/Deployment Notes.md` — production considerations
- `cloudflare_tunnel_setup.md` — Cloudflare tunnel notes
