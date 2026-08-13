FROM php:8.4-fpm-alpine

RUN docker-php-ext-install pdo_mysql opcache pcntl

RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.enable_cli=0'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

RUN { \
        echo 'upload_max_filesize=100M'; \
        echo 'post_max_size=100M'; \
    } > /usr/local/etc/php/conf.d/uploads-limits.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . /var/www

RUN if [ ! -d vendor ]; then composer install --no-scripts --no-interaction; fi

RUN php -r '$f="/var/www/.env";$c=@file_get_contents($f);if($c===false||!preg_match("/^APP_KEY=.+/m",$c)){file_put_contents($f,$c.PHP_EOL."APP_KEY=base64:".base64_encode(random_bytes(32)).PHP_EOL);}'

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
