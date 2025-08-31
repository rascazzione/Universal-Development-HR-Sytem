<?php
<?php
/**
 * Complete 360-Degree Feedback Workflow Test
 * Tests the entire 360-degree feedback cycle from multiple perspectives
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

class Feedback360WorkflowTest {
    private $db;
    private $testResults = [];
    private $testData = [
        'employee' => null,
        'manager' => null,
        'peer1' => null,
        'peer2' => null,
        'period_id' => null
    ];
    
    public function __construct() {
        global $pdo;
        $this->db = $pdo;
        $this->setup360Workflow();
        $this->runCompleteCycle();
    }
    
    private function runCompleteCycle() {
        echo "\n=== Complete 360-Degree Feedback Workflow ===\n\n";
        
        $this->prepare360Environment();
        $this->testSelfAssessmentCycle();
        $this->testAchievementLogging();
        $this->testPeerKudosExchange();
        $this->testManagerFeedback();
        $this->testUpwardFeedback();
        $this->testOKRAlignment();
        $this->testIDPIntegration();
        $this->testEvaluationCompilation();
        $this->testAnonymousFeatures();
        $this->testRealTimeUpdates();
        $this->generate360Report();
    }
    
    private function prepare360Environment() {
        echo "\n1. Preparing 360-Degree Environment...\n";
        
        $this->createOrganizationalStructure();
        $this->setUpEvaluationPeriod();
        
        echo "360 environment prepared with employee-manager-team structure\n";
    }
    
    private function createOrganizationalStructure() {
        try {
            // Create department and teams
            $deptStmt = $this->db->prepare("INSERT INTO departments (name, description) VALUES (?, ?)");
            $deptStmt->execute(['Engineering', 'Software development team']);
            $deptId = $this->db->lastInsertId();
            
            // Create employees with hierarchy
            $employees = [
                'manager' => ['Sarah', 'Manager', 'sarah.manager@company.com', $deptId, null, 'manager'],
                'employee' => ['John', 'Developer', 'john.developer@company.com', $deptId, null, 'employee'],
                'peer1' => ['Alice', 'Senior Developer', 'alice.senior@company.com', $deptId, null, 'employee'],
                'peer2' => ['Bob', 'Developer', 'bob.developer@company.com', $deptId, null, 'employee'],
            ];
            
            foreach ($employees as $role => $data) {
                $stmt = $this->db->prepare("
                    INSERT INTO employees (first_name, last_name, email, department_id, manager_id, role) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute($data);
                $this->testData[$role] = $this->db->lastInsertId();
            }
            
            // Set manager relationship
            $updateStmt = $this->db->prepare("
                UPDATE employees SET manager_id = ? WHERE id IN (?, ?, ?)
            ");
            $updateStmt->execute([
                $this->testData['manager'], 
                $this->testData['employee'], 
                $this->testData['peer1'], 
                $this->testData['peer2']
            ]);
            
            $this->recordSuccess('Setup', '360 environment created with 4 team members');
            
        } catch (Exception $e) {
            $this->recordFailure('Setup', 'Failed to create 360 environment: ' . $e->getMessage());
        }
    }
    
    private function setUpEvaluationPeriod() {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO evaluation_periods (name, start_date, end_date, status) 
                VALUES ('Q4 2024 360-Degree Review', 
                        DATE_SUB(NOW(), INTERVAL 90 DAY), 
                        DATE_ADD(NOW(), INTERVAL 30 DAY), 
                        'active')
            ");
            $stmt->execute();
            $this->testData['period_id'] = $this->db->lastInsertId();
            
            $this->recordSuccess('Setup', 'Evaluation period configured for 360-degree reviews');
            
        } catch (Exception $e) {
            $this->recordFailure('Setup', 'Failed to setup evaluation period: ' . $e->getMessage());
        }
    }
    
    private function testSelfAssessmentCycle() {
        echo "\n2. Testing Self-Assessment Cycle...\n";
        
        $_SESSION['user_id'] = $this->testData['employee'];
        $_SESSION['role'] = 'employee';
        
        try {
            // John's comprehensive self-reflection
            $selfAssessmentData = [
                $this->testData['employee'],
                $this->testData['period_id'],
                json_encode([
                    "Led successful migration from AngularJS to React, improving application performance by 45%",
                    "Established automated testing framework that reduced regression bugs by 60%",
                    "Developed mentoring program for 3 junior developers, improving team code quality"
                ]),
                json_encode([
                    "Time management during sprint deadlines needs improvement",
                    "Communication with non-technical stakeholders requires more clarity",
                    "Public speaking and presentation skills for technical sharing"
                ]),
                json_encode([
                    "Cloud architecture design patterns",
                    "Leadership skills for distributed teams",
                    "Technical writing and documentation"
                ]),
                'I have consistently met and exceeded expectations this quarter',
                'significant_improvement',
                'significant_improvement',
                9,
                9,
                1
            ];
            
            $stmt = $this->db->prepare("
                INSERT INTO enhanced_self_assessments 
                (employee_id, period_id, achievements, challenges, improvement_areas, 
                 goals_met, technical_skills_growth, leadership_skills_growth, 
                 overall_rating, growth_rating, submitted, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute($selfAssessmentData);
            
            $assessmentId = $this->db->lastInsertId();
            
            $this->recordSuccess('SelfAssessment-360', "Employee self-assessment completed with detailed self-reflection");
            $this->validateSelfAssessmentIntegrity($assessmentId);
            $this->simulateManagerReview($assessmentId);
            
        } catch (Exception $e) {
            $this->recordFailure('SelfAssessment-360', 'Error in self-assessment cycle: ' . $e->getMessage());
        }
    }
    
    private function validateSelfAssessmentIntegrity($assessmentId) {
        $stmt = $this->db->prepare("SELECT * FROM enhanced_self_assessments WHERE id = ?");
        $stmt->execute([$assessmentId]);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($assessment && $assessment['submitted'] == 1 && $assessment['employee_id'] == $this->testData['employee']) {
            $this->recordSuccess('SelfAssessment-360', 'Self-assessment data integrity validated');
        }
    }
    
    private function simulateManagerReview($assessmentId) {
        $_SESSION['user_id'] = $this->testData['manager'];
        
        $managerFeedback = [
            $assessmentId,
            json_encode([
                "Excellently captured the framework migration impact",
                "Good identification of mentoring accomplishments",
                "Consider focusing on specific quantitative metrics for achievements"
            ]),
            json_encode([
                "Excellent work on framework migration - consider presenting this at tech talks",
                "Mentoring program development shows leadership growth",
                "Strong collaboration demonstrated across teams"
            ]),
            "ready_for_senior_developer_role",
            "10% merit increase",
            true
        ];
        
        $stmt = $this->db->prepare("
            UPDATE enhanced_self_assessments 
            SET manager_feedback = ?, development_plans = ?, 
                promotion_readiness = ?, salary_adjustment = ?, 
                is_reviewed = ? WHERE id = ?
        ");
        $stmt