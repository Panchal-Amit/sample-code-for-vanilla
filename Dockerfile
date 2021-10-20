FROM php:7.2-apache

RUN apt update
RUN apt -y install git zip gettext

COPY . /var/www/gatewayapi/
COPY .env.prod /var/www/gatewayapi/.env.prod

ENV APACHE_DOCUMENT_ROOT /var/www/gatewayapi/public

RUN chown -R www-data /var/www/gatewayapi
RUN chmod -R 755 /var/www/gatewayapi

RUN a2enmod rewrite

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN docker-php-ext-install pdo pdo_mysql
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('SHA384', 'composer-setup.php') === '93b54496392c062774670ac18b134c3b3a95e5a5e5c8f1a9f115f203b75bf9a129d5daa8ba6a13e2cc8a1da0806388a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"

RUN php composer.phar install -d /var/www/gatewayapi
RUN chmod +x /var/www/gatewayapi/entrypoint.sh
ENTRYPOINT [ "sh", "-c", "/var/www/gatewayapi/entrypoint.sh" ]
