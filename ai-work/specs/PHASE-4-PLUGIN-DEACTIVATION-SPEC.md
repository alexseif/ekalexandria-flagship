# Specification: Phase 4 - Theme Cutover, Content Migration, Shortcode & Slider Remediation, Plugin Cleanup & PHP 8.2 Upgrade

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 4 - Consolidated Migration & Cutover Pass  
**STATUS**: `[REVISED SPEC / READY FOR EXECUTION]`  

---

## 1. Objective & Target Users
* **Objective**: Execute the unified cutover pass: activate the modern block-based theme `ekalexandria-flagship`, remediate legacy shortcodes & MFN builder markup, replace LayerSlider & static sliders with native blocks (`core/query` loops and `core/gallery`), programmatically migrate `alx_tachydromos` newsletters and `board_member` records (linking Polylang translations), assign navigation menu locations, deactivate and purge stalling legacy plugins using `bin/run-phase4-migration.sh` and `bin/cleanup-plugins.sh`, purge legacy caching drop-ins, and transition system environment CLI runtime to PHP 8.2.
* **Target Users**: System administrators, site auditors, content editors, and portal visitors.

---

## 2. Core Features & Acceptance Criteria

### Task 1: Theme Activation
* **Command**: `php7.4 $(which wp) theme activate ekalexandria-flagship`
* **Log File**: `ai-work/logs/phase4-unified-migration.log`
* **Criteria**: Activate `ekalexandria-flagship` theme so custom block architecture, templates, and CLI subcommands are natively registered.

### Task 2: Slider Replacement Logic (`eka replace-sliders`)
* **Command**: `php7.4 $(which wp) eka replace-sliders`
* **Log File**: `ai-work/logs/sliders-migration.log`
* **Dynamic Sliders**: Replace homepage/news sliders on pages `13236, 17194, 17215, 17219, 8934, 16920, 16923` with native `core/query` loops pulling 5 latest posts.
* **Static Sliders**: Replace static sliders on inner pages with `core/gallery` blocks using pre-mapped media IDs from Phase 2 inventory:
  - Music Museum (`7820, 17129, 17133`): Media IDs `7821, 7822, 7823`.
  - Science Museum (`7811, 17137, 17139`): Media IDs `7813, 7814, 7815`.
  - Cemeteries (`3442, 17023, 17155`): Media IDs `10329, 7667, 7668, 7669, 7670, 7671, 7672, 7673`.
  - Cemeteries Conservation (`7756, 17150`): Media IDs `7935, 7936, 7937, 7938, 7939, 7940, 7941, 7942`.
  - Community Lounge (`7390, 17018, 17020`): Media ID `10328`.

### Task 3: Shortcode & MFN Builder Remediation (`eka remediate-shortcodes`)
* **Command**: `php7.4 $(which wp) eka remediate-shortcodes`
* **Log File**: `ai-work/logs/remediate-shortcodes.log`
* **Testimonials Shortcode**: Replace `[testimonials]` with native `board_member` Query Loop.
* **Posts Grid Shortcode**: Replace `[vc_posts_grid]` with page Query Loop.
* **MFN Builder & WPBakery Markup**: Remediate legacy container grids into clean Gutenberg group/columns blocks.
* **Sidebar Injections**: Inject native `core/navigation` blocks into two-column layouts for pages requiring sub-navigation sidebars (Menus 70, 71, 117, 3377, 3378, 3944, 3945, 3707, 3716).

### Task 4: Alexandrinos Tachydromos (`alx_tachydromos`) CPT Migration (`eka migrate-tachydromos`)
* **Command**: `php7.4 $(which wp) eka migrate-tachydromos`
* **Log File**: `ai-work/logs/tachydromos-migration.log`
* **Criteria**: Migrate 32 newsletters, normalize Greek month titles, embed PDF `core/file` blocks, reassign unscaled media IDs, and store `_eka_pdf_filename` metadata for idempotency.

### Task 5: Board Members (`board_member`) CPT Migration (`eka migrate-board`)
* **Command**: `php7.4 $(which wp) eka migrate-board`
* **Log File**: `ai-work/logs/board-migration.log`
* **Criteria**: Migrate testimonials, strip `<img>` tags and `[vc_*]` shortcodes, reassign unscaled thumbnails, link Polylang translations via `pll_save_post_translations`, and store `_legacy_testimonial_id`.

### Task 6: Navigation Menu Location Assignments
* **Command**: WP-CLI menu location assignments
* **Log File**: `ai-work/logs/menu-assignments.log`
* **Criteria**: Assign Greek Main (13 -> `main-menu`), English Main (3315 -> `main-menu___en`), Arabic Main (3316 -> `main-menu___ar`), and Greek Footer (21 -> `social-menu-bottom`).

### Task 7: Plugin & Legacy Caching Cleanup (`bin/cleanup-plugins.sh`)
* **Command**: `bash bin/cleanup-plugins.sh`
* **Log File**: `ai-work/logs/cleanup-plugins.log`
* **Criteria**: Deactivate and uninstall stalling plugins (`LayerSlider`, `js_composer`, `revslider`, `ewww-image-optimizer`, `wordpress-seo`). Purge legacy caching drop-ins (`advanced-cache.php`, `object-cache.php`, `w3tc-config`, `cache`). Strict logging mandate: no automated `rm -rf` fallbacks on plugin removal failure.

### Task 8: PHP 8.2 Environment Upgrade
* **Command**: Transition CLI runtime to PHP 8.2 (`php8.2 $(which wp)`).
* **Criteria**: Confirm zero fatal errors in `public/wp-content/debug.log` under PHP 8.2 runtime.

---

## 3. Tech Stack Preferences & Constraints
* **Script Files**: `bin/run-phase4-migration.sh`, `bin/cleanup-plugins.sh`, `inc/cli-commands.php`.
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
inc/
└── cli-commands.php              # Flagship theme CLI migration commands
ai-work/
├── logs/
│   ├── phase4-unified-migration.log
│   ├── sliders-migration.log
│   ├── remediate-shortcodes.log
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
  1. Inspect `ai-work/logs/phase4-unified-migration.log` to confirm theme activation, slider replacement, shortcode remediation, CPT migrations, menu assignments, and plugin cleanup.
  2. Inspect `ai-work/logs/cleanup-plugins.log` to verify plugin removal and caching cleanup.
  3. Verify zero fatal errors or WSOD in `public/wp-content/debug.log` under PHP 8.2 runtime.
* **Boundaries**:
  - **ALWAYS**: Log failure details for manual developer remediation instead of executing `rm` / `rm -rf` fallbacks.
  - **ALWAYS**: Dump execution outputs to clean log files in `ai-work/logs/`.
  - **ALWAYS**: Require manual spec verification before generating Git commits.
  - **ALWAYS**: Require **Manual User Validation Pause** before starting Phase 5.
  - **NEVER**: Deactivate essential core plugins (e.g. `polylang`).
