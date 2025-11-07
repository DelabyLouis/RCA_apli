#!/bin/bash

# Script d'optimisation pour la mise en production
# À exécuter avant le déploiement

echo "🚀 Optimisation de l'application pour la production..."

# 1. Nettoyer le cache
echo "📁 Nettoyage du cache..."
php bin/console cache:clear --env=prod --no-debug

# 2. Précompiler les templates Twig
echo "🎨 Précompilation des templates Twig..."
php bin/console cache:warmup --env=prod --no-debug

# 3. Optimiser l'autoloader Composer
echo "🔧 Optimisation de l'autoloader Composer..."
composer install --no-dev --optimize-autoloader --no-scripts

# 4. Compiler les assets
echo "💼 Compilation des assets..."
php bin/console asset-map:compile

# 5. Optimiser les migrations Doctrine
echo "🗄️ Vérification des migrations..."
php bin/console doctrine:migrations:status --env=prod

# 6. Générer les proxies Doctrine
echo "⚡ Génération des proxies Doctrine..."
php bin/console doctrine:generate:proxies --env=prod

# 7. Créer le cache des métadatas
echo "🏃 Création du cache des métadatas..."
php bin/console doctrine:cache:clear-metadata --env=prod

# 8. Vérifier la configuration de production
echo "✅ Vérification de la configuration..."
php bin/console about --env=prod

echo "✨ Optimisation terminée ! Application prête pour la production."

# Afficher des conseils pour le déploiement
echo ""
echo "📋 Conseils pour la production :"
echo "- Vérifiez que OPcache est activé sur le serveur"
echo "- Configurez un reverse proxy (Nginx) pour les assets statiques"
echo "- Activez la compression gzip/brotli"
echo "- Configurez un système de monitoring (logs, erreurs)"
echo "- Mettez en place des sauvegardes automatiques de la base de données"