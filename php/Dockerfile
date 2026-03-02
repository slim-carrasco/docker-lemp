ARG VERSION=8.3
FROM php:${VERSION}-fpm
RUN apt-get update && \
apt-get install -y iputils-ping && \
rm -rf /var/lib/apt/lists/* && \
docker-php-ext-install mysqli pdo_mysql

