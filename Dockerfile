# syntax=docker/dockerfile:1
# ══════════════════════════════════════════════════════════════════════════════
# Stage 1 — Node.js: build Vite frontend assets
#
# Uses BuildKit cache mount for node_modules so `npm ci` only re-downloads
# packages that actually changed between builds.
# ══════════════════════════════════════════════════════════════════════════════
FROM node:20-alpine AS node-builder

WORKDIR /app

# Copy lockfiles first — layer is only re-built when they change
COPY package.json package-lock.json ./

# Cache mount: ~/.npm is reused across builds (not baked into the image layer)
RUN --mount=type=cache,target=/root/.npm,sharing=locked \
    npm ci --prefer-offline

# Copy full source so Vite can resolve imports from resources/ and node_modules/
COPY resources/ resources/
COPY vite.config.js ./
COPY public/ public/

# Build all CSS + JS bundles → public/build/
RUN npm run build


# ══════════════════════════════════════════════════════════════════════════════
# Stage 2 — PHP 8.2-FPM: application container
# ══════════════════════════════════════════════════════════════════════════════
FROM php:8.2-fpm AS app

# ── System packages ───────────────────────────────────────────────────────────
# Cache mount for apt so packages are not re-downloaded on every build.
RUN --mount=type=cache,target=/var/cache/apt,sharing=locked \
    --mount=type=cache,target=/var/lib/apt/lists,sharing=locked \
    apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libwebp-dev \
        libzip-dev \
        libicu-dev \
        libxml2-dev \
        libxslt-dev \
        libonig-dev \
        default-mysql-client \
        poppler-utils \
    && rm -rf /tmp/*

# ── PHP extensions ────────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        xml \
        xsl \
        opcache \
    && docker-php-ext-enable opcache

# ── Composer ──────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── Working directory ─────────────────────────────────────────────────────────
WORKDIR /var/www/html

# ── PHP dependencies (cache mount keeps the Composer cache between builds) ────
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/root/.composer,sharing=locked \
    composer install \
        --no-dev \
        --optimize-autoloader \
        --no-scripts \
        --no-interaction \
        --prefer-dist

# ── Application source ────────────────────────────────────────────────────────
COPY . .

# ── Compiled frontend assets from Stage 1 ────────────────────────────────────
# Copy to public/build (used when no Docker volume is mounted over it)
COPY --from=node-builder /app/public/build ./public/build
# Also copy to a separate path that is NEVER overridden by a volume mount.
# The entrypoint uses this to refresh the shared public_build Docker volume on
# every container start, ensuring Nginx always serves the current build's assets.
COPY --from=node-builder /app/public/build ./public_build_src/

# ── PHP ini tweaks ────────────────────────────────────────────────────────────
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# ── Entrypoint ────────────────────────────────────────────────────────────────
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# ── Runtime permissions (adjusted again in entrypoint for mounted volumes) ────
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache \
    && mkdir -p /var/log/php && chown www-data:www-data /var/log/php

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
