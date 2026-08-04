# TODO List: Shortcode & Classic Block Gutenberg Migration

**TOPIC NAME**: `shortcode-classic-block-migration`  
**ISSUE NAME**: `gutenberg-remediation`  
**SPEC**: `public/wp-content/themes/ekalexandria-flagship/ai-work/shortcode-classic-block-migration-SPEC.md`  

---

- [x] **Task 1: Shared Migration Utilities & FSE Inline Style Allowlist Filter**
  - [x] Implement `sanitize_inline_styles_fse()` in `bin/migration-helpers.php`.
  - [x] Implement `eka_validate_blocks_ast()` helper in `bin/migration-helpers.php`.
  - [x] Implement `eka_init_log_file()` helper in `bin/migration-helpers.php`.
  - [x] Create and run unit test script `bin/test-fse-sanitizer.php`.
  - [x] Run PHP linting (`php -l bin/migration-helpers.php`).
  - [x] Commit: `feat(migration): add shared helpers and FSE inline style allowlist filter`

- [ ] **Task 2: Stage 1 - Shortcode & WPBakery Gutenberg Transformer Script**
  - [ ] Implement `bin/remediate-shortcodes-to-blocks.php`.
  - [ ] Connect script to target database `backstage_eka`.
  - [ ] Add log file truncation on initialization (`ai-work/logs/remediate-shortcodes-to-blocks.log`).
  - [ ] Add regex/AST transformers for `[vc_row]`, `[vc_column]`, `[vc_column_text]`, `[vc_single_image]`, `[vc_raw_html]`, `[our_team]`, `[rev_slider]`, `[layerslider]`, `[caption]`, and generic shortcodes.
  - [ ] Integrate inline `parse_blocks()` AST check.
  - [ ] Run PHP linting (`php -l bin/remediate-shortcodes-to-blocks.php`).
  - [ ] Commit: `feat(migration): implement Stage 1 shortcode to block transformer script`

- [ ] **Task 3: Stage 2 - Idempotent Classic HTML to Gutenberg Block Converter Script**
  - [ ] Implement `bin/convert-classic-to-gutenberg.php`.
  - [ ] Connect script to target database `backstage_eka`.
  - [ ] Add log file truncation on initialization (`ai-work/logs/convert-classic-to-gutenberg.log`).
  - [ ] Add idempotency check to skip existing Gutenberg blocks (`<!-- wp:`).
  - [ ] Implement conversions for `<p>`, `<hN>`, `<ul>`/`<ol>`, `<table>`, `<blockquote>`.
  - [ ] Integrate `sanitize_inline_styles_fse()` for inline CSS.
  - [ ] Integrate inline `parse_blocks()` AST check.
  - [ ] Run PHP linting (`php -l bin/convert-classic-to-gutenberg.php`).
  - [ ] Commit: `feat(migration): implement Stage 2 classic HTML to block converter script`

- [ ] **Task 4: Bash Orchestration Runner Script (`bin/run-migration.sh`)**
  - [ ] Implement `bin/run-migration.sh` with `set -euo pipefail`.
  - [ ] Add execution steps for `bin/test-fse-sanitizer.php`, `bin/remediate-shortcodes-to-blocks.php`, and `bin/convert-classic-to-gutenberg.php`.
  - [ ] Add status logging and error handling.
  - [ ] Make executable (`chmod +x bin/run-migration.sh`).
  - [ ] Commit: `feat(migration): add bash runner script for automated 2-stage migration execution`

- [ ] **Task 5: Full Engine Execution & Database Verification on `backstage_eka`**
  - [ ] Run migration pipeline via `bin/run-migration.sh` on `backstage_eka`.
  - [ ] Verify log outputs in `ai-work/logs/`.
  - [ ] Perform DB queries to confirm 0 unhandled target shortcodes remain.
  - [ ] Run idempotency check (rerun Stage 2, verify 0 modifications).
  - [ ] Commit: `fix(migration): execute 2-stage Gutenberg migration on backstage_eka database`

- [ ] **Task 6: Final Checkpoint & Quality Review**
  - [ ] Review all changes against SPEC criteria.
  - [ ] Confirm `db207080_eka` remained read-only and all writes occurred strictly on `backstage_eka`.

