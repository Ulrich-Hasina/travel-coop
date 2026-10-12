# Coopérative de transport : API de réservation

Application de réservation de voyages pour une coopérative de transport.
Un voyageur crée un compte, recherche un départ, réserve pour une ou plusieurs personnes et peut annuler.
Un administrateur gère les lignes, les départs et les tarifs.

Ce dépôt contient pour l'instant le **back-end** (API REST). Le front Vue.js et l'interface d'administration sont prévus (voir la feuille de route).

## Sommaire 

1. [Stack technique](#stack-technique)
2. [Installer le projet](#installer-le-projet)
3. [Essayer l'API](#essayer-lapi)
4. [Lancer les tests](#lancer-les-tests)
5. [Fonctionnement du projet](#fonctionnement-du-projet)
6. [Structure du dépôt](#structure-du-dépôt)
7. [Problèmes fréquents](#problèmes-fréquents)
8. [Feuille de route](#feuille-de-route)

## Stack technique

| Rôle | Outil |
|---|---|
| Langage et framework | PHP 8.4, Symfony 8.1 |
| Base de données | PostgreSQL, Doctrine ORM et migrations |
| API | API Platform 5 (JSON-LD / Hydra) |
| Authentification | JWT (`lexik/jwt-authentication-bundle`) |
| Tests | PHPUnit 13, `api-platform/test` |
| Schéma (dev) | `jawira/doctrine-diagram-bundle` |
| Front (à venir) | Vue 3, TypeScript, Vite |

## Installer le projet

### Prérequis

- Git
- PHP 8.4 ou plus, avec les extensions `intl`, `mbstring`, `xml`, `curl`, `zip` et `pgsql`
- Composer 2 (préférer la version officielle de getcomposer.org à celle d'`apt`, trop ancienne pour PHP 8.4)
- PostgreSQL
- Symfony CLI (facultatif, mais pratique : `symfony serve`)
- `jq` (facultatif, pour lire les réponses JSON dans le terminal)

Sous Ubuntu :

```bash
sudo apt install git php8.4-cli php8.4-intl php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-pgsql jq
```

### 1. Cloner le dépôt

```bash
git clone <URL_DU_DEPOT> cooperative
cd cooperative
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer la base de données

Crée un fichier `.env.local` à la racine (il n'est jamais versionné) qui contient uniquement ta connexion :

```dotenv
DATABASE_URL="postgresql://UTILISATEUR:MOT_DE_PASSE@127.0.0.1:5432/cooperative?serverVersion=16&charset=utf8"
```

Adapte l'utilisateur, le mot de passe et `serverVersion` à ton installation.

Si tu n'as pas PostgreSQL en local, tu peux en lancer un avec Docker :

```bash
docker run --name coop-db \
  -e POSTGRES_USER=coop -e POSTGRES_PASSWORD=coop -e POSTGRES_DB=cooperative \
  -p 5432:5432 -d postgres:16
```

Le `DATABASE_URL` correspondant est alors `postgresql://coop:coop@127.0.0.1:5432/cooperative?serverVersion=16&charset=utf8`.

### 4. Créer la base et le schéma

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
```

### 5. Générer les clés JWT

Les clés ne sont pas dans le dépôt (elles sont ignorées par Git). Chaque installation génère les siennes :

```bash
php bin/console lexik:jwt:generate-keypair
```

Les fichiers sont créés dans `config/jwt/`. La phrase secrète vient de `JWT_PASSPHRASE` (fichier `.env`). En production, définis-la dans les variables d'environnement ou dans `.env.local`, jamais dans le dépôt.

### 6. Charger des données de démonstration (développement uniquement)

```bash
php bin/console doctrine:fixtures:load -n
```

Attention : cette commande **efface toutes les données** avant de charger les nouvelles.

Elle crée :

| Compte | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@example.com` | `admin1234` |
| Voyageur | `voyageur@example.com` | `voyageur1234` |

Elle crée aussi 3 lignes, des départs sur les 7 jours suivants (deux par jour et par ligne), un départ à tarif spécial, un départ annulé et une réservation d'exemple. Les dates sont calculées à partir du jour du chargement : relance la commande quand les départs sont passés.

Ces comptes et mots de passe servent uniquement en local.

### 7. Lancer le serveur

```bash
symfony serve -d
```

L'API répond sur `https://127.0.0.1:8000` (certificat local auto-signé, d'où l'option `-k` de `curl`). La documentation interactive est sur `https://127.0.0.1:8000/api/docs`.

Pour arrêter le serveur : `symfony server:stop`.

## Essayer l'API

```bash
# 1. Se connecter et récupérer un jeton
TOKEN=$(curl -sk -X POST https://127.0.0.1:8000/api/login_check \
  -H "Content-Type: application/json" \
  -d '{"email":"voyageur@example.com","password":"voyageur1234"}' | jq -r .token)

# 2. Lister les départs ouverts (accès public)
curl -sk "https://127.0.0.1:8000/api/departures?status=scheduled" \
  -H "Accept: application/ld+json" | jq '.member[0]'

# 3. Réserver 2 places (prends l'id d'un départ futur dans la liste précédente)
curl -sk -X POST https://127.0.0.1:8000/api/bookings \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/ld+json" -H "Accept: application/ld+json" \
  -d '{"departure":"/api/departures/ID","numberOfPersons":2}' | jq .
```

### Routes disponibles

| Méthode | Route | Accès | Rôle |
|---|---|---|---|
| `POST` | `/api/register` | public | Créer un compte voyageur |
| `POST` | `/api/login_check` | public | Obtenir un jeton JWT |
| `GET` | `/api/departures` | public | Rechercher les départs (filtres ci-dessous) |
| `GET` | `/api/departures/{id}` | public | Détail d'un départ |
| `POST` | `/api/bookings` | connecté | Réserver pour 1 à N personnes |
| `GET` | `/api/bookings` | connecté | Lister **ses** réservations |
| `GET` | `/api/bookings/{id}` | propriétaire | Détail d'une réservation |
| `POST` | `/api/bookings/{id}/cancel` | propriétaire | Annuler une réservation |

Filtres de `GET /api/departures` : `line.origin`, `line.destination` (insensible à la casse), `status`, `departureAt[after]`, `departureAt[before]`.

Exemple : `/api/departures?line.origin=Ville%20A&status=scheduled&departureAt[after]=2026-10-10`.

L'API répond par défaut en JSON-LD : envoie `Accept: application/ld+json` (et `Content-Type: application/ld+json` pour les requêtes à API Platform).

## Lancer les tests

Les tests utilisent une base dédiée (`cooperative_test`), séparée de ta base de développement.

```bash
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:migrations:migrate --env=test -n
php bin/phpunit --testdox
```

Pense à relancer la migration en environnement de test à chaque nouvelle migration.

Les tests couvrent :

- **Réservation** : succès avec prix calculé par le serveur, refus si le départ est complet, annulé, passé, ou si le nombre de personnes est invalide.
- **Sécurité** : accès sans jeton, isolation entre voyageurs (liste et lecture), refus du mauvais mot de passe.
- **Inscription** : jamais de rôle administrateur, email unique, validation, téléphone obligatoire, connexion du compte créé.
- **Annulation** : propriétaire uniquement, places restituées, double annulation, délai limite.
- **Places restantes** d'un départ (test unitaire).

Un point reste à vérifier à la main, car PHPUnit ne sait pas lancer de vraies requêtes en parallèle : la **réservation simultanée** (voir `Règles métier`).

## Fonctionnement du projet

### Rôles

- **Voyageur** (`ROLE_USER`) : crée un compte, recherche un départ, réserve pour lui et d'autres personnes, consulte et annule ses réservations.
- **Administrateur** (`ROLE_ADMIN`) : gère les lignes, les départs et les tarifs, et doit pouvoir appeler les voyageurs (le téléphone est donc obligatoire à l'inscription).

Le rôle d'un nouveau compte est **toujours fixé par le serveur** : un client ne peut pas s'attribuer `ROLE_ADMIN`.

### Modèle de données

```mermaid
erDiagram
    USER ||--o{ BOOKING : "réserve"
    LINE ||--o{ DEPARTURE : "a des départs"
    DEPARTURE ||--o{ BOOKING : "reçoit"

    USER {
        int id
        string email "unique"
        string password "haché"
        json roles
        string firstName
        string lastName
        string phone "obligatoire"
    }
    LINE {
        int id
        string origin
        string destination
        decimal basePrice
        int normalDuration "minutes"
    }
    DEPARTURE {
        int id
        datetime departureAt
        int seats
        decimal price "facultatif"
        string status "scheduled, cancelled, completed"
    }
    BOOKING {
        int id
        int numberOfPersons
        decimal totalPrice
        string status "pending, confirmed, cancelled"
        datetime createdAt
    }
```

- Une **ligne** est un trajet type (A vers B) avec son tarif de base.
- Un **départ** est une ligne à une date et une heure précises : c'est lui qu'on réserve.
- Une **réservation** appartient à un voyageur, vise un départ et compte `numberOfPersons` places. L'accompagnant n'a pas de compte : il est seulement compté.

Pour régénérer le schéma depuis les entités : `php bin/console doctrine:diagram:er`.

### Règles métier

- **Places restantes** d'un départ = `seats` moins la somme des `numberOfPersons` des réservations non annulées. Ce champ calculé (`availableSeats`) est exposé par l'API.
- **Prix** : le prix d'une personne est celui du départ s'il existe, sinon celui de la ligne (`effectivePrice`). Le total est calculé **par le serveur**, enregistré dans la réservation et figé : un changement de tarif ne modifie pas les anciennes réservations.
- **Une réservation est refusée** si le départ n'est plus `scheduled` (422), s'il est déjà passé (422), si le nombre de personnes est hors limites (422) ou s'il ne reste pas assez de places (409). La limite de personnes par réservation est la constante `MAX_PERSONS_PER_BOOKING` de `BookingService`.
- **Surréservation** : la réservation s'exécute dans une transaction qui verrouille la ligne du départ (verrou pessimiste). Deux réservations simultanées sur la dernière place sont donc traitées l'une après l'autre.
- **Annulation** : réservée au propriétaire, impossible deux fois, et refusée moins de 2 heures avant le départ (constante `CANCELLATION_DEADLINE_HOURS` de `BookingService`). Les places sont restituées automatiquement.

Test manuel de la réservation simultanée (remplace `N` par l'id d'un départ futur avec au moins 15 places libres) : six demandes de 5 places en parallèle doivent donner exactement **trois `201` et trois `409`**.

```bash
seq 1 6 | xargs -P 6 -I{} curl -sk -o /dev/null -w "%{http_code}\n" \
  -X POST https://127.0.0.1:8000/api/bookings \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/ld+json" \
  -d '{"departure":"/api/departures/N","numberOfPersons":5}'
```

### Sécurité

- Authentification **sans état** par jeton JWT, valable 1 heure (`token_ttl`).
- Un voyageur ne voit que ses propres réservations (extension Doctrine sur la liste, contrôle `security` sur la lecture individuelle).
- Les données du voyageur (téléphone, email) ne sont jamais exposées par l'API publique.
- Les clés JWT et `.env.local` ne sont jamais versionnés.

## Structure du dépôt

```text
src/
├── Controller/     inscription et annulation d'une réservation
├── DataFixtures/   données de démonstration
├── Doctrine/       extension de requête (réservations du voyageur connecté)
├── Dto/            données d'entrée de l'inscription
├── Entity/         User, Line, Departure, Booking
├── Enum/           DepartureStatus, BookingStatus
├── Repository/     requêtes Doctrine (comptage des places prises)
├── Service/        BookingService : règles de réservation et d'annulation
└── State/          processeur API Platform de création de réservation
tests/
├── Api/            tests fonctionnels (réservation, inscription, annulation)
└── Entity/         tests unitaires
config/             sécurité, API Platform, JWT
migrations/         historique du schéma de la base
```

## Problèmes fréquents

| Symptôme | Cause et solution |
|---|---|
| `curl` n'affiche rien | Le serveur est en HTTPS : utilise `https://` et l'option `-k`. |
| `curl: (3) bad range in URL` | Les crochets des filtres sont interprétés par `curl` : ajoute `-g`. |
| `406 Requested format "application/json" is not supported` | Envoie `Accept: application/ld+json`. |
| `401 JWT Token not found` | Pas de jeton dans la requête : récupère-en un avec `/api/login_check`. |
| `Unable to create a signed JWT` | Clés absentes ou passphrase différente : `php bin/console lexik:jwt:generate-keypair --overwrite`. |
| `database is being accessed by other users` | Une autre connexion est ouverte (DBeaver, `psql`) : ferme-la avant de supprimer la base. |
| Les départs de démonstration sont passés | Relance `php bin/console doctrine:fixtures:load -n`. |
| Les tests échouent après une migration | Applique-la aussi en test : `php bin/console doctrine:migrations:migrate --env=test -n`. |

## Feuille de route

- [x] Modèle de données, migrations, fixtures
- [x] Authentification JWT
- [x] Inscription
- [x] Recherche des départs (filtres, places restantes, prix effectif)
- [x] Réservation avec règles métier et protection contre la surréservation
- [x] Isolation entre voyageurs
- [x] Annulation d'une réservation
- [x] Tests automatisés
- [ ] CORS (`nelmio/cors-bundle`) pour le front
- [ ] Front Vue 3 : recherche, connexion, réservation, mes réservations
- [ ] Administration avec EasyAdmin (lignes, départs, tarifs, réservations avec le téléphone du voyageur)
- [ ] Tableau de bord pour la coopérative (départs à venir avec remplissage, réservations par jour, recettes)
- [ ] Limitation du nombre de tentatives (inscription et connexion) avant la mise en production