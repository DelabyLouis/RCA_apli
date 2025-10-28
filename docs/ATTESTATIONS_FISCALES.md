# Attestations Fiscales - Guide d'utilisation

## Description

Ce module permet de générer des attestations fiscales pour les dons (cotisations) reçus par l'association AMICALE DES ANCIENS DU RUGBY CLUB AUDOMAROIS.

## Fonctionnalités

### 1. Page d'accueil des attestations

-   **URL:** `/attestation-fiscale`
-   **Description:** Affiche la liste des donateurs éligibles aux attestations fiscales
-   **Filtres disponibles:**
    -   Par exercice
    -   Par année

### 2. Génération d'attestation PDF

-   **URL:** `/attestation-fiscale/generer/{type}/{id}?annee={annee}`
-   **Description:** Génère une attestation fiscale au format PDF
-   **Paramètres:**
    -   `type` : "personne" ou "entreprise"
    -   `id` : ID du donateur
    -   `annee` : Année fiscale (optionnel, par défaut année courante)

## Prérequis

### Type de transaction "cotisation"

Pour que le module fonctionne, vous devez avoir créé un type de transaction avec le libellé exactement "cotisation".

**Pour créer ce type de transaction:**

1. Aller dans "Gestion" > "Types de Transaction"
2. Créer un nouveau type avec :
    - Libellé : `cotisation`
    - Description : `Cotisations des membres de l'association (ouvre droit aux attestations fiscales)`
    - Type de montant autorisé : `credit` (recettes uniquement)

**OU** utiliser la commande :

```bash
php bin/console app:create-cotisation-type
```

### Transactions de cotisation

Pour qu'un donateur apparaisse dans la liste des attestations :

1. Créer des transactions avec le type "cotisation"
2. Les transactions doivent avoir un montant positif (recettes)
3. Les transactions doivent être liées à une Personne OU une Entreprise
4. Renseigner le mode de paiement pour un PDF plus précis

## Utilisation

### Étape 1 : Créer des cotisations

1. Aller dans "Transactions"
2. Créer de nouvelles transactions avec :
    - Type : "cotisation"
    - Montant positif
    - Personne ou Entreprise renseignée
    - Mode de paiement (recommandé)
    - Date de transaction

### Étape 2 : Générer les attestations

1. Aller dans "Attestations Fiscales" (dans le menu principal)
2. Utiliser les filtres si nécessaire
3. Cliquer sur le bouton PDF correspondant à l'année souhaitée

### Étape 3 : Distribution

Les attestations générées contiennent :

-   Toutes les informations légalement requises
-   Le détail des dons par mode de paiement
-   Un numéro d'ordre unique
-   Le total des dons pour l'année
-   Une annexe avec le détail des transactions (si plusieurs)

## Modèle d'attestation

L'attestation respecte le modèle officiel des impôts français et inclut :

-   Les informations de l'organisme bénéficiaire (pré-remplies)
-   La catégorie "Association sportive" (pré-cochée)
-   Les informations du donateur (auto-remplies depuis la base)
-   Le détail des dons par mode de paiement
-   Les engagements légaux de l'organisme

## Données de l'organisme (pré-configurées)

-   **Nom :** AMICALE DES ANCIENS DU RUGBY CLUB AUDOMAROIS
-   **Adresse :** N° 3 Rue Allée des sports
-   **Code postal :** 62500
-   **Commune :** SAINT OMER
-   **Catégorie :** Association sportive

## Notes techniques

-   Les attestations sont générées en PDF via la librairie Dompdf
-   Les montants sont automatiquement regroupés par mode de paiement
-   Un numéro d'ordre unique est généré pour chaque attestation
-   Les attestations s'ouvrent dans un nouvel onglet
-   Le détail des transactions est ajouté en annexe si plusieurs transactions

## Sécurité

-   L'accès aux attestations fiscales respecte les mêmes règles d'authentification que le reste de l'application
-   Les données personnelles des donateurs sont protégées selon la réglementation en vigueur
