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

EXPOSE 9000

CMD ["php-fpm"]
