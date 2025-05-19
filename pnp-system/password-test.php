<?php
// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHP Password Function Test</h1>";

// Test 1: Create a new hash for "admin123"
echo "<h2>Test 1: Create a new hash</h2>";
$password = "admin123";
$new_hash = password_hash($password, PASSWORD_DEFAULT);
echo "<p>Password: $password</p>";
echo "<p>New hash: $new_hash</p>";

// Test 2: Verify the newly created hash
echo "<h2>Test 2: Verify with the new hash</h2>";
$verify_result = password_verify($password, $new_hash);
echo "<p>Verification result: " . ($verify_result ? "✓ Success" : "✗ Failed") . "</p>";

// Test 3: Verify with the stored hash from the system
echo "<h2>Test 3: Verify with the stored hash</h2>";
$stored_hash = '$2y$10$wAp12NeZ2PU.UZaufO4Sue9EVVGrgj9KNK2TT9zVU8m/dSQfJYVQ6';
$verify_stored = password_verify($password, $stored_hash);
echo "<p>Stored hash: $stored_hash</p>";
echo "<p>Verification result: " . ($verify_stored ? "✓ Success" : "✗ Failed") . "</p>";

// Test 4: Create a hash with specific cost parameter
echo "<h2>Test 4: Create hash with specific options</h2>";
$options = ['cost' => 10];
$specific_hash = password_hash($password, PASSWORD_DEFAULT, $options);
echo "<p>Hash with cost=10: $specific_hash</p>";
$verify_specific = password_verify($password, $specific_hash);
echo "<p>Verification result: " . ($verify_specific ? "✓ Success" : "✗ Failed") . "</p>";

// Test 5: Check PHP version and password hashing info
echo "<h2>Test 5: PHP Environment Info</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Default algorithm: " . password_algos()[0] . "</p>";
echo "<pre>";
print_r(password_get_info($stored_hash));
echo "</pre>";

// Test 6: Create and verify with BCRYPT explicitly
echo "<h2>Test 6: Explicit BCRYPT test</h2>";
$bcrypt_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
echo "<p>BCRYPT hash: $bcrypt_hash</p>";
$verify_bcrypt = password_verify($password, $bcrypt_hash);
echo "<p>Verification result: " . ($verify_bcrypt ? "✓ Success" : "✗ Failed") . "</p>";

// Test 7: Different string encoding test
echo "<h2>Test 7: String encoding test</h2>";
$utf8_password = "admin123"; // UTF-8 encoded
$utf8_hash = password_hash($utf8_password, PASSWORD_BCRYPT);
echo "<p>UTF-8 hash: $utf8_hash</p>";
$verify_utf8 = password_verify($utf8_password, $utf8_hash);
echo "<p>Verification result: " . ($verify_utf8 ? "✓ Success" : "✗ Failed") . "</p>";
?> 