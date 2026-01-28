# Deployment Scripts

This repo (**huelab-byte/ucontents-backend**) is the Laravel backend only; repo root = app root (no `backend/` folder).

## Fresh VPS (no git installed)

Download and run the setup script from GitHub (no clone needed first):

```bash
apt update && apt install -y curl
curl -fsSL "https://raw.githubusercontent.com/huelab-byte/ucontents-backend/main/scripts/vps-setup.sh" -o vps-setup.sh
chmod +x vps-setup.sh
sudo ./vps-setup.sh
```

Then clone the repo:

```bash
cd /var/www
git clone https://github.com/huelab-byte/ucontents-backend.git ucontents-backend
```

## Layout on VPS

- `/var/www/ucontents-backend/` — repo root = Laravel app root
- `/var/www/ucontents-backend/scripts/` — these scripts
- `/var/www/ucontents-backend/deployment/` — nginx and systemd configs

## Scripts

### `vps-setup.sh`

One-time VPS setup (PHP, MySQL, Redis, Nginx, FFmpeg, Qdrant, Composer, Node, git). Run via curl (above) or after clone:

```bash
cd /var/www/ucontents-backend
chmod +x scripts/vps-setup.sh
sudo ./scripts/vps-setup.sh
```

### `deploy-backend.sh`

Deploy/update the app. Run from repo root:

```bash
cd /var/www/ucontents-backend
./scripts/deploy-backend.sh
# or: ./scripts/deploy-backend.sh develop
```

## Deployment configs

- **Nginx:** `deployment/nginx-app.ucontents.com.conf` → copy to `/etc/nginx/sites-available/`
- **Queue worker:** `deployment/laravel-queue-worker.service` → copy to `/etc/systemd/system/`

See `docs/DEPLOYMENT.md` in the repo for the full flow.
