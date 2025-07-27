<?php
/**
 * Test Data Population Script
 * Performance Evaluation System
 * 
 * This script populates the database with comprehensive test data including:
 * - Users (HR Admin, Managers, Employees)
 * - Departments and organizational hierarchy
 * - Job templates with KPIs, competencies, responsibilities, and values
 * - Evaluation periods and evaluations with realistic data
 * 
 * Usage: php scripts/populate_test_data.php
 */

// Set execution time limit for large data operations
set_time_limit(300);

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Employee.php';
require_once __DIR__ . '/../classes/Department.php';
require_once __DIR__ . '/../classes/CompanyKPI.php';
require_once __DIR__ . '/../classes/Competency.php';
require_once __DIR__ . '/../classes/CompanyValues.php';
require_once __DIR__ . '/../classes/JobTemplate.php';
require_once __DIR__ . '/../classes/EvaluationPeriod.php';
require_once __DIR__ . '/../classes/Evaluation.php';

class TestDataPopulator {
    private $pdo;
    private $userIds = [];
    private $employeeIds = [];
    private $departmentIds = [];
    private $kpiIds = [];
    private $competencyIds = [];
    private $valueIds = [];
    private $jobTemplateIds = [];
    private $periodIds = [];
    private $credentials = [];
    
    // Test data arrays
    private $firstNames = [
        'John', 'Jane', 'Michael', 'Sarah', 'David', 'Lisa', 'Robert', 'Emily',
        'James', 'Maria', 'William', 'Jennifer', 'Richard', 'Patricia', 'Charles',
        'Linda', 'Joseph', 'Elizabeth', 'Thomas', 'Barbara', 'Christopher', 'Susan',
        'Daniel', 'Jessica', 'Matthew', 'Karen', 'Anthony', 'Nancy', 'Mark', 'Betty'
    ];
    
    private $lastNames = [
        'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller',
        'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez',
        'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin',
        'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark',
        'Ramirez', 'Lewis', 'Robinson'
    ];
    
    private $departments = [
        'Information Technology' => 'IT systems, software development, and infrastructure',
        'Human Resources' => 'Employee relations, recruitment, and organizational development',
        'Sales' => 'Revenue generation and customer acquisition',
        'Marketing' => 'Brand management and customer engagement',
        'Finance' => 'Financial planning, accounting, and analysis',
        'Operations' => 'Business operations and process management'
    ];
    
    private $positiveComments = [
        'Consistently exceeds expectations and delivers high-quality work.',
        'Shows excellent leadership skills and mentors junior team members effectively.',
        'Demonstrates strong problem-solving abilities and innovative thinking.',
        'Excellent communication skills and works well with cross-functional teams.',
        'Takes initiative and drives projects to successful completion.',
        'Highly reliable and consistently meets deadlines.',
        'Shows great attention to detail and maintains high quality standards.',
        'Adapts well to change and embraces new challenges.',
        'Strong technical skills and stays current with industry trends.',
        'Excellent customer service orientation and builds strong relationships.'
    ];
    
    private $improvementComments = [
        'Would benefit from improved time management and prioritization skills.',
        'Needs to work on communication with stakeholders and team members.',
        'Should focus on developing technical skills in emerging technologies.',
        'Could improve attention to detail in project deliverables.',
        'Would benefit from taking more initiative in team projects.',
        'Needs to improve follow-up on action items and commitments.',
        'Should work on providing more constructive feedback to team members.',
        'Could benefit from better documentation of work processes.',
        'Needs to improve presentation and public speaking skills.',
        'Should focus on building stronger relationships with cross-functional teams.'
    ];
    
    public function __construct() {
        $this->pdo = getDbConnection();
        echo "🚀 Test Data Population Script Started\n";
        echo "=====================================\n\n";
    }
    
    /**
     * Main execution method
     */
    public function run() {
        try {
            $this->validateEnvironment();
            $this->resetDatabase();
            $this->createBasicFoundationData();
            $this->createUsersAndEmployees();
            $this->createAdvancedFoundationData();
            $this->createJobTemplatesAndAssignToEmployees();
            $this->createEvaluationPeriods();
            $this->createEvaluations();
            $this->generateDocumentation();
            
            echo "\n✅ Test data population completed successfully!\n";
            echo "📄 Check 'test_data_credentials.txt' for login information.\n";
            echo "📊 Check 'test_data_summary.txt' for data overview.\n\n";
            
        } catch (Exception $e) {
            echo "\n❌ Error: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
            exit(1);
        }
    }
    
    /**
     * Validate environment and prerequisites
     */
    private function validateEnvironment() {
        echo "🔍 Validating environment...\n";
        
        // Check database connection
        if (!$this->pdo) {
            throw new Exception("Database connection failed");
        }
        
        // Check required tables exist
        $requiredTables = [
            'users', 'employees', 'departments', 'company_kpis', 'competencies',
            'company_values', 'job_position_templates', 'evaluation_periods', 'evaluations'
        ];
        
        foreach ($requiredTables as $table) {
            $result = $this->pdo->query("SHOW TABLES LIKE '$table'");
            if ($result->rowCount() === 0) {
                throw new Exception("Required table '$table' not found");
            }
        }
        
        echo "✓ Environment validation passed\n\n";
    }
    
    /**
     * Reset database (preserve system settings)
     */
    private function resetDatabase() {
        echo "🗑️  Resetting database...\n";
        
        try {
            // Check database engine and transaction support
            echo "🔍 Checking database configuration...\n";
            
            // Check autocommit status
            $result = $this->pdo->query("SELECT @@autocommit as autocommit")->fetch();
            echo "  ✓ Autocommit status: " . ($result['autocommit'] ? 'ON' : 'OFF') . "\n";
            
            // Check if we can start a transaction
            echo "  ✓ Starting transaction...\n";
            $transactionStarted = $this->pdo->beginTransaction();
            if (!$transactionStarted) {
                throw new Exception("Failed to start transaction");
            }
            echo "  ✓ Transaction started successfully\n";
            
            // Check transaction status
            $inTransaction = $this->pdo->inTransaction();
            echo "  ✓ In transaction: " . ($inTransaction ? 'YES' : 'NO') . "\n";
            
            // Disable foreign key checks
            echo "  ✓ Disabling foreign key checks...\n";
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Clear all tables except system_settings
            $tablesToClear = [
                'audit_log',
                'evaluation_comments',
                'evaluation_value_results',
                'evaluation_responsibility_results', 
                'evaluation_competency_results',
                'evaluation_kpi_results',
                'evaluation_section_weights',
                'evaluations',
                'evaluation_periods',
                'job_template_values',
                'job_template_responsibilities',
                'job_template_competencies',
                'job_template_kpis',
                'job_position_templates',
                'company_values',
                'competencies',
                'competency_categories',
                'company_kpis',
                'employees',
                'departments',
                'users'
            ];
            
            foreach ($tablesToClear as $table) {
                try {
                    echo "  🗑️  Clearing table: $table\n";
                    $this->pdo->exec("DELETE FROM $table");
                    echo "  ✓ Cleared $table\n";
                } catch (Exception $e) {
                    echo "  ⚠️  Warning: Could not clear $table: " . $e->getMessage() . "\n";
                }
            }
            
            // Re-enable foreign key checks
            echo "  ✓ Re-enabling foreign key checks...\n";
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            
            // Check if still in transaction before committing
            if ($this->pdo->inTransaction()) {
                echo "  ✓ Committing transaction...\n";
                $this->pdo->commit();
                echo "  ✓ Transaction committed successfully\n";
            } else {
                echo "  ⚠️  Warning: No active transaction to commit\n";
            }
            
            // Reset auto-increment counters (outside transaction - DDL auto-commits)
            echo "  🔄 Resetting auto-increment counters...\n";
            foreach ($tablesToClear as $table) {
                try {
                    $this->pdo->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
                } catch (Exception $e) {
                    echo "  ⚠️  Warning: Could not reset auto-increment for $table: " . $e->getMessage() . "\n";
                }
            }
            
            echo "✓ Database reset completed\n\n";
            
        } catch (Exception $e) {
            echo "❌ Exception caught: " . $e->getMessage() . "\n";
            echo "🔍 Checking transaction state before rollback...\n";
            
            if ($this->pdo->inTransaction()) {
                echo "  ✓ Active transaction found, rolling back...\n";
                $this->pdo->rollBack();
                echo "  ✓ Transaction rolled back\n";
            } else {
                echo "  ⚠️  No active transaction to rollback\n";
            }
            
            throw new Exception("Database reset failed: " . $e->getMessage());
        }
    }
    
