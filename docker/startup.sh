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
echo "=========================================" >&2
echo "🔧 DEMARRAGE DU SCRIPT D'INITIALISATION" >&2
echo "=========================================" >&2
echo " Vérification de l'utilisateur admin..." >&2

# Vérifier si l'utilisateur admin existe et combien de rôles il a
echo "🔍 Executing SQL queries to check admin status..." >&2
ADMIN_EXISTS=$(php bin/console dbal:run-sql "SELECT COUNT(*) FROM \"user\" WHERE username='admin';" 2>/dev/null | grep -E '^[0-9]+$' || echo "0")
ADMIN_ROLES_COUNT=$(php bin/console dbal:run-sql "SELECT COUNT(*) FROM \"user\" u JOIN user_role ur ON u.id_user = ur.user_id WHERE u.username='admin';" 2>/dev/null | grep -E '^[0-9]+$' || echo "0")

# Debug output
echo "🔍 Raw admin exists result: '$ADMIN_EXISTS'" >&2
echo "🔍 Raw admin roles count result: '$ADMIN_ROLES_COUNT'" >&2

# Ensure we have valid numbers
[ -z "$ADMIN_EXISTS" ] && ADMIN_EXISTS="0"
[ -z "$ADMIN_ROLES_COUNT" ] && ADMIN_ROLES_COUNT="0"

echo "📊 Admin existe: '$ADMIN_EXISTS', Nombre de rôles: '$ADMIN_ROLES_COUNT'" >&2

