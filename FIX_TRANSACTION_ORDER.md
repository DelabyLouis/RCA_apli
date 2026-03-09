# 🔧 Correction des numéros d'ordre des transactions

## ❌ Problème identifié

Les transactions pouvaient avoir des numéros d'ordre négatifs, ce qui ne devrait jamais arriver.

## ✅ Solutions appliquées

### 1. **Validation ajoutée** (Entity)

- Ajout d'une validation `@Assert\Positive` sur le champ `numero_ordre`
- Cela empêche l'assignation de numéros négatifs à l'avenir

### 2. **Amélioration du drag & drop**

- Simplification de la logique pour éviter les numéros temporaires
- Assurance que chaque exercice a des numéros de **1 à N** uniquement

### 3. **Nettoyage des données existantes**

- Une commande Symfony a été créée pour renumméroter toutes les transactions

## 🚀 Utilisation

### Exécuter le nettoyage

```bash
cd /chemin/vers/RCA_apli

# Mode développement
php bin/console app:fix-transaction-order

# Mode production
php bin/console app:fix-transaction-order --env=prod
```

### Qu'est-ce que la commande fait ?

1. Récupère tous les exercices
2. Pour chaque exercice :
    - Récupère les transactions triées
    - Les renumérote de **1 à N**
    - Corrige les numéros négatifs ou invalides
    - Affiche un rapport de correction

### Exemple de sortie

```
 🔧 Correction des numéros d'ordre des transactions

 ➜ Exercice: 2024-2025
   Transactions trouvées: 15
   ✓ [ID: 1] 1 → 1
   ✓ [ID: 2] 2 → 2
   ⚠️  [ID: 3] -1001 → 3 (NUMÉRO INVALIDE CORRIGÉ)
   ...

 ✅ Correction terminée ! 2 transactions renummérotées
```

## 📋 Réglementation

Après cette correction :

- ✅ Tous les `numero_ordre` sont positifs (≥ 1)
- ✅ Chaque exercice a des numéros **sans écart** (1, 2, 3, ...)
- ✅ Le drag & drop est sécurisé et reconnaît les exercices
- ✅ Les validations empêchent les futures corruptions

## 🐛 En cas de problème

Si vous voyez toujours des numéros négatifs après avoir exécuté la commande :

1. Vérifiez les logs du serveur
2. Assurez-vous que la base de données est accessible
3. Essayez à nouveau avec le flag `--verbose` :
    ```bash
    php bin/console app:fix-transaction-order -vv
    ```
