FROM php:8-apache
RUN a2enmod rewrite
RUN mkdir /store
RUN chown -R www-data:www-data /store
COPY html /var/www/html
WORKDIR /var/www/html
VOLUME /store
