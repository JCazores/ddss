#!/bin/bash
sed -i "s/80/\/g" /etc/apache2/ports.conf
sed -i "s/:80/:\/g" /etc/apache2/sites-enabled/000-default.conf
exec apache2-foreground
