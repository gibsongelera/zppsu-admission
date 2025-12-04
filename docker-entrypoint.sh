#!/bin/bash
set -e

# Get port from environment variable or default to 10000
PORT=${PORT:-10000}

# Update Apache configuration to listen on the specified port
sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf
sed -i "s/:80/:$PORT/g" /etc/apache2/sites-available/000-default.conf

# Start Apache
exec apache2-foreground

