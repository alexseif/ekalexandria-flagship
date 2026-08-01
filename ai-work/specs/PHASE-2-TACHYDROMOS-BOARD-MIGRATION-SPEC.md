# Specification: Phase 2 - Content & Shortcode Migration (PHP 7.4)

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 2 - Programmatic CPT Content, Shortcode & Menu Migration  
**STATUS**: `[REVISED SPEC / VERIFICATION PENDING]`  

---

## 1. Objective & Target Users
* **Objective**: Programmatically migrate `alx_tachydromos` newsletters and `board_member` records, replace legacy sliders with native Gutenberg blocks, remediate all residual shortcodes, and assign navigation menus using a unified content migration script (`bin/run-phase2-migration.sh` wrapping `wp eka migrate-all`) under PHP 7.4. Every migration step dumps its output to a dedicated clean log file, and the unified script logs its overarching results.
* **Target Users**: Portal visitors, content editors, and system scripts.

---

## 2. Full CPT Definitions & Acceptance Criteria `[SPECIFICATION REVISED]`

### Unified Content Migration Script (`bin/run-phase2-migration.sh` / `wp eka migrate-all`)
* **Goal**: A single executable script combining all sub-migration tasks into an ordered, idempotent migration pass.
* **Unified Logging Mandate**:
  - The overarching script output MUST be dumped to `ai-work/logs/phase2-unified-migration.log`.
  - Each individual sub-command MUST also dump its stdout/stderr to its own dedicated clean log file in `ai-work/logs/`.

### Sub-Task 1: Alexandrinos Tachydromos (`alx_tachydromos`)
* **CPT Slug**: `alx_tachydromos`
* **Command**: `php7.4 $(which wp) eka migrate-tachydromos`
* **Log File**: `ai-work/logs/tachydromos-migration.log`
* **Features & Rules**:
  - Title Normalization: Convert all-caps Greek month names to proper case (e.g. "ΙΟΥΝΙΟΣ 2026" -> "Ιούνιος 2026").
  - Date Mapping: Map post dates to `YYYY-MM-01 00:00:00`.
  - PDF Embedding: Embed newsletter PDFs using native `core/file` block (`displayPreview: true`, height `600px`).
  - Image Strategy: Re-assign existing unscaled media library attachment IDs by stripping dimension suffixes (e.g. `-724x1024.jpg` -> `.jpg`). **NEVER** re-upload or re-scale existing images.
  - Metadata: Store `_eka_pdf_filename` and `_eka_pdf_attachment_id` for idempotency.

### Sub-Task 2: Board Members (`board_member`)
* **CPT Slug**: `board_member`
* **Command**: `php7.4 $(which wp) eka migrate-board`
* **Log File**: `ai-work/logs/board-migration.log`
* **Features & Rules**:
  - Body Content Cleaning: Completely strip `<img>` tags and `[vc_*]` shortcodes from `post_content`.
  - Image Strategy: Re-assign existing unscaled media attachment IDs to post thumbnail (`_thumbnail_id`) without cropping or re-downloading.
  - Polylang Translation Linking: Bind Greek (`el`), English (`en`), and Arabic (`ar`) board member posts using `pll_save_post_translations($new_group)`.
  - Metadata: Store `_legacy_testimonial_id` for idempotency.

### Sub-Task 3: Slider Replacement Logic
* **Command**: `php7.4 $(which wp) eka replace-sliders`
* **Log File**: `ai-work/logs/sliders-migration.log`
* **Dynamic Sliders**: Replace homepage/news sliders on pages `13236, 17194, 17215, 17219, 8934, 16920, 16923` with native `core/query` loops pulling 5 latest posts.
* **Static Sliders**: Replace static sliders on inner pages with `core/gallery` blocks using pre-mapped media IDs from `legacy_data.md`:
  - Music Museum (`7820, 17129, 17133`): Media IDs `7821, 7822, 7823`.
  - Science Museum (`7811, 17137, 17139`): Media IDs `7813, 7814, 7815`.
  - Cemeteries (`3442, 17023, 17155`): Media IDs `10329, 7667, 7668, 7669, 7670, 7671, 7672, 7673`.
  - Cemeteries Conservation (`7756, 17150`): Media IDs `7935, 7936, 7937, 7938, 7939, 7940, 7941, 7942`.
  - Community Lounge (`7390, 17018, 17020`): Media ID `10328`.

