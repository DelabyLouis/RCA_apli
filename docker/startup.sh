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

echo "════════════════════════════════════════" >&2
echo "🔧 Post-migration: Ensuring numero_ordre constraints are dropped" >&2
echo "════════════════════════════════════════" >&2

# Direct SQL fallback - drop any remaining numero_ordre constraints
echo "[startup.sh] Executing direct DROP for numero_ordre constraints..." >&2
php bin/console dbal:run-sql "ALTER TABLE \"transaction\" DROP CONSTRAINT IF EXISTS unique_numero_ordre_exercice;" 2>&1 | head -5 >&2 || echo "[startup.sh] First DROP attempt done"
php bin/console dbal:run-sql "ALTER TABLE \"transaction\" DROP CONSTRAINT IF EXISTS unique_numero_ordem_exercice;" 2>&1 | head -5 >&2 || echo "[startup.sh] Second DROP attempt done"

# Verify
echo "[startup.sh] Verifying constraint removal..." >&2
VERIFY=$(php bin/console dbal:run-sql "SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_name='transaction' AND constraint_name LIKE '%numero%'" 2>&1 | grep -oE "[0-9]+" | head -1)
echo "[startup.sh] Remaining numero_* constraints: $VERIFY" >&2

if [ "$VERIFY" == "0" ] || [ -z "$VERIFY" ]; then
    echo "✅ Constraints successfully removed!" >&2
else
    echo "⚠️  Some constraints may still exist - will proceed anyway" >&2
fi

echo "════════════════════════════════════════" >&2
echo "✅ Constraint drop step complete" >&2
echo "════════════════════════════════════════" >&2

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