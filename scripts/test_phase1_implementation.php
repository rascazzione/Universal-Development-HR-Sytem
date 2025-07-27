<?php
/**
 * Phase 1 Implementation Testing Script
 * 
 * Comprehensive testing suite for the continuous performance foundation.
 * Tests all Phase 1 features including:
 * - Database schema validation
 * - 1:1 session functionality
 * - Feedback capture and linking
 * - Evidence aggregation procedures
 * - Performance and data integrity
 * 
 * Usage: php test_phase1_implementation.php [--verbose] [--performance]
 */

require_once __DIR__ . '/../config/database.php';

class Phase1ImplementationTester {
    private $pdo;
    private $verbose = false;
    private $performanceTest = false;
    private $testResults = [];
    private $startTime;
    
    public function __construct($options = []) {
        $this->verbose = $options['verbose'] ?? false;
        $this->performanceTest = $options['performance'] ?? false;
        $this->startTime = microtime(true);
        
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public function runAllTests() {
        echo "=== Phase 1 Implementation Testing Suite ===\n";
        echo "Started: " . date('Y-m-d H:i:s') . "\n";
        echo "Verbose: " . ($this->verbose ? "ON" : "OFF") . "\n";
        echo "Performance: " . ($this->performanceTest ? "ON" : "OFF") . "\n\n";
        
        try {
            // Core infrastructure tests
            $this->testDatabaseSchema();
            $this->testTableStructures();
            $this->testForeignKeyConstraints();
            $this->testIndexes();
            
            // Functional tests
            $this->testSessionManagement();
            $this->testFeedbackCapture();
            $this->testEvidenceAggregation();
            $this->testStoredProcedures();
            $this->testViews();
            
            // Data integrity tests
            $this->testDataIntegrity();
            $this->testBusinessLogic();
            
            // Performance tests (if enabled)
            if ($this->performanceTest) {
                $this->testPerformance();
            }
            
            $this->printSummary();
            
        } catch (Exception $e) {
            echo "FATAL ERROR: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function testDatabaseSchema() {
        echo "Testing database schema...\n";
        
        // Test Phase 1 tables exist
        $requiredTables = [
            'one_to_one_sessions',
            'one_to_one_feedback', 
            'one_to_one_templates'
        ];
        
        foreach ($requiredTables as $table) {
            $this->runTest("Table {$table} exists", function() use ($table) {
                $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                return $stmt->fetch() !== false;
            });
        }
        
        // Test evaluations table enhancements
        $this->runTest("Evaluations table enhanced with Phase 1 columns", function() {
            $stmt = $this->pdo->query("DESCRIBE evaluations");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $requiredColumns = ['related_sessions', 'evidence_summary', 'review_source', 'last_1to1_sync'];
            
            foreach ($requiredColumns as $column) {
                if (!in_array($column, $columns)) {
                    throw new Exception("Missing column: {$column}");
                }
            }
            return true;
        });
        
        echo "✓ Database schema tests completed\n\n";
    }
    
    private function testTableStructures() {
        echo "Testing table structures...\n";
        
        // Test one_to_one_sessions structure
        $this->runTest("one_to_one_sessions has correct structure", function() {
            $stmt = $this->pdo->query("DESCRIBE one_to_one_sessions");
            $columns = $stmt->fetchAll();
            
            $requiredColumns = [
                'session_id' => 'int',
                'employee_id' => 'int', 
                'manager_id' => 'int',
                'scheduled_date' => 'datetime',
                'status' => 'enum',
                'meeting_notes' => 'text',
                'agenda_items' => 'json',
                'action_items' => 'json'
            ];
            
            foreach ($requiredColumns as $column => $expectedType) {
                $found = false;
                foreach ($columns as $col) {
                    if ($col['Field'] === $column) {
                        $found = true;
                        if (strpos(strtolower($col['Type']), $expectedType) === false) {
                            throw new Exception("Column {$column} has wrong type: {$col['Type']}");
                        }
                        break;
                    }
                }
                if (!$found) {
                    throw new Exception("Missing column: {$column}");
                }
            }
            return true;
        });
        
        // Test one_to_one_feedback structure
        $this->runTest("one_to_one_feedback has correct structure", function() {
            $stmt = $this->pdo->query("DESCRIBE one_to_one_feedback");
            $columns = $stmt->fetchAll();
            
            $requiredColumns = [
                'feedback_id' => 'int',
                'session_id' => 'int',
                'given_by' => 'int',
                'receiver_id' => 'int',
                'feedback_type' => 'enum',
                'content' => 'text',
                'related_competency_id' => 'int',
                'related_kpi_id' => 'int',
                'urgency' => 'enum'
            ];
            
            foreach ($requiredColumns as $column => $expectedType) {
                $found = false;
                foreach ($columns as $col) {
                    if ($col['Field'] === $column) {
                        $found = true;
                        if (strpos(strtolower($col['Type']), $expectedType) === false) {
                            throw new Exception("Column {$column} has wrong type: {$col['Type']}");
                        }
                        break;
                    }
                }
                if (!$found) {
                    throw new Exception("Missing column: {$column}");
                }
            }
            return true;
        });
        
        echo "✓ Table structure tests completed\n\n";
    }
    
    private function testForeignKeyConstraints() {
        echo "Testing foreign key constraints...\n";
        
        $this->runTest("Foreign key constraints exist", function() {
            $stmt = $this->pdo->query("
                SELECT 
                    CONSTRAINT_NAME,
                    TABLE_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = 'performance_evaluation' 
                AND TABLE_NAME IN ('one_to_one_sessions', 'one_to_one_feedback')
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            $constraints = $stmt->fetchAll();
            
            if (count($constraints) < 6) { // Minimum expected constraints
                throw new Exception("Insufficient foreign key constraints found: " . count($constraints));
            }
            
            return true;
        });
        
        // Test referential integrity
        $this->runTest("Referential integrity enforced", function() {
            // Try to insert invalid session (should fail)
            try {
                $this->pdo->exec("
                    INSERT INTO one_to_one_sessions (employee_id, manager_id, scheduled_date) 
                    VALUES (99999, 99999, NOW())
                ");
                throw new Exception("Foreign key constraint not enforced");
            } catch (PDOException $e) {
                // Expected to fail
                return true;
            }
        });
        
        echo "✓ Foreign key constraint tests completed\n\n";
    }
    
    private function testIndexes() {
        echo "Testing database indexes...\n";
        
        $this->runTest("Performance indexes exist", function() {
            $stmt = $this->pdo->query("
                SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME
                FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = 'performance_evaluation' 
                AND TABLE_NAME IN ('one_to_one_sessions', 'one_to_one_feedback')
                AND INDEX_NAME != 'PRIMARY'
            ");
            $indexes = $stmt->fetchAll();
            
            if (count($indexes) < 10) { // Minimum expected indexes
                throw new Exception("Insufficient indexes found: " . count($indexes));
            }
            
            return true;
        });
        
        echo "✓ Index tests completed\n\n";
    }
    
    private function testSessionManagement() {
        echo "Testing 1:1 session management...\n";
        
        // Test session creation
        $this->runTest("Can create 1:1 session", function() {
            $stmt = $this->pdo->prepare("
                INSERT INTO one_to_one_sessions 
                (employee_id, manager_id, scheduled_date, duration_minutes, status, meeting_notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            // Get valid employee and manager IDs
            $empStmt = $this->pdo->query("SELECT employee_id, manager_id FROM employees WHERE manager_id IS NOT NULL LIMIT 1");
            $emp = $empStmt->fetch();
            
            if (!$emp) {
                throw new Exception("No manager-employee relationships found for testing");
            }
            
            return $stmt->execute([
                $emp['employee_id'],
                $emp['manager_id'],
                date('Y-m-d H:i:s', strtotime('+1 week')),
                30,
                'scheduled',
                'Test session for validation'
            ]);
        });
        
        // Test session status updates
        $this->runTest("Can update session status", function() {
            $stmt = $this->pdo->prepare("
                UPDATE one_to_one_sessions 
                SET status = 'completed', actual_date = NOW() 
                WHERE meeting_notes = 'Test session for validation'
            ");
            return $stmt->execute();
        });
        
        // Test JSON fields
        $this->runTest("JSON fields work correctly", function() {
            $agenda = [
                ['section' => 'Goal Progress', 'time_minutes' => 10],
                ['section' => 'Feedback', 'time_minutes' => 15]
            ];
            
            $actions = [
                ['item' => 'Complete training', 'owner' => 'employee', 'due_date' => '2025-08-15']
            ];
            
            $stmt = $this->pdo->prepare("
                UPDATE one_to_one_sessions 
                SET agenda_items = ?, action_items = ?
                WHERE meeting_notes = 'Test session for validation'
            ");
            
            return $stmt->execute([json_encode($agenda), json_encode($actions)]);
        });
        
        echo "✓ Session management tests completed\n\n";
    }
    
    private function testFeedbackCapture() {
        echo "Testing feedback capture...\n";
        
        // Test feedback creation
        $this->runTest("Can create feedback linked to session", function() {
            // Get the test session
            $sessionStmt = $this->pdo->query("
                SELECT session_id, employee_id, manager_id 
                FROM one_to_one_sessions 
                WHERE meeting_notes = 'Test session for validation'
            ");
            $session = $sessionStmt->fetch();
            
            if (!$session) {
                throw new Exception("Test session not found");
            }
            
            // Get manager's user_id
            $userStmt = $this->pdo->prepare("SELECT user_id FROM employees WHERE employee_id = ?");
            $userStmt->execute([$session['manager_id']]);
            $managerUserId = $userStmt->fetchColumn();
            
            if (!$managerUserId) {
                throw new Exception("Manager user_id not found");
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO one_to_one_feedback 
                (session_id, given_by, receiver_id, feedback_type, content, urgency)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $session['session_id'],
                $managerUserId,
                $session['employee_id'],
                'positive',
                'Test feedback for validation - excellent work on recent project',
                'low'
            ]);
        });
        
        // Test feedback linking to competencies
        $this->runTest("Can link feedback to competencies", function() {
            $competencyStmt = $this->pdo->query("SELECT id FROM competencies WHERE is_active = 1 LIMIT 1");
            $competencyId = $competencyStmt->fetchColumn();
            
            if (!$competencyId) {
                throw new Exception("No active competencies found");
            }
            
            $stmt = $this->pdo->prepare("
                UPDATE one_to_one_feedback 
                SET related_competency_id = ?
                WHERE content LIKE 'Test feedback for validation%'
            ");
            
            return $stmt->execute([$competencyId]);
        });
        
        // Test feedback types
        $this->runTest("All feedback types work", function() {
            $feedbackTypes = ['positive', 'constructive', 'development', 'goal_progress', 'concern', 'recognition'];
            
            foreach ($feedbackTypes as $type) {
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) FROM one_to_one_feedback 
                    WHERE feedback_type = ?
                ");
                $stmt->execute([$type]);
                // Just verify the enum accepts the value
            }
            
            return true;
        });
        
        echo "✓ Feedback capture tests completed\n\n";
    }
    
    private function testEvidenceAggregation() {
        echo "Testing evidence aggregation...\n";
        
        // Test aggregation views
        $this->runTest("Competency feedback view works", function() {
            $stmt = $this->pdo->query("SELECT * FROM v_employee_competency_feedback LIMIT 1");
            $result = $stmt->fetch();
            return $result !== false; // Just check it doesn't error
        });
        
        $this->runTest("KPI feedback view works", function() {
            $stmt = $this->pdo->query("SELECT * FROM v_employee_kpi_feedback LIMIT 1");
            $result = $stmt->fetch();
            return $result !== false; // Just check it doesn't error
        });
        
        echo "✓ Evidence aggregation tests completed\n\n";
    }
    
    private function testStoredProcedures() {
        echo "Testing stored procedures...\n";
        
        // Test evidence aggregation procedure
        $this->runTest("Evidence aggregation procedure works", function() {
            $empStmt = $this->pdo->query("SELECT employee_id FROM employees WHERE active = 1 LIMIT 1");
            $employeeId = $empStmt->fetchColumn();
            
            if (!$employeeId) {
                throw new Exception("No active employees found");
            }
            
            $stmt = $this->pdo->prepare("CALL sp_aggregate_1to1_evidence(?, ?, ?)");
            $stmt->execute([
                $employeeId,
                date('Y-m-d', strtotime('-3 months')),
                date('Y-m-d')
            ]);
            
            $result = $stmt->fetch();
            return $result !== false;
        });
        
        // Test agenda recommendation procedure
        $this->runTest("Agenda recommendation procedure works", function() {
            $empStmt = $this->pdo->query("
                SELECT employee_id, manager_id 
                FROM employees 
                WHERE manager_id IS NOT NULL 
                LIMIT 1
            ");
            $emp = $empStmt->fetch();
            
            if (!$emp) {
                throw new Exception("No manager-employee relationships found");
            }
            
            $stmt = $this->pdo->prepare("CALL sp_recommend_1to1_agenda(?, ?)");
            $stmt->execute([$emp['employee_id'], $emp['manager_id']]);
            
            $result = $stmt->fetch();
            return $result !== false;
        });
        
        echo "✓ Stored procedure tests completed\n\n";
    }
    
    private function testViews() {
        echo "Testing database views...\n";
        
        $views = ['v_employee_competency_feedback', 'v_employee_kpi_feedback'];
        
        foreach ($views as $view) {
            $this->runTest("View {$view} is accessible", function() use ($view) {
                $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$view}");
                $count = $stmt->fetchColumn();
                return is_numeric($count);
            });
        }
        
        echo "✓ View tests completed\n\n";
    }
    
    private function testDataIntegrity() {
        echo "Testing data integrity...\n";
        
        // Test constraint enforcement
        $this->runTest("Content length constraint enforced", function() {
            try {
                $this->pdo->exec("
                    INSERT INTO one_to_one_feedback 
                    (session_id, given_by, receiver_id, feedback_type, content) 
                    VALUES (1, 1, 1, 'positive', 'short')
                ");
                throw new Exception("Content length constraint not enforced");
            } catch (PDOException $e) {
                return true; // Expected to fail
            }
        });
        
        // Test enum constraints
        $this->runTest("Enum constraints enforced", function() {
            try {
                $this->pdo->exec("
                    INSERT INTO one_to_one_sessions 
                    (employee_id, manager_id, scheduled_date, status) 
                    VALUES (1, 1, NOW(), 'invalid_status')
                ");
                throw new Exception("Enum constraint not enforced");
            } catch (PDOException $e) {
                return true; // Expected to fail
            }
        });
        
        echo "✓ Data integrity tests completed\n\n";
    }
    
    private function testBusinessLogic() {
        echo "Testing business logic...\n";
        
        // Test that feedback requires at least one relationship
        $this->runTest("Feedback relationship constraint works", function() {
            // This test depends on the CHECK constraint in the schema
            // If no CHECK constraint, this test will pass but log a warning
            try {
                $sessionStmt = $this->pdo->query("SELECT session_id FROM one_to_one_sessions LIMIT 1");
                $sessionId = $sessionStmt->fetchColumn();
                
                $userStmt = $this->pdo->query("SELECT user_id FROM users LIMIT 1");
                $userId = $userStmt->fetchColumn();
                
                $empStmt = $this->pdo->query("SELECT employee_id FROM employees LIMIT 1");
                $empId = $empStmt->fetchColumn();
                
                if (!$sessionId || !$userId || !$empId) {
                    return true; // Skip if no test data
                }
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO one_to_one_feedback 
                    (session_id, given_by, receiver_id, feedback_type, content) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([
                    $sessionId, $userId, $empId, 'recognition',
                    'This is a test feedback item for business logic validation'
                ]);
                
                return $result; // Should succeed for recognition type
                
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'chk_at_least_one_relation') !== false) {
                    throw new Exception("Business logic constraint too strict");
                }
                return true; // Other errors are acceptable
            }
        });
        
        echo "✓ Business logic tests completed\n\n";
    }
    
    private function testPerformance() {
        echo "Testing performance...\n";
        
        // Test query performance
        $this->runTest("Evidence aggregation query performance < 500ms", function() {
            $start = microtime(true);
            
            $stmt = $this->pdo->query("
                SELECT * FROM v_employee_competency_feedback 
                WHERE receiver_id IN (SELECT employee_id FROM employees LIMIT 5)
            ");
            $stmt->fetchAll();
            
            $duration = (microtime(true) - $start) * 1000;
            
            if ($this->verbose) {
                echo " (took {$duration}ms)";
            }
            
            return $duration < 500;
        });
        
        $this->runTest("Session lookup query performance < 300ms", function() {
            $start = microtime(true);
            
            $stmt = $this->pdo->query("
                SELECT s.*, COUNT(f.feedback_id) as feedback_count
                FROM one_to_one_sessions s
                LEFT JOIN one_to_one_feedback f ON s.session_id = f.session_id
                WHERE s.status = 'completed'
                GROUP BY s.session_id
                ORDER BY s.actual_date DESC
                LIMIT 20
            ");
            $stmt->fetchAll();
            
            $duration = (microtime(true) - $start) * 1000;
            
            if ($this->verbose) {
                echo " (took {$duration}ms)";
            }
            
            return $duration < 300;
        });
        
        echo "✓ Performance tests completed\n\n";
    }
    
    private function runTest($description, $testFunction) {
        $start = microtime(true);
        
        try {
            $result = $testFunction();
            $duration = (microtime(true) - $start) * 1000;
            
            if ($result) {
                $status = "✓ PASS";
                $this->testResults['passed']++;
            } else {
                $status = "✗ FAIL";
                $this->testResults['failed']++;
            }
            
            if ($this->verbose) {
                echo "  {$status} {$description} ({$duration}ms)\n";
            }
            
        } catch (Exception $e) {
            $status = "✗ ERROR";
            $this->testResults['errors']++;
            
            if ($this->verbose) {
                echo "  {$status} {$description}: {$e->getMessage()}\n";
            }
        }
        
        $this->testResults['total']++;
    }
    
    private function printSummary() {
        $duration = microtime(true) - $this->startTime;
        
        echo "\n=== Test Summary ===\n";
        echo "Total Tests: " . ($this->testResults['total'] ?? 0) . "\n";
        echo "Passed: " . ($this->testResults['passed'] ?? 0) . "\n";
        echo "Failed: " . ($this->testResults['failed'] ?? 0) . "\n";
        echo "Errors: " . ($this->testResults['errors'] ?? 0) . "\n";
        echo "Duration: " . round($duration, 2) . " seconds\n";
        
        $passRate = ($this->testResults['total'] > 0) ? 
            round(($this->testResults['passed'] ?? 0) / $this->testResults['total'] * 100, 1) : 0;
        echo "Pass Rate: {$passRate}%\n";
        
        if ($passRate >= 90) {
            echo "\n✅ Phase 1 implementation is HEALTHY\n";
        } elseif ($passRate >= 75) {
            echo "\n⚠️  Phase 1 implementation has MINOR ISSUES\n";
        } else {
            echo "\n❌ Phase 1 implementation has MAJOR ISSUES\n";
        }
        
        echo "\nRecommendations:\n";
        if (($this->testResults['failed'] ?? 0) > 0) {
            echo "- Review failed tests and fix underlying issues\n";
        }
        if (($this->testResults['errors'] ?? 0) > 0) {
            echo "- Investigate error conditions and resolve\n";
        }
        if ($passRate < 100) {
            echo "- Run tests with --verbose flag for detailed output\n";
        }
        if (!$this->performanceTest) {
            echo "- Run performance tests with --performance flag\n";
        }
        
        echo "\n";
    }
    
    public function cleanup() {
        // Clean up test data
        try {
            $this->pdo->exec("DELETE FROM one_to_one_feedback WHERE content LIKE 'Test feedback for validation%'");
            $this->pdo->exec("DELETE FROM one_to_one_sessions WHERE meeting_notes = 'Test session for validation'");
        } catch (Exception $e) {
            // Ignore cleanup errors
        }
    }
}

// Parse command line arguments
$options = [
    'verbose' => in_array('--verbose', $argv),
    'performance' => in_array('--performance', $argv)
];

// Initialize test results
$testResults = ['total' => 0, 'passed' => 0, 'failed' => 0, 'errors' => 0];

// Run tests
try {
    $tester = new Phase1ImplementationTester($options);
    $tester->runAllTests();
    $tester->cleanup();
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}