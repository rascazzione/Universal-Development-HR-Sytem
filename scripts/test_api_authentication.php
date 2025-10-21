<?php
/**
 * Test script to verify API authentication works
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Competency.php';

echo "=== Testing API Authentication ===\n\n";

// Start a session and simulate login
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

echo "1. Testing category API with session...\n";

// Simulate the API call
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['id'] = 1;

// Capture output
ob_start();
include __DIR__ . '/../public/api/category.php';
$output = ob_get_clean();

echo "API Response: " . $output . "\n";

if (strpos($output, '"success":true') !== false) {
    echo "✓ API authentication working correctly\n";
} else {
    echo "✗ API authentication failed\n";
}

echo "\n=== Test Complete ===\n";