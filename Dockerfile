# ==============================================================================
# STAGE 1 : BUILD THE VUE 3 FRONTEND
# ==============================================================================
FROM node:20-alpine AS frontend-builder
WORKDIR /app

# Install npm dependencies
COPY frontend/package*.json ./
RUN npm install

# Build static bundle for production
COPY frontend/ ./
RUN npm run build

# ==============================================================================
# STAGE 2 : PRODUCTION RUNTIME (PHP 8.3-FPM + NGINX + LARAVEL 13)
# ==============================================================================
FROM php:8.3-fpm-alpine

# Install system dependencies & build tools
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git \
    unzip \
    sqlite \
    sqlite-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    postgresql-dev \
    oniguruma-dev \
    libxml2-dev

# Configure and install all required PHP extensions (including mbstring, pcntl, exif)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_sqlite \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
        pcntl \
        exif

# Install official Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy backend files
COPY backend/ .

# Prepare minimal .env for composer
RUN cp -n .env.example .env 2>/dev/null || true

# Install PHP dependencies without running artisan scripts during container build
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy built frontend assets from stage 1 into Laravel public directory
COPY --from=frontend-builder /app/dist/ /var/www/html/public/

# Configure Nginx & Supervisord
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Setup startup entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && sed -i 's/\r$//' /usr/local/bin/entrypoint.sh

# Environment settings
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr
ENV PORT=10000

# Expose Render default port
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
