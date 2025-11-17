# Use official PHP + Apache image
FROM php:8.2-apache

# Install system dependencies needed for Composer + Google API client
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Enable Apache rewrite
RUN a2enmod rewrite

# Install PDO and MySQL extensions
RUN docker-php-ext-install pdo pdo_mysql

# Copy project files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Install PHP dependencies (google/apiclient)
RUN composer install --no-dev --prefer-dist

# Fix permissions
RUN chown -R www-data:www-data /var/www/html


# Copy entrypoint and make executable
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Expose port
EXPOSE 80

# Use entrypoint to fix permissions and start Apache
CMD ["/entrypoint.sh"]
