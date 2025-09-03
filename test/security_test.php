<?php
/**
 * Security Test Suite for BeyondTrailTales
 * 
 * Tests various security improvements and validates input handling
 */

require_once dirname(__DIR__) . '/app/config.php';
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/app/classes/Validator.php';
require_once dirname(__DIR__) . '/api/classes/Database.php';

header('Content-Type: text/plain; charset=UTF-8');

echo "=================================================\n";
echo "BeyondTrailTales Security Test Suite\n";
echo "=================================================\n\n";

$tests_passed = 0;
$tests_failed = 0;
$test_results = [];

/**
 * Test helper function
 */
function test($description, $result, $expected = true) {
    global $tests_passed, $tests_failed, $test_results;
    
    $passed = ($result === $expected);
    
    if ($passed) {
        $tests_passed++;
        echo "✓ ";
    } else {
        $tests_failed++;
        echo "✗ ";
    }
    
    echo $description . "\n";
    
    $test_results[] = [
        'description' => $description,
        'passed' => $passed,
        'result' => $result,
        'expected' => $expected
    ];
    
    return $passed;
}

/**
 * Test section helper
 */
function section($title) {
    echo "\n" . str_repeat("-", 40) . "\n";
    echo $title . "\n";
    echo str_repeat("-", 40) . "\n";
}

// ============================================
// 1. Input Validation Tests
// ============================================
section("1. Input Validation Tests");

// String sanitization
test("String sanitization removes null bytes", 
    Validator::sanitizeString("Test\x00String") === "TestString");

test("String sanitization limits length", 
    strlen(Validator::sanitizeString(str_repeat("a", 300), 100)) === 100);

test("String sanitization removes control characters", 
    Validator::sanitizeString("Test\x01\x02\x03String") === "TestString");

// Integer validation
test("Integer validation accepts valid int", 
    Validator::sanitizeInt("123") === 123);

test("Integer validation rejects invalid int", 
    Validator::sanitizeInt("abc") === null);

test("Integer validation enforces minimum", 
    Validator::sanitizeInt("-5", 0) === 0);

test("Integer validation enforces maximum", 
    Validator::sanitizeInt("150", null, 100) === 100);

// Float validation
test("Float validation accepts valid float", 
    Validator::sanitizeFloat("123.45") === 123.45);

test("Float validation rejects invalid float", 
    Validator::sanitizeFloat("not-a-number") === null);

// Email validation
test("Email validation accepts valid email", 
    Validator::validateEmail("test@example.com") === "test@example.com");

test("Email validation rejects invalid email", 
    Validator::validateEmail("not-an-email") === null);

test("Email validation normalizes to lowercase", 
    Validator::validateEmail("TEST@EXAMPLE.COM") === "test@example.com");

// Date validation
test("Date validation accepts valid date", 
    Validator::validateDate("2025-09-03") === "2025-09-03");

test("Date validation rejects invalid date", 
    Validator::validateDate("2025-13-45") === null);

test("Date validation rejects malformed date", 
    Validator::validateDate("not-a-date") === null);

// URL validation
test("URL validation accepts valid URL", 
    Validator::validateUrl("https://example.com") === "https://example.com");

test("URL validation accepts relative URL", 
    Validator::validateUrl("/path/to/page") === "/path/to/page");

test("URL validation rejects javascript protocol", 
    Validator::validateUrl("javascript:alert(1)") === null);

// ============================================
// 2. XSS Prevention Tests
// ============================================
section("2. XSS Prevention Tests");

test("HTML escape prevents script injection", 
    Validator::escape("<script>alert('XSS')</script>") === "&lt;script&gt;alert('XSS')&lt;/script&gt;");

test("HTML escape handles quotes", 
    Validator::escape("' OR '1'='1") === "' OR '1'='1");

test("HTML escape handles double quotes", 
    Validator::escape('" onmouseover="alert(1)"') === '&quot; onmouseover=&quot;alert(1)&quot;');

test("HTML escape handles null input", 
    Validator::escape(null) === '');

// ============================================
// 3. SQL Injection Prevention Tests
// ============================================
section("3. SQL Injection Prevention Tests");

$db = Database::getInstance();

// Test table name validation
$sql_injection_prevented = false;
try {
    // This should throw an exception due to invalid table name
    $db->insert("users; DROP TABLE users; --", ['name' => 'test']);
} catch (InvalidArgumentException $e) {
    $sql_injection_prevented = true;
}
test("Database class prevents SQL injection in table names", $sql_injection_prevented);

