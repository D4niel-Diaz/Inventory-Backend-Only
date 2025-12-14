# Use official PHP 8.2 FPM image
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-interaction --optimize-autoloader

# Expose Laravel's serve port
EXPOSE 8000

# CMD: Run migrations, seeders, clear cache, then start Laravel server
CMD sh -c "\
  echo 'Running migrations...' && \
  php artisan migrate --force || echo 'Migration failed, maybe DB not ready' && \
  echo 'Seeding database...' && \
  php artisan db:seed --force || echo 'Seeding skipped' && \
  php artisan config:clear && \
  php artisan cache:clear && \
  php artisan route:clear && \
  echo 'Starting Laravel server...' && \
  php artisan serve --host=0.0.0.0 --port=8000 \
"