    /**
     * Create foundation data (departments, KPIs, competencies, values)
     */
    private function createBasicFoundationData() {
        echo "🏗️  Creating basic foundation data...\n";

        $this->createCompetencies();
        
        echo "✓ Basic foundation data created\n\n";
    }
    
    /**
     * Create advanced foundation data (requires users)
     */
    private function createAdvancedFoundationData() {
        echo "🏗️  Creating advanced foundation data...\n";

        $this->createCompanyKPIs();
        $this->createCompanyValues();
        echo "✓ Advanced foundation data created\n\n";
    }
    
    /**
     * Create job templates and assign to existing employees
     */
    private function createJobTemplatesAndAssignToEmployees() {
        echo "🎨 Creating job templates and assigning to employees...\n";
        
        // Create job templates (now that KPIs and values exist)
        $this->createJobTemplates();
        
        // Update existing employees with job template assignments
        $this->assignJobTemplatesToExistingEmployees();
        
        echo "✓ Job templates created and assigned\n\n";
    }
    
    /**
     * Assign job templates to existing employees
     */
    private function assignJobTemplatesToExistingEmployees() {
        echo "  🔄 Assigning job templates to existing employees...\n";
        
        // Update managers with Department Manager template
        foreach ($this->employeeIds as $username => $employeeId) {
            if (strpos($username, 'manager.') === 0) {
                $this->updateEmployeeJobTemplate($employeeId, $this->jobTemplateIds['Department Manager']);
                echo "    ✓ Assigned Department Manager template to $username\n";
            }
        }
        
        // Update regular employees based on their departments
        $departments = array_keys($this->departments);
        $regularEmployees = [];
        
        // Collect all regular employees (not managers or admin)
        foreach ($this->employeeIds as $username => $employeeId) {
            if (strpos($username, 'manager.') !== 0 && strpos($username, 'admin.') !== 0) {
                $regularEmployees[] = ['username' => $username, 'employeeId' => $employeeId];
            }
        }
        
        // Assign job templates to employees (4 per department)
        $employeeIndex = 0;
        foreach ($departments as $deptIndex => $department) {
            for ($i = 0; $i < 4 && $employeeIndex < count($regularEmployees); $i++) {
                $employee = $regularEmployees[$employeeIndex];
                $jobTemplate = $this->getJobTemplateForDepartment($department, $i);
                if ($jobTemplate) {
                    $this->updateEmployeeJobTemplate($employee['employeeId'], $jobTemplate);
                    echo "    ✓ Assigned job template to {$employee['username']} ($department)\n";
                }
                $employeeIndex++;
            }
        }
    }
    
    /**
     * Update employee job template
     */
    private function updateEmployeeJobTemplate($employeeId, $jobTemplateId) {
        $sql = "UPDATE employees SET job_template_id = ? WHERE employee_id = ?";
        executeQuery($sql, [$jobTemplateId, $employeeId]);
    }
    
    /**
     * Create departments
     */
    private function createDepartments() {
        echo "  📁 Creating departments...\n";
        
        $departmentClass = new Department();
        
        foreach ($this->departments as $name => $description) {
            $data = [
                'department_name' => $name,
                'description' => $description,
                'is_active' => true,
                'created_by' => 1 // Will be updated after admin user is created
            ];
            
            $id = $departmentClass->createDepartment($data);
            $this->departmentIds[$name] = $id;
            echo "    ✓ Created department: $name\n";
        }
    }
    
    /**
     * Create company KPIs
     */
    private function createCompanyKPIs() {
        echo "  📊 Creating company KPIs...\n";
        
        $kpiClass = new CompanyKPI();
        
        $kpis = [
            // Sales KPIs
            ['Monthly Sales Target', 'Monthly revenue target achievement', 'Currency', 'Sales', 'higher_better'],
            ['Customer Acquisition', 'Number of new customers acquired', 'Count', 'Sales', 'higher_better'],
            ['Deal Closure Rate', 'Percentage of deals successfully closed', 'Percentage', 'Sales', 'higher_better'],
            ['Customer Retention', 'Percentage of customers retained', 'Percentage', 'Sales', 'higher_better'],
            
            // Quality KPIs
            ['Code Quality Score', 'Code quality assessment score', 'Score', 'Quality', 'higher_better'],
            ['Bug Resolution Time', 'Average time to resolve bugs', 'Hours', 'Quality', 'lower_better'],
            ['Customer Satisfaction', 'Customer satisfaction rating', 'Score', 'Quality', 'higher_better'],
            ['Defect Rate', 'Number of defects per deliverable', 'Count', 'Quality', 'lower_better'],
            
            // Productivity KPIs
            ['Project Completion Rate', 'Percentage of projects completed on time', 'Percentage', 'Productivity', 'higher_better'],
            ['Task Efficiency', 'Tasks completed per time period', 'Count', 'Productivity', 'higher_better'],
            ['Output Volume', 'Volume of work output', 'Count', 'Productivity', 'higher_better'],
            ['Utilization Rate', 'Percentage of time utilized effectively', 'Percentage', 'Productivity', 'higher_better'],
            
            // Leadership KPIs
            ['Team Development', 'Team skill development score', 'Score', 'Leadership', 'higher_better'],
            ['Strategic Planning', 'Strategic planning effectiveness', 'Score', 'Leadership', 'higher_better'],
            ['Decision Making', 'Quality of decision making', 'Score', 'Leadership', 'higher_better'],
            ['Employee Engagement', 'Team engagement score', 'Score', 'Leadership', 'higher_better'],
            
            // Innovation KPIs
            ['Process Improvement', 'Number of process improvements implemented', 'Count', 'Innovation', 'higher_better'],
            ['New Ideas Implementation', 'Percentage of new ideas implemented', 'Percentage', 'Innovation', 'higher_better']
        ];
        
        foreach ($kpis as $kpi) {
            $data = [
                'kpi_name' => $kpi[0],
                'kpi_description' => $kpi[1],
                'measurement_unit' => $kpi[2],
                'category' => $kpi[3],
                'target_type' => $kpi[4],
                'created_by' => 1
            ];
            
            $id = $kpiClass->createKPI($data);
            $this->kpiIds[$kpi[0]] = $id;
            echo "    ✓ Created KPI: {$kpi[0]}\n";
        }
    }
    
