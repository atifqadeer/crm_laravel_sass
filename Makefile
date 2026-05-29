# ──────────────────────────────────────────────────────────────────────────────
# CRM Laravel — Docker shortcuts
# Requires: docker, docker compose (v2)
# ──────────────────────────────────────────────────────────────────────────────

.PHONY: help up start down build rebuild restart logs shell db-shell migrate seed fresh ps ip

COMPOSE   = docker compose
APP_PORT ?= 8000

# Detect host LAN IP (Linux/Mac)
HOST_IP  := $(shell ip route get 1.1.1.1 2>/dev/null | awk '{print $$7; exit}' || hostname -I 2>/dev/null | awk '{print $$1}' || echo "localhost")

help:
	@echo ""
	@echo "  CRM Laravel — Docker"
	@echo "  ──────────────────────────────────────────────────────────"
	@echo "  make up          First-time build + start (copies .env.docker → .env)"
	@echo "  make start       Start containers without rebuilding"
	@echo "  make down        Stop and remove containers"
	@echo "  make build       Build/rebuild the PHP+Node image"
	@echo "  make restart     Rebuild image and restart the app container"
	@echo "  make logs        Follow all container logs"
	@echo "  make shell       Bash shell inside the app container"
	@echo "  make db-shell    MariaDB client inside the db container"
	@echo "  make migrate     Run php artisan migrate"
	@echo "  make seed        Run php artisan db:seed"
	@echo "  make fresh       Wipe DB volume and re-import SQL dump  ⚠ DESTRUCTIVE"
	@echo "  make ps          Container status"
	@echo "  make ip          Show the URL to open on another device"
	@echo ""
	@echo "  Demo login:  admin@crm.local  /  Admin@1234!"
	@echo ""

## First-time setup
up:
	@[ -f .env ] || ( cp .env.docker .env && echo "✓ Created .env from .env.docker" )
	DOCKER_BUILDKIT=1 $(COMPOSE) up --build -d
	@echo ""
	@echo "  ✓ CRM is starting up."
	@echo "  ✓ Local access:   http://localhost:$(APP_PORT)"
	@echo "  ✓ Network access: http://$(HOST_IP):$(APP_PORT)"
	@echo "  ✓ Demo login:     admin@crm.local  /  Admin@1234!"
	@echo "  ✓ Watch logs:     make logs"

## Start without rebuilding
start:
	$(COMPOSE) up -d

## Stop
down:
	$(COMPOSE) down

## Rebuild image
build:
	DOCKER_BUILDKIT=1 $(COMPOSE) build app

## Rebuild + restart app only (DB + Nginx untouched)
restart: build
	$(COMPOSE) up -d --no-deps app

## Follow logs
logs:
	$(COMPOSE) logs -f

## Shell in app container
shell:
	$(COMPOSE) exec app bash

## MariaDB shell
db-shell:
	$(COMPOSE) exec db mariadb -u $${DB_USERNAME:-crm_user} -p$${DB_PASSWORD:-crm_password} $${DB_DATABASE:-crm_db_portal}

## Run migrations
migrate:
	$(COMPOSE) exec app php artisan migrate --force

## Run seeders
seed:
	$(COMPOSE) exec app php artisan db:seed --force

## Wipe DB and re-import SQL dump ── DANGEROUS
fresh:
	@echo "⚠  WARNING: This destroys ALL database data and re-imports the SQL dump."
	@read -p "   Type 'yes' to continue: " ans && [ "$$ans" = "yes" ] || exit 1
	$(COMPOSE) down -v
	$(COMPOSE) up -d
	@echo "✓ Database reset complete."

## Show container status
ps:
	$(COMPOSE) ps

## Show the URL for LAN access
ip:
	@echo ""
	@echo "  Open CRM on this machine:     http://localhost:$(APP_PORT)"
	@echo "  Open CRM from another device: http://$(HOST_IP):$(APP_PORT)"
	@echo ""
	@echo "  To configure APP_URL in .env:"
	@echo "    APP_URL=http://$(HOST_IP):$(APP_PORT)"
	@echo ""
