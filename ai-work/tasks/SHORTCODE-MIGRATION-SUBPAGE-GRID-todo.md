# TOPIC NAME: SHORTCODE-MIGRATION
# ISSUE NAME: SUBPAGE-GRID

# Task List: Subpage Grid Shortcode Migration to Gutenberg Query Loop

- [ ] **Task 1: Refactor `step_4c_transform_vc_posts_grid()` in `bin/04-shortcode-migrations.php`**
  - [ ] Update function signature to `step_4c_transform_vc_posts_grid($content, $post_id = 0)`.
  - [ ] Extract `by_id:ID1,ID2...` from `loop="..."` shortcode attribute into `"include": [ID1, ID2, ...]`.
  - [ ] Inject `"parents": [$post_id]` if `$post_id > 0`.
  - [ ] Build AST-compliant 2-column `wp:query` block wrapped in `wp:group` with 4:3 aspect ratio, 12px rounded corner featured images, and level 3 linked titles.
  - [ ] Handle wrapping `[vc_row]` / `[vc_column]` replacement around `[vc_posts_grid]`.
  - Verification: Test block conversion output against AST validator (`eka_validate_blocks_ast`).

- [ ] **Task 2: Update Main Loop Execution Call Site**
  - [ ] Pass current post `$id` into `step_4c_transform_vc_posts_grid($content, $id)` in `bin/04-shortcode-migrations.php`.
  - Verification: Ensure script parses correctly without PHP syntax errors or signature mismatches.

- [ ] **Task 3: Execute Migration Pipeline, Audit Post 16 & Verify Idempotency**
  - [ ] Execute `php public/wp-content/themes/ekalexandria-flagship/bin/04-shortcode-migrations.php`.
  - [ ] Inspect Post 16 content via `wp post get 16` to confirm exact block structure matching target schema.
  - [ ] Verify `ai-work/logs/04-shortcode-migrations.log` reports zero AST validation failures.
  - [ ] Re-run migration script to confirm idempotency (0 changes on second execution).
  - Verification: Clean pipeline execution, exact block markup match on Post 16, and confirmed idempotency.
