# Repository Guidelines

## Project Structure & Module Organization
This is a monorepo containing a full-stack application orchestrated by **Docker Compose** and **Traefik**.

- **`backend/`**: A **Laravel 12** and **Statamic 5** (CMS) application. It uses **Inertia.js** with **Vue 3** for the control panel/dashboard and serves as the API for the frontend.
- **`website/`**: A **Next.js 15** frontend using **React 19** and **Tailwind CSS 4**.
- **`dbms/`**: Contains database initialization scripts and schema definitions.
- **`deploy/`**: CI/CD configurations for Google Cloud Build.

The services are routed via Traefik to local domains such as `omcp.localhost` and `api.omcp.localhost`.

## Build, Test, and Development Commands

### Root (Docker)
- **Start all services**: `docker compose up -d`
- **Start specific profiles**: `docker compose --profile backend up -d` or `docker compose --profile frontend up -d`
- **Follow logs**: `docker compose logs -f [service]`

### Backend (`/backend`)
- **Install dependencies**: `composer install` && `npm install`
- **Run dev server (Vite)**: `npm run dev`
- **Run tests**: `php artisan test` or `./vendor/bin/phpunit`
- **Run linter**: `./vendor/bin/pint`

### Website (`/website`)
- **Install dependencies**: `npm install`
- **Run dev server**: `npm run dev`
- **Build production**: `npm run build`
- **Run linter**: `npm run lint`

## Coding Style & Naming Conventions
- **PHP**: Follows Laravel standards (PSR-12/PER). Enforced by **Laravel Pint**.
- **JavaScript/React**: Follows Next.js and React best practices. Enforced by **ESLint**.
- **CSS**: Uses **Tailwind CSS** across both applications.
- **Commits**: Follow **Conventional Commits** (e.g., `feat:`, `fix:`, `refactor:`, `docs:`).

## Testing Guidelines
- **Backend**: Uses **PHPUnit**. Tests are located in `backend/tests/Unit` and `backend/tests/Feature`.
- Always run `php artisan test` before submitting backend changes.

## Commit & Pull Request Guidelines
- Use descriptive commit messages with the appropriate prefix.
- Ensure all linting (`npm run lint` or `./vendor/bin/pint`) and tests pass before pushing.
- Prefer small, focused pull requests that address a single feature or bug.
