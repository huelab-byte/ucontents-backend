#!/bin/bash

# Backend Deployment Script
# This script safely deploys updates to the Laravel backend
# It preserves all data and handles migrations safely
# Run from backend directory: ./scripts/deploy-backend.sh [branch]

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Resolve paths: script is in scripts/, APP_DIR = repo root (Laravel app)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
# Repo root = app dir when this repo is backend-only (no parent monorepo)
if [ -d "$APP_DIR/.git" ]; then
    REPO_ROOT="$APP_DIR"
else
    REPO_ROOT="$(cd "$APP_DIR/.." && pwd)"
fi
BACKUP_DIR="$APP_DIR/backups"
GIT_BRANCH="${1:-main}"  # Default to main branch

echo "=========================================="
echo "Backend Deployment Script"
echo "=========================================="
echo ""

# Check application directory
if [ ! -f "$APP_DIR/artisan" ]; then
    echo -e "${RED}Error: Not a Laravel app (artisan not found). Run from backend directory.${NC}"
    echo "Expected: $APP_DIR/artisan"
    exit 1
fi

cd "$APP_DIR"

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${RED}Error: .env file not found${NC}"
    echo "Please create .env from .env.production.example"
    exit 1
fi

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Backup database before migration
echo -e "${GREEN}[1/10] Creating database backup...${NC}"
DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2 | tr -d ' ')
DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2 | tr -d ' ')
DB_PASS=$(grep DB_PASSWORD .env | cut -d '=' -f2 | tr -d ' ')

if [ -n "$DB_NAME" ] && [ -n "$DB_USER" ]; then
    BACKUP_FILE="$BACKUP_DIR/db_backup_$(date +%Y%m%d_%H%M%S).sql"
    mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE" 2>/dev/null || {
        echo -e "${YELLOW}Warning: Could not create database backup. Continuing anyway...${NC}"
    }
    if [ -f "$BACKUP_FILE" ]; then
        echo -e "${GREEN}✓ Database backed up to: $BACKUP_FILE${NC}"
        ls -t "$BACKUP_DIR"/db_backup_*.sql 2>/dev/null | tail -n +6 | xargs rm -f 2>/dev/null || true
    fi
fi

# Enable maintenance mode
echo -e "${GREEN}[2/10] Enabling maintenance mode...${NC}"
php artisan down || true

# Pull latest code (from repo root)
echo -e "${GREEN}[3/10] Pulling latest code from GitHub...${NC}"
cd "$REPO_ROOT"
git fetch origin
git checkout "$GIT_BRANCH"
git pull origin "$GIT_BRANCH"

cd "$APP_DIR"

# Install/update Composer dependencies
echo -e "${GREEN}[4/12] Installing Composer dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction

# Regenerate autoloader for new modules (CRITICAL for new modules to be recognized)
echo -e "${GREEN}[5/12] Regenerating autoloader for modules...${NC}"
composer dump-autoload --optimize --no-interaction 2>&1 | grep -v "does not comply with psr-4" || true

# Clear module cache and rediscover modules
echo -e "${GREEN}[6/12] Clearing and rediscovering modules...${NC}"
php artisan module:clear 2>/dev/null || true
php artisan package:discover --ansi

# Install/update NPM dependencies (if needed)
if [ -f package.json ]; then
    echo -e "${GREEN}[7/12] Installing NPM dependencies...${NC}"
    npm ci --production 2>/dev/null || npm install --production 2>/dev/null || echo -e "${YELLOW}NPM install skipped${NC}"
fi

# Run database migrations (safe, won't lose data)
echo -e "${GREEN}[8/12] Running database migrations...${NC}"
php artisan migrate --force

# Run database seeders (safe - uses firstOrCreate/updateOrCreate)
echo -e "${GREEN}[9/12] Running database seeders...${NC}"
# Run seeders (all seeders use firstOrCreate/updateOrCreate - safe for repeated runs)
# Note: PSR-4 warnings from modules are harmless - Laravel Modules handles loading
php artisan db:seed --force || {
    echo -e "${YELLOW}Warning: Seeder execution had issues. Check logs. Continuing deployment...${NC}"
}

# Clear and cache configuration
echo -e "${GREEN}[10/12] Optimizing application...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache

# Route cache (skip if there are duplicate route names)
php artisan route:cache 2>/dev/null || {
    echo -e "${YELLOW}Warning: Route caching failed (likely duplicate route names). Routes will not be cached.${NC}"
    echo -e "${YELLOW}This is not critical - routes will work, just not cached for performance.${NC}"
}

# View cache (skip if view directories don't exist)
php artisan view:cache 2>/dev/null || {
    echo -e "${YELLOW}Warning: View caching failed (likely missing view directories). Views will not be cached.${NC}"
    echo -e "${YELLOW}This is not critical - views will work, just not cached for performance.${NC}"
}

