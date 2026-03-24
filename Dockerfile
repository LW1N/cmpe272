FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev \
    && docker-php-ext-install curl pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite

# Enable .htaccess (clean URLs)
COPY apache-htaccess.conf /etc/apache2/conf-available/htaccess.conf
RUN a2enconf htaccess

COPY index.php demo.php about.php products.php product.php recent-products.php most-visited-products.php news.php users.php contacts.php login.php logout.php .htaccess /var/www/html/
COPY admin /var/www/html/admin
COPY api /var/www/html/api
COPY includes /var/www/html/includes
COPY css /var/www/html/css
COPY data /var/www/html/data
COPY images /var/www/html/images

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
