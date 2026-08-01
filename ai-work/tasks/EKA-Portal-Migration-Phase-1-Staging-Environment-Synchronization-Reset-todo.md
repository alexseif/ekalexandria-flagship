# TOPIC NAME: EKA-Portal-Migration
# ISSUE NAME: Phase-1-Staging-Environment-Synchronization-Reset
# Task List: Phase 1 - Staging Environment Synchronization & Reset

## Phase 1: Environment Reset Script Refinement
- [x] Task 1.1: Refine `bin/reset-env.sh` to codify dropping & recreating `backstage_eka` DB, clean DB snapshot import, domain mapping, file synchronization with preservation rules, WPBakery ternary patch, Mailchimp vendor purge, `alexseif:www-data` ownership fix, and WP-CLI verification under PHP 7.4 <!-- id: 1.1 -->
- [ ] Task 1.2: Code review & manual spec verification of `bin/reset-env.sh` prior to Git commit <!-- id: 1.2 -->

## Phase 2: Execution & Automated Reset Audit
- [x] Task 2.1: Execute clean DB drop, recreate & staging environment reset (`bash bin/reset-env.sh > ai-work/logs/reset-env.log 2>&1`) & ensure `alexseif:www-data` permissions <!-- id: 2.1 -->
- [x] Task 2.2: Audit execution logs (`ai-work/logs/reset-env.log`), verify WP-CLI connection under PHP 7.4, confirm preservation of `wp-config.php`/`ai-work/`/`bin/`, verify WPBakery patch, and verify `alexseif:www-data` ownership <!-- id: 2.2 -->

## Phase 3: Manual User Validation Checkpoint
- [x] Task 3.1: Developer Checkpoint — HALT execution and request explicit manual user validation pause before advancing to Phase 2 content migration <!-- id: 3.1 -->

