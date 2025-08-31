<?php
<?php
/**
 * System Performance and Load Testing Suite
 * Tests database performance, API response times, and concurrent user scenarios
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class PerformanceTestSuite {
    private $db;
    private $testResults = [];
    private $benchmarks = [];
    private $concurrentUsers = 50;
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
        $this->runAllPerformanceTests();
    }
    
    private function runAllPerformanceTests() {
        echo "\n=== System Performance and Load Tests ===\n\n";
        
        $this->testDatabasePerformance();
        $this->testAPIResponseTimes();
        $this->testConcurrentUserScenarios();
        $this->testMemoryUsage();
        $this->testQueryOptimization();
        $this->testCachePerformance();
        $this->testBulkDataProcessing();
        $this->testFileUploadPerformance();
        $this->testAJAXPerformance();
        $this->testDashboardLoadTime();
        $this->generatePerformanceReport();
    }
    
    private function testDatabasePerformance() {
        echo "\n1. Testing Database Performance...\n";
        
        $queryTests = [
            'Employee Listing' => "SELECT * FROM employees WHERE status = 1 ORDER BY last_name LIMIT 50",
            'Kudos Feed' => "SELECT kp.*, e1.first_name as from_name, e2.first_name as to_name 
                             FROM kudos_points kp 
                             JOIN employees e1 ON kp.from_employee = e1.id
                             JOIN employees e2 ON kp.to_employee = e2.id
                             ORDER BY kp.created_at DESC LIMIT 20",
            'Self Assessment' => "SELECT * FROM enhanced_self_assessments WHERE employee_id = 1 AND submitted = 1",
            '360 Integration' => "SELECT es.*, em.first_name, em.last_name 
                                FROM enhanced_self_assessments es
                                JOIN employees em ON es.employee_id = em.id
                                WHERE es.period_id = 1"
        ];
        
        foreach ($queryTests as $testName => $query) {
            $startTime = microtime(true);
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $results = $stmt->fetchAll();
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            $threshold = 100; // 100ms threshold
            $status = $executionTime <= $threshold ? 'PASS' : 'SLOW';
            
            $this->recordPerformance($testName, $executionTime, $status, count($results));
            
            if ($status === 'PASS') {
                $this->recordSuccess("Database", "$testName completed in {$executionTime}ms");
            } else {
                $this->recordWarning("Database", "$testName slow at {$executionTime}ms");
            }
        }
    }
    
    private function testAPIResponseTimes() {
        echo "\n2. Testing API Response Times...\n";
        
        $apiEndpoints = [
            '/public/api/self-assessment/get.php?employee_id=1&period_id=1',
            '/public/api/achievements/list.php?employee_id=1',
            '/public/api/kudos/feed.php?limit=20',
            '/public/api/okr/list.php?employee_id=1',
            '/public/api/idp/list.php?employee_id=1'
        ];
        
        foreach ($apiEndpoints as $endpoint) {
            $startTime = microtime(true);
            
            // Simulate API call
            ob_start();
            @include __DIR__ . '/..' . $endpoint;
            $response = ob_get_contents();
            ob_end_clean();
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            $threshold = 200; // 200ms threshold
            
            $status = $responseTime <= $threshold ? 'PASS' : 'FAIL';
            
            $this->recordPerformance('API - ' . basename($endpoint), $responseTime, $status);
            
            if ($status === 'PASS') {
                $this->recordSuccess("API", basename($endpoint) . " responded in {$responseTime}ms");
            } else {
                $this->recordFailure("API", basename($endpoint) . " slow response: {$responseTime}ms");
            }
        }
    }
    
    private function testConcurrentUserScenarios() {
        echo "\n3. Testing Concurrent User Scenarios ({$this->concurrentUsers} users)...\n";
        
        $userActions = [
            ['self_assessment_submit', 5, 'medium'],
            ['achievement_create', 10, 'low'],
            ['kudos_give', 15, 'high'],
            ['upward_feedback', 8, 'medium']
        ];
        
        $this->simulateConcurrentScenario($userActions);
        
        // Test database connection limits
        $this->testConnectionPool();
        
        // Test transaction locks
        $this->testConcurrentTransactions();
    }
    
    private function simulateConcurrentScenario($actions) {
        $startTime = microtime(true);
        $totalOperations = 0;
        
        foreach ($actions as $action) {
            list($operation, $users, $intensity) = $action;
            $operations = $users * ($intensity === 'high' ? 10 : ($intensity === 'medium' ? 5 : 2));
            $totalOperations += $operations;
            
            $result = $this->executeConcurrentOperation($operation, $users);
            
            if ($result['success']) {
                $this->recordSuccess("Concurrency", "$operation handled {$users} concurrent {$intensity} users");
            } else {
                $this->recordFailure("Concurrency", "$operation failed with {$users} concurrent users");
            }
        }
        
        $totalTime = (microtime(true) - $startTime);
        $throughput = $totalOperations / $totalTime;
        
        $this->recordPerf