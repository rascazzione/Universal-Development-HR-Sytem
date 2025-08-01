<?php
/**
 * Aggregate Evidence for All Evaluations
 * Fixes missing evidence aggregation across the system
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Evaluation.php';
require_once __DIR__ . '/../classes/GrowthEvidenceJournal.php';

echo "=== AGGREGATE EVIDENCE FOR ALL EVALUATIONS ===\n\n";

$evaluationClass = new Evaluation();

// Find all evaluations that have employees with evidence entries but no aggregation results
$sql = "SELECT DISTINCT e.evaluation_id, e.employee_id, emp.first_name, emp.last_name,
               ep.period_name, ep.start_date, ep.end_date
        FROM evaluations e
        JOIN employees emp ON e.employee_id = emp.employee_id
        JOIN evaluation_periods ep ON e.period_id = ep.period_id
        WHERE e.employee_id IN (
            SELECT DISTINCT employee_id FROM growth_evidence_entries
        )
        AND e.evaluation_id NOT IN (
            SELECT DISTINCT evaluation_id 
            FROM evidence_evaluation_results 
            WHERE evidence_count > 0
        )
        ORDER BY e.evaluation_id";

$evaluationsToProcess = fetchAll($sql);

if (empty($evaluationsToProcess)) {
    echo "✅ No evaluations need evidence aggregation.\n";
    echo "All evaluations with evidence entries already have aggregation results.\n";
    exit(0);
}

echo "Found " . count($evaluationsToProcess) . " evaluations that need evidence aggregation:\n\n";

$successCount = 0;
$errorCount = 0;

foreach ($evaluationsToProcess as $evaluation) {
    $evaluationId = $evaluation['evaluation_id'];
    $employeeId = $evaluation['employee_id'];
    $employeeName = $evaluation['first_name'] . ' ' . $evaluation['last_name'];
    
    echo "Processing Evaluation ID $evaluationId - $employeeName...\n";
    
    // Check if this employee has evidence entries
    $evidenceCheckSql = "SELECT dimension, COUNT(*) as count 
                        FROM growth_evidence_entries 
                        WHERE employee_id = ? 
                        GROUP BY dimension";
    $evidenceEntries = fetchAll($evidenceCheckSql, [$employeeId]);
    
    if (empty($evidenceEntries)) {
        echo "  ⚠️  No evidence entries found for $employeeName\n";
        continue;
    }
    
    echo "  📊 Evidence entries: ";
    foreach ($evidenceEntries as $entry) {
        echo "{$entry['dimension']}({$entry['count']}) ";
    }
    echo "\n";
    
    // Prepare period data
    $period = [
        'period_id' => null, // Will be fetched by the method
        'period_name' => $evaluation['period_name'],
        'start_date' => $evaluation['start_date'],
        'end_date' => $evaluation['end_date']
    ];
    
    try {
        $result = $evaluationClass->aggregateEvidence($evaluationId, $employeeId, $period);
        if ($result) {
            echo "  ✅ Evidence aggregation completed successfully!\n";
            $successCount++;
            
            // Show results
            $resultsSql = "SELECT dimension, evidence_count, avg_star_rating, calculated_score 
                          FROM evidence_evaluation_results 
                          WHERE evaluation_id = ? AND evidence_count > 0";
            $results = fetchAll($resultsSql, [$evaluationId]);
            
            if (!empty($results)) {
                echo "     Results: ";
                foreach ($results as $result) {
                    echo "{$result['dimension']}({$result['evidence_count']} entries, score: {$result['calculated_score']}) ";
                }
                echo "\n";
            }
        } else {
            echo "  ❌ Evidence aggregation failed!\n";
            $errorCount++;
        }
    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
        $errorCount++;
    }
    
    echo "\n";
}

echo "=== AGGREGATION COMPLETE ===\n";
echo "✅ Successfully processed: $successCount evaluations\n";
echo "❌ Errors: $errorCount evaluations\n";
echo "📊 Total processed: " . count($evaluationsToProcess) . " evaluations\n\n";

if ($successCount > 0) {
    echo "🎉 Evidence aggregation has been completed for all evaluations!\n";
    echo "You can now view any evaluation and see the evidence-based data.\n";
} else {
    echo "⚠️  No evaluations were successfully processed.\n";
    echo "Please check the error messages above for details.\n";
}