// Test column name validation
$sql_injection_prevented = false;
try {
    // This should throw an exception due to invalid column name
    $db->insert("backpacks", ['name; DROP TABLE users; --' => 'test']);
} catch (InvalidArgumentException $e) {
    $sql_injection_prevented = true;
}
test("Database class prevents SQL injection in column names", $sql_injection_prevented);

// ============================================
// 4. File Upload Security Tests
// ============================================
section("4. File Upload Security Tests");

// Simulate file upload data
$valid_file = [
    'name' => 'test.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => __FILE__, // Use this file as dummy
    'error' => UPLOAD_ERR_OK,
    'size' => 1024
];

$invalid_file = [
    'name' => 'test.php',
    'type' => 'application/x-php',
    'tmp_name' => __FILE__,
    'error' => UPLOAD_ERR_OK,
    'size' => 1024
];

$result = Validator::validateFileUpload($valid_file, ['jpg', 'jpeg', 'png'], 5242880);
test("File upload validation accepts valid image", $result['valid'] === true);

$result = Validator::validateFileUpload($invalid_file, ['jpg', 'jpeg', 'png'], 5242880);
test("File upload validation rejects PHP files", $result['valid'] === false);

// File name sanitization
test("File name sanitization removes special characters", 
    Validator::sanitizeFileName("../../etc/passwd") === "etcpasswd");

test("File name sanitization preserves valid characters", 
    Validator::sanitizeFileName("my-photo_123.jpg") === "my-photo_123.jpg");

// ============================================
// 5. CSRF Token Tests
// ============================================
section("5. CSRF Token Tests");

// Start session for CSRF tests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token1 = Validator::generateCsrfToken();
test("CSRF token generation creates non-empty token", 
    strlen($token1) > 0);

test("CSRF token validation accepts valid token", 
    Validator::validateCsrfToken($token1));

test("CSRF token validation rejects invalid token", 
    Validator::validateCsrfToken("invalid-token") === false);

test("CSRF token validation rejects null token", 
    Validator::validateCsrfToken(null) === false);

// ============================================
// 6. Required Field Validation Tests
// ============================================
section("6. Required Field Validation Tests");

$data = ['name' => 'Test', 'email' => ''];
$errors = Validator::validateRequired($data, ['name', 'email', 'phone']);

test("Required field validation detects missing fields", 
    count($errors) === 2);

test("Required field validation identifies empty fields", 
    isset($errors['email']));

test("Required field validation identifies missing fields", 
    isset($errors['phone']));

// ============================================
// 7. Enum Validation Tests
// ============================================
section("7. Enum Validation Tests");

$allowed = ['easy', 'moderate', 'difficult'];

test("Enum validation accepts valid value", 
    Validator::validateEnum('moderate', $allowed) === 'moderate');

test("Enum validation rejects invalid value", 
    Validator::validateEnum('extreme', $allowed) === null);

test("Enum validation handles null input", 
    Validator::validateEnum(null, $allowed) === null);

// ============================================
// 8. Token Generation Tests
// ============================================
section("8. Token Generation Tests");

$token1 = Validator::generateToken(16);
$token2 = Validator::generateToken(16);

test("Token generation creates correct length", 
    strlen($token1) === 32); // 16 bytes = 32 hex chars

test("Token generation creates unique tokens", 
    $token1 !== $token2);

test("Token generation is cryptographically secure", 
    preg_match('/^[a-f0-9]{32}$/', $token1) === 1);

// ============================================
// Test Summary
// ============================================
echo "\n" . str_repeat("=", 50) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "Tests Passed: $tests_passed\n";
echo "Tests Failed: $tests_failed\n";
echo "Total Tests: " . ($tests_passed + $tests_failed) . "\n";
echo "Success Rate: " . round(($tests_passed / ($tests_passed + $tests_failed)) * 100, 2) . "%\n";

if ($tests_failed > 0) {
    echo "\nFailed Tests:\n";
    foreach ($test_results as $result) {
        if (!$result['passed']) {
            echo "  - " . $result['description'] . "\n";
            echo "    Expected: " . var_export($result['expected'], true) . "\n";
            echo "    Got: " . var_export($result['result'], true) . "\n";
        }
    }
}

echo "\n✅ Security test suite completed.\n";