### Sub-Task 4: Shortcode Remediation & Sub-navigation
* **Command**: `php7.4 $(which wp) eka remediate-shortcodes`
* **Log File**: `ai-work/logs/remediate-shortcodes.log`
* **Testimonials Shortcode**: Replace `[testimonials]` with native `board_member` Query Loop.
* **Posts Grid Shortcode**: Replace `[vc_posts_grid]` with page Query Loop.
* **Sidebar Injections**: Inject native `core/navigation` blocks into two-column layouts for pages requiring sub-navigation sidebars (Menus 70, 71, 117, 3377, 3378, 3944, 3945, 3707, 3716).

### Sub-Task 5: Navigation Menu Assignments
* **Greek Main Menu**: ID 13 (`Main Greek Menu`)
* **English Main Menu**: ID 3315 (`Main English Menu`)
* **Arabic Main Menu**: ID 3316 (`Main Arabic Menu`)
* **Greek Footer Menu**: ID 21 (`Footer Greek Menu`)

---

## 3. Tech Stack Preferences & Constraints
* **PHP Routing**: Strictly `php7.4` (`php7.4 $(which wp) ...`).
* **Source Files**: `inc/cli-commands.php`, `inc/custom-features.php`, `bin/run-phase2-migration.sh`.
* **Idempotency**: Verify existing metadata (`_eka_pdf_filename`, `_legacy_testimonial_id`) to prevent duplicate post creation.
* **Clean Logging**: Every subcommand and the main wrapper MUST write to clean log files in `ai-work/logs/`.

---

## 4. Commands
```bash
# Execute individual migration tasks with clean logging
php7.4 $(which wp) eka migrate-tachydromos --path=public > ai-work/logs/tachydromos-migration.log 2>&1
php7.4 $(which wp) eka migrate-board --path=public > ai-work/logs/board-migration.log 2>&1
php7.4 $(which wp) eka replace-sliders --path=public > ai-work/logs/sliders-migration.log 2>&1
php7.4 $(which wp) eka remediate-shortcodes --path=public > ai-work/logs/remediate-shortcodes.log 2>&1

# OR execute unified migration wrapper script
bash bin/run-phase2-migration.sh > ai-work/logs/phase2-unified-migration.log 2>&1

# Audit post counts
php7.4 $(which wp) post count alx_tachydromos --path=public
php7.4 $(which wp) post count board_member --path=public
```

---

## 5. Project Structure
```text
bin/
└── run-phase2-migration.sh       # Unified Phase 2 migration runner script
inc/
├── cli-commands.php              # WP-CLI subcommands
└── custom-features.php           # Registered CPTs alx_tachydromos and board_member
ai-work/
├── scopings/
│   ├── tachydromos-scoping.json
│   ├── board-scoping.json
│   ├── legacy-ids.json
│   └── legacy_data.md
└── logs/
    ├── phase2-unified-migration.log
    ├── tachydromos-migration.log
    ├── board-migration.log
    ├── sliders-migration.log
    └── remediate-shortcodes.log
```

---

## 6. Code Style & Testing Strategy
* **Testing Strategy**:
  1. Inspect `ai-work/logs/phase2-unified-migration.log` and individual logs for clean execution and zero errors.
  2. CLI verification matching imported post counts to scoping arrays.
  3. Verify zero `<img>` tags inside `board_member` body content.
  4. Re-run unified script to verify 100% idempotency.
* **Governance**:
  - **ALWAYS**: Route all WP-CLI commands through `php7.4`.
  - **ALWAYS**: Require manual spec verification before generating Git commits.
  - **ALWAYS**: Require **Manual User Validation Pause** at completion of Phase 2 before proceeding to Phase 3.
  - **NEVER**: Re-upload or re-crop existing media items.
