# StudentLink

Plateforme de collaboration académique — autonomie étudiante, évaluation par les pairs, supervision enseignante.

## Stack

- **Laravel 13** + **Inertia.js** + **React**
- **Tailwind CSS** (design system *Academic Autonomy*)
- **PostgreSQL** (production) · SQLite (dev local par défaut)
- **Laravel Breeze** (auth)

## Prérequis

- PHP 8.3+
- Composer
- Node.js 20+
- PostgreSQL (optionnel en local)

## Installation

```bash
git clone https://github.com/marcyves/studentlink.git
cd studentlink
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate --seed
php artisan serve
```

### Comptes démo (après seed)

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Professeur | `prof@studentlink.test` | `password` |
| Étudiant | `alice@studentlink.test` | `password` |

Code cours démo : **JOIN2026** · Code groupe : **BETA002**

En dev (terminal séparé ou `composer dev`) :

```bash
composer dev
# ou manuellement :
php artisan serve
php artisan reverb:start
npm run dev
```

### Chat temps réel (optionnel)

Par défaut `BROADCAST_CONNECTION=log` : le chat fonctionne sans serveur WebSocket.

Pour le **temps réel** (messages instantanés chez les autres membres) :

```bash
# .env
BROADCAST_CONNECTION=reverb
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

php artisan reverb:start   # ou composer dev
npm run dev                # rebuild si vars VITE changées
```

## Fonctionnalités MVP

- Tableaux de bord étudiant / professeur
- Rejoindre un cours (code) et un groupe (code invite)
- Grilles d'évaluation (critères pondérés)
- Évaluations par les pairs (inter-groupe et intra-groupe)
- Chat de groupe (temps réel via Reverb)
- Export CSV des notes (dashboard professeur)

## Rôles utilisateur

Colonne `users.role` : `student` (défaut) ou `professor`.

```bash
php artisan tinker
>>> App\Models\User::factory()->create(['role' => 'professor', 'email' => 'prof@example.com']);
```

## Documentation produit

Spécifications, maquettes Stitch et brief : voir le wiki Obsidian `Marc/Projet/StudentLink/`.

## Licence

MIT
