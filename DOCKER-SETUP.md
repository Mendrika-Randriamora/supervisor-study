# Déploiement Docker — Espace Pédagogique

Guide complet pour cloner et lancer l'application sur un serveur, accessible depuis
le réseau via `http://<IP-SERVER>:8000`.

---

## 1. Prérequis

Sur le serveur (Linux) :

- Docker Engine 24+ et le plugin Docker Compose v2.
- Git.
- Le port **8000** libre.

Installation rapide sur Ubuntu/Debian :

```bash
sudo apt update
sudo apt install -y git
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker "$USER"   # puis se reconnecter
docker --version
docker compose version
```

---

## 2. Récupérer le projet

```bash
git clone <URL-DU-DEPOT> etude-mahery
cd etude-mahery
```

---

## 3. Configuration (optionnelle mais recommandée)

Le conteneur fonctionne sans fichier `.env`, mais un `.env` à la racine permet de
fixer l'URL publique :

```bash
cp .env.example .env
```

Modifie ensuite ces valeurs :

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.1.50:8000   # remplacer par l'IP réelle du serveur
APP_LOCALE=fr
APP_TIMEZONE=UTC
```

> Le fichier `.env` n'est **pas** copié dans l'image. Docker Compose l'utilise
> uniquement pour substituer `APP_URL` et `APP_KEY`.
>
> Sans `.env`, `APP_URL` vaut `http://localhost:8000` par défaut. Les liens générés
> par l'application restent corrects car Laravel utilise l'hôte de la requête.

La base de données est **SQLite**, stockée dans le volume `/data`. Aucun service
MySQL n'est nécessaire.

---

## 4. Construire et démarrer

```bash
docker compose up -d --build
```

Au premier démarrage, le conteneur :

1. copie `.env.example` en `.env` interne ;
2. génère une clé d'application stable dans `/data/app.key` ;
3. crée la base `/data/database.sqlite` ;
4. applique toutes les migrations ;
5. met en cache configuration, routes et vues ;
6. démarre Apache sur le port `80`, mappé sur `8000`.

Vérifier l'état :

```bash
docker compose ps
# STATUS attendu : Up ... (healthy), PORTS : 0.0.0.0:8000->80/tcp
```

---

## 5. Créer le premier superviseur

```bash
docker compose exec app php artisan app:create-supervisor
```

Aucune inscription publique n'existe : les comptes étudiants seront créés depuis
le tableau de bord du superviseur.

---

## 6. Accès depuis le réseau

1. Récupérer l'IP du serveur :

   ```bash
   hostname -I
   # ou
   ip -4 addr show | grep inet
   ```

2. Ouvrir le port dans le pare-feu :

   ```bash
   sudo ufw allow 8000/tcp
   sudo ufw status
   ```

3. Depuis un autre appareil du réseau :

   ```
   http://<IP-SERVER>:8000
   ```

Test rapide :

```bash
curl -I http://<IP-SERVER>:8000/login   # HTTP/1.1 200 OK
curl    http://<IP-SERVER>:8000/up      # page de santé
```

> Ne pas exposer ce port directement sur Internet. L'application est prévue pour
> un réseau local. Pour un accès distant, utiliser un VPN (WireGuard, Tailscale…)
> ou un reverse proxy avec HTTPS et authentification.

---

## 7. Volumes et persistance

| Volume | Point de montage | Contenu |
|---|---|---|
| `app-data` | `/data` | Base SQLite et clé d'application |
| `app-storage` | `/var/www/html/storage/app` | PDF, photos des copies, annotations |

```bash
docker volume ls | grep etude
```

Les volumes survivent à `docker compose down` et aux rebuilds. Ils ne sont
supprimés que par `docker compose down -v` (destructif, à éviter).

---

## 8. Sauvegarde et restauration

### Sauvegarde

```bash
mkdir -p backups
DATE=$(date +%F-%H%M)

# Base de données + clé d'application
docker run --rm \
  -v etude-mahery_app-data:/data:ro \
  -v "$PWD/backups":/backup \
  alpine tar czf "/backup/data-$DATE.tar.gz" -C /data .

# Fichiers privés (PDF, images, annotations)
docker run --rm \
  -v etude-mahery_app-storage:/storage:ro \
  -v "$PWD/backups":/backup \
  alpine tar czf "/backup/storage-$DATE.tar.gz" -C /storage .
```

> Le nom des volumes dépend du dossier du projet (`etude-mahery_app-data`).
> Vérifie avec `docker volume ls`.

### Restauration

```bash
docker compose stop app

docker run --rm \
  -v etude-mahery_app-data:/data \
  -v "$PWD/backups":/backup \
  alpine sh -c 'rm -rf /data/* && tar xzf /backup/data-AAAA-MM-JJ-HHMM.tar.gz -C /data'

docker run --rm \
  -v etude-mahery_app-storage:/storage \
  -v "$PWD/backups":/backup \
  alpine sh -c 'rm -rf /storage/* && tar xzf /backup/storage-AAAA-MM-JJ-HHMM.tar.gz -C /storage'

docker compose start app
```

---

## 9. Rappels d'échéance (planificateur)

Le conteneur ne lance pas de cron. Ajoute une tâche sur l'hôte :

```bash
crontab -e
```

```cron
* * * * * cd /chemin/vers/etude-mahery && docker compose exec -T app php artisan schedule:run >> /dev/null 2>&1
```

Cela exécute `app:send-deadline-reminders` chaque jour à 07:00 (voir
`routes/console.php`).

---

## 10. Mise à jour de l'application

```bash
git pull
docker compose up -d --build
```

Les migrations manquantes sont appliquées automatiquement au redémarrage.
Les volumes et les données sont conservés.

---

## 11. Commandes utiles

```bash
docker compose logs -f app                  # journaux en direct
docker compose exec app php artisan about   # environnement
docker compose exec app php artisan migrate:status
docker compose exec app sh                  # shell dans le conteneur
docker compose restart app                  # redémarrage
docker compose down                         # arrêt (volumes conservés)
```

---

## 12. Dépannage

### Le port 8000 est déjà utilisé

```bash
sudo ss -ltnp | grep :8000
```

Arrête le service concurrent ou change le mapping dans `docker-compose.yml` :

```yaml
ports:
  - "8080:80"
```

### Le conteneur redémarre en boucle

```bash
docker compose logs --tail=100 app
```

Causes fréquentes : erreur de syntaxe Apache, permissions sur les volumes,
migration en échec.

### Erreur 500 après restauration

La clé `APP_KEY` doit correspondre aux données restaurées. Elle est incluse dans
la sauvegarde de `/data`. Sinon :

```bash
docker compose down
docker compose up -d --force-recreate
```

Si `APP_KEY` est fourni dans `.env`, il prime sur la clé du volume.

### Page blanche / assets manquants

```bash
docker compose exec app php artisan optimize:clear
docker compose restart app
```

### Réinitialiser complètement (destructif)

```bash
docker compose down -v
docker compose up -d --build
docker compose exec app php artisan app:create-supervisor
```

---

## 13. Sécurité

- `APP_DEBUG=false` et `APP_ENV=production` par défaut dans l'image.
- Accès aux fichiers privés uniquement via les contrôleurs autorisés.
- Les uploads sont limités à 20 Mo (`docker/php/zz-app.ini`).
- Apache expose uniquement `public/`, les en-têtes de version sont masqués.
- L'application n'est pas destinée à être exposée directement sur Internet.
