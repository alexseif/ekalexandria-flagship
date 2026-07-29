# Todo: Phase 3 Legacy Layout Extraction & Replication (phase3-legacy-replication)

## Phase 3.1: Global Foundation & Style Mapping
- [x] Inspect live legacy site for typography (font families, sizes, weights), color hex codes, and structural breakpoints.
- [x] Update `theme.json` with extracted color palette and typography settings.
- [x] Configure FSE layout widths in `theme.json` to match legacy container constraints.
- [x] Initialize/update base SCSS files for non-standard UI overrides.
- [x] Verify global styling applies correctly in the FSE Site Editor.

## Phase 3.2: Homepage FSE Replication
- [x] Scaffold `front-page.html` (or `home.html`) template in the block theme.
- [x] Recreate legacy homepage structure using native `core/group`, `core/columns`, etc.
- [x] Implement `core/query` block for the "News / Ανακοινώσεις" section (Category: News, limit: 5).
- [x] Verify visual parity of the new block homepage against the legacy site.

## Phase 3.3: Internal Templates & Legacy Menu Routing
- [ ] Create/update `page.html`, `single.html`, and `archive.html` FSE templates.
- [ ] Create layout structure with main content area and sidebar area.
- [ ] Configure sidebar blocks to utilize native Navigation blocks.
- [ ] Hardcode/assign legacy Menu IDs for Establishment (70, 3377, 3378), Activities (71, 3944, 3945), and Services (117, 3707, 3716) pages based on `legacy_data.md`.
- [ ] Verify internal pages render with proper spacing and correct sidebars.

## Phase 3.4: Specialty Templates (Board & Tachydrómos)
- [ ] Create `page-board.html` template.
- [ ] Implement query loop in board template querying the `board_member` (or equivalent) CPT, ordered by `menu_order`.
- [ ] Create `archive-alx_tachydromos.html` matching legacy layout.
- [ ] Create `single-alx_tachydromos.html` including the featured image (PDF thumbnail) and download link.
- [ ] Verify specialized layouts map 1:1 to legacy designs.

## Phase 3.5: Third-Party Plugin Mitigation (Galleries)
- [ ] Update 'Staff' page with a native `core/gallery` block placeholder.
- [ ] Update 'Community Lounge' page with a `core/gallery` block using Image IDs `10328`.
- [ ] Update 'Cemeteries' and 'Conservation' pages using respective Image IDs (`10329, 7667...` and `7935, 7936...`).
- [ ] Update 'Music Museum' and 'Science Museum' pages using respective Image IDs (`7821...` and `7813...`).
- [ ] Verify gallery blocks render the correct legacy media assets cleanly.
