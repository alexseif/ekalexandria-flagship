# EKA Portal: System Modernization & Redesign Project Roadmap

This document outlines the step-by-step master plan to modernize the digital infrastructure of the Greek Community of Alexandria (EKA). It combines the technical stability requirements from "PROJECT PROPOSAL: EKA PORTAL PHP CORE MODERNIZATION 2026-06-03.pdf" and the visual/functional enhancements from "The Greek Community of Alexandria (EKA) CMS update Proposal 2024-10-12.pdf" into a single, risk-free execution strategy.

---

## Phase 1: Local Sandbox Environment & Infrastructure
*Objective: Build an exact copy of the website on a local laptop to work safely without risking downtime on the live production server.*

*   [x] **Task 1.1: Local Server Initialization**
    *   Set up a localized Nginx web server and database engine (MariaDB/MySQL) on the local workstation.
*   [x] **Task 1.2: Database Snapshot Extraction**
    *   Export and download the core 400+ MB database ledger from the live server.
*   [x] **Task 1.3: Media Storage Routing (The 60GB Footprint Optimization)**
    *   Configure a specialized Nginx network proxy hack. This allows the local test site to read the massive 60+ GB media library directly from the live web without copying files locally, saving significant disk space and setup time.
*   [x] **Task 1.4: Local Domain Mapping (`backstage.ekalexandria.org`)**
    *   Route the staging domain locally on the workstation and run initial database search-and-replace scripts to ensure all internal links function correctly in isolation.

## Phase 2: Technical Debt Audit & Plugin Rationalization
*Objective: Identify obsolete code structures and eliminate heavy legacy plugins that degrade performance or cause active 500 server errors.*

*   [x] **Task 2.1: Legacy Plugin Analysis**
    *   Audit all currently active plugins to trace functionality, performance bottlenecks, and structural compatibility with modern PHP standards.
*   [x] **Task 2.2: Decommissioning Ledger (The "Throw-Away" List)**
    *   Isolate bloated layout builders, legacy sliders, and redundant tracking utilities that can be permanently removed or replaced by native, lightweight block elements.
*   [x] **Task 2.3: Core Feature Extraction Mapping**
    *   Document the unique, essential behaviors of the plugins marked for removal (e.g., custom data fields or multilingual logic) to prepare for clean reimplementation.

## Phase 3: Visual Design Modernization & Approvals
*Objective: Create and finalize the complete look and feel of the new portal before writing a single line of interface code.*

*   [ ] **Task 3.1: Structural Layout Wireframing**
    *   Draft clean structural options for the homepage, the core News section, and the specialized Tachydrómos Newsletter archive layouts.
*   [ ] **Task 3.2: High-Fidelity Design Mockups**
    *   Generate polished, modern visual designs across desktop and mobile screens, integrating enhanced media delivery guidelines (images/videos).
*   [ ] **Task 3.3: Multilingual Layout Adapters**
    *   Design the user experience pathways to fluidly handle shifting interfaces across Greek, English, and Arabic text rules.
*   [ ] **Task 3.4: Client Visual Sign-off**
    *   Present design deliverables to the EKA administration for structural approval.

## Phase 4: Greenfield Theme Development (The Flagship Block Core)
*Objective: Build a custom, high-purity, modern WordPress theme from absolute scratch using strict engineering standards.*

*   [ ] **Task 4.1: Structural Theme Scaffolding**
    *   Initialize a completely blank, lightweight, block-native theme architecture (`theme.json`) completely independent of legacy visual builders.
*   [ ] **Task 4.2: SCSS Style Architecture Setup**
    *   Establish a clean, compiled style ecosystem (SCSS) to govern typography scales, spacing tokens, and color profiles globally without inline code bloat.
*   [x] **Task 4.3: High-Performance Page Templates**
    *   Translate approved visual designs into optimized block layouts for the homepage, inner content, and operational views.

## Phase 5: Re-Engineering Missing Features & Integrations
*Objective: Program clean, native replacements for the essential features of the legacy plugins we threw away.*

*   [x] **Task 5.1: Tachydrómos Archive Engine Customization**
    *   Code clean, fast database filters and sorting functions to display the newsletter archive natively.
*   [x] **Task 5.2: Board of Directors Engineering**
    *   Build custom FSE page templates and queries to dynamically map and display translated board members across three languages natively.
*   [ ] **Task 5.3: News Section Optimization**
    *   Build optimized query loops to surface high-priority community news dynamically.
*   [ ] **Task 5.4: Modern Secure Token Setup (Mailchimp Engine)**
    *   Develop the direct OAuth token loop to securely pass communications to external marketing list engines without utilizing vulnerable text passwords.
*   [ ] **Task 5.5: Community Advertisement Injection Slots**
    *   Program clean layout positions for relevant community advertisements to safely run without tracking scripts slowing down page loading.

## Phase 6: Full Data Migration & System Calibration
*Objective: Merge the historical database content with the brand-new theme and modern database tables.*

*   [x] **Task 6.1: Legacy Data Transformation Scripting**
    *   Execute targeted script operations to map old post formats into clean, standardized block patterns (e.g., migrating legacy shortcode grids into native Gutenberg blocks and CPTs for Newsletters and Board Members).
*   [ ] **Task 6.2: Database Cleanup & Schema Alignment**
    *   Purge historical plugin remnants, clear junk metadata, and optimize database indexing across the 400+ MB file ledger to stabilize table performance.
*   [ ] **Task 6.3: Multi-Language Validation Drill**
    *   Stress-test the language switching integrity for all historic posts across English, Greek, and Arabic configurations.

## Phase 7: Production Cutover & Final Launch
*Objective: Deploy the modernized platform to the live production server with zero downtime.*

*   [ ] **Task 7.1: Server Runtime Hardening**
    *   Configure the live production stack environment to modern PHP standards.
*   [ ] **Task 7.2: Final Content Sync Delta**
    *   Run a final differential database migration to capture any new articles or announcements published on the live portal during local development.
*   [ ] **Task 7.3: Deployment, Routing & Upstream Flushes**
    *   Push the new theme and cleaned database live, point local Nginx configurations to the local media assets folder, clear system caches, and verify complete global site availability.