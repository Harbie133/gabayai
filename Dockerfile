FROM php:8.1-apache

# Install mysqli extension
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Kopyahin ang files
COPY . /var/www/html/

# Buksan ang port 80
EXPOSE 80

# Ayusin ang port para sa Render
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Start Server
CMD ["apache2-foreground"]
