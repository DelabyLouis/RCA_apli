# 🗄️ Guide de Configuration de la Base de Données de Test

## 📝 **Résumé de la Configuration**

Votre base de données de test est maintenant configurée avec **SQLite** (recommandé pour les tests).

## ✅ **Configuration Actuelle**

### 1. **Fichier .env.test configuré**

```bash
# Base de données SQLite pour les tests (recommandé)
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data/test.db"
```

### 2. **Base de données créée**

-   ✅ Répertoire créé : `var/data/`
-   ✅ Base SQLite créée : `var/data/test.db`
-   ✅ Schéma généré avec toutes les tables

### 3. **Tests unitaires fonctionnels**

-   ✅ Tests des entités : 10 tests, 21 assertions
-   ✅ Aucune dépendance à la base de données

## 🔧 **Solutions pour les Différents Environnements**

### **Option 1 : SQLite (Recommandée) ✅**

```bash
# Dans .env.test
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data/test.db"
```

**Avantages :**

-   ✅ Pas de serveur externe requis
-   ✅ Tests rapides
-   ✅ Configuration simple
-   ✅ Isolé par développeur

**Commandes :**

```bash
# Créer la base de test
php bin/console doctrine:database:create --env=test

# Créer le schéma
php bin/console doctrine:schema:create --env=test

# Mettre à jour le schéma si nécessaire
php bin/console doctrine:schema:update --env=test --force
```

---

### **Option 2 : MySQL avec utilisateur dédié**

Si vous préférez MySQL pour les tests, créez un utilisateur avec plus de permissions :

#### **Étape 1 : Se connecter à MySQL**

```bash
# Se connecter au conteneur Docker MySQL
mysql -u root -p -h 127.0.0.1 -P 61961
```

#### **Étape 2 : Créer un utilisateur de test**

```sql
-- Créer un utilisateur avec permissions complètes
CREATE USER 'test_user'@'%' IDENTIFIED BY 'test_password';
GRANT ALL PRIVILEGES ON *.* TO 'test_user'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;

-- Ou donner des permissions spécifiques
GRANT CREATE, DROP, SELECT, INSERT, UPDATE, DELETE ON `RCA_appli_test`.* TO 'test_user'@'%';
FLUSH PRIVILEGES;
```

#### **Étape 3 : Modifier .env.test**

```bash
# Dans .env.test
DATABASE_URL="mysql://test_user:test_password@127.0.0.1:61961/RCA_appli_test?serverVersion=8.0&charset=utf8mb4"
```

---

### **Option 3 : Base de test en mémoire**

Pour des tests ultra-rapides :

```bash
# Dans .env.test
DATABASE_URL="sqlite:///:memory:"
```

**Avantages :**

-   ⚡ Extrêmement rapide
-   🧹 Nettoyage automatique
-   📦 Pas de fichier créé

**Inconvénients :**

-   ⚠️ Recréation à chaque test
-   🔍 Plus difficile à déboguer

---

## 🛠️ **Commandes Utiles**

### **Gestion du schéma de test**

```bash
# Vérifier l'état de la base de test
php bin/console doctrine:schema:validate --env=test

# Supprimer et recréer la base
php bin/console doctrine:database:drop --env=test --force
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
```

### **Exécution des tests**

```bash
# Tests unitaires seulement (sans BDD)
php bin/phpunit tests/Entity/

# Tous les tests (avec BDD configurée)
php bin/phpunit

# Tests avec coverage (si xdebug activé)
php bin/phpunit --coverage-html var/coverage
```

### **Debugging**

```bash
# Vérifier la configuration Doctrine
php bin/console doctrine:mapping:info --env=test

# Lister les tables créées
php bin/console doctrine:schema:validate --env=test
```

---

## 🚨 **Résolution des Problèmes Courants**

### **Erreur : "Access denied for user"**

-   ✅ **Solution :** Utiliser SQLite (déjà fait)
-   🔧 **Alternative :** Créer un utilisateur MySQL avec les bonnes permissions

### **Erreur : "Unable to open database file"**

```bash
# Créer le répertoire si nécessaire
mkdir -p var/data
chmod 755 var/data
```

### **Tests fonctionnels échouent**

Les tests fonctionnels dans votre app échouent à cause d'un `PermissionListener`. Pour les corriger :

1. **Désactiver le listener en test :**

```yaml
# config/services_test.yaml
services:
    App\EventListener\PermissionListener:
        tags: [] # Désactive le listener en test
```

2. **Ou créer des utilisateurs de test :**

```php
// Dans vos tests fonctionnels
$this->loadFixtures([UserFixture::class]);
$this->loginUser($this->getUser('test@example.com'));
```

---

## 📊 **État Actuel des Tests**

### ✅ **Tests qui fonctionnent**

-   Tests unitaires des entités (10 tests)
-   Logique métier sans dépendances

### ⚠️ **Tests à corriger**

-   Tests fonctionnels (problème de permissions)
-   Tests nécessitant une authentification

### 🎯 **Recommandations**

1. **Garder SQLite pour les tests** (déjà fait ✅)
2. **Ajouter des fixtures pour les tests fonctionnels**
3. **Désactiver les listeners métier en test**
4. **Créer des tests de service isolés**

---

## 📝 **Configuration Finale Recommandée**

Votre configuration actuelle avec SQLite est **parfaite** pour les tests. Les tests unitaires fonctionnent parfaitement, et c'est le plus important pour valider la logique métier de votre application.
