# Todo Checklist: Standalone Production Cutover Script (`bin/deploy-production.sh`)

- [ ] **Task 1: Environment Detection, Dry-Run Flag & Fail-Safe Script Shell Architecture (`bin/deploy-production.sh`)**
  - [ ] Create `bin/deploy-production.sh` with executable permissions (`chmod +x`)
  - [ ] Add `set -eo pipefail` and `trap 'on_error $? $LINENO'` exit trap handler
  - [ ] Add `--dry-run` / `--test` CLI flag parsing to allow non-destructive test execution
  - [ ] Add dynamic `WEB_ROOT="/var/www/ekalexandria.org/public"` and `BACKUP_DIR="/var/www/ekalexandria.org"` detection
  - [ ] Direct output logging to `ai-work/logs/deploy-production.log` and stdout
  - [ ] Verify bash syntax with `bash -n bin/deploy-production.sh`
  - [ ] Verify non-destructive test execution with `bash bin/deploy-production.sh --dry-run`

- [ ] **Task 2: Implement Phase A (Steps 0–7) Pre-Flight, Backup, Early Permissions & Legacy Cleanup**
  - [ ] Step 0: Run `bin/pre-flight.sh` (includes WP-CLI plugin load sanity check)
  - [ ] Step 1: Create timestamped backups in `$BACKUP_DIR` (`.sql` dump & `.tar.gz` archive excluding `wp-content/uploads/`) [Skipped in `--dry-run`]
  - [ ] Step 2: Enable maintenance mode by dropping `.maintenance` in web root [Skipped in `--dry-run`]
  - [ ] Step 3: **Immediate Permissions Fix**: [Skipped in `--dry-run`]
    - [ ] Run `chown -R devops:www-data "$WEB_ROOT"`
    - [ ] Run `chmod -R u+w "$WEB_ROOT/wp-content"`
  - [ ] Step 4: Theme swap & cleanup: [Skipped in `--dry-run`]
    - [ ] Activate `ekalexandria-flagship` via `php7.4 wp theme activate`
    - [ ] Assign site logo ID `63053` (`wp theme mod set custom_logo`)
    - [ ] Delete `betheme` via `wp theme delete betheme` with `rm -rf` fallback
  - [ ] Step 5: Execute CPT migration (`php7.4 bin/migrate-cpts.php`) for Tachydromos PDFs & Board Members [Skipped in `--dry-run`]
  - [ ] Step 6: Plugin cleanup: [Skipped in `--dry-run`]
    - [ ] Deactivate `google-captcha` (`wp plugin deactivate google-captcha`)
    - [ ] Deactivate and uninstall legacy plugins: `LayerSlider`, `js_composer`, `display-posts-shortcode`, `force-regenerate-thumbnails`, `ewww-image-optimizer`, `wordpress-seo`, `w3-total-cache`
    - [ ] Execute `rm -rf` directory fallback for lingering plugin folders in `wp-content/plugins/`
  - [ ] Step 7: Drop-in & cache purge: [Skipped in `--dry-run`]
    - [ ] Remove `advanced-cache.php` and `object-cache.php`
    - [ ] Remove `wp-content/cache/` and `wp-content/w3tc-config/`
  - [ ] Verify syntax with `bash -n bin/deploy-production.sh` and test with `--dry-run`

- [ ] **Task 3: Implement Phase B (Steps 8–12) PHP 8.2 Modernization & FSE Binding**
  - [ ] Step 8: OPcache invalidation via `php8.2 wp eval 'if(function_exists("opcache_reset")) opcache_reset();'` [Skipped in `--dry-run`]
  - [ ] Step 9: Gutenberg content engine migration (`php8.2 bin/migration-content-engine.php --skip-plugins`) [Skipped in `--dry-run`]
  - [ ] Step 10: FSE page template assignment (`php8.2 bin/assign-page-templates.php`) [Skipped in `--dry-run`]
  - [ ] Step 11: Classic menu to FSE block migration (`php8.2 bin/migrate-classic-menus-to-fse.php`) [Skipped in `--dry-run`]
  - [ ] Step 12: Permalinks/rewrite flush, cache flush, and `.maintenance` removal [Skipped in `--dry-run`]
  - [ ] Verify syntax with `bash -n bin/deploy-production.sh` and test with `--dry-run`

- [ ] **Task 4: Script Syntax Audit & Non-Destructive Test Execution Verification**
  - [ ] Perform syntax verification across `bin/deploy-production.sh` (`bash -n bin/deploy-production.sh`)
  - [ ] Execute pre-flight check suite (`bash bin/pre-flight.sh`)
  - [ ] Execute non-destructive dry-run (`bash bin/deploy-production.sh --dry-run`)
  - [ ] Execute full PHPUnit test suite (`vendor/bin/phpunit bin/tests`)
  - [ ] Confirm script runs non-interactively with strict failure traps and zero live DB/file mutations

