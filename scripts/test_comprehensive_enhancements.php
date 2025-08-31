<?php
<?php
/**
 * Comprehensive Database Integration Test
 * Tests all comprehensive performance enhancement features
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/SelfAssessmentManager.php';
require_once __DIR__ . '/../classes/AchievementJournal.php';
require_once __DIR__ . '/../classes/KudosManager.php';
require_once __DIR__ . '/../classes/UpwardFeedbackManager.php';
require_once __DIR__ . '/../classes/OKRManager.php';
require_once __DIR__ . '/../classes/IDRManager.php';

class ComprehensiveEnhancementsTest {
    private $db;
    private $testResults = [];
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
        $this->runAllTests();
    }
    
    public function runAllTests() {
        echo "\n=== Comprehensive Performance Enhancement Tests ===\n\n";
        
        $this->testDatabaseSchema();
        $this->testDataIntegrity();
        $this->testForeignKeys();
        $this->testSampleDataFlows();
        $this->testManagerRelationships();
        $this->testAnonymousFeatures();
        $this->generateReport();
    }
    
    private function testDatabaseSchema() {
        echo "\n1. Testing Database Schema...\n";
        
        $tables = [
            'enhanced_okrs' => [
                'columns' => ['id', 'employee_id', 'objective', 'key_results', 'target_date', 'progress', 'weight', 'status', 'created_at', 'updated_at']
            ],
            'enhanced_self_assessments' => [
                'columns' => ['id', 'employee_id', 'period_id', 'achievements', 'challenges', 'improvement_areas', 'manager_feedback', 'overall_rating', 'growth_rating', 'development_plans', 'submitted', 'created_at', 'updated_at']
            ],
            'enhanced_achievements' => [
                'columns' => ['id', 'employee_id', 'title', 'description', 'impact_metrics', 'skills', 'date_achieved', 'evidence_type', 'evidence_path', 'created_at', 'updated_at']
            ],
            'enhanced_idps' => [
                'columns' => ['id', 'employee_id', 'manager_id', 'title', 'short_term_goals', 'long_term_goals', 'required_skills', 'development_activities', 'resources', 'timeline', 'progress_tracking', 'status', 'created_at', 'updated_at', 'approved_date']
            ],
            'kudos_points' => [
                'columns' => ['id', 'from_employee', 'to_employee', 'type', 'points', 'reason', 'created_at', 'is_anonymous', 'encrypted_identity', 'anonymity_code']
            ],
            'upward_feedback' => [
                'columns' => ['id', 'employee_id', 'manager_id', 'feedback_type', 'relationship_rate', 'clarity_rate', 'fairness_rate', 'support_rate', 'effectiveness_rate', 'comments', 'submitted_at', 'submitted_by', 'quarter', 'year', 'is_anonymous', 'anonymity_code']
            ]
        ];
        
        foreach ($tables as $tableName => $expected) {
            try {
                $stmt = $this->db->prepare("DESCRIBE {$tableName}");
                $stmt->execute();
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                $missing = array_diff($expected['columns'], $columns);
                
                if (empty($missing)) {
                    $this->recordSuccess("Database", "Table {$tableName} structure validated");
                } else {
                    $this->recordFailure("Database", "Table {$tableName} missing columns: " . implode(', ', $missing));
                }
            } catch (PDOException $e) {
                $this->recordFailure("Database", "Table {$tableName} doesn't exist: " . $e->getMessage());
            }
        }
    }
    
    private function testDataIntegrity() {
        echo "\n2. Testing Data Integrity...\n";
        
        // Test constraints and triggers
        try {
            $stmt = $this->db->prepare("
                INSERT INTO enhanced_self_assessments (employee_id, period_id, achievements, challenges) 
                VALUES (99999, 1, 'test', 'test')
            ");
            $stmt->execute();
            $this->recordFailure("Integrity", "Foreign key constraint failed to catch invalid employee_id");
        } catch (PDOException $e) {
            $this->recordSuccess("Integrity", "Foreign key constraints working properly");
        }
    }
    
    private function testForeignKeys() {
        echo "\n3. Testing Foreign Key Relationships...\n";
        
        // Create test employee
        $employeeStmt = $this->db->prepare("
            INSERT INTO employees (first_name, last_name, email, department_id) 
            VALUES ('Test', 'Employee', 'test@company.com', 1)
        ");
        $employeeStmt->execute();
        $employeeId = $this->db->lastInsertId();
        
        // Create test period
        $periodStmt = $this->db->prepare("
            INSERT INTO evaluation_periods (name, start_date, end_date, status) 
            VALUES ('Test Period', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active')
        ");
        $periodStmt->execute();
        $periodId = $this->db->lastInsertId();
        
        // Test all foreign key relationships
        $tests = [
            'enhanced_self_assessments' => [
                'data' => [
                    'employee_id' => $employeeId,
                    'period_id' => $periodId,
                    'achievements' => 'Test achievement',
                    'challenges' => 'Test challenges'
                ]
            ],
            'enhanced_okrs' => [
                'data' => [
                    'employee_id' => $employeeId,
                    'objective' => 'Test objective',
                    'key_results' => json_encode(['result1', 'result2']),
                    'target_date' => date('Y-m-d', strtotime('+30 days')),
                    'progress' => 0
                ]
            ]
        ];
        
        foreach ($tests as $table => $testData) {
            try {
                $columns = array_keys($testData['data']);
                $values = array_values($testData['data']);
                $placeholders = array_fill(0, count($columns), '?');
                
                $stmt = $this->db->prepare("
                    INSERT INTO {$table} (" . implode(', ', $columns) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")
                ");
                $stmt->execute($values);
                
                $this->recordSuccess("ForeignKeys", "Relationships validated for {$table}");
                
                // Clean up
                $this->db->prepare("DELETE FROM {$table} WHERE employee_id = ?")->execute([$employeeId]);
            } catch (PDOException $e) {
                $this->recordFailure("ForeignKeys", "Relationship test failed for {$table}: " . $e->getMessage());
            }
        }
        
        // Clean up test data
        $this->db->prepare("DELETE FROM employees WHERE id = ?")->execute([$employeeId]);
        $this->db->prepare("DELETE FROM evaluation_periods WHERE id = ?")->execute([$periodId]);
    }
    
    private function testSampleDataFlows() {
        echo "\n4. Testing Sample Data Flows...\n";
        
        // Create test employee and manager
        $employeeStmt = $this->db->prepare("
            INSERT INTO employees (first_name, last_name, email, department_id) 
            VALUES ('Sample', 'Employee', 'sample@company.com', 1)
        ");
        $employeeStmt->execute();
        $employeeId = $this->db->lastInsertId();
        
        $managerStmt = $this->db->prepare("
            INSERT INTO employees (first_name, last_name, email, department_id) 
            VALUES ('Sample', 'Manager', 'manager@company.com', 1)
        ");
        $managerStmt->execute();
        $managerId = $this->db->lastInsertId();
        
        $periodStmt = $this->db->prepare("
            INSERT INTO evaluation_periods (name, start_date, end_date, status) 
            VALUES ('Flow Test Period', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active')
        ");
        $periodStmt->execute();
        $periodId = $this->db->lastInsertId();
        
        // Test complete data flow
        try {
            // 1. Create self-assessment
            $selfAssessment = new SelfAssessmentManager();
            $assessmentId = $selfAssessment->createAssessment($employeeId, $periodId, [
                'achievements' => 'Completed major project',
                'challenges' => 'Remote collaboration',
                'improvement_areas' => 'Technical skills',
                'overall_rating' => 4,
                'growth_rating' => 5
            ]);
            
            // 2. Log achievement
            $achievements = new AchievementJournal();
            $achievementId = $achievements->create($employeeId, [
                'title' => 'Project Success',
                'description' => 'Led successful deployment',
                'impact_metrics' => json_encode(['user_satisfaction' => 95]),
                'date_achieved' => date('Y-m-d')
            ]);
            
            // 3. Give kudos
            $kudos = new KudosManager();
            $kudosId = $kudos->giveKudos($managerId, $employeeId, 'performance', 'Great work on the project');
            
            // 4. Create upward feedback
            $upward = new UpwardFeedbackManager();
            $feedbackId = $upward->create($employeeId, $managerId, [
                'relationship_rate' => 5,
                'clarity_rate' => 4,
                'fairness_rate' => 5,
                'support_rate' => 5,
                'effectiveness_rate' => 4,
                'comments' => 'Great leadership and support',
                'quarter' => date('n'),
                'year' => date('Y')
            ]);
            
            $this->recordSuccess("DataFlow", "Complete data flow tested successfully");
            
        } catch (Exception $e) {
            $this->recordFailure("DataFlow", "Data flow test failed: " . $e->getMessage());
        } finally {
            // Clean up all test data
            $cleanupQueries = [
                "DELETE FROM enhanced_self_assessments WHERE employee_id = ?",
                "DELETE FROM enhanced_achievements WHERE employee_id = ?",
                "DELETE FROM kudos_points WHERE from_employee = ? OR to_employee = ?",
                "DELETE FROM upward_feedback WHERE employee_id = ? OR manager_id = ?",
                "DELETE FROM employees WHERE id IN (?, ?)",
                "DELETE FROM evaluation_periods WHERE id = ?"
            ];
            
            foreach ($cleanupQueries as $query) {
                if (strpos($query, 'IN') !== false) {
                    $this->db->prepare($query)->execute([$employeeId, $managerId]);
                } elseif (strpos($query, 'evaluation_periods') !== false) {
                    $this->db->prepare($query)->execute([$periodId]);
                } else {
                    $this->db->prepare($query)->execute([$employeeId, $employeeId, $managerId]);
                }
            }
        }
    }
    
    private function testManagerRelationships() {
        echo "\n5. Testing Manager-Employee Relationships...\n";
        
        // Test reporting hierarchy
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM employees e1
            JOIN employees e2 ON e1.manager_id = e2.id
            WHERE e1.department_id = e2.department_id
        ");
        $stmt->execute();
        $hierarchyCount = $stmt->fetchColumn();
        
        if ($hierarchyCount > 0) {
            $this->recordSuccess("ManagerRelations", "Manager relationships properly validated");
        } else {
            $this->recordWarning("ManagerRelations", "No manager relationships found - check test data");
        }
    }
    
    private function testAnonymousFeatures() {
        echo "\n6. Testing Anonymous Features...\n";
        
        // Create test users
        $employeeStmt = $this->db->prepare("
            INSERT INTO employees (first_name, last_name, email, department_id) 
            VALUES ('Anonymous', 'Employee', 'anon@company.com', 1)
        ");
        $employeeStmt->execute();
        $employeeId = $this->db->lastInsertId();
        
        $managerStmt = $this->db->prepare("
            INSERT INTO employees (first_name, last_name, email, department_id) 
            VALUES ('Anonymous', 'Manager', 'anonmanager@company.com', 1)
        ");
        $managerStmt->execute();
        $managerId = $this->db->lastInsertId();
        
        try {
            // Test anonymous kudos
            $kudos = new KudosManager();
            $kudosId = $kudos->giveKudos($employeeId, $managerId, 'recognition', 'Anonymous recognition', true);
            
            // Test anonymous upward feedback
            $upward = new UpwardFeedbackManager();
            $feedbackId = $upward->create($employeeId, $managerId, [
                'relationship_rate' => 5,
                'fairness_rate' => 4,
                'comments' => 'Anonymous feedback comments',
                'quarter' => date('n'),
                'year' => date('Y'),
                'is_anonymous' => true
            ]);
            
            // Verify anonymity
            $stmt = $this->db->prepare("
                SELECT is_anonymous, anonymity_code FROM kudos_points WHERE id = ?
            ");
            $stmt->execute([$kudosId]);
            $kudosResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($kudosResult && $kudosResult['is_anonymous'] == 1 && !empty($kudosResult['anonymity_code'])) {
                $this->recordSuccess("Anonymous", "Anonymous features working correctly");
            } else {
                $this->recordFailure("Anonymous", "Anonymous features not working");
            }
            
        } catch (Exception $e) {
            $this->recordFailure("Anonymous", "Anonymous test failed: " . $e->getMessage());
        } finally {
            // Cleanup
            $this->db->prepare("DELETE FROM employees WHERE id IN (?, ?)")->execute([$employeeId, $managerId]);
        }
    }
    
    private function recordSuccess($category, $message) {
        $this->testResults[] = [
            'category' => $category,
            'status' => 'PASS',
            'message' => $message
        ];
        echo "✓ {$category}: {$message}\n";
    }
    
    private function recordFailure($category, $message) {
        $this->testResults[] = [
            'category' => $category,
            'status' => 'FAIL',
            'message' => $message
        ];
        echo "✗ {$category}: {$message}\n";
    }
    
    private function recordWarning($category, $message) {
        $this->testResults[] = [
            'category' => $category,
            'status' => 'WARN',
            'message' => $message
        ];
        echo "⚠ {$category}: {$message}\n";
    }
    
    private function generateReport() {
        echo "\n=== Test Summary ===\n";
        
        $passes = count(array_filter($this->testResults, function($r) { return $r['status'] === 'PASS'; }));
        $failures = count(array_filter($this->testResults, function($r) { return $r['status'] === 'FAIL'; }));
        $warnings = count(array_filter($this->testResults, function($r) { return $r['status'] === 'WARN'; }));
        
        echo "Passed: {$passes}\n";
        echo "Failed: {$failures}\n";
        echo "Warnings: {$warnings}\n";
        
        if ($failures > 0) {
            echo "\nFailed tests:\n";
            foreach ($this->testResults as $result) {
                if ($result['status'] === 'FAIL') {
                    echo "- {$result['category']}: {$result['message']}\n";
                }
            }
        }
        
        // Generate detailed report file
        $reportContent = [
            'timestamp' => date('Y-m-d H:i:s'),
            'results' => $this->testResults,
            'summary' => [
                'total' => count($this->testResults),
                'passed' => $passes,
                'failed' => $failures,
                'warnings' => $warnings
            ]
        ];
        
        file_put_contents(__DIR__ . '/comprehensive_test_report.json', json_encode($reportContent, JSON_PRETTY_PRINT));
        
        exit($failures === 0 ? 0 : 1);
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    new ComprehensiveEnhancementsTest();
}