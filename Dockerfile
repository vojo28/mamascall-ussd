# Use official PHP + Apache image
FROM php:8.2-apache

# Enable Apache rewrite
RUN a2enmod rewrite

# Copy all files to Apache web root
COPY . /var/www/html/

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port (Render will handle routing)
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
