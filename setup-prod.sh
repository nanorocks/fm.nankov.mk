#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────
#  FM Macedonia — Production Setup Script
#  Run from the repo root to deploy / update the app.
#  Safe to re-run: every step is idempotent.
#
#  Usage:
#    bash setup-prod.sh                   full setup + scrape
#    bash setup-prod.sh --skip-scrape     skip data scraping
#    bash setup-prod.sh --fresh           wipe DB and re-migrate  (DESTRUCTIVE)
#    bash setup-prod.sh --no-build        skip npm build (when only PHP changed)
# ─────────────────────────────────────────────────────────────
set -euo pipefail

# ── Colour helpers ────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'
ok()    { echo -e "${GREEN}✓${NC}  $*"; }
info()  { echo -e "${CYAN}→${NC}  $*"; }
warn()  { echo -e "${YELLOW}⚠${NC}  $*"; }
fail()  { echo -e "${RED}✗${NC}  $*" >&2; exit 1; }
title() { echo -e "\n${BOLD}── $* ${NC}$(printf '─%.0s' {1..40})\n"; }

# ── Flags ─────────────────────────────────────────────────────
SKIP_SCRAPE=false
FRESH_MIGRATE=false
NO_BUILD=false
for arg in "$@"; do
  case $arg in
    --skip-scrape) SKIP_SCRAPE=true ;;
    --fresh)       FRESH_MIGRATE=true ;;
    --no-build)    NO_BUILD=true ;;
  esac
done

# ── Locate project directory ──────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$SCRIPT_DIR/project"
[[ -d "$PROJECT_DIR" ]] || fail "project/ directory not found at $SCRIPT_DIR"
cd "$PROJECT_DIR"
info "Working directory: $PROJECT_DIR"

# ─────────────────────────────────────────────────────────────
# 1. REQUIREMENTS
# ─────────────────────────────────────────────────────────────
title "Requirements"

command -v composer &>/dev/null || fail "composer not found in PATH"
command -v node     &>/dev/null || fail "node not found in PATH"
command -v npm      &>/dev/null || fail "npm not found in PATH"

PHP_MAJOR=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR=$(php -r 'echo PHP_MINOR_VERSION;')
[[ "$PHP_MAJOR" -ge 8 && "$PHP_MINOR" -ge 3 ]] || \
  fail "PHP 8.3+ required (found $(php -r 'echo PHP_VERSION;'))"

ok "PHP $(php -r 'echo PHP_VERSION;')"
ok "Node $(node --version)"
ok "Composer $(composer --version --no-ansi | awk '{print $3}')"

# ─────────────────────────────────────────────────────────────
# 2. ENVIRONMENT
# ─────────────────────────────────────────────────────────────
title "Environment"

if [[ ! -f .env ]]; then
  [[ -f .env.example ]] || fail ".env.example not found"
  cp .env.example .env
  warn ".env created from .env.example — edit APP_URL and RADIO_CHANNELS_URL before continuing"
  warn "Press Enter to continue or Ctrl+C to stop and edit .env first"
  read -r
fi

# Ensure production values
if ! grep -q "^APP_ENV=production" .env; then
  warn "APP_ENV is not set to 'production' — updating..."
  sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env
  ok "APP_ENV=production"
fi
if ! grep -q "^APP_DEBUG=false" .env; then
  warn "APP_DEBUG is not false — disabling..."
  sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
  ok "APP_DEBUG=false"
fi

# Generate app key if missing
if ! grep -q "^APP_KEY=base64:" .env; then
  info "Generating application key..."
  php artisan key:generate --force --no-interaction
  ok "App key generated"
else
  ok "App key already set"
fi

# ─────────────────────────────────────────────────────────────
# 3. PHP DEPENDENCIES
# ─────────────────────────────────────────────────────────────
title "PHP dependencies"

# Clear stale bootstrap cache before install so package:discover runs clean
php artisan clear-compiled 2>/dev/null || true

info "composer install --no-dev (optimized)..."
composer install \
  --no-dev \
  --optimize-autoloader \
  --no-interaction \
  --prefer-dist \
  2>&1 | grep -E "^(- Installing|- Updating|Generating|Nothing to install)" || true

ok "Composer install complete"

# ─────────────────────────────────────────────────────────────
# 4. FRONTEND ASSETS
# ─────────────────────────────────────────────────────────────
title "Frontend assets"

if $NO_BUILD; then
  warn "Skipping npm build (--no-build)"
else
  info "Installing npm dependencies..."
  npm ci --prefer-offline --no-fund --silent 2>&1 | tail -2

  info "Building production assets..."
  npm run build 2>&1 | grep -E "(built in|vite build|error)" || true
  ok "Frontend build complete"
fi

# ─────────────────────────────────────────────────────────────
# 5. DATABASE
# ─────────────────────────────────────────────────────────────
title "Database"

