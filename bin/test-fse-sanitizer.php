<?php
/**
 * Unit Test Script for FSE Inline Style Sanitizer & Helper Utilities
 */

require_once __DIR__ . '/migration-helpers.php';

$failures = 0;

function assert_equals($expected, $actual, $test_name) {
    global $failures;
    if ($expected === $actual) {
        echo "[PASS] {$test_name}\n";
    } else {
        echo "[FAIL] {$test_name}\n  Expected: '{$expected}'\n  Actual:   '{$actual}'\n";
        $failures++;
    }
}

// 1. Test sanitize_inline_styles_fse()
$test_cases = [
    [
        'input' => 'font-family: Arial, sans-serif; color: #333; width: 100%; background-color: red;',
        'expected' => 'width: 100%;',
        'name' => 'Strip font, color, background, retain width'
    ],
    [
        'input' => 'flex-basis: 50%; margin: 20px; padding: 10px; flex-grow: 1; flex-shrink: 0; flex-direction: row;',
        'expected' => 'flex-basis: 50%; flex-grow: 1; flex-shrink: 0; flex-direction: row;',
        'name' => 'Retain flex properties, strip margin/padding'
    ],
    [
        'input' => 'aspect-ratio: 16/9; object-fit: cover; text-align: center; vertical-align: middle; float: left;',
        'expected' => 'aspect-ratio: 16/9; object-fit: cover; text-align: center; vertical-align: middle;',
        'name' => 'Retain aspect-ratio, object-fit, text-align, vertical-align, strip float'
    ],
    [
        'input' => 'font-size: 18px; line-height: 1.5; clear: both;',
        'expected' => '',
        'name' => 'Strip all non-allowlisted properties'
    ],
    [
        'input' => '',
        'expected' => '',
        'name' => 'Empty style string handling'
    ]
];

echo "--- Running FSE Style Sanitizer Tests ---\n";
foreach ($test_cases as $case) {
    $result = sanitize_inline_styles_fse($case['input']);
    assert_equals($case['expected'], $result, $case['name']);
}

// 2. Test eka_init_log_file()
echo "\n--- Running Log File Initializer Tests ---\n";
$temp_log = sys_get_temp_dir() . '/test-migration-' . uniqid() . '.log';
file_put_contents($temp_log, "Initial log content\nLine 2\n");
eka_init_log_file($temp_log);
$after_init = file_get_contents($temp_log);
assert_equals('', $after_init, 'Log file truncated to zero bytes on init');
if (file_exists($temp_log)) {
    unlink($temp_log);
}

// 3. Test eka_validate_blocks_ast()
echo "\n--- Running AST Block Validation Tests ---\n";
$valid_block_markup = '<!-- wp:paragraph --><p>Hello World</p><!-- /wp:paragraph -->';
$invalid_block_markup = '<!-- wp:group --><div><p>Unclosed group';
assert_equals(true, eka_validate_blocks_ast($valid_block_markup), 'Valid block markup passes AST check');
assert_equals(false, eka_validate_blocks_ast($invalid_block_markup), 'Unclosed/unbalanced block markup fails AST check');

if ($failures > 0) {
    echo "\nTEST SUITE FAILED with {$failures} failure(s).\n";
    exit(1);
} else {
    echo "\nALL TESTS PASSED CLEANLY.\n";
    exit(0);
}
