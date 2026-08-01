# Todo List: Phase 2 - Programmatic CPT Content, Shortcode & Menu Migration

- [x] Task 2.1: Scaffold `bin/run-phase2-migration.sh` runner script with log purging at the start of execution
- [x] Task 2.2: Execute `alx_tachydromos` CPT migration (Implemented in `inc/cli-commands.php` `wp eka migrate-tachydromos`)
- [x] Task 2.3: Execute `board_member` CPT migration (Implemented in `inc/cli-commands.php` `wp eka migrate-board`)
- [x] Task 2.4: Execute slider replacement (Implemented in `inc/cli-commands.php` `wp eka replace-sliders`)
- [x] Task 2.5: Remediate residual shortcodes (Implemented in `inc/cli-commands.php` `wp eka remediate-shortcodes`) and add navigation menu location assignments (Greek Main: 13, English Main: 3315, Arabic Main: 3316, Greek Footer: 21) in `bin/run-phase2-migration.sh`
- [ ] Task 2.6: Run unified Phase 2 migration runner script (`bin/run-phase2-migration.sh`), perform idempotency check, audit post counts and logs, and execute Manual User Validation Pause
