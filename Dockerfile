FROM php:8.2-apache

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

RUN docker-php-ext-install pdo pdo_mysql && docker-php-ext-enable pdo_mysql

COPY ./src /var/www/html/
