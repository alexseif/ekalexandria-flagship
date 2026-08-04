# SPECIFICATION: Multilingual FSE Template Part Routing

**TOPIC NAME**: `multilingual-fse`  
**ISSUE NAME**: `template-part-routing`  

---

## 1. Objective & Target Users

### Objective
Enable automatic dynamic resolution of Gutenberg FSE template parts (`header` and `footer`) based on Polylang active language context (`el`, `en`, `ar`) across general block templates (`single.html`, `page.html`, `archive.html`, etc.). This eliminates block template duplication while ensuring posts and pages automatically render their localized navigation menus, search placeholders, and footer content. Note: Front-page templates (`front-page-el.html`, `front-page-en.html`, `front-page-ar.html`) are excluded from this mechanism as they already explicitly reference their respective language template parts.

### Target Users
- **Site Visitors**: Visitors navigating Greek, English, and Arabic posts who expect localized navigation, headers, and footers matching the post language.
- **Content Editors**: Editors publishing multilingual posts without needing to manually select custom block templates in the editor sidebar.

---

## 2. Core Features & Acceptance Criteria

### Core Features
1. **Runtime Template Part Interception**: A lightweight `render_block_data` hook in `inc/custom-features.php` that intercepts `core/template-part` blocks before rendering.
2. **Language Resolution**: Detection of the current post's language via `pll_current_language()` (`el`, `en`, `ar`).
3. **Dynamic Slug Swapping**: Automatic replacement of generic template part slugs (`header` $\rightarrow$ `header-en`, `footer` $\rightarrow$ `footer-ar`) if the target language part exists in `parts/`.
4. **Defensive Fallback**: Fallback to default `header.html` / `footer.html` if the language-specific part is missing or if in admin/REST context.

### Acceptance Criteria
- [ ] `templates/single.html` uses generic template parts (`<!-- wp:template-part {"slug":"header","area":"header"} /-->`).
- [ ] Requesting a Greek post loads `parts/header-el.html` & `parts/footer-el.html`.
- [ ] Requesting an English post loads `parts/header-en.html` & `parts/footer-en.html`.
- [ ] Requesting an Arabic post loads `parts/header-ar.html` & `parts/footer-ar.html`.
- [ ] FSE Site Editor (`wp-admin/site-editor.php`) continues to load standard header/footer parts without breakage.
- [ ] Zero database query overhead added during block filtering.

---

## 3. Project Structure & Files Touched

```text
public/wp-content/themes/ekalexandria-flagship/
├── ai-work/
│   └── multilingual-fse-template-part-routing-SPEC.md
├── inc/
│   └── custom-features.php          # Add ekalexandria_dynamic_template_parts() filter
├── parts/
│   ├── header-el.html               # Greek Header (Main Menu GR)
│   ├── header-en.html               # English Header (Main Menu EN)
│   ├── header-ar.html               # Arabic Header (Main Menu AR)
│   ├── footer-el.html               # Greek Footer
│   ├── footer-en.html               # English Footer
│   └── footer-ar.html               # Arabic Footer
└── templates/
    └── single.html                  # Standard clean FSE single post template
```

---

## 4. Architecture & Code Standards

### Implementation Hook Pattern (`inc/custom-features.php`)
```php
/**
 * Dynamically route FSE header and footer template parts based on Polylang language context.
 */
add_filter( 'render_block_data', function( $parsed_block ) {
    if ( is_admin() ) {
        return $parsed_block;
    }

    if ( isset( $parsed_block['blockName'] ) && 'core/template-part' === $parsed_block['blockName'] ) {
        $slug = $parsed_block['attrs']['slug'] ?? '';
        if ( in_array( $slug, [ 'header', 'footer' ], true ) && function_exists( 'pll_current_language' ) ) {
            $lang = pll_current_language();
            if ( $lang ) {
                $target_slug = $slug . '-' . $lang;
                $theme_dir   = get_stylesheet_directory();
                if ( file_exists( $theme_dir . '/parts/' . $target_slug . '.html' ) ) {
                    $parsed_block['attrs']['slug'] = $target_slug;
                }
            }
        }
    }

    return $parsed_block;
}, 10, 1 );
```

### Separation of Concerns
- **Templates (`templates/`)**: Structure and block hierarchy only (`single.html`).
- **Template Parts (`parts/`)**: Presentation and language-specific markup (`header-el.html`, `header-en.html`, `header-ar.html`).
- **PHP Backend (`inc/`)**: Business logic and runtime template routing.

---

## 5. Testing & Verification Strategy

1. **Static Analysis & Linting**:
   - Run PHP syntax check (`php -l inc/custom-features.php`).
2. **Runtime Verification**:
   - Query test posts in Greek, English, and Arabic via `curl` / browser subagent:
     - Check HTML output for `site-header eka-header` and verify localized search input placeholder ("Αναζήτηση...", "Search...", "بحث...").
     - Verify navigation menu items correspond to the respective language.
3. **Editor Compatibility**:
   - Verify Site Editor in WP Admin opens and saves templates without block invalidation.

---

## 6. Known Boundaries & Constraints

### Always Do
- Exclude `front-page` templates (`front-page-el.html`, `front-page-en.html`, `front-page-ar.html`) as they explicitly manage their own template parts.
- Always use `file_exists()` before altering the block's `slug` attribute to avoid missing template part errors.
- Always check `! is_admin()` to prevent overriding template part slugs inside the block editor canvas.
- Maintain WordPress PHP Coding Standards (WPCS).

### Ask First About
- Modifying structural block elements inside existing `parts/header-*.html` or `parts/footer-*.html` files.

### Never Do
- Never duplicate template files per language (`single-el.html`, `single-en.html`, `single-ar.html`).
- Never perform DB queries inside the `render_block_data` filter callback.
