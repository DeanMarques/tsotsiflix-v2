## Base image
#FROM ubuntu:jammy-20240111
FROM ubuntu:jammy-20240530

# Set non-interactive mode for apt
ENV DEBIAN_FRONTEND noninteractive
ENV PHP_VERSION 8.3

# Change the UID and GID of www-data to 1000
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

ENV PHPGROUP=www-data
ENV PHPUSER=www-data

# Update and install dependencies
RUN apt-get update && apt-get install -y \
    software-properties-common \
    curl \
    autoconf \
    automake \
    libtool

# Add Nginx repository
RUN add-apt-repository -y ppa:ondrej/php

# RUN apt-get install nginx nginx-extras -y
# RUN apt-get install cron -y

## Install common packages first
RUN rm -rf /var/lib/apt/lists/* \
 && apt-get update \
 && apt-get install -y \
    nginx \
    nginx-extras \
    certbot \
    python3-certbot-nginx \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-mysql \
 && rm -rf /var/lib/apt/lists/*

# Install PHP extensions separately
RUN apt-get update && apt-get install -y \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-gmp \
    php${PHP_VERSION}-igbinary \
    php${PHP_VERSION}-imagick \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-memcached \
    php${PHP_VERSION}-msgpack \
    php${PHP_VERSION}-pdo-pgsql \
    php${PHP_VERSION}-soap \
    php${PHP_VERSION}-swoole \
    php${PHP_VERSION}-sqlite3 \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-dev \
    php-pear \
    php${PHP_VERSION}-dom \
    && rm -rf /var/lib/apt/lists/*


    

# Composer install
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Node and npm installation
RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
     && apt-get install -y nodejs


# Setup PHP-FPM
RUN mkdir -p /run/php/
RUN chown -R ${PHPUSER}:${PHPGROUP} /run/php/

# Copy Nginx configuration to sites-available
COPY docker/php/nginx.conf /etc/nginx/sites-available/nginx.conf

# Create symlink to enable the site
RUN ln -s /etc/nginx/sites-available/nginx.conf /etc/nginx/sites-enabled/

# Remove default Nginx configuration
RUN rm /etc/nginx/sites-enabled/default

# Set permissions for PHP log file
RUN touch /var/log/php${PHP_VERSION}-fpm.log \
    && chown ${PHPUSER}:${PHPGROUP} /var/log/php${PHP_VERSION}-fpm.log \
    && chmod 644 /var/log/php${PHP_VERSION}-fpm.log

# Set permissions for Nginx log directory
RUN mkdir -p /var/log/nginx/ \
    && chown -R ${PHPUSER}:${PHPGROUP} /var/log/nginx/
# Set permissions for Nginx body directory
RUN mkdir -p /var/lib/nginx/body \
    && chown -R ${PHPUSER}:${PHPGROUP} /var/lib/nginx/

RUN echo "* * * * * root php /var/www/tsotsiflix/artisan queue:work --stop-when-empty >> /var/log/cron.log 2>&1" >> /etc/crontab


# CMD bash -c "sleep 15 && php artisan migrate && php artisan storage:link && service cron start && service php${PHP_VERSION}-fpm start && nginx -g 'daemon off;'"
CMD bash -c "service php${PHP_VERSION}-fpm start && nginx -g 'daemon off;'"