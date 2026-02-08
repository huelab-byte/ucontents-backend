#!/bin/bash

# VPS Initial Setup Script for Social Management System
# This script sets up a fresh Ubuntu VPS with all required dependencies
# Run as root or with sudo

set -e  # Exit on error

echo "=========================================="
echo "VPS Setup Script - Social Management System"
echo "=========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}Please run as root or with sudo${NC}"
    exit 1
fi

# Update system packages
echo -e "${GREEN}[1/15] Updating system packages...${NC}"
export DEBIAN_FRONTEND=noninteractive
apt update && apt upgrade -y

# Install basic utilities
echo -e "${GREEN}[2/15] Installing basic utilities...${NC}"
apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release

# Add PHP repository
echo -e "${GREEN}[3/15] Adding PHP repository...${NC}"
add-apt-repository -y ppa:ondrej/php

# Update package list after adding repository
apt update

# Install PHP 8.2 and required extensions
echo -e "${GREEN}[4/15] Installing PHP 8.2 and extensions...${NC}"
apt install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-zip php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-bcmath php8.2-redis php8.2-intl php8.2-soap

# Install MySQL
echo -e "${GREEN}[5/15] Installing MySQL...${NC}"
apt install -y mysql-server

# Install Redis
echo -e "${GREEN}[6/15] Installing Redis...${NC}"
apt install -y redis-server

# Install Nginx
echo -e "${GREEN}[7/15] Installing Nginx...${NC}"
apt install -y nginx

# Install FFmpeg and FFprobe
echo -e "${GREEN}[8/15] Installing FFmpeg and FFprobe...${NC}"
apt install -y ffmpeg

# Verify FFmpeg installation
if command -v ffmpeg &> /dev/null && command -v ffprobe &> /dev/null; then
    echo -e "${GREEN}✓ FFmpeg installed: $(ffmpeg -version | head -n 1)${NC}"
    echo -e "${GREEN}✓ FFprobe installed: $(ffprobe -version | head -n 1)${NC}"
else
    echo -e "${RED}✗ FFmpeg installation failed${NC}"
    exit 1
fi

# Install Composer
echo -e "${GREEN}[9/15] Installing Composer...${NC}"
if [ ! -f /usr/local/bin/composer ]; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    chmod +x /usr/local/bin/composer
fi

# Install Node.js 20.x (for asset compilation)
echo -e "${GREEN}[10/15] Installing Node.js...${NC}"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# Install Qdrant (do not exit on failure - steps 12-15 must always run)
echo -e "${GREEN}[11/15] Installing Qdrant...${NC}"
set +e
if [ ! -f /usr/bin/qdrant ]; then
    QDRANT_VERSION="1.16.3"
    case "$(uname -m)" in
        x86_64|amd64) ARCH="x86_64-unknown-linux-gnu" ;;
        aarch64|arm64) ARCH="aarch64-unknown-linux-gnu" ;;
        *) ARCH="x86_64-unknown-linux-gnu" ;;
    esac
    echo "  Downloading Qdrant ${QDRANT_VERSION} for ${ARCH}..."
    wget -q "https://github.com/qdrant/qdrant/releases/download/v${QDRANT_VERSION}/qdrant-${ARCH}.tar.gz" -O /tmp/qdrant.tar.gz 2>/dev/null && \
    tar -xzf /tmp/qdrant.tar.gz -C /tmp 2>/dev/null && \
    mv /tmp/qdrant /usr/bin/qdrant 2>/dev/null && \
    chmod +x /usr/bin/qdrant && \
    rm -f /tmp/qdrant.tar.gz && \
    echo -e "${GREEN}  Qdrant installed.${NC}" || \
    echo -e "${YELLOW}  Qdrant install failed. Create service anyway; install binary manually later.${NC}"
fi
set -e

# Create Qdrant systemd service
echo -e "${GREEN}[12/15] Creating Qdrant systemd service...${NC}"
cat > /etc/systemd/system/qdrant.service << 'EOF'
[Unit]
Description=Qdrant Vector Search Engine
After=network.target

[Service]
Type=simple
User=qdrant
Group=qdrant
ExecStart=/usr/bin/qdrant
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

# Create Qdrant user and data directory
if ! id "qdrant" &>/dev/null; then
    useradd -r -s /bin/false qdrant
fi
mkdir -p /var/lib/qdrant
chown -R qdrant:qdrant /var/lib/qdrant

# Reload systemd to recognize the new service
systemctl daemon-reload

# Create application directory structure
echo -e "${GREEN}[13/15] Creating application directory structure...${NC}"
mkdir -p /var/www
chown -R www-data:www-data /var/www

# Configure firewall
echo -e "${GREEN}[14/15] Configuring firewall...${NC}"
if command -v ufw &> /dev/null; then
    ufw --force enable
    ufw allow 22/tcp    # SSH
    ufw allow 80/tcp   # HTTP
    ufw allow 443/tcp  # HTTPS
    ufw allow 6333/tcp # Qdrant (optional, can be restricted to localhost)
    echo -e "${YELLOW}Firewall configured. Qdrant port 6333 is open. Consider restricting it to localhost only.${NC}"
fi

# Configure Redis
echo -e "${GREEN}[15/15] Configuring Redis...${NC}"
sed -i 's/^supervised no/supervised systemd/' /etc/redis/redis.conf
systemctl restart redis-server
systemctl enable redis-server

# Configure PHP-FPM
echo -e "${GREEN}Configuring PHP-FPM...${NC}"
sed -i 's/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/' /etc/php/8.2/fpm/php.ini
systemctl restart php8.2-fpm
systemctl enable php8.2-fpm

# Configure MySQL
echo -e "${GREEN}Configuring MySQL...${NC}"
systemctl start mysql
systemctl enable mysql

# Configure Nginx
echo -e "${GREEN}Configuring Nginx...${NC}"
systemctl start nginx
systemctl enable nginx

# Summary
echo ""
echo -e "${GREEN}=========================================="
echo "Setup Complete!"
echo "==========================================${NC}"
echo ""
echo "Installed components:"
echo "  ✓ PHP 8.2 with required extensions"
echo "  ✓ MySQL Server"
echo "  ✓ Redis Server"
echo "  ✓ Nginx"
echo "  ✓ FFmpeg & FFprobe"
echo "  ✓ Composer"
echo "  ✓ Node.js $(node --version)"
echo "  ✓ Qdrant"
echo ""
echo "Next steps:"
echo "  1. Secure MySQL: sudo mysql_secure_installation"
echo "  2. Create database and user (see docs/DEPLOYMENT.md)"
echo "  3. Clone repo: cd /var/www && git clone https://github.com/huelab-byte/ucontents-backend.git ucontents-backend"
echo "  4. Run: cd /var/www/ucontents-backend && ./scripts/deploy-backend.sh"
echo "     (deploy-backend.sh installs and starts queue workers and scheduler automatically; no manual steps)"
echo ""
echo -e "${YELLOW}Important:${NC}"
echo "  - Configure MySQL root password"
echo "  - Set up SSL certificates for domains"
echo "  - Configure Qdrant API key (optional)"
echo "  - Queue worker handles async jobs (video processing, emails)"
echo "  - Scheduler handles scheduled tasks (bulk posting campaigns)"
echo ""