# Ensure storage link exists
php artisan storage:link || true

# Set proper permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Apply system configurations (PHP & Nginx)
echo -e "${GREEN}[11/13] Applying system configuration updates...${NC}"

# Update PHP limits
if [ -f "$APP_DIR/deployment/php8.2-fpm-upload-limits.ini" ]; then
    echo "Updating PHP upload limits..."
    # Try direct copy, then sudo
    cp "$APP_DIR/deployment/php8.2-fpm-upload-limits.ini" /etc/php/8.2/fpm/conf.d/99-upload-limits.ini 2>/dev/null || \
    sudo cp "$APP_DIR/deployment/php8.2-fpm-upload-limits.ini" /etc/php/8.2/fpm/conf.d/99-upload-limits.ini 2>/dev/null || \
    echo -e "${YELLOW}Warning: Could not copy PHP config. Permission denied.${NC}"
    
    # Restart PHP-FPM
    echo "Restarting PHP-FPM..."
    systemctl restart php8.2-fpm 2>/dev/null || sudo systemctl restart php8.2-fpm 2>/dev/null || echo -e "${YELLOW}Warning: Could not restart php8.2-fpm.${NC}"
fi

# Update Nginx config
# Check if config exists in sites-available (to preserve SSL config)
if [ -f "/etc/nginx/sites-available/app.ucontents.com" ]; then
    echo "Updating Nginx upload limits (in-place)..."
    # Use sed to update the limit without overwriting the file (preserving SSL settings)
    sed -i 's/client_max_body_size [0-9]*[MG];/client_max_body_size 1024M;/g' /etc/nginx/sites-available/app.ucontents.com 2>/dev/null || \
    sudo sed -i 's/client_max_body_size [0-9]*[MG];/client_max_body_size 1024M;/g' /etc/nginx/sites-available/app.ucontents.com 2>/dev/null

    # Reload Nginx
    echo "Reloading Nginx..."
    systemctl reload nginx 2>/dev/null || sudo systemctl reload nginx 2>/dev/null || echo -e "${YELLOW}Warning: Could not reload Nginx.${NC}"

# If not found in /etc/nginx, copy from deployment folder (first install)
elif [ -f "$APP_DIR/deployment/nginx-app.ucontents.com.conf" ]; then
    echo "Installing Nginx configuration..."
    # Update sites-available
    cp "$APP_DIR/deployment/nginx-app.ucontents.com.conf" /etc/nginx/sites-available/app.ucontents.com 2>/dev/null || \
    sudo cp "$APP_DIR/deployment/nginx-app.ucontents.com.conf" /etc/nginx/sites-available/app.ucontents.com 2>/dev/null || \
    echo -e "${YELLOW}Warning: Could not copy Nginx config. Permission denied.${NC}"
    
    # Reload Nginx
    echo "Reloading Nginx..."
    systemctl reload nginx 2>/dev/null || sudo systemctl reload nginx 2>/dev/null || echo -e "${YELLOW}Warning: Could not reload Nginx.${NC}"
fi

# Restart queue workers (graceful restart)
echo -e "${GREEN}[12/13] Restarting queue workers...${NC}"
php artisan queue:restart
systemctl restart laravel-queue-worker 2>/dev/null || sudo systemctl restart laravel-queue-worker 2>/dev/null || echo -e "${YELLOW}Queue worker service not found or not running${NC}"

# Restart scheduler (for scheduled tasks like bulk-posting:process-schedule)
echo -e "${GREEN}[13/13] Restarting Laravel scheduler...${NC}"
systemctl restart laravel-scheduler 2>/dev/null || sudo systemctl restart laravel-scheduler 2>/dev/null || echo -e "${YELLOW}Scheduler service not found or not running. Install with: sudo cp deployment/laravel-scheduler.service /etc/systemd/system/ && sudo systemctl enable laravel-scheduler && sudo systemctl start laravel-scheduler${NC}"

# Disable maintenance mode
echo -e "${GREEN}Disabling maintenance mode...${NC}"
php artisan up

# Summary
echo ""
echo -e "${GREEN}=========================================="
echo "Deployment Complete!"
echo "==========================================${NC}"
echo ""
echo "Deployed branch: $GIT_BRANCH"
echo "Application directory: $APP_DIR"
if [ -n "${BACKUP_FILE:-}" ] && [ -f "$BACKUP_FILE" ]; then
    echo "Database backup: $BACKUP_FILE"
fi
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "  1. Check application logs: tail -f storage/logs/laravel.log"
echo "  2. Verify queue workers: systemctl status laravel-queue-worker"
echo "  3. Verify scheduler: systemctl status laravel-scheduler"
echo "  4. Test API endpoints"
echo ""
