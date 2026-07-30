# TOPIC NAME: EKA-PORTAL
# ISSUE NAME: MIGRATION-AGY
# Task List: EKA Portal Modernization & Migration

## Phase 1: Environment & Orchestration Setup
- [ ] Task 1.1: Pre-flight environment validation (`reset-env.sh`, PHP 7.4 CLI routing, WP-CLI, ImageMagick, Ghostscript, policy.xml permissions) <!-- id: 1.1 -->
- [ ] Task 1.2: Development orchestration layer setup (`setup-orchestration.sh`, `@wordpress/scripts`, `playwright`, `@wordpress/block-serialization-default-parser`) <!-- id: 1.2 -->
- [ ] Task 1.3: Baseline screenshot extraction across Greek, English, and Arabic live pages into `ai-work/baselines/` <!-- id: 1.3 -->
- [ ] Task 1.4: Scaffold CLI command namespace (`inc/cli-commands.php`) and custom feature hooks (`inc/custom-features.php`) <!-- id: 1.4 -->

## Phase 2: Programmatic Data Migration (PHP 7.4 CLI)
- [ ] Task 2.1: Register `alx_tachydromos` CPT (`show_in_rest`, Greek slug `αλεξανδρινός-ταχυδρόμος`) and `_eka_pdf_filename` meta in `inc/custom-features.php` <!-- id: 2.1 -->
- [ ] Task 2.2: Implement and execute `wp eka migrate-tachydromos` reading `ai-work/scopings/tachydromos-scoping.json` (title, image reassignment, PDF link, post_date, core/file block, idempotency) <!-- id: 2.2 -->
- [ ] Task 2.3: Register `board_member` CPT (`publicly_queryable` = false, `menu_order` support) and `_eka_legacy_id` meta in `inc/custom-features.php` <!-- id: 2.3 -->
- [ ] Task 2.4: Implement and execute `wp eka migrate-board` reading testimonials data with `pll_save_post_translations` mapping <!-- id: 2.4 -->
- [ ] Task 2.5: Implement and execute `wp eka replace-sliders` to replace dynamic Layer Sliders with Query Loops and static Revolution Sliders with native `core/gallery` blocks <!-- id: 2.5 -->
- [ ] Task 2.6: Sub-navigation & shortcode remediation (replace BeTheme shortcodes/sidebars with Query Loops and Navigation blocks) <!-- id: 2.6 -->
- [ ] Task 2.7: AST block serialization validation and migration integrity audit <!-- id: 2.7 -->
- [ ] Task 2.8: Deactivate legacy plugins via WP-CLI (`wp plugin deactivate`) <!-- id: 2.8 -->

## Phase 3: System Architecture Switch & Environment Upgrade
- [ ] Task 3.1: Execution Checkpoint — HALT & notify user to upgrade environment to PHP 8.2 <!-- id: 3.1 -->
- [ ] Task 3.2: WP-CLI plugin update under PHP 8.2 and routing/front page permalink validation <!-- id: 3.2 -->

## Phase 4: Modern FSE Theme & Multi-language Development
- [ ] Task 4.1: SCSS styling architecture set up in `src/scss/` with token mapping from `styles.json` / `betheme-options.json` compiled via `@wordpress/scripts` <!-- id: 4.1 -->
- [ ] Task 4.2: Polylang FSE block templates scaffolding (`front-page-el`, `front-page-en`, `front-page-ar`, `header-ar`, `footer-en`, `page-el`, etc.) <!-- id: 4.4 -->
- [ ] Task 4.3: Right-to-Left (RTL) CSS integration for Arabic templates <!-- id: 4.3 -->
- [ ] Task 4.4: Mailchimp newsletter registration block re-engineering <!-- id: 4.4 -->
- [ ] Task 4.5: Search system restoration (functional `search.html` block template and header trigger integration) <!-- id: 4.5 -->

## Phase 5: Verification, Testing & Deployment Manifest
- [ ] Task 5.1: Visual regression testing via Playwright comparing FSE renders against `ai-work/baselines/` <!-- id: 5.1 -->
- [ ] Task 5.2: Final production bundle build (`npm run build`) omitting `node_modules` and raw SCSS <!-- id: 5.2 -->
- [ ] Task 5.3: Deployment manifest generation (`deployment_manifest.md`) and production cutover script (`wp eka production-cutover`) <!-- id: 5.3 -->
