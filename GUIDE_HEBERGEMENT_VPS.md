# 🚀 Guide de Déploiement VPS - Application RCA

## 1️⃣ **Préparation du Serveur VPS**

### Configuration initiale Ubuntu 22.04
```bash
# 1. Connexion SSH à votre VPS
ssh root@VOTRE_IP_VPS

# 2. Mise à jour du système
apt update && apt upgrade -y

# 3. Installation des dépendances
apt install -y nginx mysql-server php8.3 php8.3-fpm php8.3-mysql php8.3-xml php8.3-curl php8.3-mbstring php8.3-zip php8.3-gd php8.3-intl unzip curl git

# 4. Installation de Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
```

---

## 2️⃣ **Configuration MySQL**

```bash
# 1. Configuration sécurisée de MySQL
mysql_secure_installation

# 2. Création de la base de données
mysql -u root -p
```

```sql
-- Dans MySQL
CREATE DATABASE rca_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'rca_user'@'localhost' IDENTIFIED BY 'MotDePasseSecurise123!';
GRANT ALL PRIVILEGES ON rca_production.* TO 'rca_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 3️⃣ **Déploiement de l'Application**

```bash
# 1. Création du répertoire web
mkdir -p /var/www/rca-app
cd /var/www/rca-app

# 2. Cloner votre application (ou upload via FTP/SFTP)
git clone https://github.com/DelabyLouis/RCA_apli.git .

# 3. Installation des dépendances
composer install --no-dev --optimize-autoloader --no-scripts

# 4. Configuration de l'environnement de production
cp .env.prod.local.example .env.prod.local
nano .env.prod.local
```

### Configuration .env.prod.local
```bash
# Base de données
DATABASE_URL="mysql://rca_user:MotDePasseSecurise123!@localhost:3306/rca_production?serverVersion=8.0.32&charset=utf8mb4"

# Domaine (remplacer par votre vrai domaine)
DEFAULT_URI=https://votre-domaine.com

# SMTP (configurer avec votre provider email)
MAILER_DSN=smtp://localhost:1025

# Secret de production (générer un nouveau)
APP_SECRET=VotreNouveauSecretDeProd123456789
```

```bash
# 5. Migrations et optimisations
php bin/console doctrine:migrations:migrate --env=prod --no-interaction
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
php bin/console asset-map:compile

# 6. Permissions
chown -R www-data:www-data /var/www/rca-app
chmod -R 755 /var/www/rca-app/var/
chmod -R 755 /var/www/rca-app/public/
```

---

## 4️⃣ **Configuration Nginx**

```bash
# Créer le fichier de configuration Nginx
nano /etc/nginx/sites-available/rca-app
```

```nginx
server {
    listen 80;
    server_name votre-domaine.com www.votre-domaine.com;
    root /var/www/rca-app/public;
    index index.php;

    # Logs
    access_log /var/log/nginx/rca-app.access.log;
    error_log /var/log/nginx/rca-app.error.log;

    # Configuration Symfony
    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;

        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param APP_ENV prod;
        fastcgi_param APP_DEBUG 0;

        # Sécurité
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Cache des assets statiques
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary Accept-Encoding;
    }

    # Bloquer l'accès aux fichiers sensibles
    location ~ /\. {
        deny all;
    }
}
```

```bash
# Activer le site
ln -s /etc/nginx/sites-available/rca-app /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

---

## 5️⃣ **Configuration HTTPS (Certificat SSL gratuit)**

```bash
# Installation de Certbot
apt install -y certbot python3-certbot-nginx

# Génération du certificat SSL (remplacer par votre domaine)
certbot --nginx -d votre-domaine.com -d www.votre-domaine.com

# Le certificat se renouvelle automatiquement
```

---

## 6️⃣ **Optimisations de Performance**

### Configuration PHP-FPM
```bash
nano /etc/php/8.3/fpm/pool.d/www.conf
```

```ini
; Optimisations pour production
pm = dynamic
pm.max_children = 20
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 4
pm.max_requests = 500
```

### Configuration PHP.ini
```bash
nano /etc/php/8.3/fpm/php.ini
```

```ini
; Optimisations OPcache
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
opcache.preload=/var/www/rca-app/config/preload.php

; Limites
memory_limit=512M
max_execution_time=60
upload_max_filesize=50M
post_max_size=50M
```

```bash
# Redémarrer PHP-FPM
systemctl restart php8.3-fpm
```

---

## 7️⃣ **Monitoring et Maintenance**

### Script de monitoring
```bash
# Créer un script de monitoring
nano /home/monitoring.sh
```

```bash
#!/bin/bash
# Script de monitoring RCA

echo "=== Monitoring RCA App $(date) ==="

# Vérifier Nginx
systemctl is-active nginx

# Vérifier PHP-FPM  
systemctl is-active php8.3-fpm

# Vérifier MySQL
systemctl is-active mysql

# Espace disque
df -h /

# RAM utilisée
free -h

# Logs récents d'erreur
tail -n 5 /var/log/nginx/rca-app.error.log
```

### Sauvegarde automatique
```bash
# Script de sauvegarde quotidienne
nano /home/backup.sh
```

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M)

# Sauvegarde base de données
mysqldump -u rca_user -p rca_production > /home/backups/rca_db_$DATE.sql

# Sauvegarde fichiers uploadés (si applicable)
tar -czf /home/backups/rca_files_$DATE.tar.gz /var/www/rca-app/public/uploads/

# Nettoyer les anciennes sauvegardes (garder 7 jours)
find /home/backups/ -name "rca_*" -type f -mtime +7 -delete
```

```bash
# Ajouter au crontab pour exécution quotidienne
crontab -e
# Ajouter : 0 2 * * * /home/backup.sh
```

---

## 8️⃣ **Tests de Validation**

```bash
# 1. Test de connectivité
curl -I http://votre-domaine.com

# 2. Test HTTPS
curl -I https://votre-domaine.com

# 3. Test de performance
curl -w "@curl-format.txt" -o /dev/null -s "https://votre-domaine.com"
```

---

## 🎯 **Checklist de Déploiement Final**

- [ ] Serveur VPS configuré avec Ubuntu 22.04
- [ ] Nginx + PHP 8.3 + MySQL 8.0 installés
- [ ] Application déployée et dépendances installées
- [ ] Base de données créée et migrations exécutées
- [ ] Configuration Nginx active
- [ ] Certificat SSL configuré (HTTPS)
- [ ] Optimisations PHP/OPcache activées
- [ ] Monitoring et sauvegardes programmées
- [ ] Tests de performance validés
- [ ] DNS pointant vers votre VPS

---

## 💡 **Conseils de Sécurité**

1. **Firewall** : Ouvrir uniquement ports 22 (SSH), 80 (HTTP), 443 (HTTPS)
2. **SSH** : Désactiver l'auth par mot de passe, utiliser des clés
3. **Mises à jour** : Automatiser les mises à jour de sécurité
4. **Monitoring** : Configurer des alertes pour les erreurs critiques
5. **Sauvegardes** : Tester régulièrement la restauration

---

*Votre application RCA sera ainsi hébergée de manière professionnelle et sécurisée !*