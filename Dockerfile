FROM php:8.2-apache

# Install platform dependencies
RUN apt-get update && apt-get install -y \
    libmariadb-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set the working directory
WORKDIR /var/www/html

# Copy application source
COPY . .

# Create uploads directory and set permissions
RUN mkdir -p uploads && \
    chmod -R 777 uploads && \
    chown -R www-data:www-data /var/www/html

# Update Apache configuration to allow overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Expose port 80
EXPOSE 80
