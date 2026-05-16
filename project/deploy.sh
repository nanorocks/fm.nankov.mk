#!/usr/bin/env bash
# ============================================================
# Production deploy script — fm.nankov.mk
# Run this from the Laravel root on the server (project/)
# after files have been uploaded via FTP.
#
# Usage: bash deploy.sh
# ============================================================

set -e

# ── Colours ─────────────────────────────────────────────────
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

step()  { echo -e "\n${YELLOW}▶ $1${NC}"; }
ok()    { echo -e "${GREEN}  ✓ $1${NC}"; }
fail()  { echo -e "${RED}  ✗ $1${NC}"; exit 1; }

# ── PHP path detection ───────────────────────────────────────
# Prefer the shell-resolved php (honours cPanel's per-account PHP version),
# then fall back to known EasyApache paths.
for candidate in \
    "$(which php 2>/dev/null)" \
    "/opt/cpanel/ea-php84/root/usr/bin/php" \
    "/opt/cpanel/ea-php83/root/usr/bin/php" \
    "/usr/local/bin/php"; do
    if [ -x "$candidate" ]; then
        PHP="$candidate"
        break
    fi
done

[ -z "$PHP" ] && fail "PHP not found. Set PHP= manually at the top of this script."

# ── Composer path detection ──────────────────────────────────
for candidate in \
    "$HOME/bin/composer" \
    "/usr/local/bin/composer" \
    "$(which composer 2>/dev/null)"; do
    if [ -x "$candidate" ]; then
        COMPOSER="$candidate"
        break
    fi
done

[ -z "$COMPOSER" ] && fail "Composer not found. Install it or set COMPOSER= manually."

# ── Sanity check ─────────────────────────────────────────────
[ ! -f "artisan" ] && fail "artisan not found. Run this script from the Laravel root (project/)."

echo ""
echo "============================================================"
echo "  🚀  fm.nankov.mk — Production Deploy"
echo "============================================================"
echo "  PHP:      $PHP ($($PHP -r 'echo PHP_VERSION;'))"
echo "  Composer: $COMPOSER"
echo "  Dir:      $(pwd)"
echo "============================================================"

# ── 1. Maintenance mode on ───────────────────────────────────
step "Enabling maintenance mode"
$PHP artisan down --retry=15 --secret="deploy-secret" 2>/dev/null || true
ok "Maintenance mode active"

# ── 2. Composer dependencies ─────────────────────────────────
step "Installing Composer dependencies (production)"
$COMPOSER install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --quiet
ok "Dependencies installed"

# ── 3. Migrations ────────────────────────────────────────────
step "Running database migrations"
$PHP artisan migrate --force
ok "Migrations done"

# ── 4. Storage symlink ───────────────────────────────────────
step "Ensuring storage symlink"
$PHP artisan storage:link --force 2>/dev/null || true
ok "Storage linked"

# ── 5. Filament upgrade ──────────────────────────────────────
step "Upgrading Filament assets"
$PHP artisan filament:upgrade
ok "Filament upgraded"

# ── 6. Optimise ──────────────────────────────────────────────
step "Caching config / routes / views"
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
$PHP artisan event:cache
ok "Application optimised"

# ── 7. Queue restart ─────────────────────────────────────────
step "Restarting queue workers"
$PHP artisan queue:restart
ok "Queue workers signalled to restart"

# ── 8. Permissions ───────────────────────────────────────────
step "Setting directory permissions"
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
ok "Permissions set"

# ── 9. Maintenance mode off ──────────────────────────────────
step "Bringing application back online"
$PHP artisan up
ok "Application is live ✅"

echo ""
echo "============================================================"
echo "  ✅  Deploy complete!"
echo ""
echo "  Next steps (if first deploy):"
echo "  1. Ensure .env is configured on the server"
echo "  2. Add cron in cPanel (Cron Jobs):"
echo "     * * * * * $PHP $(pwd)/artisan schedule:run >> /dev/null 2>&1"
echo "============================================================"
echo ""
