<?php
/**
 * Test POST request to soft skill levels API
 */

// Test data that matches what the JavaScript would send
$testData = [
    'csrf_token' => 'test_token', // We'll bypass this for now
    'competency_key' => 'test_competency',
    'levels' => json_encode([
        'name' => 'Test Competency',
        'definition' => 'Test definition',
        'description' => 'Test description',
        'levels' => [
            '1' => [
                'title' => 'Basic Level',
                'behaviors' => ['Behavior 1', 'Behavior 2', 'Behavior 3', 'Behavior 4']
            ],
            '2' => [
                'title' => 'Intermediate Level',
                'behaviors' => ['Behavior 1', 'Behavior 2', 'Behavior 3', 'Behavior 4']
            ],
            '3' => [
                'title' => 'Advanced Level',
                'behaviors' => ['Behavior 1', 'Behavior 2', 'Behavior 3', 'Behavior 4']
            ],
            '4' => [
                'title' => 'Expert Level',
                'behaviors' => ['Behavior 1', 'Behavior 2', 'Behavior 3', 'Behavior 4']
            ]
        ]
    ])
];

// Use cURL to make POST request
$ch = curl_init('http://127.0.0.1/api/soft_skill_levels.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($testData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h1>POST Test Results</h1>";
echo "<p>HTTP Code: $httpCode</p>";
if ($error) {
    echo "<p>CURL Error: $error</p>";
}
echo "<h2>Response:</h2>";
echo "<pre>$response</pre>";

// Also check the error logs
echo "<h2>Checking Error Logs:</h2>";
$logOutput = `docker exec web_object_classification-web-1 tail -20 /var/log/apache2/error.log 2>/dev/null || echo "No error logs found"`;
echo "<pre>$logOutput</pre>";
?>