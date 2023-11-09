FROM php:8.1-apache
RUN apt-get update && apt-get install -y git zip
RUN docker-php-ext-install pdo pdo_mysql
COPY ./public /var/www/html
COPY ./src ./app /var/www/
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer update && composer install
RUN a2enmod rewrite
RUN a2enmod actions
RUN service restart apache2