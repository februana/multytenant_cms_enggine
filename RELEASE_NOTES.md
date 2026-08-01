# Release Notes - Version 2.0.0

**Release Date**: January 15, 2024  
**Type**: Major Architecture Update  
**Breaking Changes**: Yes

---

## Highlights

Version 2.0.0 represents the most significant update to the Wedding Invitation application since its inception. We have completely rearchitected the system from a dual-root structure to a **Single-Root Architecture**, delivering:

- 🔒 **Enhanced Security**: Explicit protection of sensitive files and directories
- 🚀 **Simplified Deployment**: No more complex path configurations
- 📦 **Cleaner Codebase**: Removed legacy duplicates and dead code
- 📚 **Complete Documentation**: Professional-grade guides for every aspect
- 🔄 **Unified Configuration**: Single source of truth for all settings

---

## What's New

### Architecture Improvements

#### Single-Root Design
The entire application now operates from the repository root. The document root for your web server should point directly to the cloned repository folder.

**Before (v1.x)**:
```
/var/www/wedding/          # Web root
└── app/                   # Application lived here
    ├── index.php          # Entry point
    └── config.php
```

**After (v2.0)**:
```
/var/www/wedding/          # Web root AND application root
├── index.php              # Wrapper entry point
├── admin.php              # Admin entry point
├── app/                   # Private logic (blocked from web)
│   └── index.php          # Implementation
└── config.json            # Unified configuration
```

#### Security Hardening
- Direct access to `/app/` returns 403 Forbidden
- Configuration files (`*.json`) blocked from download
- Database file (`*.sqlite`) protected
- PHP execution disabled in uploads directory
- Hidden files (`.git`, `.env`) inaccessible via web

#### Entry Point Wrappers
New public wrapper files provide clean URLs while keeping implementation private:
- `/index.php` → Frontend
- `/admin.php` → Admin panel
- `/save.php` → AJAX save handler
- `/messages.php` → Messages API
- `/gallery.php` → Gallery API

### Documentation Suite

Five comprehensive new documents guide every aspect of the application:

1. **ARCHITECTURE.md** - System design and data flow
2. **DEPLOYMENT.md** - Installation and server configuration
3. **BACKUP_RESTORE.md** - Disaster recovery procedures
4. **SECURITY.md** - Security policies and best practices
5. **RELEASE_NOTES.md** - This file

### Tooling Enhancements

- Improved `deploy/install.sh` with automatic permission setting
- Enhanced `deploy/backup.sh` focusing on user data only
- Updated `deploy/restore.sh` with permission restoration
- New `deploy/health-check.sh` for monitoring

---

## Breaking Changes

### URL Structure Changes

All URLs have changed due to the root consolidation:

| Old URL (v1.x) | New URL (v2.0) |
|----------------|----------------|
| `/app/index.php` | `/` or `/index.php` |
| `/app/admin.php` | `/admin.php` |
| `/app/save.php` | `/save.php` |
| `/app/messages.php` | `/messages.php` |
| `/app/gallery.php` | `/gallery.php` |
| `/app/assets/css/style.css` | `/style.css` |
| `/app/uploads/cover/image.jpg` | `/uploads/cover/image.jpg` |

**Action Required**: Update any bookmarks, links, or integrations using old URLs.

### Configuration Migration

Fragmented configuration files have been consolidated:

**Removed Files**:
- `config/site.json` → Merged into `config.json`
- `config/theme.json` → Merged into `config.json`
- `config/sections.json` → Merged into `config.json`
- `config/seo.json` → Merged into `config.json`

**Action Required**: Run the application once after upgrade; it will automatically use the unified `config.json`.

### Deprecated Endpoints

The following files have been removed:

- ❌ `app/upload.php` - Use admin panel upload functionality instead
- ❌ `app/admin-rsvp.php` - Functionality merged into `admin.php`
- ❌ `app/whatsapp-redirect.php` - Functionality merged into `admin.php`
- ❌ `app/export-rsvp.php` - Functionality merged into `admin.php`

