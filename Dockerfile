# Stage 0:
FROM --platform=$TARGETOS/$TARGETARCH node:22-alpine AS build
WORKDIR /app
COPY . ./
RUN npm install \
    && npm run build

# Stage 1:
FROM --platform=$TARGETOS/$TARGETARCH php:8.3-fpm-alpine
WORKDIR /app
COPY . ./
COPY --from=0 /app/public/build /app/public/build
RUN apk add --no-cache --update \
    ca-certificates dcron curl git supervisor tar unzip nginx \
    libpng-dev libxml2-dev libzip-dev icu-dev \
    oniguruma-dev libjpeg-turbo-dev freetype-dev \
    certbot certbot-nginx \
 && docker-php-ext-configure zip \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install bcmath gd intl pdo_mysql zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && cp .env.example .env \
    && mkdir -p bootstrap/cache/ storage/logs storage/framework/sessions storage/framework/views storage/framework/cache boostrap/cache \
    && chmod 777 -R bootstrap storage \
    && chmod 777 -R ./bootstrap/cache \
    && composer install \
    && rm -rf .env bootstrap/cache/*.php \
    && mkdir -p /app/storage/logs/ \
    && chown -R nginx:nginx .

RUN rm /usr/local/etc/php-fpm.conf \
    && echo "* * * * * /usr/local/bin/php /app/artisan schedule:run >> /dev/null 2>&1" >> /var/spool/cron/crontabs/root \
    && echo "0 23 * * * certbot renew --nginx --quiet" >> /var/spool/cron/crontabs/root \
    && sed -i s/ssl_session_cache/#ssl_session_cache/g /etc/nginx/nginx.conf \
    && mkdir -p /var/run/php /var/run/nginx

RUN touch /app/storage/installed
COPY .github/docker/default.conf /etc/nginx/http.d/default.conf
COPY .github/docker/www.conf /usr/local/etc/php-fpm.conf
COPY .github/docker/supervisord.conf /etc/supervisord.conf

EXPOSE 80 443
ENTRYPOINT [ "/bin/ash", ".github/docker/entrypoint.sh" ]
CMD [ "supervisord", "-n", "-c", "/etc/supervisord.conf" ]