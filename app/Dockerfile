# Utiliser l'image PHP officielle avec Apache
FROM php:8.3-apache

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    curl \
    libpq-dev \
    libonig-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip bcmath

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copier le code source
COPY . /var/www/html

# Définir le dossier de travail
WORKDIR /var/www/html

# Installer les dépendances PHP via Composer
RUN composer install --optimize-autoloader --no-dev

# Donner les droits nécessaires pour Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Exposer le port Apache
EXPOSE 80

# Commande par défaut
CMD ["apache2-foreground"]
