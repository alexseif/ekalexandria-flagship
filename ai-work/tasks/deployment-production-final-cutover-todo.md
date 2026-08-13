# Todo Checklist: Standalone Production Cutover Script (`bin/deploy-production.sh`)

- [x] **Task 1: Environment Detection, Dry-Run Flag & Fail-Safe Script Shell Architecture (`bin/deploy-production.sh`)**
  - [x] Create `bin/deploy-production.sh` with executable permissions (`chmod +x`)
  - [x] Add `set -eo pipefail` and `trap 'on_error $? $LINENO'` exit trap handler
  - [x] Add `--dry-run` / `--test` CLI flag parsing to allow non-destructive test execution
  - [x] Add dynamic `WEB_ROOT="/var/www/ekalexandria.org/public"` and `BACKUP_DIR="/var/www/ekalexandria.org"` detection
  - [x] Direct output logging to `ai-work/logs/deploy-production.log` and stdout
  - [x] Verify bash syntax with `bash -n bin/deploy-production.sh`
  - [x] Verify non-destructive test execution with `bash bin/deploy-production.sh --dry-run`

- [x] **Task 2: Implement Phase A (Steps 0–7) Pre-Flight, Backup, Early Permissions & Legacy Cleanup**
  - [x] Step 0: Run `bin/pre-flight.sh` (includes WP-CLI plugin load sanity check)
  - [x] Step 1: Create timestamped backups in `$BACKUP_DIR` (`.sql` dump & `.tar.gz` archive excluding `wp-content/uploads/`) [Skipped in `--dry-run`]
  - [x] Step 2: Enable maintenance mode by dropping `.maintenance` in web root [Skipped in `--dry-run`]
  - [x] Step 3: **Immediate Permissions Fix**: [Skipped in `--dry-run`]
    - [x] Run `chown -R devops:www-data "$WEB_ROOT"`
    - [x] Run `chmod -R u+w "$WEB_ROOT/wp-content"`
  - [x] Step 4: Theme swap & cleanup: [Skipped in `--dry-run`]
    - [x] Activate `ekalexandria-flagship` via `php7.4 wp theme activate`
    - [x] Assign site logo ID `63053` (`wp theme mod set custom_logo`)
    - [x] Delete `betheme` via `wp theme delete betheme` with `rm -rf` fallback
  - [x] Step 5: Execute CPT migration (`php7.4 bin/migrate-cpts.php`) for Tachydromos PDFs & Board Members [Skipped in `--dry-run`]
  - [x] Step 6: Plugin cleanup: [Skipped in `--dry-run`]
    - [x] Deactivate `google-captcha` (`wp plugin deactivate google-captcha`)
    - [x] Deactivate and uninstall legacy plugins: `LayerSlider`, `js_composer`, `display-posts-shortcode`, `force-regenerate-thumbnails`, `ewww-image-optimizer`, `wordpress-seo`, `w3-total-cache`
    - [x] Execute `rm -rf` directory fallback for lingering plugin folders in `wp-content/plugins/`
  - [x] Step 7: Drop-in & cache purge: [Skipped in `--dry-run`]
    - [x] Remove `advanced-cache.php` and `object-cache.php`
    - [x] Remove `wp-content/cache/` and `wp-content/w3tc-config/`
  - [x] Verify syntax with `bash -n bin/deploy-production.sh` and test with `--dry-run`

- [x] **Task 3: Implement Phase B (Steps 8–12) PHP 8.2 Modernization & FSE Binding**
  - [x] Step 8: OPcache invalidation via `php8.2 wp eval 'if(function_exists("opcache_reset")) opcache_reset();'` [Skipped in `--dry-run`]
  - [x] Step 9: Gutenberg content engine migration (`php8.2 bin/migration-content-engine.php --skip-plugins`) [Skipped in `--dry-run`]
  - [x] Step 10: FSE page template assignment (`php8.2 bin/assign-page-templates.php`) [Skipped in `--dry-run`]
  - [x] Step 11: Classic menu to FSE block migration (`php8.2 bin/migrate-classic-menus-to-fse.php`) [Skipped in `--dry-run`]
  - [x] Step 12: Permalinks/rewrite flush, cache flush, and `.maintenance` removal [Skipped in `--dry-run`]
  - [x] Verify syntax with `bash -n bin/deploy-production.sh` and test with `--dry-run`

- [x] **Task 4: Script Syntax Audit & Non-Destructive Test Execution Verification**
  - [x] Perform syntax verification across `bin/deploy-production.sh` (`bash -n bin/deploy-production.sh`)
  - [x] Execute pre-flight check suite (`bash bin/pre-flight.sh`)
  - [x] Execute non-destructive dry-run (`bash bin/deploy-production.sh --dry-run`)
  - [x] Execute full PHPUnit test suite (`vendor/bin/phpunit bin/tests`)
  - [x] Confirm script runs non-interactively with strict failure traps and zero live DB/file mutations