    /**
     * Create competencies
     */
    private function createCompetencies() {
        echo "  🎯 Creating competencies...\n";
        
        $competencyClass = new Competency();
        
        // Create categories first
        $categories = [
            'Technical Skills' => 'Job-specific technical competencies',
            'Communication' => 'Verbal and written communication abilities',
            'Leadership' => 'Leadership and management capabilities',
            'Problem Solving' => 'Analytical and problem-solving skills',
            'Teamwork' => 'Collaboration and team-working skills'
        ];
        
        $categoryIds = [];
        foreach ($categories as $name => $description) {
            $data = [
                'category_name' => $name,
                'description' => $description
            ];
            $id = $competencyClass->createCategory($data);
            $categoryIds[$name] = $id;
            echo "    ✓ Created competency category: $name\n";
        }
        
        // Create competencies
        $competencies = [
            ['Programming', 'Software development and coding skills', 'Technical Skills', 'technical'],
            ['System Administration', 'Server and system management', 'Technical Skills', 'technical'],
            ['Data Analysis', 'Data interpretation and analysis', 'Technical Skills', 'technical'],
            ['Project Management', 'Planning and executing projects', 'Technical Skills', 'technical'],
            
            ['Written Communication', 'Clear and effective written communication', 'Communication', 'soft_skill'],
            ['Verbal Communication', 'Clear and effective verbal communication', 'Communication', 'soft_skill'],
            ['Presentation Skills', 'Ability to present to groups', 'Communication', 'soft_skill'],
            
            ['Team Leadership', 'Ability to lead and motivate teams', 'Leadership', 'leadership'],
            ['Strategic Thinking', 'Long-term strategic planning', 'Leadership', 'leadership'],
            ['Decision Making', 'Making effective decisions', 'Leadership', 'leadership'],
            
            ['Analytical Thinking', 'Analyzing complex problems', 'Problem Solving', 'core'],
            ['Creative Problem Solving', 'Finding innovative solutions', 'Problem Solving', 'core'],
            
            ['Collaboration', 'Working effectively with others', 'Teamwork', 'soft_skill'],
            ['Adaptability', 'Adapting to change and new situations', 'Teamwork', 'core']
        ];
        
        foreach ($competencies as $comp) {
            $data = [
                'competency_name' => $comp[0],
                'description' => $comp[1],
                'category_id' => $categoryIds[$comp[2]],
                'competency_type' => $comp[3]
            ];
            
            $id = $competencyClass->createCompetency($data);
            $this->competencyIds[$comp[0]] = $id;
            echo "    ✓ Created competency: {$comp[0]}\n";
        }
    }
    
    /**
     * Create company values
     */
    private function createCompanyValues() {
        echo "  💎 Creating company values...\n";
        
        $valuesClass = new CompanyValues();
        
        $values = [
            ['Integrity', 'Acting with honesty and strong moral principles', 1],
            ['Excellence', 'Striving for the highest quality in everything we do', 2],
            ['Innovation', 'Embracing creativity and new ideas to drive progress', 3],
            ['Collaboration', 'Working together effectively to achieve common goals', 4],
            ['Customer Focus', 'Putting our customers at the center of everything we do', 5]
        ];
        
        foreach ($values as $value) {
            $data = [
                'value_name' => $value[0],
                'description' => $value[1],
                'sort_order' => $value[2],
                'created_by' => 1
            ];
            
            $id = $valuesClass->createValue($data);
            $this->valueIds[$value[0]] = $id;
            echo "    ✓ Created company value: {$value[0]}\n";
        }
    }
    
