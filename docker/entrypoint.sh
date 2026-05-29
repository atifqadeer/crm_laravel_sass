#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# CRM Laravel — Docker entrypoint
#
# Intentionally NOT using "set -e" so expected non-zero exits (e.g. migrate
# when SQL dump already created tables) don't kill the process.
# ─────────────────────────────────────────────────────────────────────────────
set -uo pipefail

APP_ROOT=/var/www/html
BOOTSTRAP_CACHE="${APP_ROOT}/bootstrap/cache"
SEED_MARKER="${APP_ROOT}/storage/.docker_seeded"

log()  { echo "[entrypoint] $*"; }
fail() { echo "[entrypoint] FATAL: $*" >&2; exit 1; }

log "── CRM Laravel startup ──────────────────────────────────────────"

# ── 0. Clear any stale bootstrap cache ───────────────────────────────────────
# Must happen FIRST — a cached config with an empty APP_KEY causes every
# artisan command (including key:generate) to throw MissingAppKeyException.
log "Clearing bootstrap cache ..."
rm -f "${BOOTSTRAP_CACHE}/config.php" \
      "${BOOTSTRAP_CACHE}/routes-v7.php" \
      "${BOOTSTRAP_CACHE}/services.php" \
      "${BOOTSTRAP_CACHE}/events.php"

# ── 1. Ensure .env exists ─────────────────────────────────────────────────────
if [ ! -f "${APP_ROOT}/.env" ]; then
    log ".env not found — copying .env.example"
    cp "${APP_ROOT}/.env.example" "${APP_ROOT}/.env" \
        || fail "Cannot create .env"
fi

# Patch .env with Docker-injected env vars
patch_env() {
    local key="$1" val="$2"
    if grep -q "^${key}=" "${APP_ROOT}/.env" 2>/dev/null; then
        sed -i "s|^${key}=.*|${key}=${val}|" "${APP_ROOT}/.env"
    else
        echo "${key}=${val}" >> "${APP_ROOT}/.env"
    fi
}

patch_env "DB_HOST"         "${DB_HOST:-db}"
patch_env "DB_PORT"         "${DB_PORT:-3306}"
patch_env "DB_DATABASE"     "${DB_DATABASE:-crm_db_portal}"
patch_env "DB_USERNAME"     "${DB_USERNAME:-crm_user}"
patch_env "DB_PASSWORD"     "${DB_PASSWORD:-admin@123}"
patch_env "APP_URL"         "${APP_URL:-http://localhost:8000}"
patch_env "APP_ENV"         "${APP_ENV:-local}"
patch_env "APP_DEBUG"       "${APP_DEBUG:-true}"
patch_env "TRUSTED_PROXIES" "${TRUSTED_PROXIES:-*}"
patch_env "SKIP_IP_CHECK"   "${SKIP_IP_CHECK:-true}"

# ── 2. Generate APP_KEY if missing ────────────────────────────────────────────
# We use `php -r` directly (NOT `php artisan key:generate`) because artisan
# needs to bootstrap the app, which requires APP_KEY to already be set.
# Using plain PHP avoids the chicken-and-egg completely.
CURRENT_KEY=$(grep '^APP_KEY=' "${APP_ROOT}/.env" | cut -d= -f2- | tr -d '"' | tr -d "'" | xargs 2>/dev/null || true)

if [ -z "${CURRENT_KEY}" ]; then
    log "APP_KEY is missing — generating with php -r ..."
    NEW_KEY=$(php -r "echo 'base64:' . base64_encode(random_bytes(32));") \
        || fail "php -r failed to generate APP_KEY"
    patch_env "APP_KEY" "${NEW_KEY}"
    # ── CRITICAL: export into the current shell so every artisan subprocess
    # (config:cache, migrate, etc.) inherits the real key.
    # Laravel uses Dotenv::createImmutable() which never overrides an existing
    # env var — Docker passes APP_KEY="" into the container, so without this
    # export artisan sees an empty key even after we wrote it to .env.
    export APP_KEY="${NEW_KEY}"
    log "APP_KEY generated."
else
    log "APP_KEY already set."
    # Export even when the key was already present — same Dotenv immutability
    # issue applies if the Docker env var is still empty or stale.
    export APP_KEY="${CURRENT_KEY}"
fi

# ── 3. Wait for MariaDB ───────────────────────────────────────────────────────
DB_HOST_VAL="${DB_HOST:-db}"
DB_PORT_VAL="${DB_PORT:-3306}"
DB_USER_VAL="${DB_USERNAME:-crm_user}"
DB_PASS_VAL="${DB_PASSWORD:-admin@123}"
DB_NAME_VAL="${DB_DATABASE:-crm_db_portal}"

log "Waiting for MariaDB at ${DB_HOST_VAL}:${DB_PORT_VAL} ..."
MAX=90; COUNT=0
until mysqladmin ping \
        -h"${DB_HOST_VAL}" -P"${DB_PORT_VAL}" \
        -u"${DB_USER_VAL}" -p"${DB_PASS_VAL}" \
        --silent 2>/dev/null; do
    COUNT=$((COUNT + 1))
    [ "${COUNT}" -ge "${MAX}" ] && fail "Database not ready after ${MAX}s."
    [ $((COUNT % 10)) -eq 0 ] && log "  still waiting … (${COUNT}/${MAX}s)"
    sleep 1
