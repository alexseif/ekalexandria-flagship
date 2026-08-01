# Task List: Phase 3 - Deep BeTheme Configuration, Options & Layout Scoping (PHP 7.4)

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 3 - Deep BeTheme Configuration, Options & Layout Scoping  

- [ ] Task 1: Environment & Directory Verification <!-- id: 0 -->
  - [ ] Verify `ai-work/scopings` and `ai-work/logs` directories exist <!-- id: 1 -->
  - [ ] Verify `php7.4` CLI and WP-CLI execution environment <!-- id: 2 -->
- [ ] Task 2: Implement BeTheme Scoping Script (`bin/scope-betheme-config.php`) <!-- id: 3 -->
  - [ ] Query BeTheme options (`betheme` / `mfn_theme_options`) <!-- id: 4 -->
  - [ ] Extract header settings, logo URLs/dimensions, colors, and typography tokens <!-- id: 5 -->
  - [ ] Scan postmeta for MFN builder pages (`_mfn-builder-items`) <!-- id: 6 -->
  - [ ] Extract custom CSS rules, customizer CSS, and dynamic sidebar assignments <!-- id: 7 -->
  - [ ] Export JSON and CSS outputs to `ai-work/scopings/` <!-- id: 8 -->
- [ ] Task 3: Execute Scoping & Validate Output Integrity <!-- id: 9 -->
  - [ ] Execute script via `php7.4 $(which wp) eval-file bin/scope-betheme-config.php --path=public > ai-work/logs/phase3-scoping.log 2>&1` <!-- id: 10 -->
  - [ ] Execute UnCSS active style extraction (`npx uncss public/wp-content/uploads/mfn-css/style-static.css > ai-work/scopings/betheme-active-styles.css`) <!-- id: 11 -->
  - [ ] Validate JSON syntax with `jq . ai-work/scopings/betheme-config-scoping.json` and `jq . ai-work/scopings/mfn-pages.json` <!-- id: 12 -->
  - [ ] Verify CSS artifacts `ai-work/scopings/betheme-custom-css.css` and `ai-work/scopings/betheme-active-styles.css` <!-- id: 13 -->
  - [ ] Verify execution log `ai-work/logs/phase3-scoping.log` <!-- id: 14 -->
  - [ ] Confirm zero database mutations <!-- id: 15 -->
- [ ] Checkpoint: Manual User Review Pause <!-- id: 16 -->
