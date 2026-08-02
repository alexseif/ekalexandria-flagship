# TOPIC NAME: EKA-PORTAL
# ISSUE NAME: MIGRATION-AGY

## 1. Objective
To execute a full programmatic technical modernization and migration of the Greek Community of Alexandria portal (`ekalexandria.org`) into the blank `ekalexandria-flagship` Full Site Editing (FSE) block theme. The goal is to achieve a strict 1:1 visual replication of the existing BeTheme design without manual content rewriting, utilizing a repeatable, validated, programmatic workflow.

> [!IMPORTANT]
> **Production Cutover Constraint & Issue/Reasoning Logging:** The final production deployment and cutover will be executed via standalone scripts and WP-CLI commands WITHOUT an AI assistant present. Any manual workarounds or fallback logic (e.g. file removals if WP-CLI uninstall fails) MUST be codified into script files (`inc/cli-commands.php`, `bin/cleanup-plugins.sh`). All scripts MUST log execution outputs, errors, **specific issues encountered, fallback actions taken, and the technical reasoning** into `ai-work/logs/` for post-run audits and script refinement.

## 2. Core Features and Acceptance Criteria

### 2.1 Alexandrinos Tachydromos (Newsletters) Migration
- **Status:** `[IMPLEMENTED & REFINEMENT PENDING]` — Command: `wp eka migrate-tachydromos`
- **Execution Strategy:** Refine scoping array and CLI implementation in `inc/cli-commands.php`; execute command. Log outputs to `ai-work/logs/tachydromos-migration.log`.
- **Acceptance Criteria & Specific Fixes:**
  - **Data Source & Database Integrity:** `ai-work/scopings/tachydromos-scoping.json`. Environment reset (`reset-env.sh`) must verify DB synchronization from local production copy to prevent post ID collisions/duplications.
  - **CPT:** `alx_tachydromos` (`'show_in_rest' => true`, Greek rewrite slug `αλεξανδρινός-ταχυδρόμος`).
  - **Title Normalization & Month Casing:** Strip HTML tags and enforce consistent Greek Month Title Casing (e.g. "Ιούνιος 2026", "Μάρτιος 2026").
  - **Date Mapping:** Parse extracted title/url to set authentic `post_date` (`YYYY-MM-01 00:00:00`).
  - **Featured Image (No Re-uploading / No Image Scaling):** Re-assign existing media attachment IDs from the database using unscaled original media URLs (e.g. strip dimension suffixes like `-724x1024` from `img_url` to match the original unscaled attachment in DB). Do NOT download or re-upload existing media library images.
  - **PDF Block Embedding:** Render PDF via native `core/file` block embed (`displayPreview: true`).
  - **Automated PNG Thumbnail Hook:** Save hook (`save_post_alx_tachydromos`) renders page 1 of newly uploaded PDFs into a PNG featured image via ImageMagick/Ghostscript.
  - **Idempotency:** Checked via `_eka_pdf_filename` post meta tag.

### 2.2 Board of Directors Migration
- **Status:** `[IMPLEMENTED & REFINEMENT PENDING]` — Command: `wp eka migrate-board`
- **Execution Strategy:** Refine scoping array in `ai-work/scopings/board-scoping.json` to define EL, EN, AR translation relationships. Refine CLI command in `inc/cli-commands.php`; execute command. Log outputs to `ai-work/logs/board-migration.log`.
- **Acceptance Criteria & Specific Fixes:**
  - **CPT:** `board_member` (`publicly_queryable => false`, `menu_order` support).
  - **Scoping-Driven Translation Linking:** Explicitly group EL, EN, and AR member IDs in `board-scoping.json` so `pll_save_post_translations` natively links member translations during migration.
  - **Featured Image Reassignment (No Re-uploading / No Image Scaling / No Local Cropping):** Re-assign existing media library attachment IDs by matching unscaled original filenames (e.g. strip dimension suffixes like `-246x300` to find the original `index-5.jpg` attachment ID). Do NOT re-upload images or run local ImageMagick cropping/resizing on existing media. Set as `_thumbnail_id`.
  - **Content Cleaning (Strip Images from Body):** Strip `<img>` tags, legacy WPBakery `[vc_*]` shortcodes, and image elements completely from `post_content`. The body content must strictly contain the member bio/text.
  - **Idempotency:** Checked via `_legacy_testimonial_id` post meta tag.

