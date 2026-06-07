FROM php:8.2-cli
RUN apt-get update && apt-get install -y libmysqlclient-dev
RUN docker-php-ext-install mysqli pdo pdo_mysql
COPY . /app/
WORKDIR /app
EXPOSE 80
CMD ["php", "-S", "0.0.0.0:80"]