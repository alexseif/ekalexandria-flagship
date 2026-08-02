# Production Deployment Manifest: Ekalexandria Modernization

**Environment:** Production (`ekalexandria.org`) & Staging (`backstage.ekalexandria.org`)
**Date:** 2026-06-28
**Purpose:** Phase 1 & 2 CPT Migration and Theme Architecture Upgrades

## 1. Pre-Deployment Prerequisites
- **Server:** Ensure ImageMagick and Ghostscript are installed and active to allow PDF rendering.
- **Plugins:** Verify Advanced Custom Fields (ACF) and a PDF Image Generator plugin are active.
- **Database Backups:** Validate a safe snapshot of the legacy database (`db207080_eka`) is retained before executing operations.

## 2. Codebase & Component Updates
Pull the latest theme updates from the `ekalexandria-flagship` Git repository onto the server (`public/wp-content/themes/ekalexandria-flagship`). This bundle deploys the following system modifications:
- **CPT Architecture:**
  - Removal of the legacy `neo_fos` registration.
  - Registration of the new non-translatable `alx_tachydromos` CPT (Alexandrinos Tachydromos) with native ACF PDF upload capabilities.
  - Registration of the `board_member` CPT, explicitly linked with Polylang for translation mapping.
- **Full Site Editing (FSE) Templates:**
  - New FSE layouts: `archive-alx_tachydromos.html` and `single-alx_tachydromos.html`.
  - New FSE page layout: `page-board.html`.
- **Media Resolution Optimization:**
  - Configured `functions.php` to leverage WordPress core `post-thumbnails` support.
  - Upgraded media blocks across all single and archive templates to request the `large` resolution by default.
- **Migration CLI Scripts:**
  - Deploys `wp eka migrate-tachydromos` and `wp eka migrate-board` directly inside `inc/cli-commands.php`.

## 3. Database Schema Adjustments (Automated Execution)
Once the codebase is updated, the following custom database transformations must be executed via WP-CLI to move data out of legacy formats into native blocks.

1. **Migrate Newsletters (`alx_tachydromos`)**
   - **Command:** `wp eka migrate-tachydromos`
   - **Action:** Pulls old attachments from the legacy `db207080_eka` schema. Synchronizes the specific PDF upload timestamps to align post dates. Cleans and strips image-size dimension suffixes to cleanly attach valid full-sized thumbnails to each entry.
   
2. **Migrate Board of Directors (`board_member`)**
   - **Command:** `wp eka migrate-board`
   - **Action:** Selects raw legacy WPBakery grid codes from `db207080_eka`. Maps data to the new Gutenberg structure. Utilizes `pll_save_post_translations` to correctly link Greek, English, and Arabic variants natively in the database.

## 4. Post-Migration Cleanup
- Deactivate legacy visual builder plugins (e.g., `js_composer`, `LayerSlider`).
- Flush WP cache and rewrite rules to ensure the new Greek slugs (e.g., `αλεξανδρινός-ταχυδρόμος`) resolve correctly.

## 5. Deployment Automation
Run the entire sequence securely using Deployer:
```bash
# Run on Staging
dep deploy staging

# Run on Production
dep deploy production
```
