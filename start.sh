#!/bin/bash
echo "Listen ${PORT:-80}" > /etc/apache2/ports.conf
echo "<VirtualHost *:${PORT:-80}>" > /etc/apache2/sites-enabled/000-default.conf
echo "DocumentRoot /var/www/html" >> /etc/apache2/sites-enabled/000-default.conf
echo "</VirtualHost>" >> /etc/apache2/sites-enabled/000-default.conf
exec apache2-foreground