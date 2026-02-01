FROM php:8.3-apache

# Install minimal PHP extensions for PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Apache config: point DocumentRoot to backend/public
RUN a2enmod rewrite headers && \
  sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/backend/public|g' /etc/apache2/sites-available/000-default.conf && \
  printf '%s\n' \
  '<Directory /var/www/html/backend/public>' \
  '  Options -Indexes +FollowSymLinks' \
  '  AllowOverride All' \
  '  Require all granted' \
  '</Directory>' \
  >> /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copy entire project (frontend/assets/backend/database)
COPY . /var/www/html

# Security/perf defaults (can be overridden by environment)
# Link assets folder to backend/public so they are accessible
RUN ln -s /var/www/html/assets /var/www/html/backend/public/assets

