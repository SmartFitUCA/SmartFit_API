FROM php:8.1-apache
RUN apt-get update && apt-get install -y git zip
RUN docker-php-ext-install pdo pdo_mysql
COPY . /var/www/html/
WORKDIR /var/www/html/
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer update && composer install
RUN sed -i '/upload_max_filesize/s/.*/upload_max_filesize\ = 64M/g' /etc/php/php.ini
RUN sed -i '/post_max_size/s/.*/post_max_size \= 64M/g' /etc/php/php.ini
RUN a2enmod rewrite
RUN a2enmod actions
RUN service apache2 restart
RUN mkdir -p /home/hel/smartfit_hdd
RUN chmod -R 755 /home/hel/smartfit_hdd