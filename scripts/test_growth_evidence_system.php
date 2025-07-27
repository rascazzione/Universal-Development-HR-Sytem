<?php
/**
 * Test script for the Growth Evidence System
 * This script tests the new evidence-based evaluation system
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/GrowthEvidenceJournal.php';
require_once __DIR__ . '/../classes/MediaManager.php';
require_once __DIR__ . '/../classes/Evaluation.php';
require_once __DIR__ . '/../classes/EvaluationPeriod.php';

echo "Testing Growth Evidence System...\n";

try {
    // Test 1: Create a growth evidence entry
    echo "\n1. Testing GrowthEvidenceJournal class...\n";
    $journal = new GrowthEvidenceJournal();
    
    // Create a test entry
    $entryData = [
        'employee_id' => 1, // Assuming employee ID 1 exists
        'manager_id' => 2, // Assuming manager ID 2 exists
        'content' => 'Employee demonstrated excellent leadership skills during the project meeting.',
        'star_rating' => 5,
        'dimension' => 'competencies',
        'entry_date' => date('Y-m-d')
    ];
    
    $entryId = $journal->createEntry($entryData);
    echo "   Created evidence entry with ID: $entryId\n";
    
    // Test 2: Retrieve the entry
    $entry = $journal->getEntryById($entryId);
    if ($entry) {
        echo "   Retrieved entry: " . $entry['content'] . " (Rating: " . $entry['star_rating'] . "/5)\n";
    } else {
        echo "   Failed to retrieve entry\n";
    }
    
    // Test 3: Test Evaluation class
    echo "\n2. Testing Evaluation class...\n";
    $evaluation = new Evaluation();
    
    // Create a test evaluation
    $evaluationData = [
        'employee_id' => 1,
        'evaluator_id' => 2,
        'period_id' => 1 // Assuming period ID 1 exists
    ];
    
    $evaluationId = $evaluation->createEvaluation($evaluationData);
    echo "   Created evaluation with ID: $evaluationId\n";
    
    // Test 4: Test EvaluationPeriod class
    echo "\n3. Testing EvaluationPeriod class...\n";
    $period = new EvaluationPeriod();
    
    // Get period by ID
    $periodData = $period->getPeriodById(1);
    if ($periodData) {
        echo "   Retrieved period: " . $periodData['period_name'] . "\n";
    } else {
        echo "   Failed to retrieve period\n";
    }
    
    echo "\nAll tests completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
