# 🚀 Guide de Mise en Production - RCA Application

## ✅ État de Préparation

L'application est maintenant **PRÊTE pour la mise en production** avec toutes les optimisations nécessaires.

## 🔧 Configuration Pré-Production

### 1. Variables d'Environnement

```bash
# Copier le fichier d'exemple et personnaliser
cp .env.prod.local.example .env.prod.local

# Variables critiques à configurer :
DATABASE_URL="mysql://user:password@host:3306/database?serverVersion=8.0.32"
MAILER_DSN="smtp://user:pass@smtp.example.com:587"
DEFAULT_URI="https://votre-domaine.com"
```

### 2. Installation des Dépendances

```bash
# Installation optimisée pour la production
composer install --no-dev --optimize-autoloader --no-scripts
```

### 3. Optimisation Automatique

```bash
# Exécuter le script d'optimisation
chmod +x bin/optimize-prod.sh
./bin/optimize-prod.sh
```

## 🔒 Sécurité Implémentée

-   ✅ **HTTPS obligatoire** en production
-   ✅ **En-têtes de sécurité** (CSP, HSTS, XSS Protection)
-   ✅ **Sessions sécurisées** (HttpOnly, Secure, SameSite)
-   ✅ **Mots de passe renforcés** (bcrypt cost 13)
-   ✅ **Protection CSRF** activée
-   ✅ **Contrôle d'accès par rôles**

## ⚡ Optimisations de Performance

-   ✅ **OPcache configuré** avec preloading
-   ✅ **Cache Doctrine** (métadatas, requêtes, résultats)
-   ✅ **Cache Twig** optimisé
-   ✅ **Assets compilés** et optimisés
-   ✅ **Service de pagination** pour les grandes collections
-   ✅ **Proxies Doctrine** pré-générés

## 🧪 Tests Implémentés

-   ✅ **Tests unitaires** pour les entités User et Transaction
-   ✅ **Tests fonctionnels** pour SecurityController et HomeController
-   ✅ **Configuration PHPUnit** complète

### Exécuter les tests

```bash
# Tests complets
php bin/phpunit

# Tests unitaires uniquement
php bin/phpunit tests/Entity/

# Tests fonctionnels uniquement
php bin/phpunit tests/Controller/
```

## 🐳 Docker Production

### Configuration MySQL harmonisée

```bash
# Démarrer les services
docker-compose up -d

# Vérifier l'état
docker-compose ps
```

## 📊 Monitoring et Logs

### Vérification de l'état

```bash
# Informations système
php bin/console about --env=prod

# Validation des migrations
php bin/console doctrine:migrations:status --env=prod

# Validation du schéma
php bin/console doctrine:schema:validate --env=prod
```

## 🌐 Configuration Serveur Web

### Nginx (Recommandé)

```nginx
server {
    listen 443 ssl http2;
    server_name votre-domaine.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    root /path/to/app/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;

        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;

        # Variables d'environnement
        fastcgi_param APP_ENV prod;
        fastcgi_param APP_DEBUG 0;
    }

    # Cache des assets
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### Apache (.htaccess)

```apache
# Redirection HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Cache des assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/* "access plus 1 year"
</IfModule>
```

## 📋 Checklist de Déploiement

-   [ ] Variables d'environnement configurées
-   [ ] Base de données migrée
-   [ ] Cache généré et réchauffé
-   [ ] Assets compilés
-   [ ] Tests passés avec succès
-   [ ] HTTPS configuré et testé
-   [ ] Monitoring mis en place
-   [ ] Sauvegardes programmées
-   [ ] Certificats SSL valides
-   [ ] DNS configurés

## 🆘 Dépannage

### Erreurs communes

```bash
# Problème de cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

# Problème de permissions
chmod -R 755 var/
chmod -R 755 public/assets/

# Problème de base de données
php bin/console doctrine:migrations:migrate --env=prod
```

### Logs importants

-   `var/log/prod.log` - Logs application
-   Logs serveur web (nginx/apache)
-   Logs PHP (php-fpm)
-   Logs base de données

---

## 🎉 Application Prête !

Votre application RCA est maintenant **100% prête pour la production** avec :

-   Sécurité renforcée ✅
-   Performance optimisée ✅
-   Tests complets ✅
-   Configuration professionnelle ✅
