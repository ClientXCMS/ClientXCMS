# ---------------------------------------------------------------------------
# Stage 1: Build frontend assets with Node
# ---------------------------------------------------------------------------
FROM node:22-alpine AS assets

# Optional custom theme (private GitHub repo)
ARG THEME_REPO=""
ARG THEME_NAME=""

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY resources/ resources/
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY public/ public/

# Cache-bust arg: forces re-clone when theme repo changes
ARG THEME_CACHE_BUST=""

# Clone custom theme before Vite build so its assets are compiled
RUN --mount=type=secret,id=github_token \
    if [ -n "$THEME_REPO" ] && [ -n "$THEME_NAME" ]; then \
        apk add --no-cache git; \
        TOKEN=$(cat /run/secrets/github_token 2>/dev/null || echo ""); \
        if [ -n "$TOKEN" ]; then \
            REPO_URL=$(echo "$THEME_REPO" | sed "s|https://|https://${TOKEN}@|"); \
        else \
            REPO_URL="$THEME_REPO"; \
        fi; \
        git clone "$REPO_URL" /tmp/theme; \
        cp -r /tmp/theme/resources/themes/${THEME_NAME} resources/themes/${THEME_NAME}; \
        rm -rf /tmp/theme; \
    fi

RUN npm run build

# ---------------------------------------------------------------------------
# Stage 2: Install PHP dependencies with Composer
# ---------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS vendor

RUN apk add --no-cache unzip git \
    && curl -sS https://getcomposer.org/installer | php -- \
        --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY composer.json composer.lock ./

# --no-scripts: skip post-autoload-dump (package:discover) which needs
# the full app context. We run it in the runtime stage instead.
RUN composer install --no-dev --no-scripts --no-interaction --no-progress \
    --ignore-platform-reqs

# ---------------------------------------------------------------------------
# Stage 3: Runtime image (PHP-FPM + nginx + supervisor)
# ---------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS runtime

# System packages (netcat for entrypoint DB/Redis wait)
RUN apk add --no-cache \
    nginx \
    supervisor \
    dcron \
    netcat-openbsd \
    libpng \
    libjpeg-turbo \
    freetype \
    icu-libs \
    libzip \
    oniguruma \
    libxml2 \
    linux-headers

# Build dependencies for PHP extensions (temporary)
RUN apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    libxml2-dev \
    autoconf \
    g++ \
    make \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        intl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# PHP configuration
RUN { \
    echo "upload_max_filesize = 64M"; \
    echo "post_max_size = 64M"; \
    echo "memory_limit = 256M"; \
    echo "max_execution_time = 120"; \
    echo "expose_php = Off"; \
} > /usr/local/etc/php/conf.d/clientxcms.ini

WORKDIR /app

# Copy application code
COPY --chown=www-data:www-data . .

# Copy built assets and custom theme from stage 1
COPY --from=assets --chown=www-data:www-data /app/public/build /app/public/build
COPY --from=assets --chown=www-data:www-data /app/resources/themes /app/resources/themes

# Copy vendor from stage 2
COPY --from=vendor --chown=www-data:www-data /app/vendor /app/vendor

# Copy Docker configuration files
COPY .github/docker/nginx.conf /etc/nginx/http.d/default.conf
COPY .github/docker/php-fpm.conf /usr/local/etc/php-fpm.conf
COPY .github/docker/supervisord.conf /etc/supervisord.conf
COPY .github/docker/entrypoint.sh /entrypoint.sh

# Prepare storage and cache directories
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chmod +x /entrypoint.sh

# Run Composer autoload dump and package discovery with full app context
RUN cp .env.example .env \
    && php artisan package:discover --ansi \
    && rm .env

# Prepare directories for nginx and supervisor
RUN mkdir -p \
        /var/run/nginx \
        /var/log/supervisord \
        /var/log/nginx \
    && chown www-data:www-data /var/log/nginx

# Remove files not needed at runtime
RUN rm -rf \
        node_modules \
        tests \
        .git \
        .github/docker/default_ssl.conf \
        docker-compose.example.yml

# Cron entry for Laravel scheduler
RUN echo "* * * * * /usr/local/bin/php /app/artisan schedule:run >> /dev/null 2>&1" \
    >> /var/spool/cron/crontabs/root

EXPOSE 80

ENTRYPOINT ["/bin/sh", "/entrypoint.sh"]
CMD ["supervisord", "-n", "-c", "/etc/supervisord.conf"]
