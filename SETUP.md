# CRM Portal — Setup Guide

A Laravel 11 recruitment CRM with a built-in **xplosip** softphone integration
(click-to-dial, caller-ID lookup, call logging, and dial-collision locks).

This guide covers two ways to run the project:

- **[A. Docker (recommended)](#a-docker-setup-recommended)** — one command, everything self-contained.
- **[B. Manual / local PHP](#b-manual-local-setup)** — for development against a local PHP + MySQL.

---

## Requirements

| Tool | Version | Notes |
|------|---------|-------|
| Docker + Compose | latest | For the Docker path only |
| PHP | 8.2+ | `pdo_mysql, mbstring, gd, zip, intl, bcmath, exif, xml, xsl, opcache` |
| Composer | 2.x | |
| Node.js | 20.x | with npm (for Vite asset build) |
| MySQL / MariaDB | MySQL 8 / MariaDB 11 | `REGEXP_REPLACE` is required (phone search) |

---

## A. Docker setup (recommended)

The stack runs four containers: `db` (MariaDB 11), `app` (PHP 8.2-FPM),
`nginx` (web server), and `phpmyadmin`.

```bash
# 1. Clone
git clone https://github.com/mubashiribstec/CRM.git
cd CRM

# 2. Create the environment file from the Docker template
cp .env.docker .env

# 3. Build and start everything (first run also imports the SQL dump)
docker compose up --build -d

# 4. Watch the app container come up (migrations + caching run automatically)
docker compose logs -f app
```

When the log shows `Startup complete. Starting PHP-FPM`, open:

| Service     | URL                         |
|-------------|-----------------------------|
| CRM app     | `http://<server-ip>:8000`   |
| phpMyAdmin  | `http://<server-ip>:8080`   |
| MariaDB     | host port `3307` → 3306     |

**First-time login**

```
Email:    admin@crm.local
Password: Admin@1234!
```

> Change this password immediately after the first login.

### What the container does on startup

The `app` entrypoint (`docker/entrypoint.sh`) handles first-run setup for you:

1. Creates `.env` from `.env.example` if missing, and patches DB/APP values from compose env.
2. Generates `APP_KEY` if it is empty.
3. Waits for MariaDB to be healthy.
4. Runs `php artisan migrate --force` (and reconciles the migrations table if the
   SQL dump pre-created the tables).
5. Seeds the database **only on first run** (when `users` is empty), controlled by `SEED_DATABASE`.
6. Links storage, syncs Vite assets, and caches config / routes / views.

So **no manual `artisan` commands are needed** for a Docker deploy — restart the
container and migrations re-run idempotently.

### Useful Docker commands

```bash
docker compose ps                       # container status
docker compose logs -f app              # tail app logs
docker exec -it crm_app bash            # shell into the app container
docker exec crm_app php artisan migrate:status
docker compose down                     # stop (keeps the db_data volume)
docker compose up --build -d            # rebuild after code changes
```

> After changing PHP source in a running container, run `docker restart crm_app`
> to flush OPcache (or rebuild the image so the change is baked in permanently).

---

## B. Manual / local setup

```bash
git clone https://github.com/mubashiribstec/CRM.git
cd CRM

composer install
npm install

cp .env.example .env
php artisan key:generate

# Edit .env: set DB_DATABASE / DB_USERNAME / DB_PASSWORD and APP_URL
php artisan migrate --seed

npm run build          # or: npm run dev   (hot reload during development)
php artisan serve      # http://127.0.0.1:8000
```

---

## Environment configuration

Key variables (see `.env.example` for the full list):

```ini
APP_NAME=CRM
APP_ENV=production            # local | production
APP_DEBUG=false               # MUST be false in production
APP_URL=https://your-domain   # must match how users reach the app

DB_CONNECTION=mysql
DB_HOST=127.0.0.1             # "db" inside Docker
DB_PORT=3306
DB_DATABASE=crm_db_portal
DB_USERNAME=crm_user
DB_PASSWORD=change-me

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=smtp             # "log" writes mail to the log instead of sending
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@your-domain
```

### Integration / security variables

These have sensible defaults in `config/services.php` but should be set explicitly:

```ini
# Shared secret for the desktop MicroSIP integration endpoints.
# Point MicroSIP's crmApiUrl at:  https://your-crm/api/sip/<MICROSIP_API_TOKEN>
MICROSIP_API_TOKEN=generate-a-long-random-string

# Click-to-dial collision lock duration (minutes). A number stays locked to the
# dialing agent until this expires or the call is logged.
DIAL_LOCK_MINUTES=5

# IP allow-list enforcement. Leave true during initial setup so you can log in,
# then set false once your office IPs are added in the admin panel.
SKIP_IP_CHECK=true

# Trusted reverse-proxy CIDRs (restrict in production, e.g. "172.16.0.0/12").
TRUSTED_PROXIES=*
```

> **Never commit real secrets.** `.env`, `.env.docker`, `.env.production`, and
> `.env.*.local` are git-ignored. Keep `MICROSIP_API_TOKEN` and DB passwords out
> of the repository.

---

## xplosip softphone integration

The CRM exposes API endpoints the desktop MicroSIP client calls:

| Method | Endpoint | Purpose |
|--------|----------|---------|
| `GET`  | `/api/sip/{token}/lookup?number=...` | Caller-ID lookup by phone number |
| `POST` | `/api/sip/{token}/calls`             | Store a completed call record |

`{token}` is the value of `MICROSIP_API_TOKEN`. Browser-side (Sanctum-authenticated)
equivalents also exist at `/api/contacts/lookup` and `/api/calls/log`.

Phone matching uses indexed, digits-only `*_normalized` columns on the
`applicants` table (last 10 digits), kept in sync automatically when an applicant
is saved. This is added by the migration
`2026_05_29_120000_add_normalized_phone_columns_to_applicants_table`, which also
backfills existing rows. If that migration has not run yet, lookups transparently
fall back to a `REGEXP_REPLACE` scan, so the feature works either way.

---

## Production notes

- Set `APP_ENV=production`, `APP_DEBUG=false`, and a real `APP_URL`.
- Serve over HTTPS and set `SESSION_SECURE_COOKIE=true`.
- After deploying code, cache for performance and restart to flush OPcache:
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  # Docker: docker restart crm_app
  ```
- Run migrations during a low-traffic window if the `applicants` table is large
  (the phone backfill updates every row once).

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `MissingAppKeyException` | `php artisan key:generate` (Docker does this automatically) |
| Login blocked by IP check | Set `SKIP_IP_CHECK=true`, log in, add your IP, then set it back |
| Stale CSS/JS | `npm run build`; in Docker the entrypoint re-syncs Vite assets on restart |
| Code change not taking effect | `php artisan optimize:clear`; in Docker `docker restart crm_app` (OPcache) |
| `REGEXP_REPLACE` errors | Use MySQL 8+ or MariaDB 10.0.5+ |
