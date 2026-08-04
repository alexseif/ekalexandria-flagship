# TODO List: Multilingual FSE Template Part Routing

**TOPIC NAME**: `multilingual-fse`  
**ISSUE NAME**: `template-part-routing`  
**SPEC**: `public/wp-content/themes/ekalexandria-flagship/ai-work/multilingual-fse-template-part-routing-SPEC.md`  

---

- [x] **Task 1: Add dynamic template part routing filter in `inc/custom-features.php`**
  - [x] Add `render_block_data` filter callback in `inc/custom-features.php`.
  - [x] Exclude admin context (`is_admin()`).
  - [x] Intercept `core/template-part` blocks with `slug` matching `header` or `footer`.
  - [x] Resolve language via `pll_current_language()`.
  - [x] Verify template part file existence via `file_exists()`.
  - [x] Run PHP linting (`php -l inc/custom-features.php`).
  - [x] Commit: `feat(fse): add dynamic Polylang header and footer template part routing filter`

- [x] **Task 2: Audit and align standard FSE block templates in `templates/`**
  - [x] Audit `templates/single.html` for generic template part references (`"slug":"header"`, `"slug":"footer"`).
  - [x] Audit additional general templates (`page.html`, `archive.html`, etc.).
  - [x] Verify front-page templates are untouched.
  - [x] Commit: `refactor(templates): ensure standard FSE templates use generic header and footer slugs`

- [ ] **Task 3: Multilingual Runtime Verification & Site Editor Compatibility**
  - [ ] Test Greek post loading `parts/header-el.html` & `parts/footer-el.html`.
  - [ ] Test English post loading `parts/header-en.html` & `parts/footer-en.html`.
  - [ ] Test Arabic post loading `parts/header-ar.html` & `parts/footer-ar.html`.
  - [ ] Verify FSE Site Editor stability in admin.
  - [ ] Commit: `test(fse): verify dynamic template part routing for multilingual posts`

- [ ] **Task 4: Final Checkpoint & Quality Review**
  - [ ] Review changes against SPEC criteria.
  - [ ] Verify zero DB query overhead during filtering.
