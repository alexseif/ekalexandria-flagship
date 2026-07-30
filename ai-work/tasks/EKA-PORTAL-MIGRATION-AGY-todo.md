# Task List: EKA-PORTAL - MIGRATION-AGY

## Phase 1: Environment & Extraction
- [x] **Task 1.1**: Write `reset-env.sh` and pre-flight validation script (ImageMagick, PHP 7.4).
- [x] **Task 1.2**: Write `setup-orchestration.sh` (`npm init`, Playwright, AST parser).
- [x] **Task 1.3**: Configure Playwright for baseline layout token extraction to JSON.
- [x] **Task 1.4**: Run scoping scripts to dump legacy IDs/mappings to `ai-work/scopings/`.

## Phase 2: Vertical Migrations
- [x] **Task 2.1**: Register `alx_tachydromos` CPT and Gutenberg meta fields.
- [x] **Task 2.2**: Write WP-CLI migration script for Tachydromos (PDF generation, dates, AST testing).
- [x] **Task 2.3**: Register `board_member` CPT (non-public).
- [x] **Task 2.4**: Write WP-CLI migration script for Board of Directors (Polylang translations, WPBakery extraction).
- [x] **Task 2.5**: Register `core/gallery` block variation for static legacy sliders.
- [x] **Task 2.6**: Write script to convert dynamic sliders to Query Loop blocks.
- [x] **Task 2.7**: Identify and uninstall unnecessary legacy plugins (e.g. W3 Total Cache, page builders when done) to ensure a clean environment.

## Phase 3: PHP Upgrade Checkpoint
- [x] **Task 3.1**: PAUSE execution. Wait for human operator to upgrade server to PHP 8.2.

## Phase 4: Theme FSE Implementation
- [x] **Task 4.1**: Build FSE block templates (`front-page-el`, `header-ar`, etc.) based on scoping JSON.
- [x] **Task 4.2**: Implement SCSS-driven styling and RTL CSS support for Arabic.
- [x] **Task 4.3**: Integrate native Search functionality.
- [x] **Task 4.4**: Re-engineer Mailchimp registration block.
- [x] **Task 4.5**: Replace BeTheme sidebars with native Navigation/Query blocks.

## Phase 5: Verification & Deployment
- [ ] **Task 5.1**: Execute Playwright visual regression tests against live site.
- [ ] **Task 5.2**: Prepare WP-CLI atomic cutover deployment script for live server.
