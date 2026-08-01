# Specification: Phase 4 - Plugin Cleanup & PHP 8.2 Environment Upgrade

**TOPIC NAME**: EKA Portal Migration  
**ISSUE NAME**: Phase 4 - Stalling Plugin Cleanup & Environment Upgrade to PHP 8.2  
**STATUS**: `[IMPLEMENTED & VERIFIED]`  

---

## 1. Objective & Target Users
* **Objective**: Safely deactivate and purge stalling legacy plugins using the existing script `bin/cleanup-plugins.sh`, execute directory removal fallbacks (`rm -rf`) if WP-CLI uninstallation encounters errors, log reasoning, and upgrade the system environment to PHP 8.2.
* **Target Users**: System administrators and site performance auditors.

---

## 2. Core Features & Acceptance Criteria
* **Criteria 1 (Plugin Cleanup)**: Execute `bash bin/cleanup-plugins.sh` under `php7.4` to deactivate/uninstall stalling plugins (`LayerSlider`, `js_composer`, `revslider`, `ewww-image-optimizer`, `wordpress-seo`).
* **Criteria 2 (Fallback & Logging Mandate)**: If WP-CLI fails due to missing plugin metadata, execute `rm -rf wp-content/plugins/<dir>` and log output, errors, fallbacks, and technical reasoning to `ai-work/logs/cleanup-plugins.log`.
* **Criteria 3 (Drop-in Cleanup)**: Remove legacy caching drop-ins (`advanced-cache.php`, `object-cache.php`, `w3tc-config`, `cache`).
* **Criteria 4 (PHP 8.2 Upgrade)**: Transition system environment CLI to **PHP 8.2** (`php8.2 $(which wp)`).
* **Criteria 5 (Verification)**: Confirm zero fatal errors in `public/wp-content/debug.log` under PHP 8.2 runtime.
* **Criteria 6 (Validation Pause)**: **Manual User Validation Pause** required upon completion of Phase 4.

---

## 3. Tech Stack Preferences & Constraints
* **Script File**: `bin/cleanup-plugins.sh` (existing script).
* **Target PHP Runtime**: Transition from PHP 7.4 to PHP 8.2.
* **Logging Path**: `ai-work/logs/cleanup-plugins.log`.

---

## 4. Commands
```bash
# Execute autonomous plugin cleanup script
bash bin/cleanup-plugins.sh

# Verify active plugins under PHP 8.2
php8.2 $(which wp) plugin list --status=active --path=public

# Inspect log file for fallbacks and reasoning
cat ai-work/logs/cleanup-plugins.log
```

---

## 5. Project Structure
```text
bin/
└── cleanup-plugins.sh            # Existing plugin cleanup script
ai-work/
├── logs/
│   └── cleanup-plugins.log        # Action, error, and reasoning log
└── specs/
    └── PHASE-4-PLUGIN-DEACTIVATION-SPEC.md
```

---

## 6. Code Style & Testing Strategy
* **Testing Strategy**:
  1. Verify zero fatal errors or WSOD in `public/wp-content/debug.log` under PHP 8.2.
  2. Confirm target stalling plugins are uninstalled or purged.
  3. Verify `cleanup-plugins.log` captures all execution steps, errors, and fallback reasoning.
* **Boundaries**:
  - **ALWAYS**: Log technical reasoning for any directory removals or fallbacks.
  - **ALWAYS**: Require **Manual User Validation Pause** before starting Phase 5.
  - **NEVER**: Deactivate essential core plugins (e.g. `polylang`).
