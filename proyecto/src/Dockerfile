FROM php:8.2-apache

# Enable Apache modules
RUN a2enmod rewrite

# Install PHP extensions needed for MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install other useful extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Configure Apache DocumentRoot
RUN sed -i 's|/var/www/html|/var/www/html|g' /etc/apache2/sites-available/000-default.conf

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
