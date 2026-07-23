#!/bin/bash

# Activer la sortie d'erreur
set -e

# Configuration du port Apache pour Render
echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Configurer la base de données
echo "🔧 Configuration de la base de données..." >&2
php bin/console doctrine:database:create --if-not-exists || true

# Exécuter les migrations (remplace doctrine:schema:create)
echo "🔧 Exécution des migrations..." >&2
php bin/console doctrine:migrations:migrate --no-interaction || {
    echo "⚠️  Migrations failed, trying schema:create fallback..." >&2
    php bin/console doctrine:schema:create || {
        echo "Schema creation failed or already exists" >&2
    }
}

# CRITICAL: Drop unique constraint on numero_ordre to allow duplicates
echo "🔧 Suppression forcée de la contrainte unique sur numero_ordre..." >&2
php bin/console app:force-drop-constraint || echo "⚠️  Force drop failed" >&2

# Créer utilisateur admin si nécessaire
echo "🔧 Vérification de l'utilisateur admin..." >&2

# Vérifier si l'admin existe et a des rôles
ADMIN_ROLES=$(php bin/console dbal:run-sql "SELECT COUNT(*) FROM \"user\" u JOIN user_role ur ON u.id_user = ur.user_id WHERE u.username='admin';" 2>/dev/null | grep -E '^[0-9]+$' || echo "0")

if [ "${ADMIN_ROLES:-0}" -eq "0" ]; then
    echo "🔧 Configuration de l'utilisateur admin..." >&2
    
    # Créer la personne
    php bin/console dbal:run-sql "INSERT INTO personne (id_personne, civilite, nom, prenom, email) VALUES (1, 'Mr', 'ADMIN', 'Admin', 'admin@rca-amicale.fr') ON CONFLICT (id_personne) DO NOTHING;" >/dev/null 2>&1 || true
    
    # Créer l'utilisateur - Hash pour 'admin123'
    ADMIN_HASH='$2y$10$wJC0w3ZAXIovWafBY7zHf.fTIQpE5CazyfykR2Ho11QshqfezMux6'
    php bin/console dbal:run-sql "INSERT INTO \"user\" (id_user, username, password, id_personne) VALUES (1, 'admin', '$ADMIN_HASH', 1) ON CONFLICT (id_user) DO NOTHING;" >/dev/null 2>&1 || true
    
    # Créer le rôle Administrateur
    php bin/console dbal:run-sql "INSERT INTO role (libelle, hierarchy_level) VALUES ('Administrateur', 100) ON CONFLICT (libelle) DO NOTHING;" >/dev/null 2>&1 || true
    
    # Lier l'utilisateur au rôle
    php bin/console dbal:run-sql "INSERT INTO user_role (user_id, role_id) VALUES (1, (SELECT id_role FROM role WHERE libelle = 'Administrateur' LIMIT 1)) ON CONFLICT (user_id, role_id) DO NOTHING;" >/dev/null 2>&1 || true
    
    echo "✅ Utilisateur admin créé - Login: admin / Password: admin123" >&2
else
    echo "✅ Utilisateur admin déjà configuré" >&2
fi

# Import automatique désactivé - utiliser la page d'administration pour gérer les données
echo "✅ Import automatique désactivé - données existantes conservées" >&2

# Corriger les permissions et vider le cache
chown -R www-data:www-data /var/www/html/var/cache
chmod -R 755 /var/www/html/var/cache
php bin/console cache:clear --no-warmup || true
php bin/console cache:warmup || true

echo "✅ Application prête ! Démarrage d'Apache..."

# Démarrer Apache en foreground (pas besoin de apache2ctl start avant)
exec apache2-foreground