# LQA

This project runs with Docker Compose. It includes:

- **Traefik** – reverse proxy and routing
- **MySQL (db)** – database
- **Redis** – cache and queues
- **Backend** – Laravel API (PHP 8.3)
- **Queue** – Laravel queue worker
- **Website** – Next.js frontend

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/) (v2+)

## Quick start

### 1. Clone the repository

```bash
git clone <repository-url>
cd LQA
```

### 2. Create Docker environment file

Compose expects a file named `.env.docker` at the project root. Copy the example and edit as needed:

```bash
cp .env.docker.example .env.docker
```

### 3. Set Laravel application key

In `.env.docker`, replace `APP_KEY=GENERATE_A_KEY` with a valid Laravel key.

Generate one locally (with PHP) and paste it:

```bash
# If you have PHP and Composer (e.g. in backend/)
cd backend && php artisan key:generate --show
```

Or use a 32-character base64 string. Example format in `.env.docker`:

```env
APP_KEY=base64:your-generated-key-here
```

### 4. (Optional) Backend `auth.json` for Docker build

The backend image copies `backend/auth.json` during build. If that file is missing and the build fails, create it:

```bash
echo '{}' > backend/auth.json
```

Use real Composer auth credentials in `auth.json` if the project uses private packages.

### 5. Start the stack

From the project root:

```bash
docker compose up -d
```

First run will build the backend and website images and may take a few minutes.

## Docker Profiles (Team Workflows)

To save resources and accommodate different development needs, this project uses [Docker Compose Profiles](https://docs.docker.com/compose/profiles/). Services are grouped into `backend` and `frontend` profiles.

| Profile | Purpose | Services Included |
|:--- |:--- |:--- |
| **`backend`** | API development / Local Frontend dev | `traefik`, `db`, `redis`, `backend`, `queue`, `mailpit` |
| **`frontend`** | Frontend preview / Local Backend dev | `traefik`, `website` |

### How to use:

*   **Frontend Developers** (Needs API only):
    ```bash
    docker compose --profile backend up -d
    ```
*   **Backend Developers** (Needs Website only):
    ```bash
    docker compose --profile frontend up -d
    ```
*   **Full Stack / QA** (Everything):
    ```bash
    docker compose --profile backend --profile frontend up -d
    ```

---

## Access the app

| Service            | URL                        |
|--------------------|----------------------------|
| **Website**        | http://omcp.localhost      |
| **API**            | http://api.omcp.localhost  |
| **Mailpit (UI)**   | http://mail.omcp.localhost |
| **Traefik dashboard** | http://localhost:8082   |

Ensure `omcp.localhost`, `api.omcp.localhost`, and `mail.omcp.localhost` resolve to `127.0.0.1` (e.g. add them to `/etc/hosts` or use a hosts file manager).

**Default super admin** (from `.env.docker`):

- Email: `admin@admin.com`
- Password: `changePASS` (change in `.env.docker`: `SUPER_ADMIN_EMAIL`, `SUPER_ADMIN_PASSWORD`)

## Development: Live Reload (Sail-style)

To make changes to the backend code without rebuilding the PHP image, this project uses a `docker-compose.override.yml` file.

### 1. Enable Volume Mounting
The `docker-compose.override.yml` file (included in the repo) automatically mounts the `./backend` directory to `/var/www/html` in the `backend` and `queue` containers.

### 2. Install Dependencies
When you first run `docker compose up`, the backend entrypoint will detect if the `vendor` directory is missing and run `composer install` automatically.

Alternatively, if you have PHP/Composer installed locally, you can run:
```bash
cd backend && composer install
```
This will populate the `vendor` directory on your host, which will then be visible inside the containers via the volume mount.

### 3. Workflow
Once the stack is running, any changes you make to files in the `backend/` directory will be reflected immediately in the API and queue worker.

---

## Useful commands

| Command | Description |
|--------|-------------|
| `docker compose up -d` | Start all services in the background |
| `docker compose down` | Stop and remove containers |
| `docker compose ps` | List running services |
| `docker compose logs -f [service]` | Follow logs (e.g. `backend`, `website`, `db`) |
| `docker compose build --no-cache` | Rebuild images from scratch |

## Environment variables (`.env.docker`)

The `.env.docker` file contains all configuration for the Docker stack. Below are the key groups of variables:

### 1. General Configuration
*   **`APP_DOMAIN`**: The base domain used for Traefik routing (e.g., `omcp.localhost`).
*   **`APP_KEY`**: Laravel application encryption key (Required).
*   **`APP_URL`**: The public-facing URL of the API.

### 2. Database (MySQL)
*   **`DB_HOST`**: Set to `db` (the service name) when running inside Compose, or `127.0.0.1`/`host.docker.internal` for external access.
*   **`MYSQL_ROOT_PASSWORD`**: Password for the database root user.
*   **`MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`**: Credentials for the application database.

### 3. Services
*   **`REDIS_HOST`**: Set to `redis`.
*   **`MAIL_HOST`**: Set to `mailpit` to catch all outgoing emails.
*   **`MAILPIT_UI_USERNAME` / `MAILPIT_UI_PASSWORD`**: Credentials for the Mailpit web dashboard (default: `admin`/`password`).
*   **`RUN_QUEUE_WORKER`**: A boolean flag. If `true`, the container will start the queue worker; if `false`, it starts the web server (FPM/Nginx).

### 4. Website (Frontend)
*   **`NEXT_PUBLIC_API_BASE_URL`**: The URL the frontend uses to contact the API.
*   **`NEXT_PUBLIC_IMAGE_BASE_URL`**: The URL for serving uploaded images.

### 5. Google Cloud Storage
*   **`USE_BASSET_CLOUD`**: Set to `true` to enable GCS storage for assets.
*   **`GOOGLE_CLOUD_PROJECT_ID`, `GOOGLE_CLOUD_STORAGE_BUCKET`, etc.**: Credentials for GCS integration.

## First run and database

On first start, the backend entrypoint runs `php artisan app:setup-application`, which:

- Runs migrations if the migrations table is missing.
- Runs version-specific steps (e.g. migrations and seeds on version changes).
- Imports flat-file content (Statamic/collections, etc.).

No manual migrate/seed is required for a standard Docker-based run.

## Troubleshooting

- **Website/API not loading:** Check that Traefik is up (`docker compose ps`) and that you’re using the URLs above (including `omcp.localhost` / `api.omcp.localhost`).
- **502 / connection errors:** Wait for `db` and `redis` to be healthy, then restart backend: `docker compose restart backend queue`.
- **Backend build fails on `auth.json`:** Create `backend/auth.json` with at least `{}` (see step 4).
- **Logs:** Use `docker compose logs -f backend` or `docker compose logs -f website` to inspect errors.
