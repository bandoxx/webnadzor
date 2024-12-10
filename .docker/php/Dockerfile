FROM php:8.3-fpm

# Install required packages and dependencies
RUN set -eux; \
    apt-get update; \
    apt-get upgrade -y; \
    apt-get install -y --no-install-recommends \
        nano \
        curl \
        libmemcached-dev \
        libz-dev \
        libpq-dev \
        libjpeg-dev \
        libpng-dev \
        unzip \
        libicu-dev \
        libzip-dev \
        libfreetype6-dev \
        libssl-dev \
        libwebp-dev \
        libxpm-dev \
        libmcrypt-dev \
        libonig-dev \
        netcat-traditional; \
    rm -rf /var/lib/apt/lists/*

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer

# Install PHP extensions
RUN set -eux; \
    docker-php-ext-install sysvmsg sysvsem sysvshm; \
    docker-php-ext-install zip pdo pdo_mysql; \
    docker-php-ext-configure gd \
        --prefix=/usr \
        --with-jpeg \
        --with-webp \
        --with-xpm \
        --with-freetype; \
    docker-php-ext-install gd; \
    docker-php-ext-configure intl; \
    docker-php-ext-install intl;

# Install Node.js and npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest

RUN npm install -g highcharts-export-server

# Copy custom PHP configuration
COPY .docker/php/config/php.ini /usr/local/etc/php/php.ini

# Set the working directory
WORKDIR /var/www/html

# Copy application files (adjust as needed)
COPY . .

# Run npm install in the application directory
RUN set -eux; \
    if [ -f package.json ]; then npm install; fi

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]
