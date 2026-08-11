# TODO: migration-modular-refactor / content-transformer-split

## Phase 1: Classic Editor Module (Foundation)
- [ ] Task 1: Create `ClassicEditorTransformer` class
- [ ] Task 2: Create `ClassicEditorTransformerTest` (6 tests)
- [ ] **CHECKPOINT**: git commit `refactor(migration): extract ClassicEditorTransformer module`

## Phase 2: Edge Case Module
- [ ] Task 3: Create `EdgeCasePreProcessor` class
- [ ] Task 4: Create `EdgeCasePreProcessorTest` (3 tests)
- [ ] **CHECKPOINT**: git commit `refactor(migration): extract EdgeCasePreProcessor module`

## Phase 3: MFN Builder Module
- [ ] Task 5: Create `MfnBuilderTransformer` class (depends on Classic)
- [ ] Task 6: Create `MfnBuilderTransformerTest` (15 tests)
- [ ] **CHECKPOINT**: git commit `refactor(migration): extract MfnBuilderTransformer module`

## Phase 4: Facade & Integration
- [ ] Task 7: Convert ContentTransformer to thin facade (< 150 lines)
- [ ] **CHECKPOINT**: git commit `refactor(migration): convert ContentTransformer to delegation facade`
- [ ] Task 8: Update engine scripts to use new classes directly
- [ ] **CHECKPOINT**: git commit `refactor(migration): update engine scripts for modular classes`

## Verification Gate
- [ ] All 57+ tests pass (`vendor/bin/phpunit`)
- [ ] `ContentTransformer.php` < 150 lines
- [ ] No circular dependencies (MFN→Classic only)
- [ ] `migration-content-engine.php` functional
- [ ] `convert-classic-to-gutenberg.php` functional