    /**
     * Create job templates with components
     */
    private function createJobTemplates() {
        echo "🎨 Creating job templates...\n";
        
        $jobTemplateClass = new JobTemplate();
        
        $templates = [
            [
                'title' => 'Chief Executive Officer',
                'department' => 'Executive',
                'description' => 'Senior executive responsible for overall company strategy and operations',
                'kpis' => ['Strategic Planning', 'Team Development', 'Employee Engagement'],
                'competencies' => ['Strategic Thinking', 'Team Leadership', 'Decision Making'],
                'responsibilities' => [
                    'Develop and execute company strategy',
                    'Lead executive team and board relations',
                    'Ensure organizational performance and growth'
                ]
            ],
            [
                'title' => 'Department Manager',
                'department' => 'Management',
                'description' => 'Manages department operations and team performance',
                'kpis' => ['Team Development', 'Strategic Planning', 'Employee Engagement'],
                'competencies' => ['Team Leadership', 'Strategic Thinking', 'Decision Making', 'Communication'],
                'responsibilities' => [
                    'Manage department operations and budget',
                    'Lead and develop team members',
                    'Ensure department goals are met'
                ]
            ],
            [
                'title' => 'Senior Software Developer',
                'department' => 'Information Technology',
                'description' => 'Senior developer responsible for complex software development',
                'kpis' => ['Code Quality Score', 'Project Completion Rate', 'Process Improvement'],
                'competencies' => ['Programming', 'System Administration', 'Problem Solving', 'Team Leadership'],
                'responsibilities' => [
                    'Design and develop complex software solutions',
                    'Mentor junior developers',
                    'Lead technical architecture decisions'
                ]
            ],
            [
                'title' => 'Software Developer',
                'department' => 'Information Technology',
                'description' => 'Develops software applications and systems',
                'kpis' => ['Code Quality Score', 'Task Efficiency', 'Bug Resolution Time'],
                'competencies' => ['Programming', 'Problem Solving', 'Collaboration'],
                'responsibilities' => [
                    'Develop software applications',
                    'Write and maintain code documentation',
                    'Participate in code reviews'
                ]
            ],
            [
                'title' => 'Sales Representative',
                'department' => 'Sales',
                'description' => 'Responsible for sales activities and customer relationships',
                'kpis' => ['Monthly Sales Target', 'Customer Acquisition', 'Deal Closure Rate'],
                'competencies' => ['Verbal Communication', 'Customer Focus', 'Adaptability'],
                'responsibilities' => [
                    'Generate sales leads and close deals',
                    'Maintain customer relationships',
                    'Meet monthly sales targets'
                ]
            ],
            [
                'title' => 'HR Specialist',
                'department' => 'Human Resources',
                'description' => 'Handles HR operations and employee relations',
                'kpis' => ['Employee Engagement', 'Process Improvement', 'Customer Satisfaction'],
                'competencies' => ['Written Communication', 'Verbal Communication', 'Problem Solving'],
                'responsibilities' => [
                    'Manage employee relations and policies',
                    'Support recruitment and onboarding',
                    'Handle HR compliance and documentation'
                ]
            ],
            [
                'title' => 'Marketing Specialist',
                'department' => 'Marketing',
                'description' => 'Develops and executes marketing campaigns',
                'kpis' => ['Customer Acquisition', 'Process Improvement', 'Output Volume'],
                'competencies' => ['Creative Problem Solving', 'Written Communication', 'Data Analysis'],
                'responsibilities' => [
                    'Develop marketing campaigns and materials',
                    'Analyze market trends and customer data',
                    'Manage brand presence and messaging'
                ]
            ],
            [
                'title' => 'Financial Analyst',
                'department' => 'Finance',
                'description' => 'Analyzes financial data and supports decision making',
                'kpis' => ['Data Analysis', 'Task Efficiency', 'Process Improvement'],
                'competencies' => ['Data Analysis', 'Analytical Thinking', 'Written Communication'],
                'responsibilities' => [
                    'Analyze financial data and trends',
                    'Prepare financial reports and forecasts',
                    'Support budgeting and planning processes'
                ]
            ]
        ];
        
        foreach ($templates as $template) {
            // Create job template
            $data = [
                'position_title' => $template['title'],
                'department' => $template['department'],
                'description' => $template['description'],
                'created_by' => 1
            ];
            
            $templateId = $jobTemplateClass->createJobTemplate($data);
            $this->jobTemplateIds[$template['title']] = $templateId;
            echo "  ✓ Created job template: {$template['title']}\n";
            
            // Add KPIs to template
            foreach ($template['kpis'] as $kpiName) {
                if (isset($this->kpiIds[$kpiName])) {
                    $targetValue = $this->getRandomKPITarget($kpiName);
                    $jobTemplateClass->addKPIToTemplate($templateId, $this->kpiIds[$kpiName], $targetValue, 100.0);
                }
            }
            
            // Add competencies to template
            foreach ($template['competencies'] as $compName) {
                if (isset($this->competencyIds[$compName])) {
                    $requiredLevel = $this->getRandomCompetencyLevel();
                    $jobTemplateClass->addCompetencyToTemplate($templateId, $this->competencyIds[$compName], $requiredLevel, 100.0);
                }
            }
            
            // Add responsibilities to template
            foreach ($template['responsibilities'] as $index => $responsibility) {
                $jobTemplateClass->addResponsibilityToTemplate($templateId, $responsibility, $index + 1, 100.0);
            }
            
            // Add all company values to template
            foreach ($this->valueIds as $valueName => $valueId) {
                $jobTemplateClass->addValueToTemplate($templateId, $valueId, 100.0);
            }
        }
        
        echo "✓ Job templates created\n\n";
    }
    
    /**
     * Get random KPI target value based on KPI type
     */
    private function getRandomKPITarget($kpiName) {
        $targets = [
            'Monthly Sales Target' => rand(40000, 80000),
            'Customer Acquisition' => rand(5, 20),
            'Deal Closure Rate' => rand(60, 90),
            'Customer Retention' => rand(85, 95),
            'Code Quality Score' => rand(80, 95),
            'Bug Resolution Time' => rand(4, 24),
            'Customer Satisfaction' => rand(80, 95),
            'Defect Rate' => rand(1, 5),
            'Project Completion Rate' => rand(85, 95),
            'Task Efficiency' => rand(15, 25),
            'Output Volume' => rand(20, 40),
            'Utilization Rate' => rand(75, 90),
            'Team Development' => rand(80, 95),
            'Strategic Planning' => rand(80, 95),
            'Decision Making' => rand(80, 95),
            'Employee Engagement' => rand(80, 95),
            'Process Improvement' => rand(2, 8),
            'New Ideas Implementation' => rand(60, 80)
        ];
        
        return $targets[$kpiName] ?? rand(70, 90);
    }
    
    /**
     * Get random competency level
     */
    private function getRandomCompetencyLevel() {
        $levels = ['basic', 'intermediate', 'advanced', 'expert'];
        return $levels[array_rand($levels)];
    }
    
    /**
     * Create users and employees
     */
    private function createUsersAndEmployees() {
        echo "👥 Creating users and employees...\n";
        
        $userClass = new User();
        $employeeClass = new Employee();
        
        // Create HR Admin
        $this->createHRAdmin($userClass, $employeeClass);
        
        // Create departments (after HR admin exists for created_by reference)
        $this->createDepartments();
        
        // Create Department Managers (will get job templates assigned later)
        $this->createDepartmentManagers($userClass, $employeeClass);
        
        // Create Employees (will get job templates assigned later)
        $this->createRegularEmployees($userClass, $employeeClass);
        
        echo "✓ Users and employees created\n\n";
    }
    
    /**
     * Create HR Admin user
     */
    private function createHRAdmin($userClass, $employeeClass) {
        $userData = [
            'username' => 'admin.system',
            'email' => 'admin@company.com',
            'password' => 'admin123',
            'role' => 'hr_admin'
        ];
        
        $userId = $userClass->createUser($userData);
        $this->userIds['admin.system'] = $userId;
        $this->credentials[] = [
            'username' => 'admin.system',
            'password' => 'admin123',
            'role' => 'hr_admin',
            'name' => 'System Administrator'
        ];
        
        $employeeData = [
            'user_id' => $userId,
            'employee_number' => 'EMP001',
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'position' => 'HR Administrator',
            'department' => 'Human Resources',
            'hire_date' => date('Y-m-d', strtotime('-2 years')),
            'job_template_id' => $this->jobTemplateIds['Department Manager'] ?? null
        ];
        
        $employeeId = $employeeClass->createEmployee($employeeData);
        $this->employeeIds['admin.system'] = $employeeId;
        
        echo "  ✓ Created HR Admin: admin.system\n";
    }
    
    /**
     * Create department managers
     */
    private function createDepartmentManagers($userClass, $employeeClass) {
        $departments = array_keys($this->departments);
        $managerNames = [
            ['Michael', 'Smith'],
            ['Sarah', 'Johnson'],
            ['David', 'Williams'],
            ['Lisa', 'Brown'],
            ['Robert', 'Jones'],
            ['Emily', 'Garcia']
        ];
        
        foreach ($departments as $index => $department) {
            if ($index >= count($managerNames)) break;
            
            $firstName = $managerNames[$index][0];
            $lastName = $managerNames[$index][1];
            $username = 'manager.' . strtolower($lastName);
            
            $userData = [
                'username' => $username,
                'email' => strtolower($firstName . '.' . $lastName) . '@company.com',
                'password' => 'manager123',
                'role' => 'manager'
            ];
            
            $userId = $userClass->createUser($userData);
            $this->userIds[$username] = $userId;
            $this->credentials[] = [
                'username' => $username,
                'password' => 'manager123',
                'role' => 'manager',
                'name' => "$firstName $lastName",
                'department' => $department
            ];
            
            $employeeData = [
                'user_id' => $userId,
                'employee_number' => 'MGR' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'position' => "$department Manager",
                'department' => $department,
                'hire_date' => date('Y-m-d', strtotime('-' . rand(1, 5) . ' years')),
                'job_template_id' => $this->jobTemplateIds['Department Manager'] ?? null
            ];
            
            $employeeId = $employeeClass->createEmployee($employeeData);
            $this->employeeIds[$username] = $employeeId;
            
            echo "  ✓ Created manager: $username ($firstName $lastName - $department)\n";
        }
    }
    
