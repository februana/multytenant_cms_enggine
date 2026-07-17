# Wedding Invitation Web Application

A modern, single-page wedding invitation web application built with PHP and SQLite.

## Quick Start

### Prerequisites
- Ubuntu 24.04 server
- Root or sudo access
- Domain name pointing to your server (optional)

### One-Command Installation

```bash
cd /tmp
git clone https://github.com/yourusername/wedding-invitation.git temp-wedding
cd temp-wedding
sudo bash deploy/install.sh
```

The installer will:
1. Install Nginx, PHP-FPM, and required extensions
2. Deploy the application to `/var/www/wedding`
3. Configure Nginx with security rules
4. Set proper file permissions
5. Initialize the database and configuration

After installation, visit `http://your-server-ip/` to see your invitation.

## Features

- 🎨 Beautiful single-page design
- 💌 RSVP management with SQLite database
- 📸 Photo gallery
- 🎵 Background music support
- 📅 Event calendar (.ics download)
- 🔒 Secure admin panel
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

Access the admin panel at `http://your-domain/admin.php`.

Default credentials are set during installation. Change them immediately after first login.

## Security

- Sensitive files (`config.json`, `database.sqlite`) are blocked from public access
- PHP execution is disabled in the `uploads/` directory
- File permissions are automatically set to secure defaults
- Regular backups are recommended

See [SECURITY.md](SECURITY.md) for detailed security information.

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

## Requirements

- PHP 8.1+ with extensions: `sqlite3`, `gd`, `mbstring`, `curl`
- Nginx web server
- SQLite3
- `jq` for health checks

## License

MIT License - See LICENSE file for details.

## Support

For issues and feature requests, please open an issue on GitHub.
