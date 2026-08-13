# SPECIFICATION: Production Final Cutover

**TOPIC NAME**: `deployment`
**ISSUE NAME**: `production-final-cutover`

---

## 1. Objective & Architectural Strategy

### Objective
Execute a single, continuous, native deployment script on the live production server (`/var/www/ekalexandria.org/public`). The primary goal is to safely modernize the database and codebase while transitioning the PHP runtime environment from **PHP 7.4** to **PHP 8.2**.

### Backup Naming & Storage Strategy (Outside Web Root)
Before touching any database records or files, full safety backups will be placed strictly in `/var/www/ekalexandria.org/` (one level above the public web root) using explicit date-stamped filenames to ensure zero public exposure:

- **Database Backup**: `/var/www/ekalexandria.org/eka_prod_db_backup_$(date +%Y%m%d_%H%M%S).sql`
- **Filesystem Archive**: `/var/www/ekalexandria.org/eka_prod_files_backup_$(date +%Y%m%d_%H%M%S).tar.gz` *(Excludes `wp-content/uploads/` to prevent disk bloat)*

---

## 2. PHP Runtime Transition & Execution Pipeline

Because legacy plugins (WPBakery/js_composer, LayerSlider, etc.) trigger fatal errors on PHP 8.2, the deployment pipeline strictly sequences PHP binary execution:

- **Phase A (Legacy & Cleanup - PHP 7.4)**: Executes all legacy cleanup, CPT migration, and plugin uninstallation using `php7.4`.
- **Phase B (Modernization & Template Binding - PHP 8.2)**: Executes content block transformation, template assignment, and navigation binding using `php8.2`.

### Detailed 12-Step Execution Sequence

| Step | Operation / Script | PHP Version | Purpose & Description |
| :--- | :--- | :--- | :--- |
| **0** | `pre-flight.sh` (Enhanced) | CLI / PHP | Verifies WP-CLI, Node, ImageMagick (`convert`), Ghostscript (`gs`), PDF policies, and both `php7.4` & `php8.2` Imagick extensions. |
| **1** | **Safety Backups** | N/A | Generates `.sql` dump and `.tar.gz` archive in `/var/www/ekalexandria.org/`. |
| **2** | **Maintenance Mode** | N/A | Drops `/var/www/ekalexandria.org/public/.maintenance` to serve a 503 page and block traffic. |
| **3** | **Permissions Fix** | N/A | Runs `chown -R devops:www-data /var/www/ekalexandria.org/public` and sets write access. |
| **4** | **Theme Swap & Logo** | `php7.4` | Activates `ekalexandria-flagship`, sets logo ID `63053`, and deletes legacy `betheme`. |
| **5** | **CPT Migration** | `php7.4` | Runs `bin/migrate-cpts.php` to migrate Tachydromos PDFs and Board Members. |
| **6** | **Legacy Plugin Deletion**| `php7.4` | Deactivates & uninstalls WPBakery, LayerSlider, W3TC, RankMath/SEO, etc., and `rm -rf`s folders. |
| **7** | **Drop-in & Cache Purge**| `php7.4` | Removes `advanced-cache.php`, `object-cache.php`, `w3tc-config/`, and `cache/`. |
| **8** | **OPcache Invalidation** | `php8.2` | Runs `wp eval 'if(function_exists("opcache_reset")) opcache_reset();'` (No `sudo` / `systemctl` needed). |
| **9** | **Content Engine (Stage 02)**| `php8.2` | Runs `bin/migration-content-engine.php` (via `02-migrate-content.sh`) to convert shortcodes to Gutenberg blocks. |
| **10**| **Template Assignment (Stage 03)**| `php8.2` | Runs `bin/assign-page-templates.php` (via `03-assign-templates.sh`) to bind FSE layout files. |
| **11**| **Navigation Binding (Stage 03)**| `php8.2` | Runs `bin/migrate-classic-menus-to-fse.php` (via `03-assign-templates.sh`) to convert menus to FSE blocks. |
| **12**| **Final Flush & Lift** | `php8.2` | Flushes rewrite rules & transients (`wp rewrite flush`, `wp cache flush`), then removes `.maintenance`. |

