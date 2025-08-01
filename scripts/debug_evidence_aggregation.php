<?php
/**
 * Debug Evidence Aggregation for Specific Evaluation
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Evaluation.php';
require_once __DIR__ . '/../classes/GrowthEvidenceJournal.php';

echo "=== EVIDENCE AGGREGATION DEBUG ===\n\n";

// Get the evaluation ID from the URL we saw (Robert Davis)
$evaluationId = null;

// First, let's find Robert Davis's evaluation
$sql = "SELECT e.evaluation_id, e.employee_id, emp.first_name, emp.last_name 
        FROM evaluations e 
        JOIN employees emp ON e.employee_id = emp.employee_id 
        WHERE emp.first_name = 'Robert' AND emp.last_name = 'Davis'
        LIMIT 1";

$result = fetchAll($sql);
if (!empty($result)) {
    $evaluationId = $result[0]['evaluation_id'];
    $employeeId = $result[0]['employee_id'];
    echo "Found Robert Davis - Employee ID: $employeeId, Evaluation ID: $evaluationId\n\n";
} else {
    echo "Robert Davis not found. Let's check available evaluations:\n";
    $sql = "SELECT e.evaluation_id, e.employee_id, emp.first_name, emp.last_name 
            FROM evaluations e 
            JOIN employees emp ON e.employee_id = emp.employee_id 
            LIMIT 5";
    $results = fetchAll($sql);
    foreach ($results as $row) {
        echo "- Evaluation ID: {$row['evaluation_id']}, Employee: {$row['first_name']} {$row['last_name']}\n";
    }
    
    if (!empty($results)) {
        $evaluationId = $results[0]['evaluation_id'];
        $employeeId = $results[0]['employee_id'];
        echo "\nUsing first evaluation: ID $evaluationId for Employee ID $employeeId\n\n";
    } else {
        echo "No evaluations found!\n";
        exit(1);
    }
}

// Check current evidence aggregation status
echo "=== CURRENT EVIDENCE AGGREGATION STATUS ===\n";
$sql = "SELECT * FROM evidence_evaluation_results WHERE evaluation_id = ?";
$currentResults = fetchAll($sql, [$evaluationId]);

if (empty($currentResults)) {
    echo "No evidence aggregation results found for evaluation $evaluationId\n";
} else {
    echo "Found " . count($currentResults) . " aggregation results:\n";
    foreach ($currentResults as $result) {
        echo "- {$result['dimension']}: {$result['evidence_count']} entries, avg rating: {$result['avg_star_rating']}, score: {$result['calculated_score']}\n";
    }
}

// Check available evidence entries for this employee
echo "\n=== AVAILABLE EVIDENCE ENTRIES ===\n";
$sql = "SELECT dimension, COUNT(*) as count, AVG(star_rating) as avg_rating 
        FROM growth_evidence_entries 
        WHERE employee_id = ? 
        GROUP BY dimension";
$evidenceEntries = fetchAll($sql, [$employeeId]);

if (empty($evidenceEntries)) {
    echo "No evidence entries found for employee $employeeId\n";
} else {
    echo "Found evidence entries for employee $employeeId:\n";
    foreach ($evidenceEntries as $entry) {
        echo "- {$entry['dimension']}: {$entry['count']} entries, avg rating: " . round($entry['avg_rating'], 2) . "\n";
    }
}

// Get evaluation period information
echo "\n=== GETTING EVALUATION PERIOD ===\n";
$sql = "SELECT ep.* FROM evaluations e
        JOIN evaluation_periods ep ON e.period_id = ep.period_id
        WHERE e.evaluation_id = ?";
$periodResult = fetchAll($sql, [$evaluationId]);

if (empty($periodResult)) {
    echo "❌ No evaluation period found for evaluation $evaluationId\n";
    exit(1);
}

$period = $periodResult[0];
echo "Found period: {$period['period_name']} ({$period['start_date']} to {$period['end_date']})\n";

// Now trigger evidence aggregation
echo "\n=== TRIGGERING EVIDENCE AGGREGATION ===\n";
$evaluationClass = new Evaluation();

try {
    $result = $evaluationClass->aggregateEvidence($evaluationId, $employeeId, $period);
    if ($result) {
        echo "✅ Evidence aggregation completed successfully!\n";
    } else {
        echo "❌ Evidence aggregation failed!\n";
    }
} catch (Exception $e) {
    echo "❌ Error during aggregation: " . $e->getMessage() . "\n";
}

// Check results after aggregation
echo "\n=== RESULTS AFTER AGGREGATION ===\n";
$sql = "SELECT * FROM evidence_evaluation_results WHERE evaluation_id = ?";
$newResults = fetchAll($sql, [$evaluationId]);

if (empty($newResults)) {
    echo "Still no evidence aggregation results found!\n";
} else {
    echo "Found " . count($newResults) . " aggregation results:\n";
    foreach ($newResults as $result) {
        echo "- {$result['dimension']}: {$result['evidence_count']} entries, avg rating: {$result['avg_star_rating']}, score: {$result['calculated_score']}\n";
    }
}

echo "\n=== DEBUG COMPLETE ===\n";
echo "You can now refresh the evaluation page to see the evidence data.\n";