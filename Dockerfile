# Utiliser une image PHP officielle avec Composer
FROM php:8.2-cli

# Définir le répertoire de travail
WORKDIR /var/www/html

# Installer les extensions PHP requises
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier tous les fichiers du projet dans le conteneur
COPY . .

# Installer les dépendances
RUN composer install --optimize-autoloader --no-dev

# Donner les permissions nécessaires
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Générer la clé de l'application
RUN php artisan key:generate

# Exposer le port 8000 pour Laravel
EXPOSE 8000

# Commande pour lancer le serveur interne de Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
