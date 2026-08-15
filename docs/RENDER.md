# Render Deployment

This project supports two deployment targets:

- Ubuntu/Linux server: `deploy/install.sh`
- Render: Docker using the repository `Dockerfile`

The Render deployment does not replace the existing Lightsail/Ubuntu deployment.

## Render settings

Create a **Web Service** from this repository and select:

- Language: **Docker**
- Dockerfile Path: `./Dockerfile`
- Docker Context: repository root
- Region: Singapore is recommended for Indonesia users
- Health Check Path: `/`

Render web services must listen on `0.0.0.0`. The container uses the Render `PORT` environment variable and defaults to `10000`.

## Blueprint

The repository includes `render.yaml`. It defines a Docker web service and can be used through Render Blueprints.

For a test deployment, the Blueprint uses the free plan and does not attach a persistent disk.

## Important: persistence

Render's default filesystem is ephemeral. A CMS deployment must not assume that runtime writes survive a restart or redeploy.

The container is prepared to store mutable application state under:

```text
/var/data
├── config.json
├── database.sqlite
├── guest-links.json
├── custom.css
├── uploads/
├── backups/
└── webdav/
```

The entrypoint maps those paths back into the application directory.

For a persistent production deployment, attach a Render Persistent Disk mounted at:

```text
/var/data
```

Only data under the mount path is persistent. Render documents that persistent disks are available on paid web services and that a disk is tied to a single service instance.

If no persistent disk is attached, the application can still be used for testing, but CMS changes, uploads, SQLite data, backups, and other mutable state can be lost when the service is redeployed or restarted.

## SQLite

The application continues to use SQLite. The runtime path is:

```text
/var/data/database.sqlite
```

This allows the same container image to run with either ephemeral storage for testing or a persistent disk for a single-instance production deployment.

Do not commit production database data to the repository.

## Composer

The Docker image runs:

```bash
composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
```

`composer.lock` remains the dependency source of truth. The generated `vendor/` directory is created during the image build and is not committed.

## Security

The container blocks public access to:

- `.env`
- `config.json`
- `database.sqlite`
- `guest-links.json`
- `backups/`
- `webdav/`

Application upload validation and CSRF protection remain part of the PHP application.

## Existing Ubuntu deployment

Do not use the Docker deployment as a replacement for the existing installer. Ubuntu deployment remains:

```text
~/webserver_undangan
        ↓
deploy/install.sh
        ↓
/var/www/wedding
```

The Render deployment is an additional target:

```text
GitHub
   ↓
Dockerfile
   ↓
Render Docker Web Service
```
