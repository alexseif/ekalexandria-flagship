# Todo: Migration Pipeline Optimization - Scoping & Modular Remediation

**TOPIC NAME**: Migration Pipeline Optimization  
**ISSUE NAME**: Scoping & Modular Remediation  
**SPEC PATH**: `public/wp-content/themes/ekalexandria-flagship/ai-work/MIGRATION-MODULARIZATION-SPEC.md`  
**PLAN PATH**: `public/wp-content/themes/ekalexandria-flagship/ai-work/tasks/migration-pipeline-optimization-scoping-modular-remediation-plan.md`  
**TODO PATH**: `public/wp-content/themes/ekalexandria-flagship/ai-work/tasks/migration-pipeline-optimization-scoping-modular-remediation-todo.md`

---

## Task List & Check-off Progress

- [x] **Task 1: Navigation Menu & Header Template Part Fixes**
  - [x] Replace `{"ref":72611}` with `{"slug":"main-menu"}` in `parts/header-el.html`
  - [x] Replace `{"ref":72611}` with `{"slug":"main-menu"}` in `parts/header.html`
  - [x] Update `bin/assign-menus.php` with scoping menu IDs (`main-menu`: 13, `en`: 3315, `ar`: 3316, `footer`: 21)
  - [x] Verify menu assignment script execution via WP-CLI

- [x] **Task 2: Surgical Page-Specific Migration Engine (`bin/03-surgical-migrations.php`)**
  - [x] Create `bin/03-surgical-migrations.php`
  - [x] Implement slider & gallery conversions using legacy scoping media IDs
  - [x] Implement testimonial & VC posts grid query loop conversions
  - [x] Add AST block validation before DB update
  - [x] Verify execution log `ai-work/logs/03-surgical-migrations.log`

- [x] **Task 3: Shortcode Remediation Engine (`bin/04-shortcode-migrations.php`)**
  - [x] Create `bin/04-shortcode-migrations.php`
  - [x] Implement WPBakery `vc_row` / `vc_column` column layout transformations
  - [x] Implement `vc_single_image`, `[our_team]`, and `[caption]` transformations
  - [x] Add AST block validation before DB update
  - [x] Verify execution log `ai-work/logs/04-shortcode-migrations.log`

- [ ] **Task 4: Classic HTML Block Conversion & CSS Sanitizer (`bin/05-classic-editor-migrations.php`)**
  - [ ] Create `bin/05-classic-editor-migrations.php`
  - [ ] Implement HTML element block conversion (`<p>`, `<h1>`-`<h6>`, `<ul>`, `<ol>`, `<table>`, `<blockquote>`)
  - [ ] Integrate FSE inline CSS allowlist sanitizer
  - [ ] Add AST block validation before DB update
  - [ ] Verify execution log `ai-work/logs/05-classic-editor-migrations.log`

- [ ] **Task 5: Pipeline Orchestration & Cache Flush Ordering (`bin/06-assign-templates-and-menus.sh`)**
  - [ ] Create `bin/06-assign-templates-and-menus.sh` with transient delete & cache flush prior to layout/menu assignments
  - [ ] Update `bin/03-migrate-content.sh` to run `03` -> `04` -> `05` -> `06` sequentially
  - [ ] Test full orchestrator execution

- [ ] **Task 6: End-to-End Pipeline Verification**
  - [ ] Verify Greek, English, and Arabic homepages load correct templates and menu IDs
  - [ ] Confirm zero AST validation failures in logs