done
log "MariaDB is ready."

cd "${APP_ROOT}"

# ── 4. Smart migrations ───────────────────────────────────────────────────────
# The SQL dump imports all tables but leaves the migrations table empty.
# Running `migrate` then fails on "Table users already exists" and never
# reaches new migrations (call_logs, etc.).
#
# Fix: if migrate fails AND tables already exist, populate the migrations
# table from the filesystem, then re-run migrate (only new ones execute).
log "Running migrations ..."
if ! php artisan migrate --force --ansi 2>&1; then
    USERS_EXISTS=$(mysql \
        -h"${DB_HOST_VAL}" -P"${DB_PORT_VAL}" \
        -u"${DB_USER_VAL}" -p"${DB_PASS_VAL}" "${DB_NAME_VAL}" \
        -se "SELECT COUNT(*) FROM information_schema.tables \
             WHERE table_schema='${DB_NAME_VAL}' AND table_name='users';" \
        2>/dev/null || echo "0")

    if [ "${USERS_EXISTS}" = "1" ]; then
        log "SQL dump detected — marking existing migrations as complete ..."
        VALUES=$(find "${APP_ROOT}/database/migrations" -name "*.php" | sort | \
            awk -F'/' '{n=$NF; sub(/\.php$/,"",n); printf "(\"%s\",0),",n}' | \
            sed 's/,$//')
        if [ -n "${VALUES}" ]; then
            mysql -h"${DB_HOST_VAL}" -P"${DB_PORT_VAL}" \
                  -u"${DB_USER_VAL}" -p"${DB_PASS_VAL}" "${DB_NAME_VAL}" \
                  -e "INSERT IGNORE INTO migrations (migration,batch) VALUES ${VALUES};" \
                  2>/dev/null \
                && log "Migrations table populated." \
                || log "WARN: Could not populate migrations table."
        fi
        log "Running new migrations ..."
        php artisan migrate --force --ansi 2>&1 \
            || log "WARN: Some new migrations failed (may need schema review)."
    else
        log "WARN: Migration failed and users table not found — DB may be empty."
    fi
fi
log "Migrations done."

# ── 5. Seed database on first start ───────────────────────────────────────────
# SEED_DATABASE: auto | true | force | false  (default: auto)
SEED_MODE="${SEED_DATABASE:-auto}"

should_seed() {
    case "${SEED_MODE}" in
        force)  return 0 ;;
        false)  return 1 ;;
        true)   [ -f "${SEED_MARKER}" ] && return 1 || return 0 ;;
        auto|*)
            COUNT_USERS=$(mysql \
                -h"${DB_HOST_VAL}" -P"${DB_PORT_VAL}" \
                -u"${DB_USER_VAL}" -p"${DB_PASS_VAL}" "${DB_NAME_VAL}" \
                -se "SELECT COUNT(*) FROM users;" 2>/dev/null || echo "1")
            [ "${COUNT_USERS}" = "0" ] && return 0 || return 1 ;;
    esac
}

if should_seed; then
    log "Seeding database (SEED_DATABASE=${SEED_MODE}) ..."
    if php artisan db:seed --force --ansi 2>&1; then
        touch "${SEED_MARKER}"
        log "Seeding complete. Login: admin@crm.local / Admin@1234!"
    else
        log "WARN: Seeding failed — check logs."
    fi
else
    log "Database already has data — skipping seed."
fi

# ── 6. Storage symlink ────────────────────────────────────────────────────────
log "Linking storage ..."
php artisan storage:link --force 2>/dev/null || true

# ── 6b. Sync Vite assets → shared Docker volume ───────────────────────────────
# The public/build directory is a named volume shared with the Nginx container.
# Docker volumes hide the image's content at mount time, so we keep a copy at
# public_build_src/ (never volume-mounted) and sync it here on every startup.
# This ensures Nginx always serves the assets that match the current image build.
if [ -d "${APP_ROOT}/public_build_src" ]; then
    log "Syncing Vite built assets to shared volume ..."
    cp -r "${APP_ROOT}/public_build_src/." "${APP_ROOT}/public/build/" \
      && log "Vite assets synced." \
      || log "WARN: Could not sync Vite assets — Nginx may serve stale or missing CSS/JS."
fi

# ── 7. Cache config / routes / views ─────────────────────────────────────────
# At this point APP_KEY is in .env, so config:cache will capture it correctly.
log "Caching config ..."
php artisan config:cache --ansi 2>&1 || log "WARN: config:cache failed."

log "Caching routes ..."
php artisan route:cache  --ansi 2>&1 || log "WARN: route:cache failed — check for duplicate route names."

log "Caching views ..."
php artisan view:cache   --ansi 2>&1 || log "WARN: view:cache failed."

# ── 8. Permissions ────────────────────────────────────────────────────────────
log "Setting permissions ..."
chown -R www-data:www-data "${APP_ROOT}/storage" "${APP_ROOT}/bootstrap/cache" 2>/dev/null || true
chmod -R 775               "${APP_ROOT}/storage" "${APP_ROOT}/bootstrap/cache" 2>/dev/null || true
mkdir -p /var/log/php && chown www-data:www-data /var/log/php 2>/dev/null || true

log "── Startup complete. Starting PHP-FPM ──────────────────────────"
exec "$@"
