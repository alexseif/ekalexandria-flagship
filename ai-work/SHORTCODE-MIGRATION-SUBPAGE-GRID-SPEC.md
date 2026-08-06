# Specification: SHORTCODE-MIGRATION-SUBPAGE-GRID

**Topic Name:** SHORTCODE-MIGRATION
**Issue Name:** SUBPAGE-GRID

## 1. Objective
Update the `step_4c_transform_vc_posts_grid()` shortcode transformation step in `bin/04-shortcode-migrations.php` (Option 1) so that legacy `[vc_posts_grid]` shortcodes found in sub-pages (e.g. Post 16 "Δραστηριότητες") are transformed into a modern FSE 2-column grid query block structure during Stage 04 execution.

The script targets raw `[vc_posts_grid]` shortcodes directly during database content migration. It operates idempotently without modifying pre-existing Gutenberg blocks or re-transforming previously migrated content.

---

## 2. Target Gutenberg Block Schema

The generated block HTML must strictly match this exact AST-compliant structure:

```html
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group"><!-- wp:query {"queryId":3,"query":{"perPage":50,"pages":0,"offset":0,"postType":"page","order":"asc","orderBy":"menu_order","author":"","search":"","exclude":[],"sticky":"","inherit":false,"include":[7411,7409,7405,3444],"parents":[16]},"layout":{"type":"constrained"}} -->
<div class="wp-block-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":2}} -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/3","style":{"border":{"radius":{"topLeft":"12px","topRight":"12px","bottomLeft":"12px","bottomRight":"12px"}}}} /-->

<!-- wp:post-title {"level":3,"isLink":true} /-->
<!-- /wp:post-template --></div>
<!-- /wp:query --></div>
<!-- /wp:group -->
```

---

## 3. Key Transformation Rules

1. **Shortcode Pattern Matching Only:**
   - Match raw `[vc_posts_grid ...]` shortcodes in `wp_posts.post_content`.
   - Do **NOT** attempt to touch or re-parse existing `wp:query` blocks.
2. **Dynamic Query Attribute Extraction:**
   - Automatically extract page IDs from `by_id:ID1,ID2...` parameter within the shortcode `loop="..."` attribute and inject into `"include": [ID1, ID2, ...]`.
   - Automatically set `"parents": [<current_post_id>]` using the current `$post_id` parameter passed into `step_4c_transform_vc_posts_grid($content, $post_id)`.
3. **Template Formatting & Constraints:**
   - Wrapper: `wp:group` with `{"layout":{"type":"constrained"}}`.
   - Grid layout: `wp:post-template` with `{"layout":{"type":"grid","columnCount":2}}`.
   - Image card: `wp:post-featured-image` with `{"isLink":true,"aspectRatio":"4/3","style":{"border":{"radius":{"topLeft":"12px","topRight":"12px","bottomLeft":"12px","bottomRight":"12px"}}}}`.
   - Title: `wp:post-title` with `{"level":3,"isLink":true}`.
   - Excerpt: Removed (not rendered in template).
4. **Idempotency Guarantee:**
   - When executed once on raw database content, transforms `[vc_posts_grid]` into the FSE grid query block structure.
   - Re-running the script on transformed content will detect no matching `[vc_posts_grid]` shortcodes, leaving the content untouched without duplication or corruption.

---

## 4. Architectural Choice: Integrated Pipeline (Option 1)

Refactor `step_4c_transform_vc_posts_grid($content, $post_id)` directly in `bin/04-shortcode-migrations.php`:
- Signature update: `step_4c_transform_vc_posts_grid($content, $post_id = 0)`
- Main loop update: Pass `$id` into `step_4c_transform_vc_posts_grid($original_content, $id)` in `bin/04-shortcode-migrations.php`.

---

## 5. Execution Plan & Commands

```bash
# 1. Inspect raw posts with vc_posts_grid before running stage 04
wp db query "SELECT ID, post_title FROM wp_posts WHERE post_content LIKE '%vc_posts_grid%'" --path=/var/www/backstage.ekalexandria.org/public

# 2. Run Stage 04 Shortcode Migration Pipeline
php /var/www/backstage.ekalexandria.org/public/wp-content/themes/ekalexandria-flagship/bin/04-shortcode-migrations.php

# 3. Verify transformed post content for Post 16
wp post get 16 --field=post_content --path=/var/www/backstage.ekalexandria.org/public

# 4. Verify Idempotency (re-run script and confirm 0 unexpected updates/errors)
php /var/www/backstage.ekalexandria.org/public/wp-content/themes/ekalexandria-flagship/bin/04-shortcode-migrations.php
```

---

## 6. Project Structure

```
public/wp-content/themes/ekalexandria-flagship/
├── bin/
│   ├── 04-shortcode-migrations.php        # Contains step_4c_transform_vc_posts_grid($content, $post_id)
│   └── migration-helpers.php             # Provides eka_validate_blocks_ast()
└── ai-work/
    ├── SHORTCODE-MIGRATION-SUBPAGE-GRID-SPEC.md # This specification document
    └── logs/
        └── 04-shortcode-migrations.log    # Pipeline execution log
```

---

## 7. Code Style & Standards
- Pure PHP 8.1+, AST-validated Gutenberg markup output via `eka_validate_blocks_ast()`.
- Valid JSON attribute string formatting for Gutenberg block comments.
- Single-pass transformation per raw shortcode matching `[vc_posts_grid]`.
- Strict logging of transformed post count and AST verification results.

---

## 8. Testing & Verification Strategy
1. **AST Validation:** Ensure output passes `eka_validate_blocks_ast($content)`.
2. **Post 16 Inspection:** Verify `wp post get 16` produces the exact target block markup including `"parents": [16]` and extracted `"include"` IDs.
3. **Idempotency Test:** Verify a second run produces 0 changes and leaves valid blocks untouched.

---

## 9. Known Boundaries
- **ALWAYS DO:** Pass `$post_id` dynamically to populate `"parents": [$post_id]`.
- **ALWAYS DO:** Validate AST before executing database UPDATE queries.
- **NEVER DO:** Touch existing `wp:query` blocks or pre-migrated Gutenberg blocks.
- **NEVER DO:** Hardcode static post IDs into generic shortcode logic.