touch database/database.sqlite
ok "SQLite file ready"

if $FRESH_MIGRATE; then
  warn "--fresh: wiping and re-migrating (all data will be lost)"
  php artisan migrate:fresh --force --no-interaction
else
  php artisan migrate --force --no-interaction
fi
ok "Migrations up to date"

# ─────────────────────────────────────────────────────────────
# 6. STORAGE
# ─────────────────────────────────────────────────────────────
title "Storage"

php artisan storage:link --force 2>/dev/null || true
ok "Storage symlink ready"

# Ensure PWA icons directory exists (needed if first deploy after fresh clone)
mkdir -p public/images/pwa

if [[ ! -f public/images/pwa/icon-512.png ]]; then
  warn "PWA icons missing — generating from logo-icon.svg..."
  if command -v convert &>/dev/null && [[ -f public/images/logo-icon.svg ]]; then
    for size in 72 96 128 144 152 192 384 512; do
      convert -background "#121212" -density 300 \
        public/images/logo-icon.svg \
        -resize ${size}x${size} \
        public/images/pwa/icon-${size}.png 2>/dev/null
    done
    cp public/images/pwa/icon-192.png public/apple-touch-icon.png 2>/dev/null || true
    cp public/images/pwa/icon-192.png public/favicon.png 2>/dev/null || true
    ok "PWA icons generated"
  else
    warn "ImageMagick not found — copy PNG icons to public/images/pwa/ manually"
  fi
else
  ok "PWA icons present"
fi

# ─────────────────────────────────────────────────────────────
# 7. CACHES & OPTIMISATION
# ─────────────────────────────────────────────────────────────
title "Optimisation"

# Wipe all stale caches first
php artisan optimize:clear --no-interaction 2>/dev/null || \
  { php artisan config:clear; php artisan route:clear; php artisan view:clear; php artisan cache:clear; }

# Rebuild PWA manifest from config/pwa.php
info "Regenerating PWA manifest..."
php artisan erag:update-manifest
ok "PWA manifest updated"

# Single command that does config + route + view + event cache
php artisan optimize --no-interaction
ok "Application optimised (config/route/view/event caches built)"

# ─────────────────────────────────────────────────────────────
# 8. DATA SCRAPING
# ─────────────────────────────────────────────────────────────
title "Data scraping"

if $SKIP_SCRAPE; then
  warn "Scraping skipped (--skip-scrape)"
else
  # Flush config cache temporarily so tinker can boot without cached stale config
  info "Scraping station list from makedonijafm.net..."
  php artisan app:radio-web-scraper

  SCRAPED=$(php -r "
    \$pdo = new PDO('sqlite:database/database.sqlite');
    echo \$pdo->query('SELECT COUNT(*) FROM radio_channels')->fetchColumn();
  " 2>/dev/null || echo "?")
  ok "Scraped $SCRAPED stations"

  info "Downloading station logos..."
  php artisan app:radio-channel-table-data-transformation

  info "Publishing stations with live streams..."
  php -r "
    \$pdo = new PDO('sqlite:database/database.sqlite');
    \$pdo->exec(\"UPDATE radio_channels SET published=1 WHERE audio_url IS NOT NULL AND audio_url != ''\");
    echo \$pdo->query('SELECT COUNT(*) FROM radio_channels WHERE published=1')->fetchColumn() . ' stations published';
  " 2>/dev/null && echo "" || warn "Could not auto-publish; run manually"
fi

# ─────────────────────────────────────────────────────────────
# 9. ADMIN PANEL
# ─────────────────────────────────────────────────────────────
title "Admin panel"

ADMIN_COUNT=$(php -r "
  \$pdo = new PDO('sqlite:database/database.sqlite');
  echo \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
" 2>/dev/null || echo "0")

if [[ "$ADMIN_COUNT" == "0" ]]; then
  warn "No admin users found. Create one with:"
  echo ""
  echo "    cd $PROJECT_DIR && php artisan make:filament-user"
  echo ""
else
  ok "$ADMIN_COUNT admin user(s) already exist"
fi

# ─────────────────────────────────────────────────────────────
# DONE
# ─────────────────────────────────────────────────────────────
APP_URL=$(grep "^APP_URL=" .env | cut -d= -f2 | tr -d '"')
echo ""
echo -e "${BOLD}══════════════════════════════════════════════════════════${NC}"
ok "Deployment complete"
echo ""
echo -e "  ${CYAN}Public site${NC}   ${APP_URL}/"
echo -e "  ${CYAN}Admin panel${NC}   ${APP_URL}/admin"
echo -e "  ${CYAN}PWA manifest${NC}  ${APP_URL}/manifest.json"
echo -e "${BOLD}══════════════════════════════════════════════════════════${NC}"
