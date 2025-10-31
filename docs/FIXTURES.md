# Documentation des Fixtures Complètes RCA# Documentation des Fixtures Complètes RCA

## Vue d'ensemble## Vue d'ensemble

Les fixtures `ComprehensiveFixtures.php` créent un jeu de données complet et réaliste pour l'application de gestion comptable du Rugby Club de l'Ariège (RCA).Les fixtures `ComprehensiveFixtures.php` créent un jeu de données complet et réaliste pour l'application de gestion comptable du Rugby Club de l'Ariège (RCA).

## Données créées (Total : 78 transactions)## Données créées

### 👥 Utilisateurs et Rôles (3 utilisateurs)### 👥 Utilisateurs et Rôles (3 utilisateurs)

-   **admin** / **admin123** → Jean Dupont (ROLE_ADMIN + ROLE_USER)- **admin** / **admin123** → Jean Dupont (ROLE_ADMIN + ROLE_USER)

-   **tresorier** / **tresorier123** → Marie Martin (ROLE_USER) - **tresorier** / **tresorier123** → Marie Martin (ROLE_USER)

-   **secretaire** / **secretaire123** → Sophie Dubois (ROLE_USER)- **secretaire** / **secretaire123** → Sophie Dubois (ROLE_USER)

### 👤 Personnes (10 personnes)### 👤 Personnes (10 personnes)

Membres du club avec coordonnées complètes réparties dans différentes villes de France.Membres du club avec coordonnées complètes réparties dans différentes villes de France.

### 🏢 Entreprises (10 entreprises)### 🏢 Entreprises (10 entreprises)

-   Institutions publiques : Mairie de Toulouse, Région Occitanie- Institutions publiques : Mairie de Toulouse, Région Occitanie

-   Partenaires financiers : Crédit Agricole Toulouse- Partenaires financiers : Crédit Agricole Toulouse

-   Équipementiers sportifs : Décathlon, Intersport, Gilbert Rugby- Équipementiers sportifs : Décathlon, Intersport, Gilbert Rugby

-   Organismes fédéraux : FFR, Ligue Régionale- Organismes fédéraux : FFR, Ligue Régionale

-   Services : Assurance MAIF- Services : Assurance MAIF

-   **Compte spécial** : Livret Club RCA (pour les opérations d'épargne)- **Compte spécial** : Livret Club RCA (pour les opérations d'épargne)

### 📅 Exercices (4 exercices)### 📅 Exercices (4 exercices)

-   **2023** : Clôturé (17 transactions)- **2023** : Clôturé (17 transactions)

-   **2024** : Clôturé (19 transactions) - **2024** : Clôturé (19 transactions)

-   **2025** : En cours (42 transactions)- **2025** : En cours (42 transactions)

-   **2026** : Futur (0 transaction)- **2026** : Futur (0 transaction)

### 💰 Types de Transactions (15 types)### 💰 Types de Transactions (15 types)

**Recettes :\*\***Recettes :\*\*

-   Cotisation (ouvre droit aux attestations fiscales)- Cotisation (ouvre droit aux attestations fiscales)

-   Subvention, Sponsoring, Don- Subvention, Sponsoring, Don

-   Vente événement, Remboursement- Vente événement, Remboursement

**Dépenses :\*\***Dépenses :\*\*

-   Achat équipement, Frais déplacement- Achat équipement, Frais déplacement

-   Assurance, Maintenance, Communication- Assurance, Maintenance, Communication

-   Administration, Formation, Arbitrage- Administration, Formation, Arbitrage

**Spécial :\*\***Spécial :\*\*

-   Transfert livret (both directions)- Transfert livret (both directions)

### 💳 Modes de Paiement (7 modes)### Transactions d'exemple

Espèces, Chèque, Virement, Carte bancaire, Prélèvement, Livret, Facture

-   Vente services informatiques (5 000€)

## Situations représentées- Achat matériel informatique (-3 000€)

-   Paiement salaires mars (-8 000€)

### 🔒 Exercices clôturés avec historique- Formation équipe développement (-1 500€)

-   **2023** : Clôture simple le 31/01/2024 par le trésorier

-   **2024** : Clôture → Déclôture (correction) → Re-clôture définitive## Commandes utiles

    -   Cas réaliste de correction après clôture

### Charger les fixtures

### 💵 Transactions variées

