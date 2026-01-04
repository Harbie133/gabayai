FROM php:8.1-apache

# Install mysqli
RUN docker-php-ext-install mysqli

# Copy files
COPY . /var/www/html/

# Enable Apache rewrite module (if you have .htaccess)
RUN a2enmod rewrite

# Expose port
EXPOSE 80

# Start Apache (Render will handle PORT automatically)
CMD ["apache2-foreground"]
