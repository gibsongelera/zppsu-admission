FROM php:8.2-apache

# Install extensions for PostgreSQL (Supabase) and common needs
RUN apt-get update && apt-get install -y \
    libpq-dev \
 && docker-php-ext-install pdo pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

# Set working directory to Apache doc root
WORKDIR /var/www/html

# Copy project files into container
COPY . /var/www/html

# Enable Apache rewrite
RUN a2enmod rewrite

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Render sets a PORT env var; make Apache listen on it
ENV APACHE_LISTEN_PORT=${PORT:-10000}
RUN sed -i "s/80/\${APACHE_LISTEN_PORT}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 10000

CMD ["apache2-foreground"]


