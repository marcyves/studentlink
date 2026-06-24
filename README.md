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
php artisan migrate
php artisan serve
```

En dev (terminal séparé) :

```bash
npm run dev
php artisan serve
```

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
