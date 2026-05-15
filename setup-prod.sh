#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────
#  FM Macedonia — Production Setup Script
#  Run once from the repo root to prepare the app for production.
#  Safe to re-run: idempotent for most steps.
#
#  Usage:
#    bash setup-prod.sh
#    bash setup-prod.sh --skip-scrape     # skip the data scraper
#    bash setup-prod.sh --fresh           # migrate:fresh (wipes DB!)
# ─────────────────────────────────────────────────────────────
set -euo pipefail

# ── Helpers ──────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
ok()   { echo -e "${GREEN}✓${NC}  $*"; }
info() { echo -e "${CYAN}→${NC}  $*"; }
warn() { echo -e "${YELLOW}!${NC}  $*"; }
fail() { echo -e "${RED}✗${NC}  $*" >&2; exit 1; }

SKIP_SCRAPE=false
FRESH_MIGRATE=false
for arg in "$@"; do
  case $arg in
    --skip-scrape) SKIP_SCRAPE=true ;;
    --fresh)       FRESH_MIGRATE=true ;;
  esac
done

# ── Locate project directory ──────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$SCRIPT_DIR/project"

[[ -d "$PROJECT_DIR" ]] || fail "project/ directory not found at $SCRIPT_DIR"
cd "$PROJECT_DIR"
info "Working directory: $PROJECT_DIR"

# ── 1. PHP & Node version check ───────────────────────────────
echo ""
echo "── Requirements ──────────────────────────────────────────"
PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')
NODE_VERSION=$(node --version 2>/dev/null | tr -d 'v' || echo "0")
COMPOSER_BIN=$(command -v composer || fail "composer not found in PATH")

php -r 'if(PHP_MAJOR_VERSION < 8 || (PHP_MAJOR_VERSION == 8 && PHP_MINOR_VERSION < 3)) { echo "PHP 8.3+ required\n"; exit(1); }' \
  || fail "PHP 8.3+ is required (found $PHP_VERSION)"
ok "PHP $PHP_VERSION"
ok "Node $(node --version)"
ok "Composer $(composer --version --no-ansi | awk '{print $3}')"

# ── 2. .env setup ─────────────────────────────────────────────
echo ""
echo "── Environment ───────────────────────────────────────────"
if [[ ! -f .env ]]; then
  [[ -f .env.example ]] || fail ".env.example not found"
  cp .env.example .env
  warn ".env created from .env.example — review it before continuing"
fi

if ! grep -q "^APP_KEY=base64:" .env; then
  info "Generating application key..."
  php artisan key:generate --force --no-interaction
  ok "App key generated"
else
  ok "App key already set"
fi

# ── 3. PHP dependencies ───────────────────────────────────────
echo ""
echo "── PHP dependencies ──────────────────────────────────────"
info "Running composer install (no-dev, optimized)..."
composer install \
  --no-dev \
  --optimize-autoloader \
  --no-interaction \
  --prefer-dist \
  2>&1 | grep -E "^(Installing|Updating|Generating|Nothing)" || true
ok "Composer install complete"

# ── 4. Node / frontend assets ─────────────────────────────────
echo ""
echo "── Frontend assets ───────────────────────────────────────"
info "Installing npm dependencies..."
npm ci --prefer-offline --no-fund 2>&1 | tail -3
info "Building production assets..."
npm run build 2>&1 | grep -E "(built in|error)" || true
ok "Frontend build complete"

# ── 5. Database ───────────────────────────────────────────────
echo ""
echo "── Database ──────────────────────────────────────────────"
touch database/database.sqlite
ok "SQLite file ready"

if $FRESH_MIGRATE; then
  warn "--fresh flag set: wiping and re-migrating database"
  php artisan migrate:fresh --force --no-interaction
else
  php artisan migrate --force --no-interaction
fi
ok "Migrations up to date"

# ── 6. Storage ────────────────────────────────────────────────
echo ""
echo "── Storage ───────────────────────────────────────────────"
php artisan storage:link --force 2>/dev/null || true
ok "Storage symlink ready"

# ── 7. Laravel optimisation ───────────────────────────────────
echo ""
echo "── Caches ────────────────────────────────────────────────"
php artisan config:cache  --no-interaction && ok "Config cached"
php artisan route:cache   --no-interaction && ok "Routes cached"
php artisan view:cache    --no-interaction && ok "Views cached"
php artisan event:cache   --no-interaction && ok "Events cached"
php artisan icons:cache   --no-interaction 2>/dev/null && ok "Icons cached" || true

# ── 8. Data scraping ──────────────────────────────────────────
echo ""
echo "── Data scraping ─────────────────────────────────────────"
if $SKIP_SCRAPE; then
  warn "Scraping skipped (--skip-scrape)"
else
  info "Scraping station list from makedonijafm.net..."
  php artisan app:radio-web-scraper

  SCRAPED=$(php artisan tinker --execute 'echo \App\Models\RadioChannel::count();' 2>/dev/null | tail -1)
  ok "Scraped $SCRAPED stations"

  info "Downloading station logos..."
  php artisan app:radio-channel-table-data-transformation

  info "Publishing all stations that have a live stream..."
  php artisan tinker --no-interaction \
    --execute '\App\Models\RadioChannel::where("audio_url","!=",null)->update(["published"=>true]);' \
    2>/dev/null

  PUBLISHED=$(php artisan tinker --execute 'echo \App\Models\RadioChannel::where("published",true)->count();' 2>/dev/null | tail -1)
  ok "$PUBLISHED stations published"
fi

# ── 9. Filament admin user ────────────────────────────────────
echo ""
echo "── Admin panel ───────────────────────────────────────────"
ADMIN_COUNT=$(php artisan tinker --execute 'echo \App\Models\User::count();' 2>/dev/null | tail -1)
if [[ "$ADMIN_COUNT" == "0" ]]; then
  warn "No admin users exist. Run the following to create one:"
  echo ""
  echo "    php artisan make:filament-user"
  echo ""
else
  ok "$ADMIN_COUNT admin user(s) already exist — skip user creation"
fi

# ── Done ──────────────────────────────────────────────────────
echo ""
echo "══════════════════════════════════════════════════════════"
ok "Production setup complete"
echo ""
echo "  Admin panel:  \$APP_URL/admin"
echo "  Public site:  \$APP_URL/"
echo "══════════════════════════════════════════════════════════"
