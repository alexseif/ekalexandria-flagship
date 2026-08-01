# Specification: Phase 1 - Backstage Preproduction Setup (PHP 7.4)

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 1 - Staging Environment Synchronization & Reset  
**STATUS**: `[COMPLETED]`  

---

## 1. Objective & Target Users
* **Objective**: Update the environment reset script `bin/reset-env.sh` to freshly export and import the database from production (`ekalexandria.org`, DB: `db207080_eka`) into staging (`backstage.ekalexandria.org`, DB: `backstage_eka`) with domain replacement (`ekalexandria.org` -> `backstage.ekalexandria.org`). Synchronize staging files from the neighbouring production folder (`/var/www/ekalexandria.org`), while strictly preserving `wp-config.php` and key project directories (`ai-work/`, `bin/`, `AGY_INSTRUCTIONS.md`, and `Master Project Roadmap...`). Reset all new files and modified files outside preserved criteria back to their original production state.
* **Target Users**: System administrators, developers, and AI agents.

---

## 2. Core Features & Acceptance Criteria
* **Criteria 1**: Update reset script specification in `bin/reset-env.sh`. (No direct script execution/edits without user verification).
* **Criteria 2**: Database export freshly from production DB (`db207080_eka`) and import freshly into staging DB (`backstage_eka`) with search-replace domain URL updating (`ekalexandria.org` -> `backstage.ekalexandria.org`).
* **Criteria 3**: File synchronization from neighbouring production directory (`/var/www/ekalexandria.org`).
* **Criteria 4**: Strict preservation of critical configuration and work files: `wp-config.php`, `ai-work/`, `bin/`, `AGY_INSTRUCTIONS.md`, and `Master Project Roadmap...`.
* **Criteria 5**: All newly created files or modified files outside the preservation criteria in the staging directory MUST be reset back to their original production state.
* **Criteria 6**: Automated patch resolving line 339 nested ternary operator error (`Unparenthesized a ? b : c ? d : e`) in WPBakery `class-vc-frontend-editor.php`.
* **Criteria 7**: Purges Mailchimp autoloader vendor cache (`rm -rf wp-content/plugins/mailchimp-for-woocommerce/vendor`).
* **Criteria 8**: All WP-CLI commands routed through `php7.4`.
* **Criteria 9**: Mandatory dumping of script outputs, errors, and progress to clean log file `ai-work/logs/reset-env.log` (`> ai-work/logs/reset-env.log 2>&1`).
* **Criteria 10**: **Manual Spec Verification Before Commit**: Spec changes require explicit manual user verification before committing to Git.
* **Criteria 11**: **Manual User Validation Pause** required upon script execution completion.

---

## 3. Tech Stack Preferences & Constraints
* **PHP Routing**: Strictly `php7.4` (`php7.4 $(which wp) ...`).
* **Script File**: `bin/reset-env.sh` (to be updated according to this spec).
* **Database**: Source `db207080_eka` -> Target `backstage_eka`.
* **Logging Path**: `ai-work/logs/reset-env.log`.

---

## 4. Commands
```bash
# Execute updated environment reset script and dump cleanly to log
bash bin/reset-env.sh > ai-work/logs/reset-env.log 2>&1

# Verify staging WP-CLI connection
php7.4 $(which wp) core version --path=public

# Inspect log file results
cat ai-work/logs/reset-env.log
```

---

## 5. Project Structure
```text
bin/
├── reset-env.sh            # Environment reset script (to be updated per spec)
└── pre-flight.sh            # Pre-flight system check
ai-work/
├── logs/
│   └── reset-env.log       # Execution audit log
└── specs/
    └── PHASE-1-BACKSTAGE-PREPROD-SPEC.md
```

---

## 6. Code Style & Testing Strategy
* **Testing Strategy**:
  1. Verify zero DB import errors in clean log file `ai-work/logs/reset-env.log`.
  2. Confirm `wp-config.php` and preserved directories (`ai-work/`, `bin/`, documentation) remain untouched.
  3. Confirm newly added or modified files outside preservation criteria are reset to original production baseline.
  4. Verify line 339 of WPBakery `class-vc-frontend-editor.php` contains parenthesized ternary operands.
* **Boundaries**:
  - **ALWAYS**: Route all CLI commands through `php7.4`.
  - **ALWAYS**: Dump execution output cleanly into `ai-work/logs/reset-env.log`.
  - **ALWAYS**: Require user manual spec verification before generating Git commits.
  - **ALWAYS**: Require user manual validation pause before advancing to Phase 2.
  - **NEVER**: Modify codebase scripts or files without user review.
