# Fixtures - Guide d'utilisation

## Description

Ce projet contient des fixtures simples pour initialiser votre base de données avec des données de test.

## Données créées

Les fixtures créent automatiquement :

### Rôles

-   **ADMIN** : Administrateur du système
-   **USER** : Utilisateur standard
-   **COMPTABLE** : Comptable de l'entreprise

### Entreprises

-   **SARL TechSolutions** (Paris)
    -   SIRET: 12345678901234
    -   Email: contact@techsolutions.fr
-   **SAS Innovation Hub** (Lyon)
    -   SIRET: 98765432109876
    -   Email: info@innovationhub.fr

### Personnes

-   **Jean Dupont** (lié à TechSolutions)
    -   Email: jean.dupont@email.fr
-   **Marie Martin** (liée à Innovation Hub)
    -   Email: marie.martin@email.fr
-   **Super Admin** (administrateur système)
    -   Email: admin@system.fr

### Utilisateurs

-   **admin** / mot de passe: `admin123` (Rôle: ADMIN)
-   **jean.dupont** / mot de passe: `password123` (Rôle: COMPTABLE)
-   **marie.martin** / mot de passe: `password456` (Rôle: USER)

### Exercices

-   **Exercice 2024** (01/01/2024 - 31/12/2024)
-   **Exercice 2023** (01/01/2023 - 31/12/2023)

### Types de transactions

-   **Vente** : Transaction de vente de produits ou services
-   **Achat** : Transaction d'achat de matériel ou services
-   **Salaire** : Paiement des salaires

### Transactions d'exemple

-   Vente services informatiques (5 000€)
-   Achat matériel informatique (-3 000€)
-   Paiement salaires mars (-8 000€)
-   Formation équipe développement (-1 500€)

## Commandes utiles

### Charger les fixtures

```bash
php bin/console doctrine:fixtures:load
```

### Charger les fixtures sans confirmation

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

### Vider et recharger les fixtures

```bash
php bin/console doctrine:fixtures:load --purge-with-truncate
```

## Notes importantes

⚠️ **Attention** : La commande `doctrine:fixtures:load` **supprime toutes les données existantes** avant de charger les nouvelles fixtures.

💡 **Conseil** : Utilisez ces fixtures uniquement dans un environnement de développement ou de test, jamais en production.

## Personnalisation

Pour modifier les données de test, éditez le fichier `src/DataFixtures/AppFixtures.php` et relancez la commande de chargement.
