FROM php:8.3-cli

# Install system dependencies + PHP extensions commonly needed by Laravel
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && docker-php-ext-install zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Install Composer (copy from official composer image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory inside container
WORKDIR /var/www

# Copy project files into container
COPY . .

# Install PHP deps inside image (good for first run)
# If you prefer faster rebuilds later, we can optimize this.
RUN composer install --no-interaction --prefer-dist

# Laravel usually needs these writable (in container)
RUN chmod -R 775 storage bootstrap/cache || true

# Expose the port that "php artisan serve" will listen on
EXPOSE 8000

# Default command: start the Laravel dev server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
