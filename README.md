# Wedding Invitation Web Application

A modern, single-page wedding invitation web application built with PHP and SQLite.

## Quick Start

### Prerequisites
- Ubuntu 24.04 server
- Root or sudo access
- Domain name pointing to your server (optional)

### Installation & Update Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    DEPLOYMENT LIFECYCLE                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  FRESH INSTALLATION                 UPDATES                     │
│  ─────────────────                  ───────                     │
│                                                                 │
│  1. Clone repository                1. Run update script        │
│     git clone <repo>                   sudo ./deploy/update.sh  │
│     cd webserver_undangan                                       │
│                                     2. Creates backup           │
│  2. Run installer (ONCE)            3. Downloads latest code    │
│     sudo ./deploy/install.sh        4. Runs composer install    │
│                                     5. Preserves user data:     │
│  3. Application deployed               - config.json            │
│     to /var/www/wedding              - guest-links.json         │
│                                     - database.sqlite          │
│  4. Save credentials                - uploads/                 │
│     shown at end of install          - backups/               │
│                                     - .env                     │
│  ✅ Installation complete           6. Restarts services        │
│                                     7. Runs health check        │
│                                                                 │
│  ⚠️  install.sh is ONE-TIME ONLY    ✅ Safe to run repeatedly   │
│     Do NOT use for updates                                      │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### One-Command Installation (Fresh Install Only)

```bash
git clone https://github.com/februana/webserver_undangan.git
cd webserver_undangan
sudo bash deploy/install.sh
```

