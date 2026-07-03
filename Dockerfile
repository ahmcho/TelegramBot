FROM php:8.3-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    curl \
    curl-dev \
    git \
    unzip \
    libzip-dev \
    oniguruma-dev

# Install PHP extensions
# Note: json is bundled into PHP core since 8.0 and openssl is compiled in
# by the official php-alpine image, so neither is a buildable ext module.
RUN docker-php-ext-install \
    curl \
    mbstring \
    zip \
    pdo

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock* ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy application files
COPY . .

# Create logs directory
RUN mkdir -p logs

# Set permissions
RUN chown -R www-data:www-data /app

USER www-data

CMD ["php", "-a"]
