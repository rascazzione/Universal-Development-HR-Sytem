<?php
/**
 * Test the Soft Skill Levels API endpoint
 */

// Test GET request - get People Management levels
echo "<h1>Testing Soft Skill Levels API</h1>";

// Test 1: GET request for specific competency
echo "<h2>Test 1: GET People Management Levels</h2>";
$ch = curl_init('http://localhost:8080/api/soft_skill_levels.php?competency_key=people_management');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: PHPSESSID=' . session_id()]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
if ($response) {
    $data = json_decode($response, true);
    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<p style='color: red;'>No response received</p>";
}

// Test 2: GET all soft skill definitions
echo "<h2>Test 2: GET All Soft Skill Definitions</h2>";
$ch = curl_init('http://localhost:8080/api/soft_skill_levels.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: PHPSESSID=' . session_id()]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
if ($response) {
    $data = json_decode($response, true);
    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<p style='color: red;'>No response received</p>";
}

// Test 3: Test with invalid competency
echo "<h2>Test 3: GET Invalid Competency</h2>";
$ch = curl_init('http://localhost:8080/api/soft_skill_levels.php?competency_key=invalid_competency');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: PHPSESSID=' . session_id()]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
if ($response) {
    $data = json_decode($response, true);
    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<p style='color: red;'>No response received</p>";
}

echo "<h2>Summary</h2>";
echo "<p>API endpoint testing completed. Check the responses above for any issues.</p>";
?>