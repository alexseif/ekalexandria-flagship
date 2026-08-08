# MASTER PROJECT SPECIFICATION INDEX

**Project:** Greek Community of Alexandria (EKA) Portal Modernization  
**Theme:** `ekalexandria-flagship` (Gutenberg Full Site Editing Theme)  
**Database Context:** `backstage_eka` (Development/Staging Target DB)  

---

The project documentation has been split into two dedicated specification documents:

### 1. 📘 [PROJECT-REQUIREMENTS-SPEC.md](file:///var/www/backstage.ekalexandria.org/public/wp-content/themes/ekalexandria-flagship/ai-work/PROJECT-REQUIREMENTS-SPEC.md)
*Master Technical Requirements Specification*
- **Custom Post Types (CPTs):** `alx_tachydromos` (Newsletters) & `board_member` (Board of Directors) schemas, REST meta, hooks, Polylang linking.
- **FSE Pages & Templates:** Technical specifications for `front-page.html`, `index.html`, `single.html`, `archive.html`, `search.html`, `tachydromos.html`, and `board-members.html`.
- **Remaining Shortcodes:** Categorization and transformation rules for all 7 shortcode types from `missed-shortcodes.json` & `missed-shortcodes.log`.
- **Navigation & Localization:** Menu locations, legacy mappings, and Polylang dynamic routing architecture.
- **Migration Pipeline Architecture:** 3-stage script workflow design.

---

### 2. 📙 [MIGRATION-WORK-STATUS-AND-GAP-ANALYSIS.md](file:///var/www/backstage.ekalexandria.org/public/wp-content/themes/ekalexandria-flagship/ai-work/MIGRATION-WORK-STATUS-AND-GAP-ANALYSIS.md)
*Execution Status, Gap Analysis & Script Cleanup Plan*
- **Master Implementation Status Matrix:** Component-by-component status (`[IMPLEMENTED]`, `[WORK NEEDED]`, `[PENDING MANUAL]`, `[NEEDS HUMAN REVISION]`).
- **Manual Site Admin Actions:** Admin checklist for deleting old news page, creating/setting new news page, and Greek main menu link update.
- **Component Gap Analysis:** Front Page & Index Page slider exception rules and ID assignments (`13236`, `16894`, `16892`, `18`, `16920`, `16923`); Single Post missing social share buttons & Polylang posts page mapping; Newsletter AST block validation fix & admin PDF upload metabox architecture; Board page review.
- **Migration Pipeline Renaming & Script Cleanup Audit:** 3-stage pipeline renaming (`01-reset-and-setup.sh`, `02-migrate-content.sh`, `03-assign-templates.sh` with manual menu removal) and complete script audit table (`KEEP`, `DELETE`, `NEEDS HUMAN REVISION`).
