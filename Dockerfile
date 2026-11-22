FROM php:8.4-cli

RUN docker-php-ext-install pdo_mysql

WORKDIR /var/www/html

COPY . /var/www/html

CMD ["sh", "-c", "php bin/setup.php && php -S 0.0.0.0:8000 -t public public/index.php"]
