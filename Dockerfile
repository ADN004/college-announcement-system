# ─────────────────────────────────────────────────────────────────────────────
#  College Announcement System — Dockerfile
#  Base: php:8.2-apache
# ─────────────────────────────────────────────────────────────────────────────

FROM php:8.2-apache

# Install PHP extensions required by the app (mysqli + pdo_mysql)
RUN docker-php-ext-install mysqli pdo_mysql && docker-php-ext-enable mysqli

# Enable Apache mod_rewrite (useful for future URL rewriting)
RUN a2enmod rewrite

# Copy application source into the web root
COPY . /var/www/html/

# Remove any accidentally copied .env file (credentials must come from env vars)
RUN rm -f /var/www/html/.env

# Ensure uploads/ and logs/ directories exist with correct permissions
RUN mkdir -p /var/www/html/uploads/pending \
             /var/www/html/uploads/approved \
             /var/www/html/uploads/rejected \
             /var/www/html/logs \
 && chown -R www-data:www-data /var/www/html/uploads \
                               /var/www/html/logs \
 && chmod -R 775 /var/www/html/uploads \
                 /var/www/html/logs

# Copy and set up the entrypoint script that generates db.php at runtime
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
