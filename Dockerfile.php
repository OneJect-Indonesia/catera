# ==============================================================================
# STAGE 1: Setup PHP Base & Extensions
# ==============================================================================
FROM php:8.4-fpm-alpine AS backend-base

# Install system dependencies
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    git \
    unzip \
    postgresql-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        zip \
        intl \
        bcmath \
        pdo \
        pdo_pgsql \
        opcache

# ==============================================================================
# STAGE 2: Install Composer Dependencies
# ==============================================================================
FROM backend-base AS composer-builder

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer config first
COPY composer.json composer.lock ./

# Install dependencies (without scripts and autoloader first for caching)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copy the rest of the application code
COPY . .

# Run composer autoload optimization
RUN composer install --optimize-autoloader

# ==============================================================================
# STAGE 3: Build Frontend Assets (Vite)
# ==============================================================================
FROM node:20-alpine AS asset-builder
WORKDIR /build

COPY package*.json ./
RUN npm ci

# Copy all source code + vendor from composer-builder stage
COPY --from=composer-builder /app /build

# Build the assets using Vite
RUN npm run build

# ==============================================================================
# STAGE 4: Final Production Image
# ==============================================================================
FROM backend-base

WORKDIR /var/www/html

# Copy application code from composer-builder
COPY --from=composer-builder /app /var/www/html

# Copy compiled assets from asset-builder
COPY --from=asset-builder /build/public/build /var/www/html/public/build

# Set production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Set directory permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
