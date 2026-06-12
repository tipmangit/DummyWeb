FROM php:8.2-apache

# Install PostgreSQL client dev packages and PHP drivers
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache Mod_Rewrite
RUN a2enmod rewrite

# Copy project files into the container
COPY . /var/www/html/

# Create uploads directory and set appropriate permissions
RUN mkdir -p /var/www/html/uploads \
    && chmod -R 777 /var/www/html/uploads

EXPOSE 80
