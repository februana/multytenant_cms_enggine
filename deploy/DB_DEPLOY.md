# Database deployment & configuration

This document provides deployment examples and guidance for the SQLite database used by the application.

Overview

- The app resolves the database path by priority:
  1. `UNDANGAN_DB_PATH` environment variable (recommended)
  2. Legacy path: `app/storage/data/database.sqlite` (if present)
  3. Fallback: `app/database.sqlite`

Configure via environment

- Set `UNDANGAN_DB_PATH` in your runtime environment (systemd unit, Docker, container orchestrator, or hosting control panel).

Example: systemd unit excerpt

```
[Service]
Environment=UNDANGAN_DB_PATH=/var/www/private/database.sqlite
ExecStart=/usr/bin/php-fpm8.1 --nodaemonize
```

Apache example (with PHP-FPM)

Add or edit your VirtualHost and ensure PHP-FPM is used and the environment variable is passed (use `SetEnv` and `ProxyPassMatch` or use `systemd` env management):

```
<VirtualHost *:80>
    ServerName example.com
    DocumentRoot /var/www/februandik-web

    <Directory /var/www/februandik-web>
        Require all granted
        AllowOverride None
    </Directory>

    # PHP-FPM socket example
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.1-fpm.sock|fcgi://localhost/"
    </FilesMatch>

    # Ensure environment variable is available to PHP-FPM via systemd or php-fpm.conf
    # (Prefer setting in php-fpm pool or systemd service for security)
</VirtualHost>
```

Nginx example (recommended)

Example `server` block (snippet):

```
server {
    listen 80;
    server_name example.com;
    root /var/www/februandik-web;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param UNDANGAN_DB_PATH /var/www/private/database.sqlite; # optional, can be set in systemd
    }
}
```

Docker example (docker-compose)

```
version: '3.8'
services:
  web:
    image: php:8.1-fpm
    volumes:
      - ./app:/var/www/februandik-web
      - ./private:/var/www/private:rw
    environment:
      - UNDANGAN_DB_PATH=/var/www/private/database.sqlite
    ports:
      - "9000:9000"

  nginx:
    image: nginx:stable
    volumes:
      - ./app:/var/www/februandik-web:ro
      - ./deploy/nginx-site.conf:/etc/nginx/conf.d/default.conf:ro
    ports:
      - "80:80"
```

Shared hosting notes

- If you have PHP on shared hosting without system-level env config, add `UNDANGAN_DB_PATH` to your control panel environment settings if available. Otherwise, upload the canonical DB inside your home directory (outside `public_html`) and set `UNDANGAN_DB_PATH` to that path.
- If environment variables are unavailable, put the DB in `app/database.sqlite` but ensure the file and parent directory are not web-accessible.

Migration from old installs

1. Locate your old DB (common locations):
   - `app/storage/data/database.sqlite`
   - `app/database.sqlite`

2. Choose canonical target (recommended outside webroot, e.g. `/var/www/private/database.sqlite`).

3. Copy DB preserving permissions:

```
sudo mkdir -p /var/www/private
sudo cp app/storage/data/database.sqlite /var/www/private/database.sqlite
sudo chown www-data:www-data /var/www/private/database.sqlite
sudo chmod 640 /var/www/private/database.sqlite
```

4. Set `UNDANGAN_DB_PATH` to the chosen path (systemd, container env, or hosting panel).

5. Restart PHP-FPM / web server as appropriate.

Backward compatibility

- If `UNDANGAN_DB_PATH` is NOT set, the application will continue to prefer `app/storage/data/database.sqlite` if present for backward compatibility, otherwise it will fall back to `app/database.sqlite`.

Troubleshooting

- "Unable to open database" or permission errors: check owner and permissions for file and parent dir.
- If you see an empty list on `messages.php`: verify the DB path and that the DB file contains rows (use `sqlite3 /path/to/db "SELECT count(*) FROM tamu;"`).
- To inspect DB contents locally:

```
sqlite3 /path/to/database.sqlite
sqlite> .tables
sqlite> SELECT * FROM tamu LIMIT 10;
```

Questions

If you need an example tailored to your hosting environment (specific control panel, container orchestrator, or OS), tell me the environment and I will provide an adapted snippet.
