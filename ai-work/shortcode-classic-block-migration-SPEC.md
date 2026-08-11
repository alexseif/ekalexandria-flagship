# SPECIFICATION: Deterministic Shortcode & Classic Block Gutenberg Migration

**TOPIC NAME:** shortcode-classic-block-migration  
**ISSUE NAME:** gutenberg-remediation  
**DATE:** 2026-08-04  
**STATUS:** PROPOSED (Targeting backstage_eka Environment)  

---

## 1. Objective & Background

### 1.1 Objective
Build a 100% deterministic, zero-AI, programmatic 2-stage PHP migration engine that:
1. Transforms all WPBakery structural shortcodes (`[vc_row]`, `[vc_column]`), inline team cards (`[our_team]`), classic image captions (`[caption]`), generic Revolution Sliders / LayerSliders (`[rev_slider]`), and custom shortcodes into native Gutenberg block primitives (`core/columns`, `core/column`, `core/image`, `core/gallery`, `core/html`, `core/group`).
2. Programmatically converts all classic HTML content (`<p>`, `<h2>`-`<h6>`, `<ul>`/`<ol>`, `<table>`, `<blockquote>`) into valid Gutenberg block comments (`<!-- wp:paragraph -->`, `<!-- wp:heading -->`, etc.) with strict idempotency checks to prevent duplicate block annotations.
3. Filters inline CSS (`style="..."`) via a strict **FSE Property Allowlist Filter** (retaining layout properties like `flex-basis`, `width`, `height`, `aspect-ratio`, `object-fit`), while discarding legacy styling (like font-family, hardcoded colors, or inline margins) to maintain FSE `theme.json` styling authority.
4. Includes lightweight inline `parse_blocks()` AST verification inside PHP scripts prior to DB update.
5. Manages dedicated log files (`ai-work/logs/`) with mandatory startup log truncation.
6. Executes all migration writes exclusively on the active target environment database (**`backstage_eka`**).

---

## 2. Scoping Analysis Summary

- **Scoping Reference Source:** Local production DB read-only snapshot (`db207080_eka`).
- **Target Execution Environment Database:** `backstage_eka`.
- **Total Posts/Pages Analyzed:** 6,234
- **Total Scoped Legacy Items:** 4,362
  - **Implemented (Page-Specific):** 110 items
  - **Not Implemented (Requiring Programmatic Converter):** 4,252 items

### Item Classification & Remediation Mapping:
1. **`[vc_row]` / `[vc_column width="X/Y"]` / `[vc_column_text]` (103 items):**  
   - `[vc_row]` $\rightarrow$ `<!-- wp:columns --><div class="wp-block-columns">...</div><!-- /wp:columns -->`  
   - `[vc_column width="1/2"]` $\rightarrow$ `<!-- wp:column {"width":"50%"} --><div class="wp-block-column" style="flex-basis:50%">...</div><!-- /wp:column -->` (preserves Gutenberg native `style="flex-basis:50%"`)  
   - `[vc_column_text]` $\rightarrow$ Unwrapped inner block content.  
   - `[vc_single_image image="ID"]` $\rightarrow$ `<!-- wp:image {"id":ID} -->...<!-- /wp:image -->`  
   - `[vc_raw_html]` $\rightarrow$ `<!-- wp:html -->...<!-- /wp:html -->`  
2. **`[our_team heading="ROLE" title="NAME"]` (2 items):**  
   - Converts inline team member shortcodes into `core/group` cards containing `core/heading` (ROLE) and `core/paragraph` (NAME).  
3. **`[rev_slider]` & `[layerslider]` (22 items total):**  
   - Generic page instances containing `[rev_slider]` / `[layerslider]` converted to native `core/gallery` or `core/cover` blocks.  
4. **`[caption]` (64 items):**  
   - `[caption id="..." align="..."]<img .../> text[/caption]` $\rightarrow$ `<!-- wp:image {"id":...} --><figure class="wp-block-image"><img .../><figcaption>text</figcaption></figure><!-- /wp:image -->`  
5. **Other Shortcodes (`[map]`, `[gview]`, `[mc4wp_form]`) (141 items):**  
   - Converted into `core/html` blocks.  
6. **Classic HTML Content (4,029 items):**  
   - Unannotated `<p>`, `<h2>`-`<h6>`, `<ul>`/`<ol>`, `<table>`, `<blockquote>` converted into Gutenberg block markup (`<!-- wp:paragraph -->`, `<!-- wp:heading -->`, etc.).

---

## 3. Architecture: 2-Stage Deterministic Migration Engine

### Stage 1: Shortcode & WPBakery Gutenberg Transformer (`bin/remediate-shortcodes-to-blocks.php`)
- **Log File:** `ai-work/logs/remediate-shortcodes-to-blocks.log` (truncated on script start).
- **Target DB:** `backstage_eka`
- **Logic:**
  1. Purges log file at initialization (`file_put_contents($log_file, '')`).
  2. Parses `[vc_row]`, `[vc_column]`, `[vc_column_text]` into `core/columns` and `core/column` blocks. Calculates width percentage dynamically from fraction strings (`1/2` $\rightarrow$ `50%`, `1/3` $\rightarrow$ `33.33%`, `2/3` $\rightarrow$ `66.66%`, `1/1` $\rightarrow$ `100%`) and sets native Gutenberg column styles (`style="flex-basis:X%"`).
  3. Transforms `[vc_single_image]` into `core/image` block.
  4. Transforms `[our_team]` shortcodes into `core/group` with `core/heading` & `core/paragraph`.
  5. Transforms `[rev_slider]` and `[layerslider]` into `core/gallery` blocks.
  6. Transforms `[caption]` into `core/image` with `<figcaption>`.
  7. Performs inline `parse_blocks()` check before saving.

