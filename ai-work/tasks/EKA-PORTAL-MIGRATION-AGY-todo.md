# TOPIC NAME: EKA-PORTAL
# ISSUE NAME: MIGRATION-AGY
# Task List: EKA Portal Modernization & Programmatic Migration

## Phase 1: Scoping Data & Script Refinement (Mastery & Codification Phase)
- [x] Task 1.1: Scaffold `board-scoping.json` (EL/EN/AR translation mappings) and update `tachydromos-scoping.json` (normalize unscaled image URLs) <!-- id: 1.1 -->
- [x] Task 1.2: Refine `bin/reset-env.sh` (codify DB sync check to prevent ID collisions, fix Mailchimp autoloader vendor errors) <!-- id: 1.2 -->
- [x] Task 1.3: Refine CLI migration commands in `inc/cli-commands.php` (Tachydromos Greek month title casing, unscaled media ID reassignment; Board Polylang `pll_save_post_translations` linking, strip body `<img>` tags; log outputs, errors, issues, fallbacks, and technical reasoning into `ai-work/logs/`) <!-- id: 1.3 -->
- [x] Task 1.4: Refine `bin/cleanup-plugins.sh` (codify WP-CLI deactivation/uninstall with `rm -rf` fallback logic and reasoned logging to `ai-work/logs/cleanup-plugins.log`) <!-- id: 1.4 -->

## Phase 2: Programmatic Execution & Verification Phase
- [ ] Task 2.1: Execute clean DB staging reset via `bin/reset-env.sh` <!-- id: 2.1 -->
- [ ] Task 2.2: Verify custom post type registrations (`alx_tachydromos`, `board_member`) in `inc/custom-features.php` <!-- id: 2.2 -->
- [ ] Task 2.3: Execute `wp eka migrate-tachydromos` and verify `ai-work/logs/tachydromos-migration.log` <!-- id: 2.3 -->
- [ ] Task 2.4: Execute `wp eka migrate-board` and verify `ai-work/logs/board-migration.log` <!-- id: 2.4 -->
- [ ] Task 2.5: Execute `wp eka replace-sliders` and verify `ai-work/logs/sliders-migration.log` <!-- id: 2.5 -->
- [ ] Task 2.6: Execute `wp eka remediate-shortcodes` (replace BeTheme shortcodes and sub-navigation sidebars) <!-- id: 2.6 -->
- [ ] Task 2.7: Execute `bin/cleanup-plugins.sh` and verify `ai-work/logs/cleanup-plugins.log` <!-- id: 2.7 -->
- [ ] Task 2.8: User Output Validation Checkpoint (Mandatory Human Pause for WP Admin inspection of posts, images, and translations) <!-- id: 2.8 -->

## Phase 3: System PHP Upgrade & Plugin Updates
- [ ] Task 3.1: Developer Checkpoint — HALT & notify user for system PHP 7.4 -> 8.2 upgrade (`sudo update-alternatives --config php`) <!-- id: 3.1 -->
- [ ] Task 3.2: WP-CLI plugin updates under PHP 8.2 (`wp plugin update --all`) <!-- id: 3.2 -->
- [ ] Task 3.3: Routing & permalink integrity verification (`wp rewrite flush`) <!-- id: 3.3 -->

## Phase 4: Modern FSE Theme & Multi-language Development
- [ ] Task 4.1: Modern design system & token configuration (`theme.json` + `assets/scss/`) compiled via `@wordpress/scripts` <!-- id: 4.1 -->
- [ ] Task 4.2: Scaffolding language-specific FSE templates & parts (`front-page-el.html`, `front-page-en.html`, `front-page-ar.html`, `header-ar.html`, etc.) <!-- id: 4.2 -->
- [ ] Task 4.3: Right-to-Left (RTL) SCSS framework (`assets/scss/rtl.scss`) <!-- id: 4.3 -->
- [ ] Task 4.4: Mailchimp newsletter block re-engineering <!-- id: 4.4 -->
- [ ] Task 4.5: Search system & results page restoration (`search.html` template and header search trigger integration) <!-- id: 4.5 -->

## Phase 5: Verification & Deployment Manifest
- [ ] Task 5.1: Automated Playwright visual parity audit against `ai-work/baselines/` <!-- id: 5.1 -->
- [ ] Task 5.2: Production asset bundle optimization (`npm run build`) omitting `node_modules` and raw SCSS <!-- id: 5.2 -->
- [ ] Task 5.3: Deployment manifest generation (`ai-work/deployment_manifest.md`) and production cutover script (`wp eka production-cutover`) logging to `ai-work/logs/cutover.log` <!-- id: 5.3 -->