    /**
     * Create regular employees
     */
    private function createRegularEmployees($userClass, $employeeClass) {
        $departments = array_keys($this->departments);
        $employeesPerDept = 4; // 4 employees per department
        $employeeCount = 1;
        
        echo "  🔍 Available job template IDs: " . json_encode($this->jobTemplateIds) . "\n";
        
        foreach ($departments as $department) {
            for ($i = 0; $i < $employeesPerDept; $i++) {
                $firstName = $this->firstNames[array_rand($this->firstNames)];
                $lastName = $this->lastNames[array_rand($this->lastNames)];
                $username = strtolower($firstName . '.' . $lastName);
                
                // Ensure unique username and email
                $originalUsername = $username;
                $counter = 1;
                while (isset($this->userIds[$username]) ||
                       fetchOne("SELECT user_id FROM users WHERE username = ? OR email = ?",
                               [$username, $username . '@company.com'])) {
                    $username = $originalUsername . $counter;
                    $counter++;
                }
                
                $userData = [
                    'username' => $username,
                    'email' => $username . '@company.com',
                    'password' => 'employee123',
                    'role' => 'employee'
                ];
                
                echo "  🔍 Attempting to create user: $username\n";
                try {
                    $userId = $userClass->createUser($userData);
                } catch (Exception $e) {
                    echo "  ❌ Failed to create user $username: " . $e->getMessage() . "\n";
                    
                    // Check if user actually exists in database
                    $existingUser = fetchOne("SELECT username, email FROM users WHERE username = ? OR email = ?",
                                           [$username, $username . '@company.com']);
                    if ($existingUser) {
                        echo "  🔍 Found existing user in DB: " . json_encode($existingUser) . "\n";
                    } else {
                        echo "  🔍 No existing user found in DB\n";
                    }
                    throw $e;
                }
                $this->userIds[$username] = $userId;
                $this->credentials[] = [
                    'username' => $username,
                    'password' => 'employee123',
                    'role' => 'employee',
                    'name' => "$firstName $lastName",
                    'department' => $department
                ];
                
                // Get manager for this department
                $managerEmployeeId = $this->getManagerForDepartment($department);
                
                // Assign appropriate job template based on department
                $jobTemplate = $this->getJobTemplateForDepartment($department, $i);
                echo "  🔍 Assigning job template for $username ($department, position $i): " .
                     ($jobTemplate ? "ID $jobTemplate" : "NULL") . "\n";
                
                $employeeData = [
                    'user_id' => $userId,
                    'employee_number' => 'EMP' . str_pad($employeeCount + 1, 3, '0', STR_PAD_LEFT),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'position' => $this->getPositionForDepartment($department, $i),
                    'department' => $department,
                    'manager_id' => $managerEmployeeId,
                    'hire_date' => date('Y-m-d', strtotime('-' . rand(6, 36) . ' months')),
                    'job_template_id' => $jobTemplate
                ];
                
                $employeeId = $employeeClass->createEmployee($employeeData);
                $this->employeeIds[$username] = $employeeId;
                
                $employeeCount++;
                echo "  ✓ Created employee: $username ($firstName $lastName - $department)\n";
            }
        }
    }
    
    /**
     * Get manager employee ID for department
     */
    private function getManagerForDepartment($department) {
        $managerUsernames = [
            'Information Technology' => 'manager.smith',
            'Human Resources' => 'manager.johnson',
            'Sales' => 'manager.williams',
            'Marketing' => 'manager.brown',
            'Finance' => 'manager.jones',
            'Operations' => 'manager.garcia'
        ];
        
        $managerUsername = $managerUsernames[$department] ?? null;
        return $managerUsername ? $this->employeeIds[$managerUsername] : null;
    }
    
    /**
     * Get job template for department and position
     */
    private function getJobTemplateForDepartment($department, $position) {
        $templates = [
            'Information Technology' => [
                'Senior Software Developer',
                'Software Developer',
                'Software Developer',
                'Software Developer'
            ],
            'Sales' => [
                'Sales Representative',
                'Sales Representative',
                'Sales Representative',
                'Sales Representative'
            ],
            'Human Resources' => [
                'HR Specialist',
                'HR Specialist',
                'HR Specialist',
                'HR Specialist'
            ],
            'Marketing' => [
                'Marketing Specialist',
                'Marketing Specialist',
                'Marketing Specialist',
                'Marketing Specialist'
            ],
            'Finance' => [
                'Financial Analyst',
                'Financial Analyst',
                'Financial Analyst',
                'Financial Analyst'
            ],
            'Operations' => [
                'Department Manager',
                'Department Manager',
                'Department Manager',
                'Department Manager'
            ]
        ];
        
        $deptTemplates = $templates[$department] ?? ['Department Manager'];
        $templateName = $deptTemplates[$position] ?? $deptTemplates[0];
        
        return $this->jobTemplateIds[$templateName] ?? null;
    }
    
    /**
     * Get position title for department and level
     */
    private function getPositionForDepartment($department, $level) {
        $positions = [
            'Information Technology' => [
                'Senior Developer',
                'Software Developer',
                'Junior Developer',
                'DevOps Engineer'
            ],
            'Sales' => [
                'Senior Sales Representative',
                'Sales Representative',
                'Sales Coordinator',
                'Sales Associate'
            ],
            'Human Resources' => [
                'HR Specialist',
                'HR Coordinator',
                'Recruiter',
                'HR Assistant'
            ],
            'Marketing' => [
                'Marketing Specialist',
                'Marketing Coordinator',
                'Content Creator',
                'Marketing Assistant'
            ],
            'Finance' => [
                'Senior Financial Analyst',
                'Financial Analyst',
                'Accountant',
                'Finance Assistant'
            ],
            'Operations' => [
                'Operations Specialist',
                'Operations Coordinator',
                'Process Analyst',
                'Operations Assistant'
            ]
        ];
        
        $deptPositions = $positions[$department] ?? ['Specialist'];
        return $deptPositions[$level] ?? $deptPositions[0];
    }
    
