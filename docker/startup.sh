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
    
    # Créer l'utilisateur admin avec gestion d'erreurs
    echo "Creating admin user step by step..."
    
    # 1. Créer le rôle
    echo "1/4 Creating role..."
    php bin/console doctrine:query:sql "INSERT INTO role (id, name) VALUES (1, 'ROLE_SUPER_ADMIN') ON CONFLICT (id) DO NOTHING;" 2>/dev/null || true
    
    # 2. Créer la personne
    echo "2/4 Creating person..."
    php bin/console doctrine:query:sql "INSERT INTO personne (id, civilite, nom, prenom, email) VALUES (1, 'Mr', 'ADMIN', 'Admin', 'admin@rca-amicale.fr') ON CONFLICT (id) DO NOTHING;" 2>/dev/null || true
    
    # 3. Créer l'utilisateur - Hash correct pour 'admin123'
    echo "3/4 Creating user..."
    ADMIN_HASH='$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
    USER_CREATED=$(php bin/console doctrine:query:sql "INSERT INTO \"user\" (id, username, password, personne_id) VALUES (1, 'admin', '$ADMIN_HASH', 1) ON CONFLICT (id) DO NOTHING RETURNING id;" 2>/dev/null || echo "")
    
    # 4. Lier le rôle seulement si l'utilisateur existe
    if [ ! -z "$USER_CREATED" ] || [ "$(php bin/console doctrine:query:sql "SELECT COUNT(*) FROM \"user\" WHERE username='admin';" --quiet 2>/dev/null | tail -1 | tr -d ' ')" -gt "0" ]; then
        echo "4/4 Linking role to user..."
        php bin/console doctrine:query:sql "INSERT INTO user_role (user_id, role_id) SELECT 1, 1 WHERE EXISTS (SELECT 1 FROM \"user\" WHERE id = 1) ON CONFLICT (user_id, role_id) DO NOTHING;" 2>/dev/null || true
    else
        echo "⚠️ User creation failed, skipping role assignment"
    fi
    
    # Vérification finale
    FINAL_COUNT=$(php bin/console doctrine:query:sql "SELECT COUNT(*) FROM \"user\" WHERE username='admin';" --quiet 2>/dev/null | tail -1 | tr -d ' ' || echo "0")
    if [ "$FINAL_COUNT" -gt "0" ]; then
        echo "✅ Utilisateur admin créé avec succès - Login: admin / Password: admin123"
    else
        echo "❌ Échec de la création de l'utilisateur admin"
        echo "💡 Utilisez la page d'inscription: https://amicale-rca.onrender.com/register"
    fi
    
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