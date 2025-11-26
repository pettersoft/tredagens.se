FROM php:8.3-apache

RUN a2enmod rewrite

RUN pecl install xdebug \ && docker-php-ext-enable xdebug

COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini