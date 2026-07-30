# TOPIC NAME: EKA-PORTAL
# ISSUE NAME: MIGRATION-AGY
# Task List: EKA Portal Modernization & Migration

## Phase 1: Environment & Orchestration Setup
- [x] Task 1.1: Update & execute `bin/reset-env.sh` (preserve `ai-work/`, `tasks/`, and project roadmap) <!-- id: 1.1 -->
- [x] Task 1.2: Verify development orchestration layer (reuse existing `node_modules`, `@wordpress/scripts`, `playwright` with `ignoreHTTPSErrors: true`) <!-- id: 1.2 -->
- [x] Task 1.3: Full-page baseline screenshot scrape across Greek (/el/), English (/en/welcome), and Arabic (/ar/مرحبا) live pages with scroll load delay into `ai-work/baselines/` (Mandatory Human Pause Checkpoint) <!-- id: 1.3 -->
- [x] Task 1.4: Verify or scaffold `inc/cli-commands.php` and `inc/custom-features.php` <!-- id: 1.4 -->

## Phase 2: Programmatic Data Migration (PHP 7.4 CLI)
- [ ] Task 2.1: Register `alx_tachydromos` CPT (`show_in_rest`, Greek slug `αλεξανδρινός-ταχυδρόμος`), `_eka_pdf_filename` meta, and PDF thumbnail generation hook via ImageMagick <!-- id: 2.1 -->
- [ ] Task 2.2: Implement and execute `wp eka migrate-tachydromos` reading `ai-work/scopings/tachydromos-scoping.json` <!-- id: 2.2 -->
- [ ] Task 2.3: Register `board_member` CPT (`publicly_queryable` = false, `menu_order` support) and `_eka_legacy_id` meta <!-- id: 2.3 -->
- [ ] Task 2.4: Implement and execute `wp eka migrate-board` with `pll_save_post_translations` mapping <!-- id: 2.4 -->
- [ ] Task 2.5: Implement and execute `wp eka replace-sliders` reading `ai-work/scopings/layer-sliders-scoping.json` & `legacy-ids.json` / `legacy_data.md` <!-- id: 2.5 -->
- [ ] Task 2.6: Sub-navigation & shortcode remediation (replace BeTheme shortcodes/sidebars with Query Loops and Navigation blocks) <!-- id: 2.6 -->
- [ ] Task 2.7: Legacy plugin deactivation & environment cleanup via `bin/cleanup-plugins.sh` <!-- id: 2.7 -->

## Phase 3: PHP Upgrade Checkpoint & Plugin Updates
- [ ] Task 3.1: Developer Checkpoint — HALT & notify user for system PHP 7.4 -> 8.2 upgrade <!-- id: 3.1 -->
- [ ] Task 3.2: WP-CLI plugin update under PHP 8.2 (`wp plugin update --all`) <!-- id: 3.2 -->
- [ ] Task 3.3: Routing & permalink integrity verification (`wp rewrite flush`, permalink check) <!-- id: 3.3 -->

## Phase 4: Modern FSE Theme & Multi-language Development
- [ ] Task 4.1: Modern design system & token configuration (`theme.json` + `assets/scss/`) compiled via `@wordpress/scripts` <!-- id: 4.1 -->
- [ ] Task 4.2: Scaffolding language-specific FSE templates & parts (`front-page-el.html`, `front-page-en.html`, `front-page-ar.html`, `header-ar.html`, etc.) <!-- id: 4.2 -->
- [ ] Task 4.3: Right-to-Left (RTL) SCSS framework (`assets/scss/rtl.scss`) <!-- id: 4.3 -->
- [ ] Task 4.4: Mailchimp newsletter block re-engineering <!-- id: 4.4 -->
- [ ] Task 4.5: Search system & results page restoration (`search.html` template and header search trigger integration) <!-- id: 4.5 -->

## Phase 5: Verification & Deployment Manifest
- [ ] Task 5.1: Automated Playwright visual parity audit against `ai-work/baselines/` <!-- id: 5.1 -->
- [ ] Task 5.2: Production asset bundle optimization (`npm run build`) omitting `node_modules` and raw SCSS <!-- id: 5.2 -->
- [ ] Task 5.3: Deployment manifest generation (`ai-work/deployment_manifest.md`) and production cutover script (`wp eka production-cutover`) <!-- id: 5.3 -->
