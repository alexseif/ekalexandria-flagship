# EKA Portal - Legacy Layout Implementation SPEC

## 1. Topic and Issue Name
**Topic:** EKA Portal Modernization
**Issue:** Phase 3 Pivot - Legacy Layout Implementation 

## 2. Objective and Target Users
**Objective:** 
Following the cancellation of the Phase 3 visual redesign, the objective is to implement a strict 1:1 visual replication of the existing live website's layout (previously built on BeTheme) using the modern, block-native Full Site Editing (FSE) architecture from Phase 4. 

**Target Users:** 
The target users remain unchanged. The transition should be visually seamless to visitors, providing the exact same interface while benefiting from modern, performant backend infrastructure.

## 3. Core Features and Acceptance Criteria
The new FSE theme must successfully replicate the following core page structures:
*   **Homepage:** Exact visual replication, replacing legacy sliders with modern equivalents (e.g., Carousel Block).
*   **Internal Pages:** Standard content pages, predominantly featuring sidebar layouts.
*   **News Page:** Archival layout including a categories sidebar.
*   **Categories Page:** Similar layout to the News page, but without slider elements.
*   **New Updated Pages (Exceptions):** 
    *   Tachydrómos Newsletter Archive (Custom Phase 5 implementation).
    *   Board of Directors (Custom Phase 5 implementation).
*   **Contact Page:** Clean integration replacing legacy embedded plugins.

**Acceptance Criteria:**
*   Typography, colors, and global spacing perfectly match the existing live site (ekalexandria.org).
*   All layouts are fully built using native WordPress block features or `theme.json`, avoiding hardcoded layout CSS where block settings suffice.
*   Broken layouts caused by outdated plugins in the legacy environment are resolved by mapping them to clean, native blocks. (User will handle final manual verification).

## 4. Tech Stack Preferences and Constraints
*   **Theme Architecture:** WordPress Block Theme (FSE), `theme.json`.
*   **Styling:** Native block settings where possible, augmented by an SCSS architecture for custom utilities or complex components not supported natively. Minimal modifications; primary focus is extracting colors and typography from the existing live site.
*   **Plugins:** Utilize standard, lightweight, and modern block plugins when native WordPress blocks fall short (e.g., using `carousel-block` for slideshows). No heavy page builders.

## 5. Known Boundaries
*   **Always do:** 
    *   Maintain the existing aesthetic (1:1 clone).
    *   Present at least two options with pros/cons when ambiguity arises in how to replicate a legacy feature using blocks.
*   **Ask first about:** 
    *   Any third-party block plugin recommendations required to fulfill a legacy feature.
*   **Never do:** 
    *   Introduce new visual designs, micro-animations, or layout changes not present on the legacy site (except for the explicitly defined new pages: Tachydrómos and Board of Directors).
    *   Over-engineer custom CSS; rely on `theme.json` and block settings as the primary styling engine.

## 6. Design Patterns & Coding Standards
*   **Separation of Concerns:** Global styles (colors, typography scales, layout widths) must be defined in `theme.json`. SCSS should be strictly reserved for block-specific styling overrides that cannot be achieved via FSE.
*   **Block Patterns:** Reusable layout structures (e.g., standard sidebars, hero sections) should be registered as WordPress Block Patterns to ensure consistency across templates.
*   **Fallback Handling:** Where legacy plugins (like LayerSlider) were used, transition to native solutions (Cover blocks, Gallery blocks) or approved lightweight replacements (Carousel Block).
