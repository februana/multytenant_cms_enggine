# Architecture Documentation

## Overview

This wedding invitation application uses a **Single-Root Architecture** designed for security, maintainability, and ease of deployment. The repository root serves as the public document root, with clear separation between public assets and private application logic.

## Directory Structure

```
/ (Document Root)
├── index.php              # Frontend Entry Point (Public)
├── admin.php              # Admin Panel Redirect (Public) → redirects to /admin/
├── save.php               # AJAX Save Handler (Public) → includes app/save.php
├── messages.php           # Messages API Handler (Public) → includes app/messages.php
├── gallery.php            # Gallery API Handler (Public) → includes app/gallery.php
├── config.php             # Configuration Loader (Private Logic)
├── .htaccess              # Apache Security Rules
│
├── admin/                 # Admin Panel Implementation (PRIVATE - Blocked by Web Server)
│   ├── index.php          # Main Admin Panel UI
│   ├── backup.php         # Backup Handler
│   ├── restore.php        # Restore Handler
│   ├── qr.php             # QR Code Generator
│   ├── app.js             # Admin JavaScript
│   └── style.css          # Admin Stylesheet
│
├── app/                   # Application Logic (PRIVATE - Blocked by Web Server)
│   ├── config.php         # Internal Configuration Helper
│   ├── save.php           # Save Logic Implementation
│   ├── messages.php       # Messages Logic Implementation
│   ├── gallery.php        # Gallery Logic Implementation
│   ├── love-story.php     # Love Story API Implementation
│   └── index.php          # Frontend Rendering Logic (legacy, not used)
│
├── uploads/               # User Media Uploads (Public)
│   ├── cover/             # Cover Images
│   ├── music/             # Audio Files
│   ├── gallery/           # Gallery Photos
│   ├── background/        # Background Images
│   └── love-story/        # Love Story Images
│
├── config.json            # Main Configuration (PRIVATE - Blocked)
├── guest-links.json       # Guest Link Data (PRIVATE - Blocked)
├── database.sqlite        # RSVP Database (PRIVATE - Blocked)
├── event.ics              # Calendar Event File (Public)
│
├── backups/               # Backup Storage (PRIVATE - Blocked)
│
└── deploy/                # Deployment Scripts
    ├── install.sh         # Installation Script
    ├── backup.sh          # Backup Script
    ├── restore.sh         # Restore Script
    ├── update.sh          # Update Script
    └── health-check.sh    # Health Check Script
```

## Request Flow

### 1. Static Assets
```
User → Web Server → /assets/css/style.css → Served Directly
User → Web Server → /uploads/cover/image.jpg → Served Directly
```

### 2. Dynamic Pages
```
User → Web Server → / → index.php → load config.php → Render HTML
User → Web Server → /admin.php → redirects to /admin/index.php → Admin UI
```

### 3. API Endpoints
```
AJAX POST → /save.php → include app/save.php → Process → JSON Response
AJAX GET → /messages.php → include app/messages.php → Query DB → JSON Response
```

## Security Model

### Protected Resources
The following are **blocked** from direct web access:
- `/app/` directory (application source code)
- `*.json` files (configuration data)
- `*.sqlite` files (database)
- `/backups/` directory (backup archives)
- Hidden files (`.*`)

### Upload Security
- PHP execution is **disabled** in `/uploads/` directory
- Files are served as static content only
- Prevents Remote Code Execution (RCE) via file upload

### File Permissions
| Resource | Permission | Owner | Reason |
|----------|------------|-------|--------|
| `config.json` | `600` | www-data | Secrets, read-only by owner |
| `database.sqlite` | `600` | www-data | Database file |
| `uploads/` | `755` | www-data | Writable by web server |
| `app/` | `755` | root | Source code, read-only |

## Data Flow

### Configuration
1. Application loads `config.json` at runtime
2. Settings merged into global `$config` array
3. Used by all components for paths, theme, content

### Uploads
1. Admin panel receives file via `save.php`
2. File validated and moved to `/uploads/{type}/`
3. Path saved to `config.json`
4. Frontend reads path from config and displays

### RSVP
1. Guest submits form on frontend
2. Data inserted into `database.sqlite`
3. Admin views submissions via `admin.php`
4. Export available through admin panel

## Deployment Requirements

### Server Requirements
- **PHP**: 7.4 or higher
- **Extensions**: PDO, SQLite, JSON, GD (for image processing)
- **Web Server**: Nginx or Apache with mod_rewrite
- **Permissions**: Write access to `uploads/`, `config.json`, `database.sqlite`

### Web Server Configuration
- **Nginx**: Use `deploy/nginx-site.conf` template
- **Apache**: Enable `AllowOverride All` for `.htaccess` support

## Version History

- **2.0.0**: Single-root architecture consolidation
- **1.x**: Legacy dual-root architecture (deprecated)

## Maintenance

### Adding New Entry Points
1. Create wrapper in root: `new-feature.php`
2. Include app logic: `include './app/new-feature.php';`
3. Block direct app access (already configured)

### Backup Strategy
- Run `deploy/backup.sh` daily via cron
- Store backups off-server
- Test restore procedure quarterly

### Updates
1. Pull latest code from repository
2. Run `deploy/install.sh` to verify permissions
3. Clear any opcode caches
4. Verify health check passes