### Stage 2: Idempotent Classic HTML to Gutenberg Block Converter (`bin/convert-classic-to-gutenberg.php`)
- **Log File:** `ai-work/logs/convert-classic-to-gutenberg.log` (truncated on script start).
- **Target DB:** `backstage_eka`
- **Logic:**
  1. Purges log file at initialization (`file_put_contents($log_file, '')`).
  2. **Idempotency & Scope Guard:** Scans DOM/HTML elements. Skips any element, text block, or container already enclosed within Gutenberg block comments (`<!-- wp:` ... `<!-- /wp:`). Existing Gutenberg blocks are left untouched.
  3. Converts bare `<p>...</p>` $\rightarrow$ `<!-- wp:paragraph --><p>...</p><!-- /wp:paragraph -->`
  4. Converts bare `<hN>...</hN>` $\rightarrow$ `<!-- wp:heading {"level":N} --><hN>...</hN><!-- /wp:heading -->`
  5. Converts bare `<ul>...</ul>` / `<ol>...ol>` $\rightarrow$ `<!-- wp:list --><ul/ol>...<ul/ol><!-- /wp:list -->`
  6. Converts bare `<table>...</table>` $\rightarrow$ `<!-- wp:table --><figure class="wp-block-table"><table>...</table></figure><!-- /wp:table -->`
  7. Converts bare `<blockquote>...</blockquote>` $\rightarrow$ `<!-- wp:quote --><blockquote class="wp-block-quote">...</blockquote><!-- /wp:quote -->`
  8. **Front Page Welcome Text Extraction:** Front page IDs (`13236` EL, `16894` EN, `16892` AR) strip all hero sliders, shortcodes, and query grid wrappers, extracting the welcome body text cleanly into Gutenberg paragraph blocks across all 3 languages.
  9. **Classic Plain Text Line Break Normalization (`wpautop`):** Un-annotated classic editor plain text lines separated by line breaks (`\n`, `\n\n`) outside block comments are normalized via `wpautop()` into `<p>` elements prior to block conversion.
  10. **Paragraph Inline Style Stripping:** Inline `style="..."` attributes on `<p>` tags (e.g. `style="text-align: justify;"`) are stripped to produce clean `<p>` markup inside `<!-- wp:paragraph -->` blocks.
  11. **HTML Attribute Bracket False-Positive Exclusion:** Brackets `[...]` inside HTML tag attributes (such as `href="...search_coll[metadata]=1..."`) are strictly ignored during shortcode scanning and preserved as raw HTML attributes without shortcode transformation or `<!-- wp:html -->` wrapping.
  12. **FSE Style Filtering:** Filters `style="..."` attributes via `sanitize_inline_styles_fse()` on converted layout elements.
  13. Performs inline `parse_blocks()` check before saving.

---

## 4. FSE Inline Style Allowlist Algorithm

During Stage 1 and Stage 2 conversion, any `style="..."` attribute found on converted elements is processed via `sanitize_inline_styles_fse($style_string)`:

1. **Parser:** Parses `style_string` into individual CSS property-value pairs (`key: value`).
2. **FSE Property Allowlist:**
   - Layout & Grid: `flex-basis`, `flex-grow`, `flex-shrink`, `flex-direction`, `grid-template-columns`
   - Sizing & Media: `width`, `height`, `min-height`, `max-width`, `aspect-ratio`, `object-fit`
   - Alignment: `vertical-align`, `text-align`
3. **Filtering:**
   - Retains ONLY properties in the FSE Property Allowlist.
   - Discards legacy presentation styles (`font-family`, hardcoded `color`, `background-color`, `line-height`, `font-size`, `margin`, `padding`, `float`, `clear`).
4. **Re-serialization:** Rebuilds the cleaned `style="..."` attribute string.

---

## 5. Log & Safety Management

### 5.1 Log Management
Each migration script initialises its log file by truncating previous logs:
```php
$log_file = dirname(__DIR__) . '/ai-work/logs/remediate-shortcodes-to-blocks.log';
file_put_contents($log_file, ""); // Purge on start
```

### 5.2 Inline AST Validation
Before updating `post_content` in `backstage_eka`, the script runs a lightweight PHP check:
```php
$parsed = parse_blocks($new_content);
if (empty($parsed) && !empty(trim($new_content))) {
    $log("AST validation failed for post ID $post_id. Skipping update.", "WARNING");
    continue;
}
```

---

## 6. Boundaries & Execution Rules

- **Target DB:** All execution and migration writes target `backstage_eka`. Production database (`db207080_eka`) is strictly READ-ONLY.
- **Deterministic Execution:** No AI or external service calls at runtime. All transformations rely on pure PHP regex/DOM parsing.
- **FSE Allowlist Discipline:** Only filter inline CSS via `sanitize_inline_styles_fse()`.
