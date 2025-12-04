FROM php:8.2-apache

# Install extensions for both MySQL (local dev) and PostgreSQL (Supabase)
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pdo_mysql \
        mysqli \
        gd \
    && rm -rf /var/lib/apt/lists/*

# Set working directory to Apache doc root
WORKDIR /var/www/html

# Copy project files into container
COPY . /var/www/html

# Create uploads directory with proper permissions
RUN mkdir -p /var/www/html/uploads/qrcodes \
    && chmod -R 777 /var/www/html/uploads

# Enable Apache rewrite
RUN a2enmod rewrite

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Render sets a PORT env var; make Apache listen on it
ENV APACHE_LISTEN_PORT=${PORT:-10000}
RUN sed -i "s/80/\${APACHE_LISTEN_PORT}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Set PHP error reporting for debugging (remove in production)
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/errors.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/errors.ini

EXPOSE 10000

CMD ["apache2-foreground"]
