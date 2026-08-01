# Specification: Phase 2 - Legacy Shortcode Remediation & Slider Replacement (PHP 7.4)

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 2 - Programmatic Shortcode Remediation & Slider Replacement  
**STATUS**: `[REVISED SPEC / VERIFICATION PENDING]`  

---

## 1. Objective & Target Users
* **Objective**: Programmatically remediate legacy WPBakery / BeTheme shortcodes and replace legacy LayerSlider and static sliders with native Gutenberg blocks (`core/query` loops and `core/gallery` blocks) using a unified script (`bin/run-phase2-migration.sh`) under PHP 7.4. To keep theme activation deferred to Phase 4, WP-CLI invocations MUST pass `--require=public/wp-content/themes/ekalexandria-flagship/inc/cli-commands.php` to load subcommands while the legacy theme remains active. Every step writes to clean log files in `ai-work/logs/`.
* **Target Users**: Portal visitors, content editors, and system scripts.

---

## 2. Core Features & Acceptance Criteria `[SPECIFICATION REVISED]`

### Unified Migration Script (`bin/run-phase2-migration.sh`)
* **Goal**: Execute slider replacement and shortcode remediation in an ordered, idempotent migration pass without activating the flagship theme.
* **Unified Logging Mandate**:
  - The overarching script output MUST be dumped to `ai-work/logs/phase2-unified-migration.log`.
  - Each individual sub-command MUST also dump its stdout/stderr to its own dedicated clean log file in `ai-work/logs/`.

### Task 1: Slider Replacement Logic
* **Command**: `php7.4 $(which wp) --require=wp-content/themes/ekalexandria-flagship/inc/cli-commands.php eka replace-sliders`
* **Log File**: `ai-work/logs/sliders-migration.log`
* **Dynamic Sliders**: Replace homepage/news sliders on pages `13236, 17194, 17215, 17219, 8934, 16920, 16923` with native `core/query` loops pulling 5 latest posts.
* **Static Sliders**: Replace static sliders on inner pages with `core/gallery` blocks using pre-mapped media IDs from `legacy_data.md`:
  - Music Museum (`7820, 17129, 17133`): Media IDs `7821, 7822, 7823`.
  - Science Museum (`7811, 17137, 17139`): Media IDs `7813, 7814, 7815`.
  - Cemeteries (`3442, 17023, 17155`): Media IDs `10329, 7667, 7668, 7669, 7670, 7671, 7672, 7673`.
  - Cemeteries Conservation (`7756, 17150`): Media IDs `7935, 7936, 7937, 7938, 7939, 7940, 7941, 7942`.
  - Community Lounge (`7390, 17018, 17020`): Media ID `10328`.

### Task 2: Shortcode Remediation & Sub-navigation
* **Command**: `php7.4 $(which wp) --require=wp-content/themes/ekalexandria-flagship/inc/cli-commands.php eka remediate-shortcodes`
* **Log File**: `ai-work/logs/remediate-shortcodes.log`
* **Testimonials Shortcode**: Replace `[testimonials]` with native `board_member` Query Loop.
* **Posts Grid Shortcode**: Replace `[vc_posts_grid]` with page Query Loop.
* **Sidebar Injections**: Inject native `core/navigation` blocks into two-column layouts for pages requiring sub-navigation sidebars (Menus 70, 71, 117, 3377, 3378, 3944, 3945, 3707, 3716).

---

## 3. Tech Stack Preferences & Constraints
* **PHP Routing**: Strictly `php7.4` (`php7.4 $(which wp) --require=... ...`).
* **Source Files**: `inc/cli-commands.php`, `bin/run-phase2-migration.sh`.
* **Clean Logging**: Every subcommand and the main wrapper MUST write to clean log files in `ai-work/logs/`.
* **Theme Independence**: Must execute via `--require` without modifying active theme.

---

## 4. Commands
```bash
# Execute individual migration tasks with explicit --require flag
php7.4 $(which wp) --require=wp-content/themes/ekalexandria-flagship/inc/cli-commands.php eka replace-sliders --path=public > ai-work/logs/sliders-migration.log 2>&1
php7.4 $(which wp) --require=wp-content/themes/ekalexandria-flagship/inc/cli-commands.php eka remediate-shortcodes --path=public > ai-work/logs/remediate-shortcodes.log 2>&1

# OR execute unified migration wrapper script
bash bin/run-phase2-migration.sh > ai-work/logs/phase2-unified-migration.log 2>&1
```

---

## 5. Project Structure
```text
bin/
└── run-phase2-migration.sh       # Unified Phase 2 migration runner script (uses --require flag)
inc/
└── cli-commands.php              # WP-CLI subcommands
ai-work/
├── scopings/
│   └── legacy_data.md
└── logs/
    ├── phase2-unified-migration.log
    ├── sliders-migration.log
    └── remediate-shortcodes.log
```

---

## 6. Code Style & Testing Strategy
* **Testing Strategy**:
  1. Inspect `ai-work/logs/phase2-unified-migration.log` and individual logs for clean execution.
  2. Confirm zero `[rev_slider]` or `[layerslider]` shortcodes remain in pages.
  3. Re-run unified script to verify 100% idempotency.
* **Governance**:
  - **ALWAYS**: Route all WP-CLI commands through `php7.4` with `--require=wp-content/themes/ekalexandria-flagship/inc/cli-commands.php`.
  - **ALWAYS**: Require manual spec verification before generating Git commits.
  - **ALWAYS**: Require **Manual User Validation Pause** at completion of Phase 2 before proceeding to Phase 3.

