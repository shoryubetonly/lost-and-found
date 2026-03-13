FROM php:8.2-apache

# อัปเดตและติดตั้งเครื่องมือสำหรับ PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# เปิดใช้งาน mod_rewrite สำหรับทำ URL สวยๆ (ถ้ามี Routing)
RUN a2enmod rewrite