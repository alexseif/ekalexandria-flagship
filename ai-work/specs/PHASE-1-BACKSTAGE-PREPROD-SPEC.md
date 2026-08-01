# Specification: Phase 1 - Backstage Preproduction Setup (PHP 7.4)

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 1 - Staging Environment Synchronization & Reset  
**STATUS**: `[IMPLEMENTED & VERIFIED]`  

---

## 1. Objective & Target Users
* **Objective**: Synchronize `backstage.ekalexandria.org` (DB: `backstage_eka`) from live production (`ekalexandria.org`, DB `db207080_eka`) using the existing script `bin/reset-env.sh`. Preserves core project structure (`ai-work/`, `bin/`, `AGY_INSTRUCTIONS.md`, `Master Project Roadmap...`) while cleaning up temporary testing artifacts.
* **Target Users**: System administrators, developers, and AI agents.

---

## 2. Core Features & Acceptance Criteria
* **Criteria 1**: Execution via existing script `bin/reset-env.sh`.
* **Criteria 2**: Database import from production dump (`db207080_eka`) into staging DB (`backstage_eka`) with domain replacement (`ekalexandria.org` -> `backstage.ekalexandria.org`).
* **Criteria 3**: Preserves critical folders: `ai-work/`, `bin/`, `AGY_INSTRUCTIONS.md`, and `Master Project Roadmap...`.
* **Criteria 4**: Automated patch resolving line 339 nested ternary operator error (`Unparenthesized a ? b : c ? d : e`) in WPBakery `class-vc-frontend-editor.php`.
* **Criteria 5**: Purges Mailchimp autoloader vendor cache (`rm -rf wp-content/plugins/mailchimp-for-woocommerce/vendor`).
* **Criteria 6**: All WP-CLI commands routed through `php7.4`.
* **Criteria 7**: Mandatory logging of output, issues, and reasoning to `ai-work/logs/reset-env.log`.
* **Criteria 8**: **Manual User Validation Pause** required upon script completion.

---

## 3. Tech Stack Preferences & Constraints
* **PHP Routing**: Strictly `php7.4` (`php7.4 $(which wp) ...`).
* **Script File**: `bin/reset-env.sh` (existing script).
* **Database**: Source `db207080_eka` -> Target `backstage_eka`.

---

## 4. Commands
```bash
# Execute environment reset script
bash bin/reset-env.sh

# Verify staging WP-CLI connection
php7.4 $(which wp) core version --path=public

# Inspect log file
cat ai-work/logs/reset-env.log
```

---

## 5. Project Structure
```text
bin/
├── reset-env.sh            # Existing reset script (updated with line 339 patch)
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
  1. Verify zero DB import errors in `ai-work/logs/reset-env.log`.
  2. Verify line 339 of WPBakery `class-vc-frontend-editor.php` contains parenthesized ternary operands.
  3. Verify `ai-work/` and `bin/` directories remain completely intact post-reset.
* **Boundaries**:
  - **ALWAYS**: Route all CLI commands through `php7.4`.
  - **ALWAYS**: Require user manual validation pause before advancing to Phase 2.
  - **NEVER**: Delete `ai-work/` or `bin/` files during reset.
