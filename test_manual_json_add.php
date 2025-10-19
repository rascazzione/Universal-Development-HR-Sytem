<?php
/**
 * Test adding competencies manually to JSON
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/classes/Competency.php';

$competencyClass = new Competency();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Manual JSON Addition Test</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-5'>
    <h1><i class='fas fa-file-code'></i> Manual JSON Addition Test</h1>
    <p class='lead'>Testing how manually added competencies integrate with the system</p>";

// Test 1: Show current JSON structure
echo "<div class='card mb-4'>
    <div class='card-header'>
        <h5><i class='fas fa-database'></i> Current JSON Structure</h5>
    </div>
    <div class='card-body'>";

$jsonFile = __DIR__ . '/config/soft_skill_levels.json';
if (file_exists($jsonFile)) {
    $jsonContent = file_get_contents($jsonFile);
    $data = json_decode($jsonContent, true);
    
    echo "<h6>Current Competencies in JSON:</h6>";
    echo "<ul>";
    foreach ($data['soft_skills'] as $key => $competency) {
        echo "<li><strong>" . htmlspecialchars($competency['name']) . "</strong> (key: <code>" . htmlspecialchars($key) . "</code>)</li>";
    }
    echo "</ul>";
    
    echo "<h6>JSON File Size:</h6>";
    echo "<p>" . number_format(filesize($jsonFile)) . " bytes</p>";
}

echo "</div></div>";

// Test 2: Key conversion logic
echo "<div class='card mb-4'>
    <div class='card-header'>
        <h5><i class='fas fa-exchange-alt'></i> Competency Name to Key Conversion</h5>
    </div>
    <div class='card-body'>";

echo "<h6>How the system converts names to keys:</h6>";
echo "<div class='table-responsive'>
    <table class='table table-striped'>
        <thead>
            <tr>
                <th>Competency Name</th>
                <th>JSON Key</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>";

$testNames = [
    'Communication Skills',
    'Team Leadership',
    'Problem Solving',
    'Time Management',
    'Conflict Resolution'
];

foreach ($testNames as $name) {
    $key = strtolower(str_replace([' ', '-'], '_', $name));
    $exists = isset($data['soft_skills'][$key]);
    
    echo "<tr>
            <td>" . htmlspecialchars($name) . "</td>
            <td><code>" . htmlspecialchars($key) . "</code></td>
            <td>";
    
    if ($exists) {
        echo "<span class='badge bg-success'>Exists in JSON</span>";
    } else {
        echo "<span class='badge bg-secondary'>Not in JSON</span>";
    }
    
    echo "</td>
        </tr>";
}

echo "</tbody></table></div>";

echo "<div class='alert alert-info'>
        <i class='fas fa-info-circle'></i>
        <strong>Note:</strong> To add a competency manually, create a key using this pattern and add it to the JSON file.
      </div>";

echo "</div></div>";

// Test 3: Show example of adding a new competency
echo "<div class='card mb-4'>
    <div class='card-header'>
        <h5><i class='fas fa-plus-circle'></i> Example: Adding "Communication Skills"</h5>
    </div>
    <div class='card-body'>";

echo "<h6>Step 1: Determine the key</h6>";
echo "<p><strong>Competency Name:</strong> Communication Skills</p>";
echo "<p><strong>JSON Key:</strong> <code>communication_skills</code></p>";

echo "<h6>Step 2: Add to JSON file</h6>";
echo "<pre class='bg-light p-3'>{
  \"name\": \"Communication Skills\",
  \"definition\": \"Does the person effectively convey information and ideas to others?\",
  \"description\": \"Communication involves the ability to clearly express thoughts, ideas, and information to others through various channels.\",
  \"levels\": {
    \"1\": {
      \"title\": \"Conveys basic information\",
      \"behaviors\": [
        \"Shares basic information clearly and directly\",
        \"Listens attentively when others speak\",
        \"Uses appropriate language for the situation\",
        \"Asks questions to ensure understanding\"
      ]
    },
    \"2\": {
      \"title\": \"Adapts communication to audience\",
      \"behaviors\": [
        \"Adjusts communication style based on audience\",
        \"Presents information in a structured way\",
        \"Handles questions and feedback effectively\",
        \"Uses non-verbal cues to support message\"
      ]
    },
    \"3\": {
      \"title\": \"Influences through communication\",
      \"behaviors\": [
        \"Persuades others to accept ideas or proposals\",
        \"Communicates complex concepts clearly\",
        \"Manages difficult conversations constructively\",
        \"Builds rapport through effective communication\"
      ]
    },
    \"4\": {
      \"title\": \"Masters strategic communication\",
      \"behaviors\": [
        \"Develops and implements communication strategies\",
        \"Represents the organization effectively to stakeholders\",
        \"Manages communication crises with confidence\",
        \"Mentors others in improving communication skills\"
      ]
    }
  }
}</pre>";

echo "<h6>Step 3: What happens in the UI</h6>";
echo "<ul>
        <li><i class='fas fa-check text-success'></i> The system finds the competency using the key</li>
        <li><i class='fas fa-check text-success'></i> All fields populate correctly in the modal</li>
        <li><i class='fas fa-check text-success'></i> Behaviors appear as bullet points in text areas</li>
        <li><i class='fas fa-check text-success'></i> You can edit and save changes</li>
        <li><i class='fas fa-check text-success'></i> Changes persist in the JSON file</li>
      </ul>";

echo "</div></div>";

// Test 4: File permissions check
echo "<div class='card mb-4'>
    <div class='card-header'>
        <h5><i class='fas fa-shield-alt'></i> File Permissions Check</h5>
    </div>
    <div class='card-body'>";

$writable = is_writable($jsonFile);
if ($writable) {
    echo "<div class='alert alert-success'>
            <i class='fas fa-check'></i> JSON file is writable (666 permissions)
            <br>UI can save changes to manually added competencies.
          </div>";
} else {
    echo "<div class='alert alert-warning'>
            <i class='fas fa-exclamation-triangle'></i> JSON file is not writable
            <br>Run: <code>chmod 666 config/soft_skill_levels.json</code>
          </div>";
}

echo "</div></div>";

echo "<div class='text-center mt-4 mb-5'>
        <a href='/admin/competencies.php' class='btn btn-primary btn-lg me-2'>
            <i class='fas fa-external-link-alt'></i> Test in Live System
        </a>
        <a href='docs/MANUAL_JSON_EDITING_GUIDE.md' class='btn btn-info btn-lg' target='_blank'>
            <i class='fas fa-book'></i> View Full Guide
        </a>
      </div>";

echo "</div></body></html>";
?>