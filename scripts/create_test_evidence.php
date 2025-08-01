<?php
/**
 * Create Test Evidence Entries for Integration Testing
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/GrowthEvidenceJournal.php';
require_once __DIR__ . '/../classes/Employee.php';

echo "=== CREATING TEST EVIDENCE ENTRIES ===\n\n";

try {
    $journalClass = new GrowthEvidenceJournal();
    $employeeClass = new Employee();
    
    // Get some employees and managers for testing
    $employees = fetchAll("SELECT e.employee_id, e.user_id, e.first_name, e.last_name, e.manager_id 
                          FROM employees e 
                          WHERE e.manager_id IS NOT NULL 
                          LIMIT 5");
    
    if (empty($employees)) {
        echo "No employees with managers found!\n";
        exit(1);
    }
    
    $dimensions = ['responsibilities', 'kpis', 'competencies', 'values'];
    $evidenceTemplates = [
        'responsibilities' => [
            ['content' => 'Successfully completed project deliverables ahead of schedule', 'rating' => 5],
            ['content' => 'Took initiative to improve team processes', 'rating' => 4],
            ['content' => 'Consistently meets all assigned responsibilities', 'rating' => 4],
            ['content' => 'Needs improvement in time management for complex tasks', 'rating' => 2],
            ['content' => 'Excellent attention to detail in all work outputs', 'rating' => 5],
        ],
        'kpis' => [
            ['content' => 'Exceeded monthly sales target by 15%', 'rating' => 5],
            ['content' => 'Maintained 98% customer satisfaction score', 'rating' => 5],
            ['content' => 'Reduced bug resolution time by 30%', 'rating' => 4],
            ['content' => 'Met all project deadlines this quarter', 'rating' => 4],
            ['content' => 'Customer retention rate below target', 'rating' => 2],
        ],
        'competencies' => [
            ['content' => 'Demonstrated excellent technical problem-solving skills', 'rating' => 5],
            ['content' => 'Strong communication with stakeholders', 'rating' => 4],
            ['content' => 'Effective leadership during team crisis', 'rating' => 5],
            ['content' => 'Needs development in presentation skills', 'rating' => 2],
            ['content' => 'Good collaboration with cross-functional teams', 'rating' => 4],
        ],
        'values' => [
            ['content' => 'Consistently demonstrates integrity in all interactions', 'rating' => 5],
            ['content' => 'Shows innovation in approaching challenges', 'rating' => 4],
            ['content' => 'Excellent collaboration with team members', 'rating' => 5],
            ['content' => 'Strong customer focus in all decisions', 'rating' => 4],
            ['content' => 'Could improve on embracing change initiatives', 'rating' => 3],
        ]
    ];
    
    $entriesCreated = 0;
    
    foreach ($employees as $employee) {
        echo "Creating evidence for {$employee['first_name']} {$employee['last_name']}...\n";
        
        foreach ($dimensions as $dimension) {
            // Create 3-5 evidence entries per dimension per employee
            $numEntries = rand(3, 5);
            
            for ($i = 0; $i < $numEntries; $i++) {
                $template = $evidenceTemplates[$dimension][array_rand($evidenceTemplates[$dimension])];
                
                // Vary the dates within the last 3 months
                $daysAgo = rand(1, 90);
                $entryDate = date('Y-m-d', strtotime("-$daysAgo days"));
                
                $entryData = [
                    'employee_id' => $employee['employee_id'],
                    'manager_id' => $employee['manager_id'],
                    'content' => $template['content'] . " (Entry #" . ($i + 1) . ")",
                    'star_rating' => $template['rating'],
                    'dimension' => $dimension,
                    'entry_date' => $entryDate
                ];
                
                try {
                    $entryId = $journalClass->createEntry($entryData);
                    if ($entryId) {
                        $entriesCreated++;
                        echo "  ✓ Created {$dimension} evidence entry (Rating: {$template['rating']}/5)\n";
                    }
                } catch (Exception $e) {
                    echo "  ✗ Failed to create evidence entry: " . $e->getMessage() . "\n";
                }
            }
        }
        echo "\n";
    }
    
    echo "=== EVIDENCE CREATION SUMMARY ===\n";
    echo "✓ Total evidence entries created: $entriesCreated\n";
    echo "✓ Employees with evidence: " . count($employees) . "\n";
    echo "✓ Dimensions covered: " . implode(', ', $dimensions) . "\n\n";
    
    // Now test evidence aggregation
    echo "=== TESTING EVIDENCE AGGREGATION ===\n";
    
    // Get an evaluation to test aggregation
    $evaluation = fetchOne("SELECT e.evaluation_id, e.employee_id, ep.start_date, ep.end_date 
                           FROM evaluations e 
                           JOIN evaluation_periods ep ON e.period_id = ep.period_id 
                           WHERE e.employee_id IN (" . implode(',', array_column($employees, 'employee_id')) . ")
                           AND ep.status = 'active'
                           LIMIT 1");
    
    if ($evaluation) {
        require_once __DIR__ . '/../classes/Evaluation.php';
        $evaluationClass = new Evaluation();
        
        echo "Testing evidence aggregation for evaluation ID: {$evaluation['evaluation_id']}\n";
        
        $period = [
            'start_date' => $evaluation['start_date'],
            'end_date' => $evaluation['end_date']
        ];
        
        $success = $evaluationClass->aggregateEvidence(
            $evaluation['evaluation_id'], 
            $evaluation['employee_id'], 
            $period
        );
        
        if ($success) {
            echo "✓ Evidence aggregation completed successfully\n";
            
            // Get the aggregated results
            $results = fetchAll("SELECT * FROM evidence_evaluation_results WHERE evaluation_id = ?", 
                              [$evaluation['evaluation_id']]);
            
            echo "✓ Aggregated results:\n";
            foreach ($results as $result) {
                echo "  - {$result['dimension']}: {$result['evidence_count']} entries, avg rating: {$result['avg_star_rating']}, score: {$result['calculated_score']}\n";
            }
        } else {
            echo "✗ Evidence aggregation failed\n";
        }
    } else {
        echo "No active evaluations found for testing\n";
    }
    
    echo "\n=== TEST EVIDENCE CREATION COMPLETED ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}