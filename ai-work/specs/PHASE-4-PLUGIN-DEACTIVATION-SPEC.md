# Specification: Phase 4 - Plugin Cleanup & PHP 8.2 Environment Upgrade

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 4 - Stalling Plugin Cleanup & Environment Upgrade to PHP 8.2  
**STATUS**: `[REVISED SPEC / VERIFICATION PENDING]`  

---

## 1. Objective & Target Users
* **Objective**: Safely deactivate and purge stalling legacy plugins and legacy caching drop-ins using `bin/cleanup-plugins.sh`. Strictly prohibit automated `rm` / `rm -rf` fallbacks; if any plugin deactivation or removal fails, the script MUST log the failure details into `ai-work/logs/cleanup-plugins.log` for developer inspection without modifying scripts or forcing deletions. Upgrade system environment to PHP 8.2 following verification.
* **Target Users**: System administrators and site performance auditors.

---

## 2. Core Features & Acceptance Criteria
* **Criteria 1 (Plugin Cleanup)**: Deactivate and uninstall stalling plugins (`LayerSlider`, `js_composer`, `revslider`, `ewww-image-optimizer`, `wordpress-seo`) via WP-CLI under `php7.4`.
* **Criteria 2 (Strict Logging & No RM Fallbacks Mandate)**: Scripts and AI MUST NOT execute automated `rm` or `rm -rf` fallbacks upon failure. If WP-CLI deactivation/uninstallation fails, LOG THE FAILURE, context, and error message directly into `ai-work/logs/cleanup-plugins.log` so the issue can be identified and remediated in the next iteration. Do NOT update scripts or perform unapproved file deletions.
* **Criteria 3 (Legacy Caching Clean Up)**: Include legacy caching cleanup in `bin/cleanup-plugins.sh` specification to purge legacy caching drop-ins (`advanced-cache.php`, `object-cache.php`, `w3tc-config`, `cache` folder). If any drop-in removal fails, log the failure to `ai-work/logs/cleanup-plugins.log`.
* **Criteria 4 (PHP 8.2 Upgrade)**: Transition system environment CLI to **PHP 8.2** (`php8.2 $(which wp)`).
* **Criteria 5 (Verification)**: Confirm zero fatal errors in `public/wp-content/debug.log` under PHP 8.2 runtime.
* **Criteria 6 (Clean Output Log Dumping)**: All cleanup execution outputs, error messages, and logged failures MUST be dumped into clean log file `ai-work/logs/cleanup-plugins.log`.
* **Criteria 7 (Manual Spec Verification Before Commit)**: Spec changes require manual user verification before creating a Git commit.
* **Criteria 8 (Validation Pause)**: **Manual User Validation Pause** required upon completion of Phase 4.

---

## 3. Tech Stack Preferences & Constraints
* **Script File**: `bin/cleanup-plugins.sh` (to be updated according to this spec).
* **Target PHP Runtime**: Transition from PHP 7.4 to PHP 8.2.
* **Logging Path**: `ai-work/logs/cleanup-plugins.log`.

---

## 4. Commands
```bash
# Execute plugin & caching cleanup script with clean log dumping
bash bin/cleanup-plugins.sh > ai-work/logs/cleanup-plugins.log 2>&1

# Verify active plugins under PHP 8.2
php8.2 $(which wp) plugin list --status=active --path=public

# Inspect log file for failures or issues
cat ai-work/logs/cleanup-plugins.log
```

---

## 5. Project Structure
```text
bin/
└── cleanup-plugins.sh            # Plugin & legacy caching cleanup script (no rm fallbacks)
ai-work/
├── logs/
│   └── cleanup-plugins.log        # Action, failure, and execution audit log
└── specs/
    └── PHASE-4-PLUGIN-DEACTIVATION-SPEC.md
```

---

## 6. Code Style & Testing Strategy
* **Testing Strategy**:
  1. Inspect `ai-work/logs/cleanup-plugins.log` to confirm all script actions, failures, and legacy caching cleanup events are documented.
  2. Verify zero fatal errors or WSOD in `public/wp-content/debug.log` under PHP 8.2.
  3. Confirm legacy caching drop-ins are cleaned up.
* **Boundaries**:
  - **ALWAYS**: Log failure details for manual developer remediation instead of executing `rm` / `rm -rf` fallbacks.
  - **ALWAYS**: Dump execution outputs to `ai-work/logs/cleanup-plugins.log`.
  - **ALWAYS**: Require manual spec verification before generating Git commits.
  - **ALWAYS**: Require **Manual User Validation Pause** before starting Phase 5.
  - **NEVER**: Deactivate essential core plugins (e.g. `polylang`).
  - **NEVER**: Execute automated or unapproved `rm` deletions on failure.
