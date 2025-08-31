<?php
<?php
/**
 * Enhanced API Integration Tests
 * Tests all comprehensive enhanced API endpoints with real data flows
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class EnhancedAPITester {
    private $baseUrl;
    private $db;
    private $testResults = [];
    private static $testData = [];
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
        $this->baseUrl = getenv('API_BASE_URL') ?: 'http://localhost';
        
        // Set up test data
        $this->setupTestData();
        
        // Run all tests
        $this->runAllTests();
    }
    
    private function runAllTests() {
        echo "\n=== Enhanced API Integration Tests ===\n\n";
        
        $this->testSelfAssessmentAPIs();
        $this->testAchievementsAPIs();
        $this->testKudosAPIs();
        $this->testUpwardFeedbackAPIs();
        $this->testOKRAPIs();
        $this->testIDRAPIs();
        $this->testWorkflowIntegration();
        $this->testSecurityFeatures();
        $this->testErrorHandling();
        $this->generateReport();
    }
    
    private function setupTestData() {
        // Create test employees
        $stmt = $this->db->prepare("
            INSERT INTO employees (first_name, last_name, email, department_id, manager_id) 
            VALUES ('API', 'TestEmployee', 'api.test@company.com', 1, NULL)
        ");
        $stmt->execute();
        self::$testData['employee_id'] = $this->db->lastInsertId();
        
        $stmt = $this->db->prepare("
            INSERT INTO employees (first_name, last_name, email, department_id, manager_id) 
            VALUES ('API', 'TestManager', 'api.manager@company.com', 1, NULL)
        ");
        $stmt->execute();
        self::$testData['manager_id'] = $this->db->lastInsertId();
        
        // Update employee to have manager
        $stmt = $this->db->prepare("UPDATE employees SET manager_id = ? WHERE id = ?");
        $stmt->execute([self::$testData['manager_id'], self::$testData['employee_id']]);
        
        // Create test period
        $stmt = $this->db->prepare("
            INSERT INTO evaluation_periods (name, start_date, end_date, status) 
            VALUES ('API Test Period', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 'active')
        ");
        $stmt->execute();
        self::$testData['period_id'] = $this->db->lastInsertId();
        
        // Create test session
        $_SESSION['user_id'] = self::$testData['employee_id'];
        $_SESSION['role'] = 'employee';
    }
    
    private function testSelfAssessmentAPIs() {
        echo "\n1. Testing Self Assessment APIs...\n";
        
        try {
            // Test create self-assessment
            $createData = [
                'period_id' => self::$testData['period_id'],
                'achievements' => 'Test API achievements',
                'challenges' => 'Test API challenges',
                'improvement_areas' => 'Test API improvement areas',
                'overall_rating' => '4',
                'growth_rating' => '5'
            ];
            
            $createResponse = $this->makeAPICall('/public/api/self-assessment/create.php', 'POST', $createData);
            if ($createResponse['success']) {
                $assessmentId = $createResponse['data']['id'];
                $this->recordSuccess('SelfAssessment', 'API - Creation successful');
                
                // Test update
                $updateResponse = $this->makeAPICall('/public/api/self-assessment/update.php', 'POST', [
                    'id' => $assessmentId,
                    'achievements' => 'Updated API achievements'
                ]);
                
                if ($updateResponse['success']) {
                    $this->recordSuccess('SelfAssessment', 'API - Update successful');
                } else {
                    $this->recordFailure('SelfAssessment', 'API - Update failed: ' . $updateResponse['error']);
                }
                
                // Test retrieval
                $getResponse = $this->makeAPICall('/public/api/self-assessment/get.php', 'GET', [
                    'employee_id' => self::$testData['employee_id'],
                    'period_id' => self::$testData['period_id']
                ]);
                
                if ($getResponse['success']) {
                    $this->recordSuccess('SelfAssessment', 'API - Retrieval successful');
                }
                
                // Test submission
                $submitResponse = $this->makeAPICall('/public/api/self-assessment/submit.php', 'POST', [
                    'id' => $assessmentId
                ]);
                
                if ($submitResponse['success']) {
                    $this->recordSuccess('SelfAssessment', 'API - Submission successful');
                }
                
            } else {
                $this->recordFailure('SelfAssessment', 'API - Creation failed: ' . $createResponse['error']);
            }
            
        } catch (Exception $e) {
            $this->recordFailure('SelfAssessment', 'API - Exception: ' . $e->getMessage());
        }
    }
    
    private function testAchievementsAPIs() {
        echo "\n2. Testing Achievements APIs...\n";
        
        try {
            $achievementData = [
                'title' => 'API Achievement Test',
                'description' => 'Testing API creation of achievement',
                'impact_metrics' => json_encode(['user_satisfaction' => 95, 'cost_savings' => 5000]),
                'skills' => '["PHP", "MySQL", "API Development"]',
                'date_achieved' => date('Y-m-d'),
                'evidence_type' => 'file'
            ];
            
            $createResponse = $this->makeAPICall('/public/api/achievements/create.php', 'POST', $achievementData);
            
            if ($createResponse['success']) {
                $achievementId = $createResponse['data']['id'];
                $this->recordSuccess('Achievements', 'API - Creation successful');
                
                // Test update
                $updateResponse = $this->makeAPICall('/public/api/achievements/update.php', 'POST', [
                    'id' => $achievementId,
                    'title' => 'Updated API Achievement Test'
                ]);
                
                if ($updateResponse['success']) {
                    $this->recordSuccess('Achievements', 'API - Update successful');
                }
                
                // Test retrieval
                $getResponse = $this->makeAPICall('/public/api/achievements/get.php', 'GET', [
                    'employee_id' => self::$testData['employee_id']
                ]);
                
                if ($getResponse['success']) {
                    $this->recordSuccess('Achievements', 'API - Retrieval successful');
                }
                
                // Test listing
                $listResponse = $this->makeAPICall('/public/api/achievements/list.php', 'GET', [
                    'employee_id' => self::$testData['employee_id']
                ]);
                
                if ($listResponse['success']) {
                    $this->recordSuccess('Achievements', 'API - Listing successful');
                }
                
                // Test deletion
                $deleteResponse = $this->makeAPICall('/public/api/achievements/delete.php', 'POST', [
                    'id' => $achievementId
                ]);
                
                if ($deleteResponse['success']) {
                    $this->recordSuccess('Achievements', 'API - Deletion successful');
                }
                
            } else {
                $this->recordFailure('Achievements', 'API - Creation failed: ' . $createResponse['error']);
            }
            
        } catch (Exception $e) {
            $this->recordFailure('Achievements', 'API - Exception: ' . $e->getMessage());
        }
    }
    
    private function testKudosAPIs() {
        echo "\n3. Testing Kudos APIs...\n";
        
        try {
            // Test creating kudos
            $kudosData = [
                'to_employee' => self::$testData['employee_id'],
                'type' => 'performance',
                'reason' => 'API test recognition',
                'points' => 10
            ];
            
            $createResponse = $this->makeAPICall('/public/api/kudos/create.php', 'POST', $kudosData);
            
            if ($createResponse['success']) {
                $kudosId = $createResponse['data']['id'];
                $this->recordSuccess('Kudos', 'API - Creation successful');
                
                // Test giving kudos (from manager to employee)
                $giveResponse = $this->makeAPICall('/public/api/kudos/give.php', 'POST', [
                    'from_employee' => self::$testData['manager_id'],
                    'to_employee' => self::$testData['employee_id'],
                    'type' => 'leadership',
                    'reason' => 'Excellent leadership demonstrated',
                    'points' => 20
                ]);
                
                if ($giveResponse['success']) {
                    $this->recordSuccess('Kudos', 'API - Giving successful');
                }
                
                // Test reaction
                $reactResponse = $this->makeAPICall('/public/api/kudos/react.php', 'POST', [
                    'kudos_id' => $kudosId,
                    'reaction' => 'clap'
                ]);
                
                if ($reactResponse['success']) {
                    $this->recordSuccess('Kudos', 'API - Reaction successful');
                }
                
                // Test feed
                $feedResponse = $this->makeAPICall('/public/api/kudos/feed.php', 'GET');
                
                if ($feedResponse['success']) {
                    $this->recordSuccess('Kudos', 'API - Feed successful');
                }
                
                // Test points calculation
                $pointsResponse = $this->makeAPICall('/public/api/kudos/points.php', 'GET', [
                    'employee_id' => self::$testData['employee_id']
                ]);
                
                if ($pointsResponse['success']) {
                    $this->recordSuccess('Kudos', 'API - Points calculation successful');
                }
                
            } else {
                $this->recordFailure('Kudos', 'API - Creation failed: ' . $createResponse['error']);
            }
            
        } catch (Exception $e) {
            $this->recordFailure('Kudos', 'API - Exception: ' . $e->getMessage());
        }
    }
    
    private function testUpwardFeedbackAPIs() {
        echo "\n4. Testing Upward Feedback APIs...\n";
        
        try {
            // Test anonymous feedback
            $feedbackData = [
                'manager_id' => self::$testData['manager_id'],
                'feedback_type' => 'regular_review',
                'relationship_rate' => 5,
                'clarity_rate' => 4,
                'fairness_rate' => 5,
                'support_rate' => 5,
                'effectiveness_rate' => 4,
                'comments' => 'Excellent management support',
                'quarter' => date('n'),
                'year' => date('Y'),
                'is_anonymous' => true
            ];
            
            $initiateResponse = $this->makeAPICall('/public/api/upward-feedback/initiate.php', 'POST', [
                'manager_id' => self::$testData['manager_id']
            ]);
            
            if ($initiateResponse['success']) {
                $this->recordSuccess('UpwardFeedback', 'API - Initiation successful');
                
                // Test submission
                $submitResponse = $this->makeAPICall('/public/api/upward-feedback/submit.php', 'POST', $feedbackData);
                
                if ($submitResponse['success']) {
                    $this->recordSuccess('UpwardFeedback', 'API - Submission successful');
                }
                
                // Test summary
                $summaryResponse = $this->makeAPICall('/public/api/upward-feedback/summary.php', 'GET', [
                    'manager_id' => self::$testData['manager_id']
                ]);
                
                if ($summaryResponse['success']) {
                    $this->recordSuccess('UpwardFeedback', 'API - Summary successful');
                }
                
                // Test anonymous form generation
                $anonFormResponse = $this->makeAPICall('/public/api/upward-feedback/anonymous-form.php', 'GET', [
                    'manager_id' => self::$testData['manager_id']
                ]);
                
                if ($anonFormResponse['success']) {
                    $this->recordSuccess('UpwardFeedback', 'API - Anonymous form generation successful');
                }
                
            } else {
                $this->recordFailure('UpwardFeedback', 'API - Initiation failed: ' . $initiateResponse['error']);
            }
            
        } catch (Exception $e) {
            $this->recordFailure('UpwardFeedback', 'API - Exception: ' . $e->getMessage());
        }
    }
    
    private function testOKRAPIs() {
        echo "\n5. Testing OKR APIs...\n";
        
        try {
            $okrData = [
                'objective' => 'Test API Objective',
                'key_results' => json_encode(['KR1', 'KR2', 'KR3']),
                'target_date' => date('Y-m-d', strtotime('+30 days')),
                'weight' => 10
            ];
            
            $createResponse = $this->makeAPICall('/public/api/okr/create.php', 'POST', $okrData);
            
            if ($createResponse['success']) {
                $okrId = $createResponse['data']['id'];
                $this->recordSuccess('OKR', 'API - Creation successful');
                
                // Test listing
                $listResponse = $this->makeAPICall('/public/api/okr/list.php', 'GET', [
                    'employee_id' => self::$testData['employee_id']
                ]);
                
                if ($listResponse['success']) {
                    $this->recordSuccess('OKR', 'API - Listing successful');
                }
                
                // Test progress update
                $progressResponse = $this->makeAPICall('/public/api/okr/progress.php', 'POST', [
                    'id' => $okrId,
                    'progress' => 50
                ]);
                
                if ($progressResponse['success']) {
                    $this->recordSuccess('OKR', 'API - Progress update successful');
                }
                
            } else {
                $this->recordFailure('OKR', 'API - Creation failed: ' . $createResponse['error']);
            }
            
        } catch (Exception $e) {
            $this->recordFailure('OKR', 'API - Exception: ' . $e->getMessage());
        }
    }
    
    private function testIDRAPIs() {
        echo "\n6. Testing IDP APIs...\n";
        
        try {
            $idpData = [
                'manager_id' => self::$testData['manager_id'],
                'title' => 'Test API IDP',
                'short_term_goals' => json_encode(['Goal 1', 'Goal 2']),
                'long_term_goals' => json_encode(['Goal 3', 'Goal 4']),
                'required_skills' => '["PHP", "JavaScript", "Leadership"]',
                'development_activities' => json_encode(['Training', 'Mentoring']),
                'timeline' => '6 months'
            ];
            
            $createResponse = $this->makeAPICall('/public/api/idp/create.php', 'POST', $idpData);
            
            if ($createResponse['success']) {
                $idpId = $createResponse['data']['id'];
                $this->recordSuccess('IDP', 'API - Creation successful');
                
                // Test listing
                $listResponse = $this->makeAPICall('/public/api/idp/list.php', 'GET');
                
                if ($listResponse['success']) {
                    $this->recordSuccess('IDP', 'API - Listing successful');
                }
                
                // Test updates
                $updateResponse = $this->makeAPICall('/public/api/idp/update.php', 'POST', [
                    'id' => $idpId,
                    'status' => 'in_progress'
                ]);
                
                if ($updateResponse['success']) {
                    $this->recordSuccess('IDP', 'API - Update successful');
                }
                
                // Test submission
                $submitResponse = $this->makeAPICall('/public/api/idp/submit.php', 'POST', [
                    'id' => $idpId
                ]);
                
                if ($submitResponse['success']) {
                    $this->recordSuccess('IDP', 'API - Submission successful');
                }
                
            } else {
                $this->recordFailure('IDP', 'API - Creation failed: ' . $createResponse['error']);
            }
            
        } catch (Exception $e) {
            $this->recordFailure('IDP', 'API - Exception: ' . $e->getMessage());
        }
    }
    
    private function testWorkflowIntegration() {
        echo "\n7. Testing Workflow Integration...\n";
        
        try {
            // Create a complete workflow: Self-assessment → Manager feedback → Kudos → Achievement
            $completeWorkflow = [
                'employee_id' => self::$testData['employee_id'],
                'manager_id' => self::$testData['manager_id'],
                'period_id' => self::$testData['period_id']
            ];
            
            // Step 1: Employee creates self-assessment
            $assessmentResponse = $this->makeAPICall('/public/api/self-assessment/create.php', 'POST', [
                'period_id' => $completeWorkflow['period_id'],
                'achievements' => 'Complete API workflow test',
                'challenges' => 'Testing all systems together',
                'overall_rating' => '5',
                'growth_rating' => '5'
            ]);
            
            if ($assessmentResponse['success']) {
                $this->recordSuccess('Workflow', 'Integration - Self-assessment creation');
                
                // Step 2: Manager gives kudos
                $kudosResponse = $this->makeAPICall('/public/api/kudos/give.php', 'POST', [
                    'from_employee' => $completeWorkflow['manager_id'],
                    'to_employee' => $completeWorkflow['employee_id'],
                    'type' => 'leadership',
                    'reason' => 'Excellent self-assessment and reflection',
                    'points' => 25
                ]);
                
                if ($kudosResponse['success']) {
                    $this->recordSuccess('Workflow', 'Integration - Manager kudos given');
                    
                    // Step 3: Employee logs achievement
                    $achievementResponse = $this->makeAPICall('/public/api/achievements/create.php', 'POST', [
                        'title' => 'Workflow Integration Test Achievement',
                        'description' => 'Successfully completed comprehensive workflow test',
                        'impact_metrics' => json_encode(['tests_passed' => 10, 'workflow_score' => 100])
                    ]);
                    
                    if ($achievementResponse['success']) {
                        $this->recordSuccess('Workflow', 'Integration - Achievement logged');
                        
                        // Step 4: Employee provides upward feedback
                        $feedbackResponse = $this->makeAPICall('/public/api/upward-feedback/submit.php', 'POST', [
                            'manager_id' => $completeWorkflow['manager_id'],
                            'relationship_rate' => 5,
                            'clarity_rate' => 5,
                            'support_rate' => 5,
                            'effectiveness_rate' => 5,
                            'comments' => 'Excellent guidance throughout the process',
                            'quarter' => date('n'),
                            'year' => date('Y')
                        ]);
                        
                        if ($feedbackResponse['success']) {
                            $this->recordSuccess('Workflow', 'Integration - Complete 360 workflow validated');
                        }
                    }
                }
            }
            
        } catch (Exception $e) {
            $this->recordFailure('Workflow', 'Integration - Exception in workflow: ' . $e->getMessage());
        }
    }
    
    private function testSecurityFeatures() {
        echo "\n8. Testing Security Features...\n";
        
        try {
            // Test CSRF protection
            $invalidCsrfResponse = $this->makeAPICall('/public/api/self-assessment/create.php', 'POST', [
                'csrf_token' => 'invalid_token',
                'test_data' => 'should_fail'
            ]);
            
            if (!$invalidCsrfResponse['success']) {
                $this->recordSuccess('Security', 'API - CSRF protection active');
            }
            
            // Test input validation
            $invalidInputResponse = $this->makeAPICall('/public/api/self-assessment/create.php', 'POST', [
                'achievements' => str_repeat('X', 10000), // Too long
                'overall_rating' => 'invalid_rating'
            ]);
            
            if (!$invalidInputResponse['success']) {
                $this->recordSuccess('Security', 'API - Input validation working');
            }
            
            // Test authorization checks
            $otherEmployeeResponse = $this->makeAPICall('/public/api/achievements/list.php', 'GET', [
                'employee_id' => self::$testData['manager_id'] + 1000 // Non-existent
            ]);
            
            if (!$otherEmployeeResponse['success']) {
                $this->recordSuccess('Security', 'API - Authorization checks working');
            }
            
        } catch (Exception $e) {
            $this->recordFailure('Security', 'API - Security test exception: ' . $e->getMessage());
        }
    }
    
    private function testErrorHandling() {
        echo "\n9. Testing Error Handling...\n";
        
        try {
            // Test validation errors
            $missingFieldResponse = $this->makeAPICall('/public/api/self-assessment/create.php', 'POST', [
                'achievements' => 'Test achievements'
                // Missing required period_id
            ]);
            
            if ($missingFieldResponse['success'] === false && isset($missingFieldResponse['error'])) {
                $this->recordSuccess('Error', 'API - Validation errors properly handled');
            }
            
            // Test not found errors
            $notFoundResponse = $this->makeAPICall('/public/api/self-assessment/get.php', 'GET', [
                'employee_id' => self::$testData['employee_id'],
                'period_id' => 999999
            ]);
            
            if ($notFoundResponse['success'] === false) {
                $this->recordSuccess('Error', 'API - Not found errors properly handled');
            }
            
            // Test database errors
            $invalidDataResponse = $this->makeAPICall('/public/api/self-assessment/create.php', 'POST', [
                'employee_id' => 'invalid',
                'period_id' => 'invalid',
                'achievements' => []
            ]);
            
            if ($invalidDataResponse['success'] === false) {
                $this->recordSuccess('Error', 'API - Database errors properly handled');
            }
            
        } catch (Exception $e) {
            $this->recordFailure('Error', 'API - Error handling test exception: ' . $e->getMessage());
        }
    }
    
    private function makeAPICall($endpoint, $method = 'GET', $data = []) {
        $url = $this->baseUrl . $endpoint;
        
        // Mock API call for testing - in real scenario, use cURL
        ob_start();
        $_SERVER['REQUEST_METHOD'] = $method;
        
        // Set GET/POST data
        if ($method === 'GET') {
            $_GET = $data;
        } else {
            $_POST = $data;
        }
        
        $_SESSION['user_id'] = self::$testData['employee_id'];
        $_SESSION['role'] = 'employee';
        
        try {
            include __DIR__ . '/..' . $endpoint;
            $response = json_decode(ob_get_contents(), true);
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
        
        ob_end_clean();
        return $response;
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
    
    private function generateReport() {
        echo "\n=== API Test Summary ===\n";
        
        $passes = count(array_filter($this->testResults, function($r) { return $r['status'] === 'PASS'; }));
        $failures = count(array_filter($this->testResults, function($r) { return $r['status'] === 'FAIL'; }));
        
        echo "Passed: {$passes}\n";
        echo "Failed: {$failures}\n";
        echo "Total Tests: " . count($this->testResults) . "\n";
        
        // Generate detailed API test report
        $reportContent = [
            'timestamp' => date('Y-m-d H:i:s'),
            'base_url' => $this->baseUrl,
            'results' => $this->testResults,
            'summary' => [
                'total' => count($this->testResults),
                'passed' => $passes,
                'failed' => $failures
            ]
        ];
        
        file_put_contents(__DIR__ . '/api_test_report.json', json_encode($reportContent, JSON_PRETTY_PRINT));
        
        // Cleanup test data
        $this->cleanupTestData();
        
        exit($failures === 0 ? 0 : 1);
    }
    
    private function cleanupTestData() {
        echo "\nCleaning up test data...\n";
        
        $cleanupQueries = [
            "DELETE FROM enhanced_self_assessments WHERE employee_id IN (?, ?)",
            "DELETE FROM enhanced_achievements WHERE employee_id IN (?, ?)",
            "DELETE FROM enhanced_okrs WHERE employee_id IN (?, ?)",
            "DELETE FROM enhanced_idps WHERE employee_id IN (?, ?) OR manager_id IN (?, ?)",
            "DELETE FROM kudos_points WHERE from_employee IN (?, ?) OR to_employee IN (?, ?)",
            "DELETE FROM upward_feedback WHERE employee_id IN (?, ?) OR manager_id IN (?, ?)",
            "DELETE FROM employees WHERE id IN (?, ?)",
            "DELETE FROM evaluation_periods WHERE name LIKE 'API Test%'"
        ];
        
        $employeeIds = [self::$testData['employee_id'], self::$testData['manager_id']];
        
        foreach ($cleanupQueries as $query) {
            if (strpos($query, 'evaluation_periods') !== false) {
                $this->db->prepare($query)->execute();
            } else {
                $this->db->prepare($query)->execute(array_merge($employeeIds, $employeeIds));
            }
        }
        
        echo "Cleanup completed.\n";
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    new EnhancedAPITester();
}