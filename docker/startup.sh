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

# Créer un utilisateur admin si nécessaire
echo "📋 Vérification des utilisateurs..."
USER_COUNT=$(php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM \"user\"" --quiet 2>/dev/null | tail -1 | tr -d ' ' || echo "0")
echo "Nombre d'utilisateurs trouvés: $USER_COUNT"

if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo "� Création de l'utilisateur admin..."
    
    # Créer l'utilisateur admin via commande Symfony
    echo "Creating admin user..."
    php bin/console doctrine:query:sql "INSERT INTO role (id, name) VALUES (1, 'ROLE_SUPER_ADMIN') ON CONFLICT (id) DO NOTHING;" || true
    php bin/console doctrine:query:sql "INSERT INTO personne (id, civilite, nom, prenom, email) VALUES (1, 'Mr', 'ADMIN', 'Admin', 'admin@rca-amicale.fr') ON CONFLICT (id) DO NOTHING;" || true
    
    # Hash correct pour 'admin123'
    ADMIN_HASH='$2y$13$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
    php bin/console doctrine:query:sql "INSERT INTO \"user\" (id, username, password, personne_id) VALUES (1, 'admin', '$ADMIN_HASH', 1) ON CONFLICT (id) DO NOTHING;" || true
    php bin/console doctrine:query:sql "INSERT INTO user_role (user_id, role_id) VALUES (1, 1) ON CONFLICT (user_id, role_id) DO NOTHING;" || true
    
    echo "✅ Utilisateur admin créé - Login: admin / Password: admin123"
    
    # Essayer les fixtures si disponibles
    php bin/console doctrine:fixtures:load --no-interaction --env=prod 2>/dev/null || echo "Fixtures non disponibles en production"
else
    echo "👥 Utilisateurs déjà présents"
fi

# Corriger les permissions et vider le cache
chown -R www-data:www-data /var/www/html/var/cache
chmod -R 755 /var/www/html/var/cache
php bin/console cache:clear --no-warmup || true
php bin/console cache:warmup || true

echo "✅ Application prête ! Démarrage d'Apache..."

# Démarrer Apache
apache2-foreground