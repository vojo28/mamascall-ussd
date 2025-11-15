# Use official PHP image with CLI and built-in server
FROM php:8.2-cli

# Set working directory
WORKDIR /app

# Copy project files
COPY . /app

# Ensure logs directory exists (optional)
RUN touch ussd_debug.log && chmod 666 ussd_debug.log

# Expose the port that Render will set via $PORT
EXPOSE 8080

# Start PHP built-in server using the PORT Render provides
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} index.php"]
