FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libicu-dev \
    libzip-dev \
    && docker-php-ext-install \
    pdo_pgsql \
    intl \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN composer install

EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
