# PHP 8.2 avec PHP-FPM pour exécuter Symfony
FROM php:8.2-fpm

# Répertoire de travail de Symfony dans le conteneur
WORKDIR /var/www/html

# Configuration PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

# Outils et bibliothèques nécessaires à Symfony et MongoDB
RUN apt-get update \
    && apt-get install -y \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
        libssl-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        intl \
        pdo \
        pdo_mysql \
        zip \
    && pecl install mongodb-1.21.2 \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

# Installation de Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copie des fichiers de dépendances
COPY backend/composer.json backend/composer.lock ./

# Installation des dépendances Symfony
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --no-scripts

# Copie du backend Symfony
COPY backend/ ./

# Démarrage de PHP-FPM
CMD ["php-fpm"]