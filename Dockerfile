# Dockerfile optimisé pour Symfony 7.3 sur Render
FROM php:8.3-apache

# Variables d'environnement
ENV APP_ENV=prod
ENV COMPOSER_ALLOW_SUPERUSER=1

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration Apache
RUN a2enmod rewrite headers
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Copie du code source
WORKDIR /var/www/html
COPY . /var/www/html

# Installation des dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-scripts
RUN composer run-script --no-dev post-install-cmd

# Génération des assets (si nécessaire)
RUN php bin/console asset-map:compile || true

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/var

# Port d'exposition
EXPOSE 80

# Configuration pour Render (utilise le port dynamique)
CMD sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf && \
    sed -i "s/80/$PORT/g" /etc/apache2/ports.conf && \
    apache2-foreground