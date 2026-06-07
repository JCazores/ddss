FROM php:8.2-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
ENV APACHE_RUN_DIR /var/run/apache2
CMD ["apache2-foreground"]