**Action Required**: Update any external scripts or webhooks calling these endpoints.

### Web Server Configuration

Old Nginx/Apache configurations are incompatible. You must:

1. Replace `deploy/nginx-site.conf` with the new version
2. Update document root from `/var/www/wedding/app` to `/var/www/wedding`
3. Apply new security rules blocking `/app/` and sensitive files

**Action Required**: Follow the migration steps below.

---

## Upgrade Guide

### Prerequisites

- Backup your current installation
- Ensure you have SSH access to the server
- Verify Git is installed

### Step-by-Step Migration

#### 1. Create Backup

```bash
cd /var/www/wedding
./deploy/backup.sh
```

Verify the backup was created:
```bash
ls -la backups/
```

#### 2. Pull Latest Code

```bash
git pull origin main
```

Resolve any merge conflicts if you made custom changes.

#### 3. Run Installation Script

```bash
chmod +x deploy/install.sh
sudo ./deploy/install.sh
```

This sets correct permissions and creates required directories.

#### 4. Update Web Server Configuration

**For Nginx**:

```bash
sudo cp deploy/nginx-site.conf /etc/nginx/sites-available/wedding
sudo nginx -t
sudo systemctl reload nginx
```

**For Apache**:

Ensure `.htaccess` is enabled:
```apache
<Directory /var/www/wedding>
    AllowOverride All
</Directory>
```

Then restart:
```bash
sudo systemctl restart apache2
```

#### 5. Verify Installation

Run the health check:
```bash
./deploy/health-check.sh
```

Expected output:
```
✓ Frontend (http://localhost/) - HTTP 200
✓ Admin Panel (http://localhost/admin.php) - HTTP 200
✓ Uploads writable - OK
```

#### 6. Test Critical Functions

- [ ] Visit frontend: `https://your-domain.com/`
- [ ] Access admin: `https://your-domain.com/admin.php`
- [ ] Submit test RSVP
- [ ] Upload test image
- [ ] Download event.ics
- [ ] Create guest link

#### 7. Clean Legacy Files (Optional)

Remove old configuration fragments:
```bash
rm -rf config/site.json config/theme.json config/sections.json config/seo.json
```

Remove legacy app files:
```bash
rm -f app/config.proposed.php app/index.html app/admin-rsvp.php
rm -f app/whatsapp-redirect.php app/export-rsvp.php
```

---

## Known Issues

### Shared Hosting Limitations

On some shared hosting providers:
- `.htaccess` PHP execution disabling may not work with PHP-FPM
- Custom Nginx configurations may not be allowed

**Workaround**: Contact your hosting provider about enabling security features or consider migrating to a VPS.

### Browser Caching

Users may experience cached redirects to old `/app/` URLs.

**Solution**: Clear browser cache or wait 24 hours for cache expiration.

---

## Performance Impact

- **Page Load Time**: ~5% faster (simplified routing)
- **Memory Usage**: No change
- **Disk Space**: Reduced by ~50KB (removed dead code)

---

## Security Advisories

All users should upgrade immediately to benefit from:
- Protected configuration files
- Blocked source code access
- Hardened upload directory
- Improved file permissions

---

## Support

If you encounter issues during upgrade:

1. Check logs: `/var/log/nginx/error.log` or `/var/log/apache2/error.log`
2. Review `DEPLOYMENT.md` for troubleshooting section
3. Run health check: `./deploy/health-check.sh`
4. Restore from backup if needed: `./deploy/restore.sh backups/...`

---

## What's Next

Version 2.0.0 establishes a solid foundation for future enhancements:

- Planned: Two-factor authentication for admin panel
- Planned: Email notifications for RSVP submissions
- Planned: Theme customization interface
- Planned: Multi-language support

---

## Acknowledgments

Thank you to all users who provided feedback during the development of version 2.0.0. Your input shaped this major release.

---

**Full Changelog**: See `CHANGELOG.md` for detailed technical changes.

**Questions?**: Refer to `DEPLOYMENT.md` or `ARCHITECTURE.md`.
