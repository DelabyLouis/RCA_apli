#!/bin/bash
set -e

echo "🚀 Démarrage de l'application Symfony..."

# Configuration du port Apache pour Render
sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf
sed -i "s/80/$PORT/g" /etc/apache2/ports.conf

echo "🗄️  Exécution des migrations Doctrine..."

# Attendre que la base soit prête (max 30 secondes)
timeout 30 bash -c 'until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do sleep 1; done' || true

# Créer la base si elle n'existe pas
php bin/console doctrine:database:create --if-not-exists --no-interaction || echo "Base déjà existante"

# Régénérer le schéma pour PostgreSQL (si pas de tables)
php bin/console doctrine:schema:update --force --no-interaction || echo "Schéma déjà à jour"

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction || echo "Aucune migration à exécuter"

# Charger les données de test (fixtures) - forcer le rechargement
echo "📋 Vérification et chargement des fixtures..."
USER_COUNT=$(php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM \"user\"" --quiet 2>/dev/null | tail -1 | tr -d ' ' || echo "0")
echo "Nombre d'utilisateurs trouvés: $USER_COUNT"

if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo "🔄 Chargement des fixtures (base vide)..."
    php bin/console doctrine:fixtures:load --no-interaction --env=prod
else
    echo "👥 Utilisateurs déjà présents, pas de rechargement"
fi

# Corriger les permissions et vider le cache
chown -R www-data:www-data /var/www/html/var/cache
chmod -R 755 /var/www/html/var/cache
php bin/console cache:clear --no-warmup || true
php bin/console cache:warmup || true

echo "✅ Application prête ! Démarrage d'Apache..."

# Démarrer Apache
apache2-foreground