if [ "$ADMIN_EXISTS" = "0" ] || [ "$ADMIN_ROLES_COUNT" = "0" ]; then
    echo "🔧 Configuration de l'utilisateur admin..." >&2
    
    # Créer l'utilisateur admin avec gestion d'erreurs
    echo "Creating admin user step by step..." >&2
    
    # 1. Vérifier que le rôle Administrateur existe (normalement créé par les fixtures)
    echo "1/4 Checking admin role..." >&2
    php bin/console dbal:run-sql "SELECT COUNT(*) FROM role WHERE libelle = 'Administrateur';" >/dev/null 2>&1 || echo "Admin role check completed" >&2
    
    # 2. Créer la personne
    echo "2/4 Creating person..." >&2
    php bin/console dbal:run-sql "INSERT INTO personne (id_personne, civilite, nom, prenom, email) VALUES (1, 'Mr', 'ADMIN', 'Admin', 'admin@rca-amicale.fr') ON CONFLICT (id_personne) DO NOTHING;" >/dev/null 2>&1 || true
    
    # 3. Créer l'utilisateur - Hash correct pour 'admin123'
    echo "3/4 Creating user..." >&2
    ADMIN_HASH='$2y$10$wJC0w3ZAXIovWafBY7zHf.fTIQpE5CazyfykR2Ho11QshqfezMux6'
    echo "Attempting to create user with hash: $ADMIN_HASH" >&2
    php bin/console dbal:run-sql "INSERT INTO \"user\" (id_user, username, password, id_personne) VALUES (1, 'admin', '$ADMIN_HASH', 1) ON CONFLICT (id_user) DO NOTHING;" >/dev/null 2>&1 || echo "User creation query failed" >&2
    
    # 4. S'assurer que l'utilisateur admin a toujours ses rôles
    USER_EXISTS_NOW=$(php bin/console doctrine:query:sql "SELECT COUNT(*) FROM \"user\" WHERE username='admin';" --quiet 2>/dev/null | tail -1 | tr -d ' ' 2>/dev/null || echo "0")
    if [ "${USER_EXISTS_NOW:-0}" -gt "0" ]; then
        echo "4/4 Ensuring admin user has roles..." >&2
        
                # Trouver l'ID réel de l'utilisateur admin
        ADMIN_USER_ID=$(php bin/console dbal:run-sql "SELECT id_user FROM \"user\" WHERE username='admin' LIMIT 1;" 2>/dev/null | grep -E '^[0-9]+$' || echo "1")
        echo "Found admin user ID: ${ADMIN_USER_ID}" >&2
        
        # Vérifier combien de rôles il a déjà
        EXISTING_ROLES=$(php bin/console dbal:run-sql "SELECT COUNT(*) FROM user_role WHERE user_id = ${ADMIN_USER_ID};" 2>/dev/null | grep -E '^[0-9]+$' || echo "0")
        echo "Admin has ${EXISTING_ROLES} existing role(s)" >&2
        
        if [ "${EXISTING_ROLES:-0}" -eq "0" ]; then
            echo "🔧 Assigning admin role to user..." >&2
            
            # Créer le rôle Administrateur s'il n'existe pas
            php bin/console dbal:run-sql "INSERT INTO role (libelle, hierarchy_level) VALUES ('Administrateur', 100) ON CONFLICT (libelle) DO NOTHING;" >/dev/null 2>&1 || echo "Role creation attempted" >&2
            
            # Trouver l'ID du rôle Administrateur
            ADMIN_ROLE_ID=$(php bin/console dbal:run-sql "SELECT id_role FROM role WHERE libelle = 'Administrateur' LIMIT 1;" 2>/dev/null | grep -E '^[0-9]+$' || echo "0")
            echo "Found Administrateur role ID: ${ADMIN_ROLE_ID}" >&2
            
            if [ "${ADMIN_ROLE_ID:-0}" -gt "0" ]; then
                # Lier le rôle à l'utilisateur admin
                echo "Linking admin user to Administrateur role..." >&2
                php bin/console dbal:run-sql "INSERT INTO user_role (user_id, role_id) VALUES (${ADMIN_USER_ID}, ${ADMIN_ROLE_ID}) ON CONFLICT (user_id, role_id) DO NOTHING;" >/dev/null 2>&1 || echo "Role linking failed" >&2
                
                # Vérifier que ça a fonctionné
                FINAL_ROLES=$(php bin/console dbal:run-sql "SELECT COUNT(*) FROM user_role WHERE user_id = ${ADMIN_USER_ID};" 2>/dev/null | grep -E '^[0-9]+$' || echo "0")
                echo "✅ Admin now has ${FINAL_ROLES} role(s)" >&2
            else
                echo "❌ Could not find or create Administrateur role" >&2
            fi
    else
        echo "⚠️ Admin user verification failed, skipping role assignment" >&2
    fi
    
    # Vérification finale avec détails
    FINAL_COUNT=$(php bin/console dbal:run-sql "SELECT COUNT(*) FROM \"user\" WHERE username='admin';" 2>/dev/null | grep -E '^[0-9]+$' || echo "0")
    if [ "${FINAL_COUNT:-0}" -gt "0" ]; then
        echo "✅ Utilisateur admin créé avec succès - Login: admin / Password: admin123" >&2
        # Afficher les rôles de l'utilisateur admin
        echo "📋 Rôles de l'utilisateur admin:" >&2
        php bin/console dbal:run-sql "SELECT r.libelle, r.hierarchy_level FROM \"user\" u JOIN user_role ur ON u.id_user = ur.user_id JOIN role r ON ur.role_id = r.id_role WHERE u.username = 'admin';" 2>/dev/null || echo "Impossible d'afficher les rôles" >&2
    else
        echo "❌ Échec de la création de l'utilisateur admin" >&2
        echo "💡 Utilisez la page d'inscription: https://amicale-rca.onrender.com/register" >&2
    fi
    
    # Essayer les fixtures si disponibles
    php bin/console doctrine:fixtures:load --no-interaction --env=prod 2>/dev/null || echo "Fixtures non disponibles en production"
else
    echo "✅ Utilisateur admin déjà configuré avec des rôles" >&2
fi

echo "=========================================" >&2
echo "🔧 FIN DU SCRIPT D'INITIALISATION" >&2
echo "=========================================" >&2

# Corriger les permissions et vider le cache
chown -R www-data:www-data /var/www/html/var/cache
chmod -R 755 /var/www/html/var/cache
php bin/console cache:clear --no-warmup || true
php bin/console cache:warmup || true

echo "✅ Application prête ! Démarrage d'Apache..."

# Démarrer Apache
apache2-foreground