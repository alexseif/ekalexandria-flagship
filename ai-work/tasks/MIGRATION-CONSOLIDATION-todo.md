# Todo Checklist: Legacy WordPress Migration Strategy & Modular Pipeline Refactoring

- [ ] **Task 1: Refactor Staging Environment Reset Script (`bin/01-reset-env.sh`)**
  - [ ] Copy and refactor `bin/reset-env.sh` to `bin/01-reset-env.sh`
  - [ ] Add explicit `--exclude='wp-content/themes/ekalexandria-flagship/***'` to `rsync`
  - [ ] Truncate log `ai-work/logs/01-reset-env.log` on startup
  - [ ] Ensure autoloader hash patches and WPBakery nested ternary syntax fix are preserved
  - [ ] Verify bash syntax with `bash -n bin/01-reset-env.sh`

- [ ] **Task 2: Implement Theme Activation, CPT Import & Legacy Cleanup (`bin/02-setup-theme-and-plugins.sh`)**
  - [ ] Create `bin/02-setup-theme-and-plugins.sh`
  - [ ] Extract CPT migration logic to `bin/migrate-cpts.php` (Tachydromos PDFs & Board Member testimonials)
  - [ ] Add WP-CLI theme activation for `ekalexandria-flagship`
  - [ ] Implement legacy plugin deletion with `rm -rf` directory fallback
  - [ ] Remove legacy drop-ins (`advanced-cache.php`, `object-cache.php`, `cache/`, `w3tc-config/`)
  - [ ] Verify syntax with `bash -n bin/02-setup-theme-and-plugins.sh` and `php7.4 -l bin/migrate-cpts.php`

- [ ] **Task 3: Consolidate Content Migration Engine (`bin/migration-content-engine.php` & `bin/03-migrate-content.sh`)**
  - [ ] Create `bin/migration-content-engine.php` with modular 6-step transformation handlers:
    - [ ] Step 3A: Scoped Slider Replacement (`[rev_slider]`, `[layerslider]`)
    - [ ] Step 3B: Testimonials Shortcode Remediation (`[testimonials]`)
    - [ ] Step 3C: Isolated `vc_posts_grid` Sub-navigation Handler + `TODO` comment for future variant analysis
    - [ ] Step 3D: Structural WPBakery & Caption Shortcode Conversion (`[vc_row]`, `[vc_column]`, `[caption]`)
    - [ ] Step 3E: Residual Shortcode Clean-Up (`/vc_*`, `/mfn_*`, wrap unrecognized in `wp:html`)
    - [ ] Step 3F: Classic HTML AST Block Conversion & Inline CSS Allowlist Filtering
  - [ ] Implement `bin/03-migrate-content.sh` orchestrator:
    - [ ] Trigger content engine execution
    - [ ] Flush transient cache (`wp transient delete --all`)
    - [ ] Assign Page Templates (Homepage `front-page-el/en/ar`, Parent/Child pages `page-parent-sidebar`)
    - [ ] Assign Main Navigation Menu Locations & Seed Footer Posts
    - [ ] Assign Sidebar Navigation Menus + `TODO` comment for implementation logic
  - [ ] Add structured metric reporting (`Scanned`, `Converted`, `Skipped`, `Failed`)
  - [ ] Verify syntax with `bash -n bin/03-migrate-content.sh` and `php7.4 -l bin/migration-content-engine.php`

- [ ] **Task 4: Legacy Script Cleanup & Preservation Verification**
  - [ ] Remove deprecated scripts: `ai-work/cleanup-plugins.sh`, `bin/cutover.sh`, `bin/run-phase2-migration.sh`, `bin/run-phase4-migration.sh`
  - [ ] Verify all scoping JSON files in `ai-work/scopings/` remain intact

- [ ] **Task 5: Post-Migration PHP 8.2 Upgrade & WP Core Update Documentation**
  - [ ] Document PHP 8.2 web server & CLI switch commands
  - [ ] Document WP core database and plugin update commands
