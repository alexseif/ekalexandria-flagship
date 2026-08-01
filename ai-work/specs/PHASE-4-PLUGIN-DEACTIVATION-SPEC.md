# Specification: Phase 4 - Theme Activation, CPT Migration, Plugin Cleanup & PHP 8.2 Upgrade

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 4 - Theme Activation, CPT Migration, Plugin Cleanup & Environment Upgrade to PHP 8.2  
**STATUS**: `[REVISED SPEC / VERIFICATION PENDING]`  

---

## 1. Objective & Target Users
* **Objective**: Activate the modern block-based theme `ekalexandria-flagship`, programmatically migrate `alx_tachydromos` newsletters and `board_member` records (linking Polylang translations), assign navigation menu locations, safely deactivate and purge stalling legacy plugins using `bin/run-phase4-migration.sh` and `bin/cleanup-plugins.sh`, purge legacy caching drop-ins, and transition system environment CLI runtime to PHP 8.2. Strictly prohibit automated `rm` / `rm -rf` fallbacks; if any plugin deactivation or removal fails, the script MUST log failure details into `ai-work/logs/cleanup-plugins.log` for developer inspection without modifying scripts or forcing deletions.
* **Target Users**: System administrators, site auditors, and portal users.

---

## 2. Core Features & Acceptance Criteria

### Task 1: Theme Activation
* **Command**: `php7.4 $(which wp) theme activate ekalexandria-flagship`
* **Log File**: `ai-work/logs/phase4-unified-migration.log`
* **Criteria**: Activate `ekalexandria-flagship` theme so custom CPT registrations and CLI subcommands are available natively.

### Task 2: Alexandrinos Tachydromos (`alx_tachydromos`) CPT Migration
* **Command**: `php7.4 $(which wp) eka migrate-tachydromos`
* **Log File**: `ai-work/logs/tachydromos-migration.log`
* **Criteria**: Migrate 32 newsletters, normalize Greek month titles, embed PDF `core/file` blocks, reassign unscaled media IDs, and store `_eka_pdf_filename` metadata for idempotency.

### Task 3: Board Members (`board_member`) CPT Migration
* **Command**: `php7.4 $(which wp) eka migrate-board`
* **Log File**: `ai-work/logs/board-migration.log`
* **Criteria**: Migrate testimonials, strip `<img>` tags and `[vc_*]` shortcodes, reassign unscaled thumbnails, link Polylang translations via `pll_save_post_translations`, and store `_legacy_testimonial_id`.

### Task 4: Navigation Menu Location Assignments
* **Command**: WP-CLI menu location assignments
* **Log File**: `ai-work/logs/menu-assignments.log`
* **Criteria**: Assign Greek Main (13 -> `main-menu`), English Main (3315 -> `main-menu___en`), Arabic Main (3316 -> `main-menu___ar`), and Greek Footer (21 -> `social-menu-bottom`).

### Task 5: Plugin & Legacy Caching Cleanup
* **Command**: `bash bin/cleanup-plugins.sh`
* **Log File**: `ai-work/logs/cleanup-plugins.log`
* **Criteria**: Deactivate and uninstall stalling plugins (`LayerSlider`, `js_composer`, `revslider`, `ewww-image-optimizer`, `wordpress-seo`). Purge legacy caching drop-ins (`advanced-cache.php`, `object-cache.php`, `w3tc-config`, `cache`). Strict logging mandate: no automated `rm -rf` fallbacks on plugin removal failure.

### Task 6: PHP 8.2 Environment Upgrade
* **Command**: Transition CLI runtime to PHP 8.2 (`php8.2 $(which wp)`).
* **Criteria**: Confirm zero fatal errors in `public/wp-content/debug.log` under PHP 8.2 runtime.

---

## 3. Tech Stack Preferences & Constraints
* **Script Files**: `bin/run-phase4-migration.sh`, `bin/cleanup-plugins.sh`.
* **Target PHP Runtime**: PHP 7.4 for migration commands -> PHP 8.2 for post-cleanup runtime.
* **Logging Path**: `ai-work/logs/phase4-unified-migration.log`, `ai-work/logs/cleanup-plugins.log`.

---

## 4. Commands
```bash
# Execute Phase 4 unified migration & cleanup runner
bash bin/run-phase4-migration.sh > ai-work/logs/phase4-unified-migration.log 2>&1

# Verify active plugins & theme under PHP 8.2
php8.2 $(which wp) theme status ekalexandria-flagship --path=public
php8.2 $(which wp) plugin list --status=active --path=public

# Inspect log files for failures or issues
cat ai-work/logs/phase4-unified-migration.log
cat ai-work/logs/cleanup-plugins.log
```

---

## 5. Project Structure
```text
bin/
├── run-phase4-migration.sh       # Unified Phase 4 runner script
└── cleanup-plugins.sh            # Plugin & legacy caching cleanup script (no rm fallbacks)
ai-work/
├── logs/
│   ├── phase4-unified-migration.log
│   ├── tachydromos-migration.log
│   ├── board-migration.log
│   ├── menu-assignments.log
│   └── cleanup-plugins.log
└── specs/
    └── PHASE-4-PLUGIN-DEACTIVATION-SPEC.md
```

---

## 6. Code Style & Testing Strategy
* **Testing Strategy**:
  1. Inspect `ai-work/logs/phase4-unified-migration.log` to confirm theme activation, Tachydromos migration, Board migration, and menu assignments.
  2. Inspect `ai-work/logs/cleanup-plugins.log` to verify plugin removal and caching cleanup.
  3. Verify zero fatal errors or WSOD in `public/wp-content/debug.log` under PHP 8.2 runtime.
* **Boundaries**:
  - **ALWAYS**: Log failure details for manual developer remediation instead of executing `rm` / `rm -rf` fallbacks.
  - **ALWAYS**: Dump execution outputs to clean log files in `ai-work/logs/`.
  - **ALWAYS**: Require manual spec verification before generating Git commits.
  - **ALWAYS**: Require **Manual User Validation Pause** before starting Phase 5.
  - **NEVER**: Deactivate essential core plugins (e.g. `polylang`).

