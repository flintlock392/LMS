# The Cozy Nook LMS on Render (Docker web service).
# PHP 8.3 + Apache; the web root is public/ (CodeIgniter 4).
FROM php:8.3-apache

# PHP extensions CodeIgniter and the app need: intl (dates/formatting), mysqli (MySQL).
RUN apt-get update \
 && apt-get install -y --no-install-recommends libicu-dev \
 && docker-php-ext-install intl mysqli \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

# Serve only public/, and let public/.htaccess route requests to index.php.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && echo "date.timezone=Asia/Manila" > "$PHP_INI_DIR/conf.d/timezone.ini" \
 && printf 'upload_max_filesize=5M\npost_max_size=8M\n' > "$PHP_INI_DIR/conf.d/uploads.ini" \
 && echo 'variables_order = "EGPCS"' > "$PHP_INI_DIR/conf.d/env.ini"
# ^ EGPCS: Render's environment variables reach CodeIgniter's config ($_ENV), e.g. database_default_hostname.

WORKDIR /var/www/html
COPY . .

# Startup script (strip Windows line endings in case Git added them).
RUN sed -i 's/\r$//' docker/start.sh && chmod +x docker/start.sh \
 && chown -R www-data:www-data writable public/uploads

CMD ["docker/start.sh"]
