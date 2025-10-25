<?php
/**
 * Test the simplified UI for Soft Skill Levels
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/classes/Competency.php';

$competencyClass = new Competency();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Simplified UI Test - Soft Skill Levels</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-5'>
    <h1><i class='fas fa-layer-group'></i> Simplified UI Test</h1>
    <p class='lead'>Testing the new markdown-compatible interface for soft skill levels</p>";

// Test 1: Load existing data
echo "<div class='card mb-4'>
    <div class='card-header'>
        <h5><i class='fas fa-database'></i> Test 1: Loading Existing Data</h5>
    </div>
    <div class='card-body'>";

$peopleManagement = $competencyClass->getSoftSkillLevels('people_management');
if ($peopleManagement) {
    echo "<div class='alert alert-success'><i class='fas fa-check'></i> ✓ People Management loaded successfully</div>";
    
    echo "<h6>Current Data Structure:</h6>";
    echo "<pre class='bg-light p-3'>" . json_encode($peopleManagement, JSON_PRETTY_PRINT) . "</pre>";
    
    echo "<h6>Markdown Conversion Example:</h6>";
    echo "<div class='row'>";
    foreach ($peopleManagement['levels'] as $levelNum => $level) {
        echo "<div class='col-md-6 mb-3'>
            <div class='card'>
                <div class='card-header bg-primary text-white'>
                    Level $levelNum: " . htmlspecialchars($level['title']) . "
                </div>
                <div class='card-body'>
                    <h6>Original Behaviors:</h6>
                    <ul>";
        foreach ($level['behaviors'] as $behavior) {
            echo "<li>" . htmlspecialchars($behavior) . "</li>";
        }
        echo "</ul>
                    <h6>Markdown Format:</h6>
                    <textarea class='form-control' rows='4' readonly>";
        foreach ($level['behaviors'] as $behavior) {
            echo "- " . htmlspecialchars($behavior) . "\n";
        }
        echo "</textarea>
                </div>
            </div>
        </div>";
    }
    echo "</div>";
} else {
    echo "<div class='alert alert-danger'><i class='fas fa-times'></i> ✗ Failed to load People Management</div>";
}

echo "</div></div>";

// Test 2: Markdown Parsing Function
echo "<div class='card mb-4'>
    <div class='card-header'>
        <h5><i class='fas fa-code'></i> Test 2: Markdown Parsing Logic</h5>
    </div>
    <div class='card-body'>";

echo "<h6>Test Cases:</h6>";
$testCases = [
    "- First behavior\n- Second behavior\n- Third behavior\n- Fourth behavior",
    "1. First behavior\n2. Second behavior\n3. Third behavior\n4. Fourth behavior",
    "* First behavior\n* Second behavior\n* Third behavior\n* Fourth behavior",
    "First behavior\nSecond behavior\nThird behavior\nFourth behavior"
];

foreach ($testCases as $i => $testCase) {
    echo "<div class='mb-3'>
        <h6>Test Case " . ($i + 1) . ":</h6>
        <pre class='bg-light p-2'>" . htmlspecialchars($testCase) . "</pre>
        <div class='text-muted'>Expected: Array with 4 behaviors</div>
    </div>";
}

echo "</div></div>";

// Test 3: UI Improvements
echo "<div class='card mb-4'>
    <div class='card-header'>
        <h5><i class='fas fa-paint-brush'></i> Test 3: UI Improvements</h5>
    </div>
    <div class='card-body'>";

echo "<div class='alert alert-info'>
        <h6><i class='fas fa-info-circle'></i> Simplified UI Features:</h6>
        <ul>
            <li><strong>Non-collapsible layout:</strong> All 4 levels visible at once</li>
            <li><strong>Markdown text areas:</strong> Single field for all behaviors</li>
            <li><strong>Color-coded levels:</strong> Visual distinction between levels</li>
            <li><strong>Helper text:</strong> Instructions for markdown formatting</li>
            <li><strong>2x2 grid layout:</strong> Better use of screen space</li>
        </ul>
    </div>";

echo "<h6>Benefits:</h6>";
echo "<ul>
        <li><i class='fas fa-check text-success'></i> Faster editing - no need to click through accordions</li>
        <li><i class='fas fa-check text-success'></i> Copy-paste friendly - markdown format is standard</li>
        <li><i class='fas fa-check text-success'></i> Better overview - see all levels at once</li>
        <li><i class='fas fa-check text-success'></i> Flexible formatting - supports bullets, numbers, or plain text</li>
    </ul>";

echo "</div></div>";

// Test 4: Instructions
echo "<div class='card mb-4'>
    <div class='card-header'>
        <h5><i class='fas fa-question-circle'></i> Test 4: User Instructions</h5>
    </div>
    <div class='card-body'>";

echo "<div class='row'>
        <div class='col-md-6'>
            <h6>How to Edit:</h6>
            <ol>
                <li>Click 'View Levels' button for any soft skill competency</li>
                <li>Edit the definition and description as needed</li>
                <li>For each level, enter a title</li>
                <li>In the behaviors field, use one of these formats:</li>
            </ol>
            <ul class='list-unstyled ms-4'>
                <li><code>- Behavior one</code> (bullet points)</li>
                <li><code>1. Behavior one</code> (numbered list)</li>
                <li><code>Behavior one</code> (plain text, one per line)</li>
            </ul>
        </div>
        <div class='col-md-6'>
            <h6>Example:</h6>
            <div class='bg-light p-3'>
                <pre>- Gives clear instructions
- Delegates routine tasks
- Assigns responsibilities
- Organizes team work</pre>
            </div>
            <small class='text-muted'>This will be converted to an array of 4 behaviors</small>
        </div>
    </div>";

echo "</div></div>";

echo "<div class='text-center mt-4 mb-5'>
        <a href='/admin/competencies.php' class='btn btn-primary btn-lg me-2'>
            <i class='fas fa-external-link-alt'></i> Test the Simplified UI
        </a>
        <button class='btn btn-secondary btn-lg' onclick='window.location.reload()'>
            <i class='fas fa-sync'></i> Refresh Test
        </button>
      </div>";

echo "</div></body></html>";
?>