---

## 3. Comprehensive Inventory of Borrowed Sub-Scripts

Below is the complete audit of scripts across `01`, `02`, and `03` incorporated into this specification:

### From Stage 01 (`bin/01-reset-and-setup.sh`) & `bin/pre-flight.sh`
- `bin/pre-flight.sh`: Validates WP-CLI, `php7.4`, `php8.2`, Node.js, `convert`, `gs`, PDF `policy.xml`, and Imagick modules for both PHP versions.
- `bin/migrate-cpts.php`: Converts Tachydromos issue metadata and Board Member testimonials to CPTs.
- `wp theme activate ekalexandria-flagship` & `wp theme mod set custom_logo 63053`
- `wp theme delete betheme` & `rm -rf wp-content/themes/betheme`
- Plugin Uninstallation List: `LayerSlider`, `js_composer`, `display-posts-shortcode`, `force-regenerate-thumbnails`, `ewww-image-optimizer`, `wordpress-seo`, `w3-total-cache`, `google-captcha`.
- Drop-in Cleanup: `wp-content/advanced-cache.php`, `wp-content/object-cache.php`, `wp-content/cache`, `wp-content/w3tc-config`.

### From Stage 02 (`bin/02-migrate-content.sh`)
- `bin/migration-content-engine.php`: Executed with `php8.2` and `--skip-plugins`. Transforms 6,400+ posts into clean Gutenberg block AST output.

### From Stage 03 (`bin/03-assign-templates.sh`)
- `bin/assign-page-templates.php`: Binds FSE HTML templates (`page-en`, `page-ar`, `archive-alx_tachydromos`, etc.) to posts/pages.
- `bin/migrate-classic-menus-to-fse.php`: Converts classic menus to FSE navigation blocks and links Polylang translations.

---

## 4. Rollback & Disaster Recovery Protocol

If the deployment script fails at any step:
1. Keep `.maintenance` active to prevent site exposure.
2. Delete the modified `/var/www/ekalexandria.org/public` folder (or restore files).
3. Unpack the exact dated backup:
   `tar -xzf /var/www/ekalexandria.org/eka_prod_files_backup_[TIMESTAMP].tar.gz -C /var/www/ekalexandria.org/`
4. Restore the database dump:
   `php7.4 $(which wp) db import /var/www/ekalexandria.org/eka_prod_db_backup_[TIMESTAMP].sql --path=/var/www/ekalexandria.org/public`
5. Remove `.maintenance`. Site is restored to pre-deployment state.

---

## 5. Known Boundaries & Constraints

### Always Do
- Always run Phase A (Steps 4–7) using `php7.4`.
- Always run Phase B (Steps 9–12) using `php8.2`.
- Always place backups in `/var/www/ekalexandria.org/` with `$(date +%Y%m%d_%H%M%S)` timestamps when running live cutover.
- Always include a `--dry-run` / `--test` mode in `bin/deploy-production.sh` that validates environment binaries, path readability, WP-CLI responsiveness, and step logic without making file/DB modifications or generating backup archives.
- Log stdout and stderr of every step to dedicated log files in `ai-work/logs/`.
- Use non-interactive OPcache invalidation (`wp eval 'opcache_reset();'`) instead of `systemctl`.

### Ask First About
- Initiating the live execution of `bin/deploy-production.sh` on production.

### Never Do
- Never execute `bin/deploy-production.sh` live during developer/build automation passes — only execute test modes (`--dry-run`, `bash -n`, PHPUnit).
- Never invoke `systemctl` or commands requiring interactive `sudo` passwords.
- Never output `.sql` or `.tar.gz` backup files inside `/var/www/ekalexandria.org/public/`.
- Never execute `bin/01-reset-and-setup.sh` on production (kill-switch active).
