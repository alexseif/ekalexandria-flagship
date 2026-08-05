# Post-Migration Upgrade Guide: PHP 8.2 & WP Core Update

This document provides step-by-step instructions for completing the final post-migration upgrade phase once the 3-stage migration pipeline (`01-reset-env.sh`, `02-setup-theme-and-plugins.sh`, `03-migrate-content.sh`) has successfully executed.

---

## 1. Environment Verification & Baseline Check

Before switching PHP versions, verify that Stage 3 migration completed without errors:

```bash
# Verify log files in flagship theme directory
cat ai-work/logs/01-reset-env.log
cat ai-work/logs/02-setup-theme-and-plugins.log
cat ai-work/logs/03-migrate-content.log
```

---

## 2. Upgrade PHP Runtime to PHP 8.2

### Step 2.1: Verify PHP 8.2 CLI Availability
```bash
php8.2 -v
```

### Step 2.2: Switch Web Server (Nginx / PHP-FPM) to PHP 8.2
Update Nginx site configuration or socket pool to target `php8.2-fpm`:

```nginx
# /etc/nginx/sites-available/backstage.ekalexandria.org
fastcgi_pass unix:/run/php/php8.2-fpm.sock;
```

Reload Nginx and PHP-FPM services:
```bash
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx
```

---

## 3. WordPress Core & Database Upgrade

Execute WordPress Core update and database schema migration using PHP 8.2 context:

```bash
cd /var/www/backstage.ekalexandria.org/public

# 1. Update WordPress Core
php8.2 $(which wp) core update --allow-root

# 2. Update Database Schema
php8.2 $(which wp) core update-db --allow-root

# 3. Verify Core Version
php8.2 $(which wp) core version --allow-root
```

---

## 4. Active Plugin Updates

Update remaining active modern plugins (Polylang, Rank Math, etc.) to PHP 8.2 compliant versions:

```bash
# Update all active plugins
php8.2 $(which wp) plugin update --all --allow-root

# Verify plugin status
php8.2 $(which wp) plugin list --allow-root
```

---

## 5. Final Verification & Cache Flush

```bash
# Flush rewrite rules and object cache
php8.2 $(which wp) rewrite flush --allow-root
php8.2 $(which wp) cache flush --allow-root

# Run block AST check on key pages
php8.2 $(which wp) eval-file wp-content/themes/ekalexandria-flagship/bin/test-runtime-render.php --allow-root
```

---

## 6. Rollback Protocol

If an unrecoverable PHP 8.2 error occurs:
1. Revert Nginx/PHP-FPM socket back to `php7.4-fpm`.
2. Reload services: `sudo systemctl reload php7.4-fpm && sudo systemctl reload nginx`.
3. Check error logs: `tail -n 100 /var/log/nginx/error.log` and `ai-work/logs/*.log`.
