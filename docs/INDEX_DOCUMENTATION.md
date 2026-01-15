# 📊 Guide des Diagrammes - Application RCA

## 🎯 Vue d'ensemble

Ce dossier contient tous les diagrammes et documents explicatifs de l'application de gestion financière du Rugby Club Audomarois (RCA). Ces documents sont conçus pour être compris par tous, même sans connaissances techniques.

---

## 📁 Contenu du dossier

### 📋 **README_CLIENT.md**

**👥 Pour qui :** Direction du club, trésoriers, membres
**🎯 Objectif :** Comprendre à quoi sert l'application et comment l'utiliser
**📖 Contenu :**

-   Explication simple des fonctionnalités
-   Guide des droits utilisateurs
-   Avantages par rapport aux fichiers Excel
-   Exemples concrets d'utilisation

### 🏗️ **architecture_simple.puml**

**👥 Pour qui :** Direction, informaticiens
**🎯 Objectif :** Comprendre comment l'application est construite
**📖 Contenu :**

-   Architecture technique simplifiée
-   Couches de sécurité
-   Technologies utilisées
-   Flux des données

### 🗃️ **database_diagram.puml**

**👥 Pour qui :** Développeurs, administrateurs techniques
**🎯 Objectif :** Structure complète des données
**📖 Contenu :**

-   Toutes les tables de données avec émojis explicatifs
-   Relations entre les informations
-   Exemples concrets
-   Types d'utilisateurs et permissions

### 📋 **database_diagram_simple_new.puml**

**👥 Pour qui :** Direction, trésoriers
**🎯 Objectif :** Vue d'ensemble des données sans détails techniques
**📖 Contenu :**

-   4 grandes catégories de données
-   Exemples concrets
-   Avantages pour le club
-   Schéma très simplifié

### 🎭 **diagramme_use_case.puml**

**👥 Pour qui :** Tous les utilisateurs
**🎯 Objectif :** Qui peut faire quoi dans l'application
**📖 Contenu :**

-   Types d'utilisateurs (visiteur, membre, trésorier, admin)
-   Fonctionnalités disponibles par profil
-   Hiérarchie des droits
-   Organisation des permissions

### 🚀 **diagramme_sequence.puml**

**👥 Pour qui :** Direction, futurs utilisateurs
**🎯 Objectif :** Comment se déroule une opération typique
**📖 Contenu :**

-   Exemple concret : ajouter une cotisation
-   Étapes détaillées du processus
-   Vérifications de sécurité
-   Résultat final

---

## 🛠️ Comment utiliser ces documents

### 👀 **Pour visualiser les diagrammes**

**Option 1 - Avec PlantUML (recommandé) :**

1. Installer l'extension PlantUML dans VS Code
2. Ouvrir un fichier `.puml`
3. Appuyer sur `Alt + D` pour voir le diagramme

**Option 2 - En ligne :**

1. Aller sur [plantuml.com](https://plantuml.com/plantuml)
2. Copier le contenu d'un fichier `.puml`
3. Coller dans l'éditeur en ligne

**Option 3 - Export en image :**

```bash
# Si PlantUML est installé localement
java -jar plantuml.jar *.puml
```

### 📱 **Selon votre rôle**

#### 🏛️ **Direction du club**

1. Commencer par `README_CLIENT.md`
2. Regarder `diagramme_use_case.puml` (qui fait quoi)
3. Parcourir `database_diagram_simple_new.puml` (vue d'ensemble)

#### 💼 **Trésorier**

1. Lire `README_CLIENT.md` en entier
2. Étudier `diagramme_sequence.puml` (comment ça marche)
3. Comprendre `diagramme_use_case.puml` (vos droits)

#### 👤 **Membre du club**

1. Section "Qui peut faire quoi ?" dans `README_CLIENT.md`
2. Regarder `diagramme_use_case.puml` pour votre profil

#### 🔧 **Informaticien/Développeur**

1. Tous les documents pour vue globale
2. Focus sur `database_diagram.puml` et `architecture_simple.puml`
3. `README_CLIENT.md` pour comprendre les besoins métier

---

## 💡 Conseils de lecture

### 🎯 **Si vous découvrez l'application**

➡️ Commencez par `README_CLIENT.md` - c'est fait pour vous !

### 🤔 **Si vous vous demandez "qui peut faire quoi"**

➡️ Direction vers `diagramme_use_case.puml`

### 🔍 **Si vous voulez comprendre le fonctionnement**

➡️ Regardez `diagramme_sequence.puml` avec un exemple concret

### 📊 **Si vous voulez voir les données stockées**

➡️ `database_diagram_simple_new.puml` pour une vue simple
➡️ `database_diagram.puml` pour les détails complets

---

## ❓ Besoin d'aide ?

### 🆘 **Questions sur l'utilisation**

-   Consultez d'abord `README_CLIENT.md`
-   Contactez le trésorier ou l'administrateur

### 🔧 **Questions techniques**

-   Consultez les diagrammes d'architecture
-   Contactez l'équipe de développement

### 📞 **Support**

-   Email : [contact du support]
-   Documentation mise à jour : janvier 2026

---

## 🔄 Historique des versions

| Date       | Version | Modifications                                       |
| ---------- | ------- | --------------------------------------------------- |
| 15/01/2026 | 2.0     | Refonte complète avec émojis et explications client |
| 2025       | 1.x     | Versions techniques initiales                       |

---

_📝 Documentation créée avec ❤️ pour le Rugby Club Audomarois_
_🎯 Objectif : rendre la technique accessible à tous_
