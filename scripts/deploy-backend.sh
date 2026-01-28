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

# Resolve paths: script is in backend/scripts/, so APP_DIR = backend
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
REPO_ROOT="$(cd "$APP_DIR/.." && pwd)"
BACKUP_DIR="$REPO_ROOT/backups"
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
echo -e "${GREEN}[4/10] Installing Composer dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction

# Install/update NPM dependencies (if needed)
if [ -f package.json ]; then
    echo -e "${GREEN}[5/10] Installing NPM dependencies...${NC}"
    npm ci --production 2>/dev/null || npm install --production 2>/dev/null || echo -e "${YELLOW}NPM install skipped${NC}"
fi

# Run database migrations (safe, won't lose data)
echo -e "${GREEN}[6/10] Running database migrations...${NC}"
php artisan migrate --force

# Clear and cache configuration
echo -e "${GREEN}[7/10] Optimizing application...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure storage link exists
echo -e "${GREEN}[8/10] Ensuring storage link...${NC}"
php artisan storage:link || true

# Set proper permissions
echo -e "${GREEN}[9/10] Setting permissions...${NC}"
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Restart queue workers (graceful restart)
echo -e "${GREEN}[10/10] Restarting queue workers...${NC}"
php artisan queue:restart
systemctl restart laravel-queue-worker 2>/dev/null || echo -e "${YELLOW}Queue worker service not found or not running${NC}"

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
echo "  3. Test API endpoints"
echo ""