-   **Montants positifs et négatifs** répartis équitablement (50/50)```bash

-   **Cotisations étalées** sur l'année (attestations fiscales)php bin/console doctrine:fixtures:load

-   **Subventions importantes** en début d'année```

-   **Opérations livret** : dépôts et retraits avec transactions liées

-   **Remboursement négatif** : cas d'erreur de cotisation### Charger les fixtures sans confirmation

### 📊 Répartition temporelle réaliste```bash

-   **2023** : 17 transactions (exercice fermé)php bin/console doctrine:fixtures:load --no-interaction

-   **2024** : 19 transactions (exercice fermé avec corrections)```

-   **2025** : 42 transactions (exercice en cours, activité intense)

### Vider et recharger les fixtures

### 🏦 Gestion compte courant / livret

-   **70 transactions** sur compte courant```bash

-   **8 transactions** sur livret d'épargnephp bin/console doctrine:fixtures:load --purge-with-truncate

-   Transferts bidirectionnels entre comptes```

### 🎯 Cas d'usage spécifiques## Notes importantes

#### Attestations fiscales⚠️ **Attention** : La commande `doctrine:fixtures:load` **supprime toutes les données existantes** avant de charger les nouvelles fixtures.

-   Cotisations réparties sur plusieurs membres

-   Montants variés (180€ en 2023, 200€ en 2025)💡 **Conseil** : Utilisez ces fixtures uniquement dans un environnement de développement ou de test, jamais en production.

-   Étalées sur différentes dates pour tests multi-année

## Personnalisation

#### Gestion associative

-   Tournois avec recettes de buvettePour modifier les données de test, éditez le fichier `src/DataFixtures/AppFixtures.php` et relancez la commande de chargement.

-   Frais de déplacement et remboursements
-   Maintenance et investissements
-   Formation des bénévoles

#### Relations tiers

-   Transactions avec personnes physiques (membres)
-   Transactions avec entreprises (fournisseurs, partenaires)
-   Transactions internes (transferts livret)

## Tests possibles

### Tests d'attestations fiscales

```sql
-- Cotisations 2024 pour attestations
SELECT p.prenom, p.nom, SUM(t.montant) as total_cotisations
FROM transaction t
JOIN personne p ON t.id_personne = p.id_personne
JOIN type_transaction tt ON t.id_type = tt.id_type
WHERE tt.libelle = 'Cotisation' AND YEAR(t.date_transaction) = 2024
GROUP BY p.id_personne;
```

### Tests de clôture

-   Exercice 2025 ouvert → permet les modifications
-   Exercices 2023/2024 fermés → lecture seule

### Tests de livret

-   Transferts avec transactions liées (transaction_liee_id)
-   Comptes séparés (type_compte = 'livret' vs 'compte_courant')

### Tests de montants négatifs

-   Remboursement cotisation (cas réel d'erreur)
-   Charges et achats (montants négatifs normaux)

## Données de connexion

Après avoir chargé les fixtures, vous pouvez vous connecter avec :

-   **Compte administrateur** : `admin` / `admin123`
-   **Compte trésorier** : `tresorier` / `tresorier123`
-   **Compte secrétaire** : `secretaire` / `secretaire123`

## Chargement des fixtures

### Rechargement simple

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

### Rechargement complet (recommandé)

```bash
# Suppression complète et recréation
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
php bin/console doctrine:fixtures:load --no-interaction
```

### Vérification des données

```bash
# Nombre de transactions par table
php bin/console dbal:run-sql "SELECT 'Transactions' as Table_Name, COUNT(*) FROM transaction"

# Répartition par exercice
php bin/console dbal:run-sql "SELECT e.libelle, COUNT(*) as nb FROM transaction t JOIN exercice e ON t.id_exercice = e.id_exercice GROUP BY e.libelle"
```

⚠️ **Attention** : Le chargement des fixtures supprime toutes les données existantes !

## Évolution des fixtures

Pour ajouter de nouvelles situations :

1. **Nouveaux types de transactions** → `createTypesTransaction()`
2. **Nouvelles personnes/entreprises** → `createPersonnes()` / `createEntreprises()`
3. **Situations spéciales** → `createTransactions2025()` (exercice en cours)
4. **Historiques complexes** → `createHistoriquesCloture()`

Les fixtures sont modulaires et extensibles pour répondre à de nouveaux besoins de test.
