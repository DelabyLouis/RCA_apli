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

# Forcer la recréation du schéma (pour debug)
echo "🔄 Recréation complète du schéma..."
php bin/console doctrine:schema:drop --force --full-database --no-interaction 2>/dev/null || true
php bin/console doctrine:schema:create --no-interaction || echo "Erreur création schéma"

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
    
    # 1. Vérifier que le rôle Administrateur existe (normalement créé par les fixtures)
    echo "1/4 Checking admin role..."
    php bin/console doctrine:query:sql "SELECT COUNT(*) FROM role WHERE libelle = 'Administrateur';" --quiet >/dev/null || echo "Admin role check completed"
    
    # 2. Créer la personne
    echo "2/4 Creating person..."
    php bin/console doctrine:query:sql "INSERT INTO personne (id_personne, civilite, nom, prenom, email) VALUES (1, 'Mr', 'ADMIN', 'Admin', 'admin@rca-amicale.fr') ON CONFLICT (id_personne) DO NOTHING;" 2>/dev/null || true
    
    # 3. Créer l'utilisateur - Hash correct pour 'admin123'
    echo "3/4 Creating user..."
    ADMIN_HASH='$2y$10$wJC0w3ZAXIovWafBY7zHf.fTIQpE5CazyfykR2Ho11QshqfezMux6'
    echo "Attempting to create user with hash: $ADMIN_HASH"
    php bin/console doctrine:query:sql "INSERT INTO \"user\" (id_user, username, password, id_personne) VALUES (1, 'admin', '$ADMIN_HASH', 1) ON CONFLICT (id_user) DO NOTHING;" || echo "User creation query failed"
    
    # 4. S'assurer que l'utilisateur admin a toujours ses rôles
    USER_EXISTS=$(php bin/console doctrine:query:sql "SELECT COUNT(*) FROM \"user\" WHERE username='admin';" --quiet 2>/dev/null | tail -1 | tr -d ' ' 2>/dev/null || echo "0")
    if [ "${USER_EXISTS:-0}" -gt "0" ]; then
        echo "4/4 Ensuring admin user has roles..."
        
        # Trouver l'ID réel de l'utilisateur admin
        ADMIN_USER_ID=$(php bin/console doctrine:query:sql "SELECT id_user FROM \"user\" WHERE username='admin' LIMIT 1;" --quiet 2>/dev/null | tail -1 | tr -d ' ' 2>/dev/null || echo "1")
        echo "Admin user ID: ${ADMIN_USER_ID}"
        
        # Vérifier combien de rôles l'admin a actuellement
        CURRENT_ROLES=$(php bin/console doctrine:query:sql "SELECT COUNT(*) FROM user_role WHERE user_id = ${ADMIN_USER_ID};" --quiet 2>/dev/null | tail -1 | tr -d ' ' 2>/dev/null || echo "0")
        echo "Admin currently has ${CURRENT_ROLES} role(s)"
        
        # Si l'admin n'a aucun rôle, utiliser la commande Symfony pour les restaurer
        if [ "${CURRENT_ROLES:-0}" -eq "0" ]; then
            echo "⚠️ Admin has no roles, fixing with Symfony command..."
            php bin/console app:fix-admin-roles && echo "✅ Admin roles restored" || echo "❌ Failed to restore admin roles"
            
            # Vérifier à nouveau
            FINAL_ROLES=$(php bin/console doctrine:query:sql "SELECT COUNT(*) FROM user_role WHERE user_id = ${ADMIN_USER_ID};" --quiet 2>/dev/null | tail -1 | tr -d ' ' 2>/dev/null || echo "0")
            echo "Admin now has ${FINAL_ROLES} role(s)"
        else
            echo "✅ Admin already has roles"
        fi
    else
        echo "⚠️ User creation failed, skipping role assignment"
    fi
    
    # Vérification finale avec détails
    FINAL_COUNT=$(php bin/console doctrine:query:sql "SELECT COUNT(*) FROM \"user\" WHERE username='admin';" --quiet 2>/dev/null | tail -1 | tr -d ' ' 2>/dev/null || echo "0")
    if [ "${FINAL_COUNT:-0}" -gt "0" ]; then
        echo "✅ Utilisateur admin créé avec succès - Login: admin / Password: admin123"
        # Afficher les rôles de l'utilisateur admin
        echo "📋 Rôles de l'utilisateur admin:"
        php bin/console doctrine:query:sql "SELECT r.libelle, r.hierarchy_level FROM \"user\" u JOIN user_role ur ON u.id_user = ur.user_id JOIN role r ON ur.role_id = r.id_role WHERE u.username = 'admin';" 2>/dev/null || echo "Impossible d'afficher les rôles"
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