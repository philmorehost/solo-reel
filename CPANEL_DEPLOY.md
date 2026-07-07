# SOLOSHORT — cPanel Deployment Guide

## Prerequisites

- cPanel hosting with PHP 8.1+ (8.2+ recommended)
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled
- SSL certificate (free via AutoSSL / Let's Encrypt)
- SSH access (recommended for Composer)

## Step 1: Upload Files

1. Upload all files from the `web/` directory to your `public_html/` folder
2. Ensure the `.htaccess` file is uploaded (may be hidden on some FTP clients)

```
public_html/
├── index.php
├── .htaccess
├── .env
├── assets/
├── app/
├── templates/
├── install/
├── admin/
├── storage/
├── vendor/
└── composer.json
```

## Step 2: Create MySQL Database

1. In cPanel, go to **MySQL® Databases**
2. Create a new database: `yourprefix_soloshort`
3. Create a new user: `yourprefix_solouser` with a strong password
4. Add the user to the database with **ALL PRIVILEGES**
5. Note down the database name, username, and password — you'll need these in the installer

## Step 3: Configure PHP

1. In cPanel, go to **Select PHP Version**
2. Select PHP 8.2 or 8.3
3. Enable these extensions:
   - `pdo_mysql` (REQUIRED)
   - `gd` (REQUIRED - for favicon generation)
   - `curl` (REQUIRED - for API calls)
   - `mbstring` (REQUIRED)
   - `fileinfo` (REQUIRED)
   - `zip` (REQUIRED)
   - `openssl` (REQUIRED)
   - `redis` (OPTIONAL - for caching)
   - `imagick` (OPTIONAL - for WebP)
4. Set `memory_limit` to `256M` or higher
5. Set `max_execution_time` to `300`
6. Set `post_max_size` to `500M`
7. Set `upload_max_filesize` to `500M`

## Step 4: Run Composer

Via SSH (recommended):
```bash
cd ~/public_html
php -d memory_limit=512M /usr/local/bin/composer install --no-dev --optimize-autoloader
```

Or via cPanel's "PHP Composer" tool if available.

## Step 5: Set Permissions

Via SSH or File Manager:
```bash
chmod 755 ~/public_html
chmod 644 ~/public_html/.htaccess
chmod 644 ~/public_html/.env
chmod -R 755 ~/public_html/storage
chmod -R 755 ~/public_html/public/assets
```

## Step 6: Run Installer

1. Visit `https://yourdomain.com`
2. The installer will automatically start
3. Complete the 4 stages:
   - **Stage 1**: License validation + PHP requirements check
   - **Stage 2**: Database configuration (use MySQL credentials from Step 2)
   - **Stage 3**: Admin account creation
   - **Stage 4**: Congratulations

## Step 7: Post-Installation

1. Log in to admin panel at `https://yourdomain.com/admin`
2. Configure SMTP in **Admin → Settings** for email notifications
3. Set up shelves and banners in the admin panel
4. Upload your first series and episodes
5. Generate sitemap: **Admin → Settings → Sitemap**
6. Review security settings: **Admin → Settings → Login Security**

## Step 8: Cron Jobs

The current, correct list is also shown live (with your actual server path filled
in) at **Admin → Settings → Cron Jobs Setup**, which is the source of truth —
add whatever it displays. For reference, the same jobs:

```
# Email queue processing (every minute)
* * * * * php /home/username/public_html/cron/process-email-queue.php

# Weekly bonus — credits every active user's weekly bonus coins (Monday 00:00)
0 0 * * 1 php /home/username/public_html/cron/weekly_bonus.php

# Login attempt cleanup — prunes old brute-force records, retention only (daily)
0 4 * * * php /home/username/public_html/cron/cleanup-login-attempts.php

# Session file cleanup — only needed if your host disables PHP's own
# automatic session garbage collection (hourly)
0 * * * * php /home/username/public_html/cron/cleanup-sessions.php
```

Note: the sitemap (`/sitemap.xml`) is generated live on every request by
`SeoController@sitemap()`, not from a pre-built file — there is no
`generate-sitemap.php` cron job and none is needed.

## Troubleshooting

**White screen on installer?**
- Check PHP error logs in cPanel
- Ensure all PHP extensions are enabled
- Verify `.env` file permissions

**Database connection fails?**
- Verify host is `localhost` (not `127.0.0.1` on some cPanel setups)
- Check username/password
- Ensure user is assigned to the database

**Clean URLs not working?**
- Ensure mod_rewrite is enabled in Apache
- Verify `.htaccess` file exists in public_html
- Check `AllowOverride All` is set (contact host if needed)

**Favicon not showing?**
- Ensure GD extension is enabled
- Upload a font file to `assets/fonts/Inter-Bold.ttf`

**Redis not available?**
- System automatically falls back to file-based caching and sessions
- No configuration needed — works out of the box

## Security Checklist

- [ ] Change admin password to a strong one
- [ ] Set up SMTP for email notifications
- [ ] Enable HTTPS (install SSL certificate)
- [ ] Configure brute force protection settings
- [ ] Set country blocking rules if needed
- [ ] Review CSP headers in `.htaccess`
- [ ] Restrict `storage/` directory via `.htaccess`
- [ ] Keep PHP version updated to latest 8.x
- [ ] Regular backups (cPanel Backup Wizard)
