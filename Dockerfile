FROM php:8.3-fpm

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js 20 & NPM
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# ১. আগে composer.json ও composer.lock কপি করে প্যাকেজ ইনস্টল
COPY src/composer.json src/composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --no-autoloader

# ২. package.json ও package-lock.json কপি করে Node প্যাকেজ ইনস্টল
COPY src/package*.json ./
RUN npm ci

# ৩. src ফোল্ডারের বাকি সব কোড ইমেজের ভেতরে কপি করা
COPY src/ .

# ৪. Autoloader ও Vite ফ্রন্টএন্ড বিল্ড
RUN composer dump-autoload --optimize \
    && npm run build

# ৫. Storage ও Cache পারমিশন ঠিক করা
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]