The installer will:
1. Detect the repository location automatically
2. Install Nginx, PHP-FPM, and required extensions
3. Deploy the application to `/var/www/wedding`
4. Configure Nginx with security rules
5. Set proper file permissions
6. Initialize the database and configuration
7. **Generate a cryptographically secure administrator password** (if .env doesn't exist)

After installation, visit `http://your-server-ip/` to see your invitation.

> **Important**: If this is a fresh installation, the installer will display the generated administrator credentials at the end. **Save these credentials immediately** as they will not be displayed again.

### Updating an Existing Installation

**DO NOT use `install.sh` for updates.** It is designed for one-time installation only and will refuse to run if the application is already installed.

To update your installation safely:

```bash
cd /var/www/wedding
sudo ./deploy/update.sh
```

Or from any location:

```bash
sudo /var/www/wedding/deploy/update.sh
```

The update script will:
1. ✅ Verify the application is installed at `/var/www/wedding`
2. ✅ Create a backup using `backup.sh` before proceeding
3. ✅ Download the latest source code from GitHub
4. ✅ Run `composer install --no-dev --optimize-autoloader`
5. ✅ Copy only application files (preserving user data)
6. ✅ Preserve critical user data:
   - `config.json` - Your configuration
   - `guest-links.json` - Guest link data
   - `database.sqlite` - All RSVP and message data
   - `.env` - Environment settings
   - `event.ics` - Event calendar
   - `uploads/` - All uploaded media (images, music, etc.)
   - `backups/` - Previous backups
   - `storage/` - Storage directory if exists
7. ✅ Set correct ownership (`www-data`) and permissions
8. ✅ Restart PHP-FPM (auto-detects PHP version)
9. ✅ Reload Nginx configuration
10. ✅ Run health check to verify deployment
11. ✅ Clean up temporary files

If the health check fails, the script will:
- Display clear error messages
- Preserve the backup (do NOT delete it)
- Exit without removing the backup

The update script is **idempotent** - safe to run multiple times.

## Features

- 🎨 Beautiful single-page design
- 💌 RSVP management with SQLite database
- 📸 Photo gallery
- 🎵 Background music support
- 📅 Event calendar (.ics download)
- 🔒 Secure admin panel with password hashing
- 📱 Mobile-responsive design

## Documentation

- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Detailed deployment guide
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Technical architecture overview
- **[SECURITY.md](SECURITY.md)** - Security policies and best practices
- **[BACKUP_RESTORE.md](BACKUP_RESTORE.md)** - Backup and restore procedures

## Directory Structure

```
/var/www/wedding/           # Deployment directory
├── index.php              # Frontend entry point
├── admin.php              # Admin panel
├── save.php               # AJAX save handler
├── messages.php           # Messages endpoint
├── gallery.php            # Gallery endpoint
├── config.json            # Configuration (protected)
├── database.sqlite        # Database (protected)
├── .env                   # Environment variables (protected)
├── .env.example           # Example environment file
├── uploads/               # User media (public)
│   ├── cover/
│   ├── music/
│   ├── gallery/
│   └── background/
├── app/                   # Application logic (protected)
├── assets/                # Static CSS/JS/images
├── backups/               # Automated backups
└── deploy/                # Deployment scripts
```

## Administration

Access the admin panel at `http://your-domain/admin`.

### Administrator Credentials

#### Fresh Installation

During first installation, if no `.env` file exists:

1. The installer copies `.env.example` to `.env`
2. A cryptographically secure random password is generated using `openssl rand -base64 24`
3. The credentials are written to `.env`:
   ```bash
   ADMIN_USER=admin
   ADMIN_PASS=<generated random password>
   ```
4. The credentials are displayed once at the end of installation:
   ```
   ======================================
   Administrator account created
   
   Username:
   admin
   
   Password:
   xxxxxxxxxxxxxxxxxxxxxxxx
   
   Credentials have been saved to:
   
   /var/www/wedding/.env
   
   Save these credentials now.
   ======================================
   ```

**Save these credentials immediately** - they will not be displayed again.

The `.env` file serves as a recovery mechanism for:
- Initial login before password change
- Recovery if password is forgotten
- Maintenance operations
- Reinstallation scenarios

#### Existing Installation

For existing installations with `.env` already present:
- Username: `admin` (or as configured in `.env`)
- Password: As set in `.env` or changed via admin panel

### Authentication Priority

The authentication system uses the following priority:

1. **Priority 1**: `config.json` → `admin.password_hash`
   - If a password has been changed via the admin panel, this takes precedence
   - Uses secure bcrypt hashing
   - Once set, `ADMIN_PASS` in `.env` is ignored for login

2. **Priority 2**: `.env` file → `ADMIN_USER` + `ADMIN_PASS`
   - Used for initial login before password change
   - Falls back to default username `admin` if `ADMIN_USER` not set
   - Remains available for recovery purposes

3. **No Fallback**: If neither is configured, login is rejected
   - No hardcoded passwords
   - No silent fallback to insecure defaults

### Changing Administrator Password

1. Log into the admin panel
2. Navigate to Settings
3. Enter a new password in the "Admin Password" field
4. Save changes

After changing the password:
- The new password hash is stored in `config.json` under `admin.password_hash`
- The `.env` `ADMIN_PASS` is **ignored** for authentication
- Changing `ADMIN_PASS` in `.env` after setting a password hash will **NOT** change the login password
- The `.env` file remains for recovery, maintenance, and reinstallation purposes only

## Security

- Sensitive files (`config.json`, `database.sqlite`, `.env`) are blocked from public access
- PHP execution is disabled in the `uploads/` directory
- File permissions are automatically set to secure defaults
- Passwords are hashed using `password_hash()` with bcrypt
- Regular backups are recommended

See [SECURITY.md](SECURITY.md) for detailed security information.

## .env File Configuration

The `.env` file supports optional environment configuration:

```bash
# Administrator credentials (used until password is changed via admin panel)
ADMIN_USER=admin
ADMIN_PASS=your-secure-password-here

# WhatsApp configuration
WHATSAPP_NUMBER=6285162909164
WHATSAPP_MESSAGE=Assalamu'alaikum...

# Optional: Database path
UNDANGAN_DB_PATH=/var/www/private/database.sqlite

# Upload settings
MAX_UPLOAD_SIZE=5242880
SESSION_TIMEOUT=3600
ALLOWED_IMAGE_TYPES=jpg,jpeg,png,gif,webp
```

### Creating .env Manually

If you need to create `.env` manually:

```bash
cp .env.example .env
# Edit .env and set ADMIN_PASS to a secure password
chmod 600 .env
chown www-data:www-data .env
```

## Backup & Restore

### Create Backup
```bash
sudo /var/www/wedding/deploy/backup.sh
```

### Restore Backup
```bash
sudo /var/www/wedding/deploy/restore.sh /path/to/backup.tar.gz
```

See [BACKUP_RESTORE.md](BACKUP_RESTORE.md) for more details.

## Health Check

Verify your deployment:
```bash
sudo /var/www/wedding/deploy/health-check.sh
```

## Deployment Scripts Summary

| Script | Purpose | When to Use |
|--------|---------|-------------|
| `deploy/install.sh` | Fresh installation | **ONE-TIME ONLY** - Initial setup on new server |
| `deploy/update.sh` | Update existing installation | Every time you want to update to latest version |
| `deploy/backup.sh` | Create backup | Before updates, or regularly for safety |
| `deploy/restore.sh` | Restore from backup | When you need to recover from a backup |
| `deploy/health-check.sh` | Verify deployment health | After install/update, or anytime to check status |

### Quick Reference

```bash
# Fresh installation (run ONCE)
git clone https://github.com/februana/webserver_undangan.git
cd webserver_undangan
sudo ./deploy/install.sh

# Update existing installation (safe to run repeatedly)
sudo /var/www/wedding/deploy/update.sh

# Create backup before making changes
sudo /var/www/wedding/deploy/backup.sh

# Verify deployment health
sudo /var/www/wedding/deploy/health-check.sh
```

## Requirements

- PHP 8.1+ with extensions: `sqlite3`, `gd`, `mbstring`, `curl`
- Nginx web server
- SQLite3
- `jq` for health checks
- `openssl` for password generation during installation

## License

MIT License - See LICENSE file for details.

## Support

For issues and feature requests, please open an issue on GitHub.
