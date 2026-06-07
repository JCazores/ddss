FROM php:8.2-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql
COPY . /var/www/html/
COPY start.sh /start.sh
RUN chmod +x /start.sh
RUN chown -R www-data:www-data /var/www/html
EXPOSE 80
CMD ["/start.sh"]
