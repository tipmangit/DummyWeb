FROM php:8.2-apache

# Install SQLite extensions for our database
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Enable Apache Mod_Rewrite
RUN a2enmod rewrite

# Copy project files into the container
COPY . /var/www/html/

# Create uploads directory and set appropriate permissions
RUN mkdir -p /var/www/html/uploads /var/www/html/database \
    && chmod -R 777 /var/www/html/uploads /var/www/html/database

EXPOSE 80