    /**
     * Create evaluation periods
     */
    private function createEvaluationPeriods() {
        echo "📅 Creating evaluation periods...\n";
        
        $periodClass = new EvaluationPeriod();
        
        $periods = [
            [
                'period_name' => '2024 Q3 Performance Review',
                'period_type' => 'quarterly',
                'start_date' => '2024-07-01',
                'end_date' => '2024-09-30',
                'status' => 'completed',
                'description' => 'Third quarter 2024 performance evaluation period'
            ],
            [
                'period_name' => '2024 Q4 Performance Review',
                'period_type' => 'quarterly',
                'start_date' => '2024-10-01',
                'end_date' => '2024-12-31',
                'status' => 'active',
                'description' => 'Fourth quarter 2024 performance evaluation period'
            ],
            [
                'period_name' => '2025 Q1 Performance Review',
                'period_type' => 'quarterly',
                'start_date' => '2025-01-01',
                'end_date' => '2025-03-31',
                'status' => 'draft',
                'description' => 'First quarter 2025 performance evaluation period'
            ]
        ];
        
        foreach ($periods as $period) {
            $id = $periodClass->createPeriod($period);
            $this->periodIds[$period['period_name']] = $id;
            echo "  ✓ Created period: {$period['period_name']} ({$period['status']})\n";
        }
        
        echo "✓ Evaluation periods created\n\n";
    }
    
    /**
     * Create evaluations with realistic data
     */
    private function createEvaluations() {
        echo "📝 Creating evaluations...\n";
        
        $evaluationClass = new Evaluation();
        
        // Create evaluations for each period
        foreach ($this->periodIds as $periodName => $periodId) {
            echo "  📋 Creating evaluations for: $periodName\n";
            
            $this->createEvaluationsForPeriod($evaluationClass, $periodId, $periodName);
        }
        
        echo "✓ Evaluations created\n\n";
    }
    
