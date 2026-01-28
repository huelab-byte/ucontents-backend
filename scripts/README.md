# Deployment Scripts

Scripts live inside the backend repo so you can push them to Git and run them on the VPS after cloning.

## Layout on VPS

Clone the **whole repo** (monorepo with `backend` and `frontend`) so that on the VPS you have:

- `/var/www/ucontents/` — repo root  
- `/var/www/ucontents/backend/` — Laravel app  
- `/var/www/ucontents/backend/scripts/` — these scripts  
- `/var/www/ucontents/backend/deployment/` — nginx and systemd configs  

## Scripts

### `vps-setup.sh`

One-time VPS setup. Installs PHP, MySQL, Redis, Nginx, FFmpeg, Qdrant, Composer, Node, etc.

**On VPS (after cloning backend):**

```bash
cd /var/www/ucontents/backend
chmod +x scripts/vps-setup.sh
sudo ./scripts/vps-setup.sh
```

Or download only the backend (e.g. from GitHub) and run:

```bash
cd /path/to/backend
chmod +x scripts/vps-setup.sh
sudo ./scripts/vps-setup.sh
```

### `deploy-backend.sh`

Deploys/updates the Laravel app: backup DB, pull code, migrate, cache, permissions, restart queue.

**On VPS (run from backend directory):**

```bash
cd /var/www/ucontents/backend
chmod +x scripts/deploy-backend.sh
./scripts/deploy-backend.sh          # uses branch 'main'
./scripts/deploy-backend.sh develop  # or another branch
```

The script detects it is inside `backend/` and uses the parent directory as repo root for `git pull`.

## Deployment configs

- **Nginx:** `backend/deployment/nginx-app.ucontents.com.conf`  
  Copy to `/etc/nginx/sites-available/` and enable the site.

- **Queue worker:** `backend/deployment/laravel-queue-worker.service`  
  Copy to `/etc/systemd/system/` and enable/start the service.

See `docs/DEPLOYMENT.md` (in the repo root) for full steps.
