# Deployment Guide – Social Management System

Backend on Contabo VPS (`app.ucontents.com`), frontend on Vercel (`ucontents.com`).  
Use this order on a **fresh VPS where git is not installed**.

---

## 1. Fresh VPS: get curl and run the setup script

SSH in, then install `curl` and run the VPS setup script **without cloning** (script is fetched from GitHub).

Repo: **huelab-byte/ucontents-backend** (repo root = Laravel app; there is no `backend/` folder inside the repo).

```bash
# SSH into the VPS
ssh root@YOUR_VPS_IP

# Install curl, then download and run the setup script (installs git, PHP, MySQL, Redis, Nginx, FFmpeg, Qdrant, etc.)
apt update && apt install -y curl
curl -fsSL "https://raw.githubusercontent.com/huelab-byte/ucontents-backend/main/scripts/vps-setup.sh" -o vps-setup.sh
chmod +x vps-setup.sh
sudo ./vps-setup.sh
```

After this, **git and all other dependencies are installed**. Next: clone the repo.

---

## 2. Clone the repository

```bash
cd /var/www
sudo git clone https://github.com/huelab-byte/ucontents-backend.git ucontents-backend
```

Result: app root is `/var/www/ucontents-backend` (Laravel app is at repo root; no `backend/` subfolder).

---

## 3. Database: create DB and user

```bash
sudo mysql_secure_installation
sudo mysql -u root -p
```

In MySQL:

```sql
CREATE DATABASE ucontents_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ucontents_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON ucontents_db.* TO 'ucontents_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 4. Qdrant: start and check

```bash
sudo systemctl start qdrant
sudo systemctl enable qdrant
curl http://localhost:6333/health
# Expect: {"status":"ok"}
```

---

## 5. Backend: .env and first deploy

```bash
cd /var/www/ucontents-backend
cp .env.production.example .env
nano .env
```

Set at least: `APP_KEY` (generate below), `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `APP_URL=https://app.ucontents.com`.

```bash
php artisan key:generate
chmod +x scripts/deploy-backend.sh
./scripts/deploy-backend.sh
```

(If you use a different branch: `./scripts/deploy-backend.sh your-branch`.)

---

## 6. Nginx and site

```bash
cd /var/www/ucontents-backend
sudo cp deployment/nginx-app.ucontents.com.conf /etc/nginx/sites-available/app.ucontents.com
sudo ln -sf /etc/nginx/sites-available/app.ucontents.com /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

Point `app.ucontents.com` in DNS to this server's IP, then test: `http://app.ucontents.com`.

---

## 7. SSL (HTTPS)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d app.ucontents.com
```

Follow prompts. Certbot will adjust Nginx for HTTPS.

---

## 8. Queue worker (systemd)

```bash
cd /var/www/ucontents-backend
sudo cp deployment/laravel-queue-worker.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable laravel-queue-worker
sudo systemctl start laravel-queue-worker
sudo systemctl status laravel-queue-worker
```

---

## 9. Frontend on Vercel

- Vercel → New Project → Import repo.
- **Root Directory:** `frontend` (use your frontend repo).
- **Environment variable:** `NEXT_PUBLIC_API_URL=https://app.ucontents.com/api`.
- Deploy, then add domain `ucontents.com` in Vercel and set DNS as shown.

---

## Order summary (copy-paste flow)

| Step | What |
|------|------|
| 1 | SSH → `apt install -y curl` → download and run `scripts/vps-setup.sh` from raw GitHub (huelab-byte/ucontents-backend) |
| 2 | `cd /var/www && git clone https://github.com/huelab-byte/ucontents-backend.git ucontents-backend` |
| 3 | Create MySQL database and user |
| 4 | Start Qdrant and check `/health` |
| 5 | In `/var/www/ucontents-backend`: `.env` from `.env.production.example`, `key:generate`, `./scripts/deploy-backend.sh` |
| 6 | Nginx: copy `deployment/nginx-app.ucontents.com.conf` and enable site |
| 7 | SSL: `certbot --nginx -d app.ucontents.com` |
| 8 | Queue: copy `deployment/laravel-queue-worker.service` and enable/start |
| 9 | Vercel: import **frontend** repo, set `NEXT_PUBLIC_API_URL`, deploy and add domain |

---

## Updating the backend later

From the server:

```bash
cd /var/www/ucontents-backend
./scripts/deploy-backend.sh
# or: ./scripts/deploy-backend.sh develop
```

This pulls the repo, runs migrations, refreshes caches, and restarts the queue worker. Database is backed up automatically before migrations.

---

## Backend .env (main variables)

See `.env.production.example` in the repo root. Minimum:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.ucontents.com
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=ucontents_db
DB_USERNAME=ucontents_user
DB_PASSWORD=...
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
QDRANT_URL=http://localhost:6333
QDRANT_FOOTAGE_COLLECTION=footage_embeddings
```

---

## Quick checks

```bash
# Services
sudo systemctl status nginx php8.2-fpm mysql redis-server qdrant laravel-queue-worker

# Logs
tail -f /var/www/ucontents-backend/storage/logs/laravel.log
sudo journalctl -u laravel-queue-worker -f
```

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| Permission errors | `sudo chown -R www-data:www-data /var/www/ucontents-backend/storage` and `sudo chmod -R 775 .../storage` |
| Queue not running | `sudo systemctl restart laravel-queue-worker` and `cd /var/www/ucontents-backend && php artisan queue:restart` |
| Qdrant not reachable | `curl http://localhost:6333/health` and check `QDRANT_URL` in `.env` |
| FFmpeg missing | `which ffmpeg ffprobe` — both should be under `/usr/bin` after `vps-setup.sh` |

---

## Security checklist

- [ ] SSL on `app.ucontents.com` (Certbot)
- [ ] UFW: 22, 80, 443 (and 6333 only if needed)
- [ ] MySQL user limited to `ucontents_db`
- [ ] `.env` not in web root; permissions 600
- [ ] Queue worker runs as `www-data`