### 2.3 Legacy Slider & Shortcode Mitigation
- **Status:** `[IMPLEMENTED & VERIFIED]` — Command: `wp eka replace-sliders`
- **Execution Strategy:** Validate existing CLI implementation in `inc/cli-commands.php`; execute command. Log outputs to `ai-work/logs/sliders-migration.log`.
- **Acceptance Criteria:**
  - **Dynamic Layer Sliders:** Replace `[layerslider]` / `[rev_slider]` shortcodes on dynamic pages (IDs: `13236, 17194, 17215, 17219, 8934, 16920, 16923`) with native `core/query` blocks.
  - **Static Revolution Sliders:** Replace `[rev_slider]` / `[layerslider]` shortcodes on static gallery pages with native `core/gallery` blocks using pre-mapped attachment Media IDs defined in `legacy_data.md` and `legacy-ids.json`.

### 2.4 Sub-Navigation & Shortcode Remediation
- **Status:** `[IMPLEMENTED & VERIFIED]` — Integration in migration scripts.
- **Acceptance Criteria:** Replace BeTheme sidebars and custom shortcodes with native core Query Loops or Navigation blocks.

### 2.5 Legacy Plugin Cleanup & Deactivation
- **Status:** `[IMPLEMENTED & PENDING EXECUTION]` — Script: `bin/cleanup-plugins.sh`
- **Execution & Logging Strategy:** Script must attempt WP-CLI plugin deactivation/uninstallation first; if WP-CLI fails (e.g. missing plugin files), execute explicit directory/file fallback removal (`rm -rf wp-content/plugins/<plugin-dir>`) AND log all actions, issues encountered, fallbacks, and technical reasoning into `ai-work/logs/cleanup-plugins.log`.

### 2.6 Core Site Features & Template Systems
- **1:1 Visual Parity:** Visual output must strictly match legacy BeTheme design.
- **Mailchimp Newsletter Registration:** Re-engineer newsletter registration block securely without legacy plugin autoload dependencies.
- **Search System Restoration:** Functional search template (`search.html`) integrated with main header search trigger.
- **Polylang FSE Template Variations:** Scaffolding explicit language templates (`front-page-el`, `front-page-en`, `front-page-ar`, `header-ar`, `footer-en`, etc.).
- **RTL Language Support:** Comprehensive SCSS framework (`assets/scss/rtl.scss`).

## 3. Tech Stack Preferences and Constraints
- **Required Packages & Plugins:**
  - *NPM:* `@wordpress/scripts`, `playwright`, `@wordpress/block-serialization-default-parser`.
  - *System:* `php7.4`, `wp-cli`, `imagemagick`, `ghostscript`.
  - *WordPress:* `polylang` (active during migration for translation binding).

- **Execution Flow & Staged Migration Protocol:**

  #### Phase 1: Scoping Data & Script Refinement (Mastery & Codification Phase)
  1. **Scoping Data Update:** Refine `ai-work/scopings/board-scoping.json` (add translation group mappings for EL, EN, AR) and `tachydromos-scoping.json` (normalize unscaled image URLs).
  2. **Environment Reset Script Refinement (`bin/reset-env.sh`):** Codify database synchronization from local production copy to mirror `eka` without post ID duplications/collisions, and resolve Mailchimp plugin autoloader vendor errors.
  3. **Migration Command Refinement (`inc/cli-commands.php`):**
     - Update `wp eka migrate-tachydromos`: enforce Greek month title casing, re-assign existing unscaled attachment IDs without re-uploading, and log reasoning to `ai-work/logs/tachydromos-migration.log`.
     - Update `wp eka migrate-board`: execute Polylang translation linking via `pll_save_post_translations`, re-assign existing unscaled attachment IDs without re-uploading/cropping, strip `<img>` tags from body content, and log reasoning to `ai-work/logs/board-migration.log`.
  4. **Plugin Cleanup Script Refinement (`bin/cleanup-plugins.sh`):** Codify autonomous plugin deactivation and uninstallation with `rm -rf` directory fallback logic and reasoned logging to `ai-work/logs/cleanup-plugins.log`.

  #### Phase 2: Programmatic Execution & Verification Phase
  1. **Reset Environment:** Execute updated `bin/reset-env.sh` to mirror production database cleanly.
  2. **Register Custom Post Types:** Verify CPT registrations in `inc/custom-features.php`.
  3. **Execute Migration Commands:** Run `wp eka migrate-tachydromos`, `wp eka migrate-board`, and `wp eka replace-sliders` via PHP 7.4 CLI.
  4. **Disable & Clean Legacy Plugins:** Run updated `bin/cleanup-plugins.sh` autonomously.
  5. **User Output Validation Checkpoint:** User reviews migration results, post IDs, and images in WP Admin before proceeding to PHP upgrade.

  #### Phase 3: System PHP Upgrade & Plugin Updates
  1. **PHP Upgrade Checkpoint:** HALT and notify user for system PHP 7.4 -> 8.2 upgrade.
  2. **Plugin Updates:** Execute `wp plugin update --all` under PHP 8.2.
  3. **Routing Preservation:** Execute `wp rewrite flush` to verify permalinks.

  #### Phase 4: Modern FSE Theme & Multi-language Development
  1. Build modern SCSS design system (`assets/scss/`) & RTL framework (`assets/scss/rtl.scss`).
  2. Scaffold language-specific FSE templates (`front-page-el`, `front-page-en`, `front-page-ar`, `header-ar`, etc.).
  3. Re-engineer Mailchimp newsletter block and restore search template (`search.html`).

  #### Phase 5: Verification & Deployment Manifest
  1. Perform visual parity audit via Playwright against `ai-work/baselines/`.
  2. Compile production bundle (`npm run build`).
  3. Generate standalone cutover script (`wp eka production-cutover`).

