# Espace Pédagogique

Application de suivi pédagogique pour un enseignant (superviseur) et ses étudiants.
Interface en français, responsive (mobile d'abord), conçue pour être hébergée sur un
réseau local sans exposition sur Internet.

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Pile technique](#pile-technique)
- [Installation locale](#installation-locale)
- [Premier compte superviseur](#premier-compte-superviseur)
- [Déploiement sur réseau local (LAN)](#déploiement-sur-réseau-local-lan)
- [Pare-feu et non-exposition à Internet](#pare-feu-et-non-exposition-à-internet)
- [Sauvegardes et restauration](#sauvegardes-et-restauration)
- [Tâches planifiées](#tâches-planifiées)
- [Utilisation](#utilisation)
- [Règles de notation](#règles-de-notation)
- [Sécurité](#sécurité)
- [Tests et qualité du code](#tests-et-qualité-du-code)
- [Dépannage](#dépannage)

## Fonctionnalités

### Deux rôles, sans inscription publique

- **Superviseur** : gestion des étudiants, matières, thèmes, exercices, quiz, tâches,
  corrections, notes, notifications, statistiques, historique et calendrier.
- **Étudiant** : tableau de bord, matières, exercices, dépôt de réponses, quiz,
  résultats, corrections, tâches, notifications, historique et calendrier.

Aucune page d'inscription, de réinitialisation de mot de passe par e-mail ni de
vérification d'adresse e-mail n'est exposée. Les comptes étudiants sont créés par le
superviseur. Le premier superviseur est créé en ligne de commande.

### Activités pédagogiques

- Hiérarchie **matières → thèmes → activités** (exercices, quiz, tâches).
- Archivage des matières, thèmes, exercices et quiz au lieu de suppressions
  destructrices. Les données pédagogiques restent consultables.
- **Exercices** : énoncé texte ou PDF, plusieurs pièces jointes, date limite,
  publication/archivage.
- **Soumissions étudiantes** : réponse texte ou photos JPG/JPEG/PNG/WEBP.
  Les fichiers sont privés et servis uniquement après contrôle d'accès.
- **Annotation d'images** : dessin, surlignage et commentaires enregistrés dans un
  calque séparé ; l'image d'origine n'est jamais modifiée.
- **Correction** : note sur un barème libre, conversion automatique sur 20,
  commentaire, demande d'une nouvelle réponse, conservation de l'historique.
- **Quiz** : questions à choix unique ou multiple, tentatives multiples, correction
  serveur, aucune fuite des bonnes réponses avant la soumission.
- **Tâches** : indépendantes ou liées à un exercice/un quiz, affectation individuelle
  ou à plusieurs étudiants, statuts à faire / en cours / terminé, retard déduit
  automatiquement de la date limite.
- **Notifications internes** : cloche, compteur de non-lues, liens directs,
  marquage lu/non lu, rappels d'échéance.
- **Calendrier** : filtres par matière et par type, vue mensuelle sur ordinateur,
  liste chronologique sur mobile.
- **Statistiques** : indicateurs globaux, performance individuelle, graphiques
  Chart.js et historique filtrable.

## Pile technique

| Élément | Version / choix |
| --- | --- |
| PHP | 8.3 ou supérieur (validé avec PHP 8.5) |
| Framework | Laravel 13 |
| Authentification | Laravel Breeze (Blade), inscription désactivée |
| Base de données | MySQL 8/9 (SQLite en mémoire pour les tests) |
| Vues | Blade + Bootstrap 5 |
| Icônes | Bootstrap Icons |
| Graphiques | Chart.js |
| JavaScript | JavaScript natif, sans framework SPA |
| Assets | Vite + npm |
| Tests | Pest |

## Installation locale

### Prérequis

- PHP 8.3+ avec les extensions `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`,
  `xml`, `ctype`, `json`, `fileinfo` et `bcmath`.
- Composer 2.
- Node.js 20+ et npm.
- MySQL 8/9.

Installation type sur Ubuntu/Debian (adapter la version de PHP disponible) :

```bash
sudo apt update
sudo apt install -y git unzip nginx mysql-server \
  php8.3-cli php8.3-fpm php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-mysql php8.3-zip php8.3-bcmath

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

Vérifier les versions :

```bash
php -v
composer --version
node -v
npm -v
mysql --version
```

### Étapes

```bash
# 1. Dépendances PHP
composer install

# 2. Environnement
cp .env.example .env
php artisan key:generate

# 3. Créer la base et un utilisateur MySQL dédié
sudo mysql <<'SQL'
CREATE DATABASE etude_mahery CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'etude_mahery'@'localhost' IDENTIFIED BY 'mot-de-passe-fort';
GRANT ALL PRIVILEGES ON etude_mahery.* TO 'etude_mahery'@'localhost';
FLUSH PRIVILEGES;
SQL

# 4. Renseigner .env (voir ci-dessous)

# 5. Schéma de base
php artisan migrate

# 6. Assets
npm ci
npm run build

# 7. Compte superviseur
php artisan app:create-supervisor
```

Variables `.env` essentielles :

```dotenv
APP_NAME="Espace Pedagogique"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=UTC

APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr
APP_FAKER_LOCALE=fr_FR

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=etude_mahery
DB_USERNAME=etude_mahery
DB_PASSWORD=mot-de-passe-fort

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

> Le disque `local` pointe vers `storage/app/private`. Les fichiers pédagogiques ne
> sont donc pas accessibles directement par URL : ils passent par des contrôleurs
> autorisés. Aucun `php artisan storage:link` n'est nécessaire.

`APP_TIMEZONE` vaut `UTC` par défaut. Pour afficher les dates à l'heure locale (par
exemple `Indian/Antananarivo` à Madagascar), modifier cette variable puis exécuter
`php artisan config:clear`. Les heures de rappel du planificateur utilisent également
ce fuseau.

### Lancement en développement

```bash
composer run dev
```

ou, séparément :

```bash
php artisan serve
npm run dev
```

## Premier compte superviseur

La commande suivante est interactive et crée un compte superviseur actif :

```bash
php artisan app:create-supervisor
```

Elle demande le nom, l'adresse e-mail, le mot de passe et sa confirmation
(8 caractères minimum). Aucun mot de passe par défaut n'est fourni.

Les étudiants sont ensuite créés depuis **Superviseur → Étudiants → Nouvel étudiant**.
Un superviseur peut aussi réinitialiser un mot de passe étudiant, désactiver un
compte ou l'archiver. L'archivage utilise une suppression douce : l'historique
pédagogique est conservé.

## Déploiement sur réseau local (LAN)

Cette application est prévue pour un ordinateur serveur accessible uniquement depuis
le réseau local (par exemple une salle de classe ou le domicile de l'enseignant).

### 1. Préparer l'application en mode production

```bash
cd /var/www/etude-mahery

composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate

# Renseigner APP_URL avec l'adresse IP LAN du serveur, par exemple :
# APP_URL=http://192.168.1.50
# APP_ENV=production
# APP_DEBUG=false
# DB_* avec un utilisateur MySQL dédié

php artisan migrate --force
php artisan app:create-supervisor
npm ci
npm run build

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Après toute modification du fichier `.env`, exécuter
> `php artisan optimize:clear` puis relancer la mise en cache.

### 2. Choisir un serveur web

#### Option A — Recommandée : Nginx + PHP-FPM

Exemple de configuration `/etc/nginx/sites-available/etude-mahery` :

```nginx
server {
    listen 80;
    server_name 192.168.1.50;

    root /var/www/etude-mahery/public;
    index index.php;

    client_max_body_size 25M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "same-origin";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Puis :

```bash
sudo ln -s /etc/nginx/sites-available/etude-mahery /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### Option B — Test rapide : serveur intégré Laravel

```bash
php artisan serve --host=192.168.1.50 --port=8000
```

Cette option est pratique pour une démonstration, mais Nginx est préférable pour un
usage régulier avec plusieurs étudiants connectés.

### 3. Accès depuis les postes du réseau

Les étudiants et le superviseur ouvrent :

```text
http://192.168.1.50
```

(ou `http://192.168.1.50:8000` avec le serveur intégré).

Le serveur et les postes clients doivent être sur le même réseau local. Aucun nom de
domaine public n'est nécessaire.

## Pare-feu et non-exposition à Internet

L'application doit rester strictement interne.

### Règles UFW recommandées

Adapter `192.168.1.0/24` au sous-réseau réel :

```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow from 192.168.1.0/24 to any port 80 proto tcp
sudo ufw allow ssh
sudo ufw enable
sudo ufw status verbose
```

Avec le serveur intégré, remplacer le port `80` par `8000`.

### Points de contrôle

- **Ne pas** configurer de redirection de port (NAT) sur le routeur.
- **Ne pas** placer le serveur en DMZ et désactiver l'UPnP du routeur.
- **Ne pas** publier l'application via un nom de domaine public ou un tunnel.
- MySQL doit écouter uniquement en local. Vérifier la configuration :

  ```ini
  # /etc/mysql/mysql.conf.d/mysqld.cnf
  bind-address = 127.0.0.1
  ```

  ```bash
  sudo systemctl restart mysql
  sudo ss -tlnp | grep 3306
  ```

- Vérifier que seuls les ports utiles sont ouverts :

  ```bash
  sudo ss -tlnp
  ```

- En production : `APP_ENV=production`, `APP_DEBUG=false`, mots de passe forts,
  utilisateur MySQL dédié avec droits limités à la base `etude_mahery`.
- HTTPS n'est pas obligatoire sur un réseau local isolé. Si un certificat interne est
  mis en place, passer `APP_URL` en `https://...` et activer les cookies sécurisés.

## Sauvegardes et restauration

Sauvegarder à la fois la base de données, les fichiers privés et le fichier `.env`.

### Script de sauvegarde

```bash
#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/etude-mahery"
BACKUP_DIR="/var/backups/etude-mahery"
STAMP="$(date +%F_%H%M)"
DB_NAME="etude_mahery"
DB_USER="etude_mahery"

mkdir -p "$BACKUP_DIR"

mysqldump --single-transaction --routines --triggers \
  -u "$DB_USER" -p "$DB_NAME" | gzip > "$BACKUP_DIR/db-$STAMP.sql.gz"

tar -czf "$BACKUP_DIR/files-$STAMP.tar.gz" \
  -C "$APP_DIR" storage/app/private .env

# Conserver 30 jours
find "$BACKUP_DIR" -type f -mtime +30 -delete
```

Automatisation quotidienne (crontab de l'utilisateur qui a accès à MySQL) :

```cron
0 2 * * * /usr/local/bin/backup-etude-mahery.sh >> /var/log/backup-etude-mahery.log 2>&1
```

Copier régulièrement les archives sur un support externe chiffré. Une sauvegarde non
testée n'est pas une sauvegarde fiable.

### Restauration

```bash
# Base de données
gunzip < /var/backups/etude-mahery/db-2026-11-10_0200.sql.gz \
  | mysql -u etude_mahery -p etude_mahery

# Fichiers privés et configuration
tar -xzf /var/backups/etude-mahery/files-2026-11-10_0200.tar.gz \
  -C /var/www/etude-mahery

php artisan optimize:clear
php artisan migrate --force
```

## Tâches planifiées

Les rappels d'échéance sont envoyés par la commande :

```bash
php artisan app:send-deadline-reminders
```

Elle est planifiée chaque jour à 07:00 dans `routes/console.php`. Le planificateur
Laravel doit être appelé chaque minute par cron :

```cron
* * * * * cd /var/www/etude-mahery && php artisan schedule:run >> /dev/null 2>&1
```

Sans cette ligne, les rappels ne partent pas.

## Utilisation

### Parcours superviseur

1. Créer les étudiants depuis **Étudiants**.
2. Créer une matière, puis un ou plusieurs thèmes.
3. Créer un exercice (texte ou PDF), un quiz ou une tâche.
4. Publier l'activité pour la rendre visible aux étudiants actifs.
5. Corriger les soumissions, attribuer une note et un commentaire.
6. Consulter les statistiques, l'historique et le calendrier.
7. Envoyer des notifications manuelles ou ciblées.

### Parcours étudiant

1. Consulter le tableau de bord et les échéances.
2. Ouvrir un exercice, télécharger les pièces jointes et déposer une réponse.
3. Passer un quiz (plusieurs tentatives possibles).
4. Consulter les résultats, les corrections et les commentaires.
5. Suivre l'avancement des tâches.
6. Lire les notifications et l'historique.

## Règles de notation

- Chaque activité possède un barème libre (`max_points`).
- La note est convertie sur 20 pour l'affichage : `note / max_points × 20`.
- Seules les activités effectivement notées entrent dans les moyennes.
- **Exercice** : la dernière version notée fait foi pour la moyenne.
- **Quiz** : la meilleure tentative est utilisée pour la moyenne ; la dernière
  tentative, la moyenne des tentatives et le nombre de tentatives restent visibles.
- **Choix unique** : la bonne réponse vaut tous les points, sinon zéro.
- **Choix multiple** : formule de crédit partiel
  `points × (bonnes sélectionnées − mauvaises sélectionnées) / nombre de bonnes`,
  bornée entre 0 et le barème de la question.
- Les notes et les soumissions historiques sont conservées.

## Sécurité

- Protection CSRF sur tous les formulaires.
- Validation serveur via des Form Requests.
- Politiques d'accès et middleware de rôle pour séparer les espaces.
- Protection contre les accès directs (IDOR) : chaque ressource est vérifiée avant
  affichage ou téléchargement.
- Mots de passe hachés (bcrypt) ; aucun mot de passe en clair en base.
- Fichiers privés servis par des contrôleurs autorisés, avec en-têtes
  `X-Content-Type-Options: nosniff`.
- Contrôle du type MIME et de la taille des fichiers envoyés.
- Aucune bonne réponse de quiz n'est envoyée au navigateur avant la soumission.
- Inscription publique, réinitialisation par e-mail et vérification d'e-mail
  désactivées.
- Requêtes SQL via Eloquent/Query Builder (pas de concaténation SQL).

## Tests et qualité du code

```bash
# Suite complète (SQLite en mémoire)
php artisan test --compact

# Un fichier ou un test précis
php artisan test --compact tests/Feature/QuizFlowTest.php
php artisan test --compact --filter="a student can"

# Formatage PHP
vendor/bin/pint --format agent
```

La suite couvre notamment : authentification et rôles, gestion des étudiants,
soumissions d'exercices, quiz et notation, tâches et notifications, statistiques,
rendu de toutes les pages et filtres du calendrier.

## Dépannage

| Problème | Solution |
| --- | --- |
| `Unable to locate file in Vite manifest` | `npm run build` (ou `npm run dev`) |
| Erreur 500 après modification de `.env` | `php artisan optimize:clear` puis recharger |
| `SQLSTATE[HY000] [2002] Connection refused` | Vérifier que MySQL tourne et le port `DB_PORT` |
| Erreur de permissions sur `storage` | `sudo chown -R www-data:www-data storage bootstrap/cache` |
| Les rappels ne partent pas | Vérifier la ligne cron `schedule:run` et `php artisan schedule:list` |
| Un étudiant ne peut pas se connecter | Vérifier que son compte est actif (superviseur) |
| Page inaccessible depuis un autre poste | Vérifier l'IP, le pare-feu et que le serveur écoute sur l'interface LAN |
| Les modifications de style ne s'affichent pas | Relancer `npm run build` et vider le cache du navigateur |

Journaux utiles :

```bash
tail -f storage/logs/laravel.log
php artisan about
php artisan route:list
```
