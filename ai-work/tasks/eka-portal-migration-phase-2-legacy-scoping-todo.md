# Task List: Phase 2 - Deep Legacy Item & Page URL Scoping (PHP 7.4)

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 2 - Deep Scoping of Legacy Sliders, Shortcodes, MFN Builder Items & Page URLs  

- [x] Task 1: Environment & Directory Verification <!-- id: 0 -->
  - [x] Verify `ai-work/scopings` and `ai-work/logs` directories exist <!-- id: 1 -->
  - [x] Verify `php7.4` CLI and WP-CLI execution environment <!-- id: 2 -->
- [x] Task 2: Implement Scoping Script (`bin/scope-legacy-items.php`) <!-- id: 3 -->
  - [x] Implement query loop for `page` and `post` entries <!-- id: 4 -->
  - [x] Implement permalink resolution via `get_permalink()` <!-- id: 5 -->
  - [x] Implement shortcode pattern scanner (`[layerslider]`, `[rev_slider]`, `[vc_*]`, `[testimonials]`, static sliders) <!-- id: 6 -->
  - [x] Implement postmeta builder scanner (`_mfn-builder-items`, slider meta) <!-- id: 7 -->
  - [x] Implement JSON formatting and export to `ai-work/scopings/legacy-items-inventory.json` <!-- id: 8 -->
- [x] Task 3: Execute Scoping & Validate Inventory Output <!-- id: 9 -->
  - [x] Execute script via `php7.4 $(which wp) eval-file bin/scope-legacy-items.php --path=public > ai-work/logs/phase2-legacy-scoping.log 2>&1` <!-- id: 10 -->
  - [x] Validate JSON syntax with `jq . ai-work/scopings/legacy-items-inventory.json` <!-- id: 11 -->
  - [x] Verify execution log `ai-work/logs/phase2-legacy-scoping.log` <!-- id: 12 -->
  - [x] Confirm zero database mutations <!-- id: 13 -->
- [ ] Checkpoint: Manual User Review Pause <!-- id: 14 -->
