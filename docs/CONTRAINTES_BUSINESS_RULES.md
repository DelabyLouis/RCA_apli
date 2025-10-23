# Contraintes et Règles Métier - Gestion des Relations

## 📋 Analyse des Relations et Contraintes dans l'Application

### 🔴 **Contraintes Critiques Identifiées**

#### 1. **Transaction → TypeTransaction** ✅ **TRAITÉ**

-   **Problème :** Changer les contraintes d'un type (débit/crédit) affecte les transactions existantes
-   **Solution implémentée :**
    -   Relation rendue `nullable: true`
    -   Logique de détachement automatique des transactions incompatibles
    -   Message d'avertissement à l'utilisateur
    -   Transactions orphelines peuvent être réassignées manuellement

#### 2. **Transaction → Exercice** ⚠️ **À TRAITER**

-   **Problème :** Si on supprime un exercice, toutes ses transactions deviennent orphelines
-   **Contrainte actuelle :** `nullable: false`
-   **Impact :** Suppression impossible si transactions liées
-   **Solutions proposées :**
    -   Option A : Empêcher la suppression d'un exercice avec transactions
    -   Option B : Transférer les transactions vers un autre exercice avant suppression
    -   Option C : Rendre la relation nullable et créer un "exercice par défaut"

#### 3. **User → Personne** ⚠️ **À TRAITER**

-   **Problème :** Suppression d'une personne impossible si un utilisateur y est lié
-   **Contrainte actuelle :** `nullable: false`
-   **Impact :** Gestion complexe des suppressions
-   **Solutions proposées :**
    -   Option A : Cascade delete (supprimer l'utilisateur avec la personne)
    -   Option B : Transfert vers un compte "utilisateur anonyme"
    -   Option C : Archivage au lieu de suppression

### 🟡 **Contraintes Modérées**

#### 4. **Transaction → Personne/Entreprise** ✅ **OK**

-   **État :** `nullable: true` - Déjà flexible
-   **Validation :** Une transaction doit avoir soit une personne, soit une entreprise (validation métier)

#### 5. **Personne ↔ Entreprise** ✅ **OK**

-   **État :** Relation Many-to-Many sans contraintes strictes
-   **Flexibilité :** Ajout/suppression de relations sans impact

### 🟢 **Relations Flexibles**

#### 6. **User ↔ Role** ✅ **OK**

-   **État :** Many-to-Many avec table de liaison
-   **Gestion :** Ajout/suppression de rôles sans contraintes

## 🛠️ **Recommandations d'Implémentation**

### **Priorité 1 - Actions Immédiates**

1. **Exercice avec Transactions**

    ```php
    // Dans ExerciceController::delete()
    $transactionCount = $exerciceRepository->countTransactions($exercice);
    if ($transactionCount > 0) {
        $this->addFlash('error',
            "Impossible de supprimer cet exercice car {$transactionCount} transaction(s) y sont liées."
        );
        return $this->redirectToRoute('app_exercice_index');
    }
    ```

2. **Interface de Transfert de Transactions**
    - Créer une page spéciale pour transférer les transactions avant suppression
    - Formulaire de sélection du nouvel exercice de destination

### **Priorité 2 - Améliorations**

1. **Gestion des Suppressions Sécurisées**

    ```php
    // Trait à créer pour toutes les entités
    trait SafeDeletionTrait {
        public function canBeDeleted(): bool;
        public function getDependenciesCount(): array;
        public function getBlockingDependencies(): array;
    }
    ```

2. **Notifications Préventives**
    - Affichage des dépendances avant suppression
    - Messages d'avertissement contextuels

### **Priorité 3 - Évolutions Futures**

1. **Système d'Archivage**

    - Soft delete au lieu de hard delete
    - État "archivé" pour les entités sensibles

2. **Audit Trail**
    - Traçabilité des modifications critiques
    - Historique des changements de contraintes

## 🔧 **Patterns de Code Recommandés**

### **Vérification Avant Modification**

```php
// Pattern à utiliser avant toute modification de contrainte
$originalValue = $entity->getConstraintField();
// ... traitement du formulaire ...
if ($originalValue !== $entity->getConstraintField()) {
    $affectedEntities = $this->checkAffectedEntities($entity, $originalValue);
    if (!empty($affectedEntities)) {
        $this->handleConstraintChange($entity, $affectedEntities);
    }
}
```

### **Message d'Avertissement Standardisé**

```php
$this->addFlash('warning', sprintf(
    'Attention : %d entité(s) affectée(s) par ce changement. Détails : %s',
    count($affected),
    $detailMessage
));
```

## 📊 **Matrice de Risques**

| Relation                          | Risque | Impact | Priorité | Statut       |
| --------------------------------- | ------ | ------ | -------- | ------------ |
| Transaction → TypeTransaction     | Élevé  | Moyen  | P1       | ✅ Traité    |
| Transaction → Exercice            | Élevé  | Élevé  | P1       | ⚠️ À traiter |
| User → Personne                   | Moyen  | Élevé  | P2       | ⚠️ À traiter |
| Transaction → Personne/Entreprise | Faible | Faible | P3       | ✅ OK        |

## 🎯 **Objectifs de Qualité**

1. **Intégrité des Données** : Aucune donnée orpheline non intentionnelle
2. **UX Prévisible** : L'utilisateur sait toujours ce qui va se passer
3. **Récupération Possible** : Les actions critiques sont réversibles
4. **Performance** : Les vérifications n'impactent pas les performances

---

_Document créé le 23/10/2025 - À maintenir à jour lors des évolutions_
