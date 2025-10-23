# PHP 8.2 + Apache
FROM php:8.2-apache

# Sistem güncellemesi ve SQLite için gerekli paket
RUN apt-get update && apt-get install -y libsqlite3-dev

# Gerekli PHP eklentileri
RUN docker-php-ext-install pdo pdo_sqlite

# Apache mod_rewrite etkinleştir
RUN a2enmod rewrite

# BONUS: Apache "ServerName" uyarısını düzelt
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# DÜZELTME 1: PHP hatalarını tek satırda, temiz bir şekilde aç
RUN echo "display_errors=On" > /usr/local/etc/php/conf.d/errors.ini && echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/errors.ini

# Kopyalama komutlarını (COPY) sildiğimizden emindik (çünkü volumes kullanıyoruz)

# İzinler için db klasörünü oluştur (bu kalsın)
RUN mkdir -p /var/www/db && \
    chown -R www-data:www-data /var/www/db && \
    chmod -R 775 /var/www/db

# Çalışma dizini
WORKDIR /var/www/html

# Apache dış portu
EXPOSE 80