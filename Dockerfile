FROM php:8.2-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
ENV PORT 80
CMD sed -i "s/80/\/g" /etc/apache2/ports.conf && sed -i "s/:80/:\/g" /etc/apache2/sites-enabled/000-default.conf && apache2-foreground
