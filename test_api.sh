#!/bin/bash

# Script de test pour l'API bulk-update-order
echo "=== TEST API BULK-UPDATE-ORDER ==="
echo "URL: http://localhost:8000/transaction/bulk-update-order"
echo ""

# Données de test simples
TEST_DATA='{"transactions":[{"id":370,"order":1,"exercice_id":213},{"id":371,"order":2,"exercice_id":213}]}'

echo "Données de test:"
echo "$TEST_DATA"
echo ""

echo "Envoi de la requête..."
curl -X POST \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "$TEST_DATA" \
  http://localhost:8000/transaction/bulk-update-order \
  -v

echo ""
echo "=== FIN DU TEST ==="