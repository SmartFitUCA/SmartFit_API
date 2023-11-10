FROM php:8.1-apache
RUN apt-get update && apt-get install -y git zip
RUN docker-php-ext-install pdo pdo_mysql
COPY . /var/www/html/
WORKDIR /var/www/html/
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer update && composer install
RUN echo "file_uploads = On\nmemory_limit = 64M\nupload_max_filesize = 64M\npost_max_size = 64M\nmax_execution_time = 600\n" > /usr/local/etc/php/conf.d/uploads.ini
RUN a2enmod rewrite
RUN a2enmod actions
RUN service apache2 restart
RUN mkdir -p /home/hel/smartfit_hdd
RUN chmod -R 777 /home/hel/smartfit_hdd