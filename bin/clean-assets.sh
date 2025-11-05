#!/bin/bash

# Script pour nettoyer les assets générés tout en préservant les images
# Usage: ./bin/clean-assets.sh

echo "Nettoyage des assets générés..."

# Supprimer les fichiers générés mais préserver le dossier images
rm -f public/assets/*.js
rm -f public/assets/*.json
rm -rf public/assets/styles/
rm -rf public/assets/vendor/
rm -rf public/assets/@symfony/
rm -rf public/assets/controllers/

echo "Assets nettoyés. Le dossier images a été préservé."
echo "Vous pouvez maintenant recompiler avec: php bin/console asset-map:compile"