    /**
     * Create evaluations for a specific period
     */
    private function createEvaluationsForPeriod($evaluationClass, $periodId, $periodName) {
        $statusDistribution = $this->getStatusDistributionForPeriod($periodName);
        $statusIndex = 0;
        
        foreach ($this->employeeIds as $username => $employeeId) {
            // Skip admin user
            if ($username === 'admin.system') continue;
            
            // Get evaluator (manager or HR admin)
            $evaluatorId = $this->getEvaluatorForEmployee($username);
            
            if (!$evaluatorId) continue;
            
            try {
                // Create evaluation
                $evaluationData = [
                    'employee_id' => $employeeId,
                    'evaluator_id' => $evaluatorId,
                    'period_id' => $periodId
                ];
                
                $evaluationId = $evaluationClass->createEvaluation($evaluationData);
                
                // Determine status for this evaluation
                $status = $statusDistribution[$statusIndex % count($statusDistribution)];
                $statusIndex++;
                
                // Populate evaluation data based on status
                $this->populateEvaluationData($evaluationClass, $evaluationId, $status, $periodName);
                
                echo "    ✓ Created evaluation for $username (status: $status)\n";
                
            } catch (Exception $e) {
                echo "    ❌ Failed to create evaluation for $username: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Get status distribution for period
     */
    private function getStatusDistributionForPeriod($periodName) {
        if (strpos($periodName, '2024 Q3') !== false) {
            // Previous period - mostly completed
            return ['approved', 'approved', 'approved', 'approved', 'reviewed'];
        } elseif (strpos($periodName, '2024 Q4') !== false) {
            // Current period - mixed status
            return ['approved', 'reviewed', 'submitted', 'draft', 'submitted'];
        } else {
            // Future period - mostly draft
            return ['draft', 'draft', 'draft', 'draft', 'draft'];
        }
    }
    
    /**
     * Get evaluator for employee
     */
    private function getEvaluatorForEmployee($username) {
        // Find employee's manager
        foreach ($this->credentials as $cred) {
            if ($cred['username'] === $username && isset($cred['department'])) {
                $department = $cred['department'];
                
                // Find manager for this department
                foreach ($this->credentials as $managerCred) {
                    if ($managerCred['role'] === 'manager' &&
                        isset($managerCred['department']) &&
                        $managerCred['department'] === $department) {
                        return $this->userIds[$managerCred['username']];
                    }
                }
            }
        }
        
        // Fallback to HR admin
        return $this->userIds['admin.system'];
    }
    
    /**
     * Populate evaluation with realistic data
     */
    private function populateEvaluationData($evaluationClass, $evaluationId, $status, $periodName) {
        // Only populate data for non-draft evaluations
        if ($status === 'draft') {
            return;
        }
        
        // Get evaluation details to access job template components
        $evaluation = $evaluationClass->getJobTemplateEvaluation($evaluationId);
        
        if (!$evaluation) {
            return;
        }
        
        // Populate KPI results
        $this->populateKPIResults($evaluationClass, $evaluationId, $evaluation['kpi_results'] ?? []);
        
        // Populate competency results
        $this->populateCompetencyResults($evaluationClass, $evaluationId, $evaluation['competency_results'] ?? []);
        
        // Populate responsibility results
        $this->populateResponsibilityResults($evaluationClass, $evaluationId, $evaluation['responsibility_results'] ?? []);
        
        // Populate value results
        $this->populateValueResults($evaluationClass, $evaluationId, $evaluation['value_results'] ?? []);
        
        // Update evaluation status and comments
        $this->updateEvaluationStatus($evaluationClass, $evaluationId, $status);
    }
    
    /**
     * Populate KPI results
     */
    private function populateKPIResults($evaluationClass, $evaluationId, $kpiResults) {
        foreach ($kpiResults as $kpi) {
            $targetValue = $kpi['target_value'];
            $achievedValue = $this->generateAchievedValue($targetValue, $kpi['kpi_name']);
            $score = $this->calculateKPIScore($targetValue, $achievedValue, $kpi['kpi_name']);
            
            $data = [
                'achieved_value' => $achievedValue,
                'score' => $score,
                'comments' => $this->generateKPIComment($score, $kpi['kpi_name'])
            ];
            
            $evaluationClass->updateKPIResult($evaluationId, $kpi['kpi_id'], $data);
        }
    }
    
    /**
     * Generate achieved value for KPI
     */
    private function generateAchievedValue($targetValue, $kpiName) {
        // Generate realistic variance around target
        $variance = rand(-20, 30) / 100; // -20% to +30% variance
        $achievedValue = $targetValue * (1 + $variance);
        
        // Round based on KPI type
        if (strpos($kpiName, 'Rate') !== false || strpos($kpiName, 'Percentage') !== false) {
            return round($achievedValue, 1);
        } elseif (strpos($kpiName, 'Target') !== false) {
            return round($achievedValue, 0);
        } else {
            return round($achievedValue, 2);
        }
    }
    
    /**
     * Calculate KPI score
     */
    private function calculateKPIScore($targetValue, $achievedValue, $kpiName) {
        if ($targetValue == 0) return 3.0;
        
        $percentage = ($achievedValue / $targetValue) * 100;
        
        // Adjust for "lower is better" KPIs
        $lowerBetterKPIs = ['Bug Resolution Time', 'Defect Rate'];
        if (in_array($kpiName, $lowerBetterKPIs)) {
            if ($achievedValue <= $targetValue) {
                $improvement = (($targetValue - $achievedValue) / $targetValue) * 100;
                if ($improvement >= 20) return 5.0;
                if ($improvement >= 10) return 4.5;
                if ($improvement >= 5) return 4.0;
                return 3.5;
            } else {
                return rand(20, 30) / 10; // 2.0-3.0 for worse performance
            }
        }
        
        // Standard "higher is better" scoring
        if ($percentage >= 120) return 5.0;
        if ($percentage >= 110) return 4.5;
        if ($percentage >= 100) return 4.0;
        if ($percentage >= 90) return 3.5;
        if ($percentage >= 80) return 3.0;
        if ($percentage >= 70) return 2.5;
        return 2.0;
    }
    
    /**
     * Generate KPI comment
     */
    private function generateKPIComment($score, $kpiName) {
        if ($score >= 4.0) {
            return $this->positiveComments[array_rand($this->positiveComments)];
        } elseif ($score >= 3.0) {
            return "Meets expectations for $kpiName. Consistent performance with room for improvement.";
        } else {
            return $this->improvementComments[array_rand($this->improvementComments)];
        }
    }
    
    /**
     * Populate competency results
     */
    private function populateCompetencyResults($evaluationClass, $evaluationId, $competencyResults) {
        $levels = ['basic', 'intermediate', 'advanced', 'expert'];
        
        foreach ($competencyResults as $competency) {
            $requiredLevel = $competency['required_level'];
            $achievedLevel = $this->generateAchievedLevel($requiredLevel);
            $score = $this->calculateCompetencyScore($requiredLevel, $achievedLevel);
            
            $data = [
                'achieved_level' => $achievedLevel,
                'score' => $score,
                'comments' => $this->generateCompetencyComment($score, $competency['competency_name'])
            ];
            
            $evaluationClass->updateCompetencyResult($evaluationId, $competency['competency_id'], $data);
        }
    }
    
    /**
     * Generate achieved competency level
     */
    private function generateAchievedLevel($requiredLevel) {
        $levels = ['basic', 'intermediate', 'advanced', 'expert'];
        $requiredIndex = array_search($requiredLevel, $levels);
        
        // 70% chance to meet or exceed, 30% chance to be below
        if (rand(1, 100) <= 70) {
            // Meet or exceed
            $achievedIndex = rand($requiredIndex, count($levels) - 1);
        } else {
            // Below required
            $achievedIndex = rand(0, max(0, $requiredIndex - 1));
        }
        
        return $levels[$achievedIndex];
    }
    
    /**
     * Calculate competency score
     */
    private function calculateCompetencyScore($requiredLevel, $achievedLevel) {
        $levels = ['basic' => 1, 'intermediate' => 2, 'advanced' => 3, 'expert' => 4];
        
        $requiredScore = $levels[$requiredLevel];
        $achievedScore = $levels[$achievedLevel];
        
        if ($achievedScore >= $requiredScore) {
            $ratio = $achievedScore / $requiredScore;
            if ($ratio >= 1.5) return 5.0;
            if ($ratio >= 1.25) return 4.5;
            return 4.0;
        } else {
            $ratio = $achievedScore / $requiredScore;
            if ($ratio >= 0.75) return 3.0;
            if ($ratio >= 0.5) return 2.5;
            return 2.0;
        }
    }
    
    /**
     * Generate competency comment
     */
    private function generateCompetencyComment($score, $competencyName) {
        if ($score >= 4.0) {
            return "Strong performance in $competencyName. " . $this->positiveComments[array_rand($this->positiveComments)];
        } elseif ($score >= 3.0) {
            return "Adequate performance in $competencyName. Meets basic requirements.";
        } else {
            return "Needs improvement in $competencyName. " . $this->improvementComments[array_rand($this->improvementComments)];
        }
    }
    
    /**
     * Populate responsibility results
     */
    private function populateResponsibilityResults($evaluationClass, $evaluationId, $responsibilityResults) {
        foreach ($responsibilityResults as $responsibility) {
            $score = $this->generateRandomScore();
            
            $data = [
                'score' => $score,
                'comments' => $this->generateResponsibilityComment($score, $responsibility['responsibility_text'])
            ];
            
            $evaluationClass->updateResponsibilityResult($evaluationId, $responsibility['responsibility_id'], $data);
        }
    }
    
    /**
     * Populate value results
     */
    private function populateValueResults($evaluationClass, $evaluationId, $valueResults) {
        foreach ($valueResults as $value) {
            $score = $this->generateRandomScore();
            
            $data = [
                'score' => $score,
                'comments' => $this->generateValueComment($score, $value['value_name'])
            ];
            
            $evaluationClass->updateValueResult($evaluationId, $value['value_id'], $data);
        }
    }
    
    /**
     * Generate random score with realistic distribution
     */
    private function generateRandomScore() {
        $rand = rand(1, 100);
        
        if ($rand <= 20) return 5.0; // 20% excellent
        if ($rand <= 70) return rand(35, 44) / 10; // 50% good (3.5-4.4)
        if ($rand <= 95) return rand(25, 34) / 10; // 25% satisfactory (2.5-3.4)
        return rand(15, 24) / 10; // 5% needs improvement (1.5-2.4)
    }
    
    /**
     * Generate responsibility comment
     */
    private function generateResponsibilityComment($score, $responsibilityText) {
        $shortText = substr($responsibilityText, 0, 50);
        
        if ($score >= 4.0) {
            return "Excellent performance in: $shortText. " . $this->positiveComments[array_rand($this->positiveComments)];
        } elseif ($score >= 3.0) {
            return "Good performance in: $shortText. Meets expectations consistently.";
        } else {
            return "Needs improvement in: $shortText. " . $this->improvementComments[array_rand($this->improvementComments)];
        }
    }
    
    /**
     * Generate value comment
     */
    private function generateValueComment($score, $valueName) {
        if ($score >= 4.0) {
            return "Strongly demonstrates $valueName. " . $this->positiveComments[array_rand($this->positiveComments)];
        } elseif ($score >= 3.0) {
            return "Adequately demonstrates $valueName in daily work.";
        } else {
            return "Needs to better demonstrate $valueName. " . $this->improvementComments[array_rand($this->improvementComments)];
        }
    }
    
    /**
     * Update evaluation status and overall comments
     */
    private function updateEvaluationStatus($evaluationClass, $evaluationId, $status) {
        $overallComments = $this->generateOverallComment($status);
        
        $data = [
            'status' => $status,
            'overall_comments' => $overallComments,
            'goals_next_period' => $this->generateGoals(),
            'development_areas' => $this->generateDevelopmentAreas(),
            'strengths' => $this->generateStrengths()
        ];
        
        $evaluationClass->updateEvaluation($evaluationId, $data);
    }
    
    /**
     * Generate overall comment based on status
     */
    private function generateOverallComment($status) {
        $comments = [
            'approved' => 'Overall performance meets expectations. Employee demonstrates consistent results and positive contribution to team goals.',
            'reviewed' => 'Performance review completed. Employee shows good progress with identified areas for continued development.',
            'submitted' => 'Evaluation submitted for review. Performance demonstrates solid contribution with opportunities for growth.'
        ];
        
        return $comments[$status] ?? 'Evaluation in progress.';
    }
    
    /**
     * Generate goals for next period
     */
    private function generateGoals() {
        $goals = [
            'Improve technical skills through training and certification programs',
            'Take on additional leadership responsibilities within the team',
            'Enhance communication and collaboration with cross-functional teams',
            'Focus on process improvement and efficiency optimization',
            'Develop expertise in emerging technologies relevant to role',
            'Increase customer satisfaction scores through improved service delivery',
            'Lead a major project or initiative to demonstrate growth',
            'Mentor junior team members and contribute to knowledge sharing'
        ];
        
        return $goals[array_rand($goals)];
    }
    
    /**
     * Generate development areas
     */
    private function generateDevelopmentAreas() {
        $areas = [
            'Time management and prioritization of competing demands',
            'Public speaking and presentation skills development',
            'Advanced technical skills in emerging technologies',
            'Strategic thinking and long-term planning capabilities',
            'Cross-functional collaboration and stakeholder management',
            'Data analysis and decision-making based on metrics',
            'Leadership and team management skills',
            'Customer service and relationship building'
        ];
        
        return $areas[array_rand($areas)];
    }
    
    /**
     * Generate strengths
     */
    private function generateStrengths() {
        $strengths = [
            'Strong technical expertise and problem-solving abilities',
            'Excellent communication and interpersonal skills',
            'Reliable and consistent performance in all assignments',
            'Positive attitude and willingness to take on new challenges',
            'Strong attention to detail and quality focus',
            'Effective collaboration and team player mentality',
            'Adaptability and flexibility in changing environments',
            'Customer-focused approach and service orientation'
        ];
        
        return $strengths[array_rand($strengths)];
    }
    
    /**
     * Generate comprehensive documentation
     */
    private function generateDocumentation() {
        echo "📚 Generating documentation...\n";
        
        $this->generateCredentialsFile();
        $this->generateSummaryFile();
        
        echo "✓ Documentation generated\n";
    }
    
    /**
     * Generate credentials file
     */
    private function generateCredentialsFile() {
        $content = "=== TEST USER CREDENTIALS ===\n";
        $content .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Group by role
        $roleGroups = [
            'hr_admin' => 'HR ADMINISTRATORS',
            'manager' => 'DEPARTMENT MANAGERS',
            'employee' => 'EMPLOYEES'
        ];
        
        foreach ($roleGroups as $role => $title) {
            $content .= "$title:\n";
            $content .= str_repeat('-', strlen($title)) . "\n";
            
            foreach ($this->credentials as $cred) {
                if ($cred['role'] === $role) {
                    $content .= sprintf("Username: %-20s | Password: %-12s | Name: %s",
                        $cred['username'], $cred['password'], $cred['name']);
                    
                    if (isset($cred['department'])) {
                        $content .= " | Department: " . $cred['department'];
                    }
                    $content .= "\n";
                }
            }
            $content .= "\n";
        }
        
        $content .= "\nNOTES:\n";
        $content .= "- All passwords are for testing purposes only\n";
        $content .= "- Change passwords in production environment\n";
        $content .= "- HR Admin has full system access\n";
        $content .= "- Managers can manage their department employees\n";
        $content .= "- Employees can view their own evaluations\n";
        
        file_put_contents(__DIR__ . '/test_data_credentials.txt', $content);
        echo "  ✓ Created credentials file: test_data_credentials.txt\n";
    }
    
    /**
     * Generate summary file
     */
    private function generateSummaryFile() {
        $content = "=== TEST DATA SUMMARY ===\n";
        $content .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Count data
        $userCount = count($this->userIds);
        $employeeCount = count($this->employeeIds);
        $departmentCount = count($this->departmentIds);
        $kpiCount = count($this->kpiIds);
        $competencyCount = count($this->competencyIds);
        $valueCount = count($this->valueIds);
        $jobTemplateCount = count($this->jobTemplateIds);
        $periodCount = count($this->periodIds);
        
        // Count evaluations
        $evaluationCount = 0;
        foreach ($this->periodIds as $periodId) {
            $result = $this->pdo->query("SELECT COUNT(*) as count FROM evaluations WHERE period_id = $periodId");
            $evaluationCount += $result->fetch()['count'];
        }
        
        $content .= "USERS CREATED: $userCount\n";
        $content .= "- HR Admins: 1\n";
        $content .= "- Managers: " . count(array_filter($this->credentials, function($c) { return $c['role'] === 'manager'; })) . "\n";
        $content .= "- Employees: " . count(array_filter($this->credentials, function($c) { return $c['role'] === 'employee'; })) . "\n\n";
        
        $content .= "ORGANIZATIONAL STRUCTURE:\n";
        $content .= "- Departments: $departmentCount\n";
        $content .= "- Job Templates: $jobTemplateCount\n";
        $content .= "- Employees: $employeeCount\n\n";
        
        $content .= "EVALUATION FRAMEWORK:\n";
        $content .= "- KPIs: $kpiCount\n";
        $content .= "- Competencies: $competencyCount\n";
        $content .= "- Company Values: $valueCount\n\n";
        
        $content .= "EVALUATION DATA:\n";
        $content .= "- Evaluation Periods: $periodCount\n";
        $content .= "- Total Evaluations: $evaluationCount\n\n";
        
        $content .= "EVALUATION PERIODS:\n";
        foreach ($this->periodIds as $periodName => $periodId) {
            $result = $this->pdo->query("SELECT COUNT(*) as count FROM evaluations WHERE period_id = $periodId");
            $count = $result->fetch()['count'];
            $content .= "- $periodName: $count evaluations\n";
        }
        
        $content .= "\nDEPARTMENTS:\n";
        foreach (array_keys($this->departments) as $dept) {
            $content .= "- $dept\n";
        }
        
        $content .= "\nTESTING SCENARIOS:\n";
        $content .= "1. HR Admin Testing:\n";
        $content .= "   - Login as admin.system\n";
        $content .= "   - View all employees and evaluations\n";
        $content .= "   - Create new evaluation periods\n";
        $content .= "   - Generate reports\n\n";
        
        $content .= "2. Manager Testing:\n";
        $content .= "   - Login as any manager.* account\n";
        $content .= "   - View team members\n";
        $content .= "   - Create/edit evaluations for direct reports\n";
        $content .= "   - Review evaluation progress\n\n";
        
        $content .= "3. Employee Testing:\n";
        $content .= "   - Login as any employee account\n";
        $content .= "   - View personal evaluations\n";
        $content .= "   - Check performance history\n";
        $content .= "   - Update profile information\n";
        
        file_put_contents(__DIR__ . '/test_data_summary.txt', $content);
        echo "  ✓ Created summary file: test_data_summary.txt\n";
    }
}

// Check if script is run from command line
if (php_sapi_name() === 'cli') {
    // Command line execution
    $populator = new TestDataPopulator();
    $populator->run();
} else {
    // Web execution
    echo "<pre>";
    $populator = new TestDataPopulator();
    $populator->run();
    echo "</pre>";
}
?>
