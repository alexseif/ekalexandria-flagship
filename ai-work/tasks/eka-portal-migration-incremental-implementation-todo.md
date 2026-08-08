# TASK LIST: EKA PORTAL INCREMENTAL MODERNIZATION

**Topic Name:** `eka-portal-migration`  
**Issue Name:** `incremental-implementation`  

- [ ] **Phase 1: Theme & FSE Foundation Standardization**
  - [ ] Task 1.1: Standardize `theme.json` with `customTemplates` array and `templateParts` area definitions
  - [ ] Task 1.2: Research & evaluate lightweight plugins vs custom code for Social Share buttons and Polylang FSE integration

- [ ] **Phase 2: CPT & Newsletter Architecture Fixes**
  - [ ] Task 2.1: Implement Newsletter (`alx_tachydromos`) admin PDF upload metabox and fix Gutenberg AST block output in `single-alx_tachydromos.html`
  - [ ] Task 2.2: Refine Board of Directors (`board_member`) page layout and submit for human revision checkpoint

- [ ] **Phase 3: Content Engine & Pipeline Script Refactoring**
  - [ ] Task 3.1: Update `bin/migration-content-engine.php` with LayerSlider Exception List (`13236`, `16894`, `16892`, `18`, `16920`, `16923`) and remaining 7 shortcode categories
  - [ ] Task 3.2: Rename scripts (`03` $\rightarrow$ `02-migrate-content.sh`, `06` $\rightarrow$ `03-assign-templates.sh`), remove automated menu assignments, update page ID mappings, and delete deprecated legacy scripts

- [ ] **Phase 4: Verification, Testing & Final Sign-Off**
  - [ ] Task 4.1: Execute end-to-end 3-stage pipeline dry-run, verify AST block validity, audit log files, and present manual admin checklist