- **Logging & Reasoning Mandate:** Every CLI command and bash script MUST log execution output, errors, specific issues encountered, fallback actions taken, and technical reasoning to a dedicated log file in `ai-work/logs/` (e.g. `tee -a ai-work/logs/<script-name>.log`).

## 4. Boundaries
- **Always Do:** 
  - **Explicit PHP CLI Routing:** Run WP-CLI via PHP 7.4 (`php7.4 $(which wp) ...`) until PHP 8.2 upgrade checkpoint.
  - **Self-Contained Script Codification with Reasoned Logging:** Any manual fix or workaround used by an agent MUST be written back into the script files and logged with issue & reasoning so production cutover runs without human or AI intervention.
  - **Execution & Reasoning Logging:** Ensure all scripts log outputs, issues, and reasoning to `ai-work/logs/`.
  - Preserve `AGY_INSTRUCTIONS.md`, `Master Project Roadmap...`, and `ai-work/` directories.
  - Use atomic Git commits per task.
  - **Execution Halt Standard:** Halt immediately on infrastructure failures or ambiguity; provide options and wait for approval.
- **Ask First:** If any translation mapping is ambiguous, halt for user input.
- **Never Do:** 
  - Never download or re-upload existing media library images or apply scaling/cropping during migration; re-assign existing attachment IDs using unscaled original media URLs.
  - Never include `<img>` tags inside Board Member `post_content`.
  - Never rely on interactive AI workarounds for production cutover tasks without codifying them into scripts.
  - Never write `style="..."` attributes inside templates. 
  - Never download the full media library. 
  - Never rewrite content manually.

## 5. Design Patterns & Coding Standards
- **WP-CLI Custom Commands:** Established in `inc/cli-commands.php` (`wp eka migrate-tachydromos`, `wp eka migrate-board`, `wp eka replace-sliders`).
- **Feature Encapsulation:** CPT registrations, meta fields, and save hooks encapsulated in `inc/custom-features.php`.
- **SCSS Architecture & Compilation:** Modular SCSS compiled via `@wordpress/scripts` (`npm run dev` for dev, `npm run build` for production bundle).

## 6. Project Structure & Observability
- **Root Directory:** `/var/www/backstage.ekalexandria.org/public/wp-content/themes/ekalexandria-flagship/`
- **Data Handoff:** `ai-work/scopings/`
- **Logs Directory:** `ai-work/logs/`
- **CLI Commands:** `inc/cli-commands.php`
- **Custom Features:** `inc/custom-features.php`

## 7. Testing & Verification Strategy
- **Log & Reasoning Auditing:** Inspect log files in `ai-work/logs/` after each command execution to review logged issues, fallbacks, and technical reasoning.
- **Command Output Validation:** Verify post counts, CPT status, meta assignments, unscaled featured image attachment IDs, and Polylang language linkages.
- **AST Serialization:** Validate block structures via `@wordpress/block-serialization-default-parser`.
- **Visual Regression:** Audit modern templates against baselines in `ai-work/baselines/`.

## 8. Deployment Strategy
- **Manual Theme Upload:** Upload minified production theme bundle (excluding `node_modules` and raw SCSS).
- **Standalone Production Cutover Script:** A single self-contained script (`wp eka production-cutover`) executes on the live server without AI assistance, running theme activation, plugin cleanup (with file removal fallbacks), and CLI data migrations while logging all outputs, issues, and technical reasoning to `ai-work/logs/cutover.log`.
