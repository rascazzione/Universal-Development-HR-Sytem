<?php
<?php
/**
 * Security Validation Test Suite
 * Tests authentication, authorization, CSRF protection, input sanitization, and security features
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class SecurityTestSuite {
    private $db;
    private $testResults = [];
    private $testUsers = [];
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
        $this->runAllSecurityTests();
    }
    
    private function runAllSecurityTests() {
        echo "\n=== Security Feature Validation Tests ===\n\n";
        
        $this->testAuthenticationSystem();
        $this->testAuthorizationControls();
        $this->testCSRFProtection();
        $this->testInputSanitization();
        $this->testSQlInjectionProtection();
        $this->testXSSProtection();
        $this->testSessionSecurity();
        $this->testFileUploadSecurity();
        $this->testAnonymousFeedbacksSecurity();
        $this->testRoleBasedAccess();
        $this->generateSecurityReport();
    }
    
    private function testAuthenticationSystem() {
        echo "\n1. Testing Authentication System...\n";
        
        try {
            // Test login protection against brute force
            for ($i = 0; $i < 5; $i++) {
                $result = $this->simulateIncorrectLogin('test@company.com', 'wrongpassword');
                if ($i >= 3 && !$result['success']) {
                    $this->recordSuccess('Authentication', 'Login attempt limiting active');
                    break;
                }
            }
            
            // Test session timeout
            $sessionData = $this->testSessionTimeout();
            if ($sessionData['expiry'] <= 1800) { // 30 minutes
                $this->recordSuccess('Authentication', 'Session timeout configured correctly');
            }
            
            // Test remember me security
            $rememberMe = $this->testRememberMeTokens();
            if ($rememberMe['secure']) {
                $this->recordSuccess('Authentication', 'Remember me tokens properly secured');
            }
            
        } catch (Exception $e) {
            $this->recordFailure('Authentication', 'Login system test error: ' . $e->getMessage());
        }
    }
    
    private function testAuthorizationControls() {
        echo "\n2. Testing Authorization Controls...\n";
        
        try {
            // Create test users with different roles
            $this->setupTestUsers();
            
            foreach ($this->testUsers as $user) {
                $this->testUserPermissions($user);
                $this->testCrossUserAccess($user);
                $this->testRoleLimitations($user);
            }
            
        } catch (Exception $e) {
            $this->recordFailure('Authorization', 'Authorization test error: ' . $e->getMessage());
        }
    }
    
    private function setupTestUsers() {
        $userRoles = ['employee', 'manager', 'hr', 'admin'];
        
        foreach ($userRoles as $role) {
            $stmt = $this->db->prepare("
                INSERT INTO employees (first_name, last_name, email, password, role, status) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $password = password_hash('testpassword123', PASSWORD_DEFAULT);
            $stmt->execute(["Security", $role, "security.{$role}@company.com", $password, $role, 1]);
            $this->testUsers[$role] = ['id' => $this->db->lastInsertId(), 'role' => $role];
        }
    }
    
    private function testUserPermissions($user) {
        $testCases = [
            'employee' => [
                'valid' => ['/public/self-assessment/dashboard.php', '/public/achievements/journal.php'],
                'invalid' => ['/public/admin/departments.php', '/public/reports/admin.php']
            ],
            'manager' => [
                'valid' => ['/public/dashboard/manager.php', '/public/employees/view.php'],
                'invalid' => ['/public/admin/competencies.php', '/public/reports/hr.php']
            ],
            'hr' => [
                'valid' => ['/public/admin/departments.php', '/public/reports/analytics.php'],
                'invalid' => ['/public/admin/servers.php', '/public/system/config.php']
            ]
        ];
        
        $role = $user['role'];
        
        foreach ($testCases[$role]['valid'] as $page) {
            $result = $this->canAccessPage($user['id'], $page);
            if ($result) {
                $this->recordSuccess('Authorization', "User [$role] correctly granted access to $page");
            } else {
                $this->recordFailure('Authorization', "User [$role] denied access to valid page $page");
            }
        }
        
        foreach ($testCases[$role]['invalid'] as $page) {
            $result = $this->canAccessPage($user['id'], $page);
            if (!$result) {
                $this->recordSuccess('Authorization', "User [$role] correctly denied access to $page");
            } else {
                $this->recordFailure('Authorization', "User [$role] granted unauthorized access to $page");
            }
        }
    }
    
    private function testCrossUserAccess($user) {
        // Test accessing another user's data
        $otherUserId = $user['id'] + 1;
        
        $sqlTests = [
            "SELECT * FROM enhanced_self_assessments WHERE employee_id != ? AND id = ?",
            "SELECT * FROM enhanced_achievements WHERE employee_id != ? AND id = ?",
            "SELECT * FROM enhanced_okrs WHERE employee_id != ? AND id = ?"
        ];
        
        foreach ($sqlTests as $query) {
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user['id'], 1]);
            $result = $stmt->fetchAll();
            
            if (empty($result)) {
                $this->recordSuccess('Authorization', 'Cross-user data access properly protected');
            } else {
                $this->recordFailure('Authorization', 'Potential cross-user data access vulnerability');
            }
        }
    }
    
    private function testCSRFProtection() {
        echo "\n3. Testing CSRF Protection...\n";
        
        // Test form submissions without CSRF token
        $csrfTests = [
            '/public/api/self-assessment/create.php' => ['POST' => ['achievements' => 'Test']],
            '/public/api/achievements/create.php' => ['POST' => ['title' => 'Test Achievement']],
            '/public/api/kudos/give.php' => ['POST' => ['reason' => 'Test Kudos']]
        ];
        
        foreach ($csrfTests as $endpoint => $data) {
            $result = $this->simulateFormSubmission($endpoint, $data);
            if (!$result['success'] && strpos($result['error'], 'csrf')) {
                $this->recordSuccess('CSRF', "CSRF protection active for $endpoint");
            } else {
                $this->recordFailure('CSRF', "CSRF protection missing for $endpoint");
            }
        }
    }
    
    private function testInputSanitization() {
        echo "\n4. Testing Input Sanitization...\n";
        
        $testInputs = [
            'xss_payload' => '<script>alert("XSS")</script>',
            'sql_payload' => "admin' OR '1'='1",
            'code_payload' => '<?php system("rm -rf /"); ?>',
            'html_payload' => '<img onerror="alert(1)" src="x">',
            'js_payload' => 'javascript:alert(document.cookie)'
        ];
        
        $sanitizationTests = [
            'self_assessment_achievements' => 'enhanced_self_assessments.achievements',
            'achievement_title' => 'enhanced_achievements.title',
            'kudos_reason' => 'kudos_points.reason'
        ];
        
        foreach ($sanitizationTests as $field => $fullField) {
            foreach ($testInputs as $type => $payload) {
                $result = $this->testSanitization($fullField, $payload);
                if ($result['clean']) {
                    $this-> 