<?php
/**
 * Phase 1 Test Data Population Script
 * 
 * Populates realistic test data for the continuous performance management system:
 * - 1:1 sessions with realistic scheduling patterns
 * - Feedback linked to competencies, KPIs, and values
 * - Evidence trails that demonstrate the continuous feedback loop
 * 
 * This script creates data that showcases the transformation from event-based
 * to continuous performance management.
 */

require_once __DIR__ . '/../config/database.php';

class Phase1TestDataPopulator {
    private $pdo;
    private $employees = [];
    private $managers = [];
    private $competencies = [];
    private $kpis = [];
    private $values = [];
    private $templates = [];
    
    public function __construct() {
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
    
    public function populate() {
        echo "=== Phase 1 Test Data Population ===\n\n";
        
        try {
            $this->loadExistingData();
            $this->create1to1Sessions();
            $this->createRealisticFeedback();
            $this->createFollowUpSessions();
            $this->demonstrateEvidenceAggregation();
            
            echo "\n=== Test Data Population Completed ===\n";
            $this->printSummary();
            
        } catch (Exception $e) {
            echo "ERROR: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function loadExistingData() {
        echo "Loading existing data...\n";
        
        // Load employees and their managers
        $stmt = $this->pdo->query("
            SELECT e.employee_id, e.first_name, e.last_name, e.manager_id, e.position,
                   m.first_name as manager_first_name, m.last_name as manager_last_name
            FROM employees e
            LEFT JOIN employees m ON e.manager_id = m.employee_id
            WHERE e.active = 1
        ");
        $this->employees = $stmt->fetchAll();
        
        // Separate managers
        $this->managers = array_filter($this->employees, function($emp) {
            return !empty($emp['manager_id']);
        });
        
        // Load competencies
        $stmt = $this->pdo->query("SELECT * FROM competencies WHERE is_active = 1");
        $this->competencies = $stmt->fetchAll();
        
        // Load KPIs
        $stmt = $this->pdo->query("SELECT * FROM company_kpis WHERE is_active = 1");
        $this->kpis = $stmt->fetchAll();
        
        // Load values
        $stmt = $this->pdo->query("SELECT * FROM company_values WHERE is_active = 1");
        $this->values = $stmt->fetchAll();
        
        // Load 1:1 templates
        $stmt = $this->pdo->query("SELECT * FROM one_to_one_templates WHERE is_active = 1");
        $this->templates = $stmt->fetchAll();
        
        echo "✓ Loaded " . count($this->employees) . " employees\n";
        echo "✓ Loaded " . count($this->managers) . " manager relationships\n";
        echo "✓ Loaded " . count($this->competencies) . " competencies\n";
        echo "✓ Loaded " . count($this->kpis) . " KPIs\n";
        echo "✓ Loaded " . count($this->values) . " values\n";
        echo "✓ Loaded " . count($this->templates) . " 1:1 templates\n\n";
    }
    
    private function create1to1Sessions() {
        echo "Creating realistic 1:1 sessions...\n";
        
        if (empty($this->managers)) {
            echo "⚠ No manager-employee relationships found. Creating sample relationships...\n";
            $this->createSampleManagerRelationships();
        }
        
        $sessionStmt = $this->pdo->prepare("
            INSERT INTO one_to_one_sessions 
            (employee_id, manager_id, scheduled_date, actual_date, duration_minutes, status, 
             meeting_notes, agenda_items, action_items, follow_up_required, next_session_scheduled)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $sessionsCreated = 0;
        
        foreach ($this->managers as $employee) {
            // Create 6 months of weekly 1:1s (showing continuous pattern)
            for ($week = 24; $week >= 0; $week--) {
                $scheduledDate = date('Y-m-d H:i:s', strtotime("-{$week} weeks Monday 10:00"));
                $actualDate = $this->calculateActualDate($scheduledDate, $week);
                $status = $week > 2 ? 'completed' : ($week > 0 ? 'scheduled' : 'completed');
                
                // Vary session content based on week pattern
                $sessionData = $this->generateSessionContent($employee, $week, $status);
                
                $sessionStmt->execute([
                    $employee['employee_id'],
                    $employee['manager_id'],
                    $scheduledDate,
                    $actualDate,
                    $sessionData['duration'],
                    $status,
                    $sessionData['notes'],
                    json_encode($sessionData['agenda']),
                    json_encode($sessionData['actions']),
                    $sessionData['follow_up_required'],
                    $week > 0 ? date('Y-m-d H:i:s', strtotime("-" . ($week-1) . " weeks Monday 10:00")) : null
                ]);
                
                $sessionsCreated++;
            }
        }
        
        echo "✓ Created {$sessionsCreated} 1:1 sessions\n\n";
    }
    
    private function calculateActualDate($scheduledDate, $week) {
        // Simulate realistic scheduling patterns
        if ($week > 20) {
            // Older sessions - some rescheduled
            return rand(0, 10) < 2 ? 
                date('Y-m-d H:i:s', strtotime($scheduledDate . ' +2 days')) : 
                $scheduledDate;
        } elseif ($week > 2) {
            // Recent sessions - mostly on time
            return rand(0, 10) < 1 ? 
                date('Y-m-d H:i:s', strtotime($scheduledDate . ' +1 day')) : 
                $scheduledDate;
        } else {
            // Future sessions
            return null;
        }
    }
    
    private function generateSessionContent($employee, $week, $status) {
        $templates = [
            'weekly' => [
                'duration' => 30,
                'agenda' => [
                    ['section' => 'Goal Progress', 'time_minutes' => 10],
                    ['section' => 'Current Projects', 'time_minutes' => 10],
                    ['section' => 'Feedback Exchange', 'time_minutes' => 5],
                    ['section' => 'Next Steps', 'time_minutes' => 5]
                ]
            ],
            'monthly' => [
                'duration' => 45,
                'agenda' => [
                    ['section' => 'Monthly Review', 'time_minutes' => 15],
                    ['section' => 'Development Goals', 'time_minutes' => 15],
                    ['section' => 'Career Discussion', 'time_minutes' => 10],
                    ['section' => 'Action Planning', 'time_minutes' => 5]
                ]
            ]
        ];
        
        $isMonthlySession = ($week % 4 == 0);
        $template = $isMonthlySession ? $templates['monthly'] : $templates['weekly'];
        
        // Generate realistic notes based on session type and employee
        $notes = $this->generateSessionNotes($employee, $week, $isMonthlySession);
        
        // Generate action items
        $actions = $this->generateActionItems($employee, $week);
        
        return [
            'duration' => $template['duration'],
            'agenda' => $template['agenda'],
            'notes' => $notes,
            'actions' => $actions,
            'follow_up_required' => !empty($actions) && rand(0, 10) < 3
        ];
    }
    
    private function generateSessionNotes($employee, $week, $isMonthly) {
        $notes = [];
        
        if ($isMonthly) {
            $notes[] = "Monthly development-focused 1:1 with {$employee['first_name']}.";
            $notes[] = "Discussed progress on quarterly goals and upcoming development opportunities.";
            if ($week < 12) {
                $notes[] = "Strong performance on current projects. Showing growth in leadership competencies.";
            }
        } else {
            $notes[] = "Weekly check-in with {$employee['first_name']}.";
            $notes[] = "Reviewed current project status and upcoming deadlines.";
            
            // Add variety based on week
            if ($week % 3 == 0) {
                $notes[] = "Discussed challenges with stakeholder communication - provided coaching.";
            } elseif ($week % 5 == 0) {
                $notes[] = "Celebrated successful completion of Q3 deliverable ahead of schedule.";
            }
        }
        
        return implode(' ', $notes);
    }
    
    private function generateActionItems($employee, $week) {
        $actions = [];
        
        // Generate realistic action items
        if ($week % 4 == 0) {
            $actions[] = [
                'item' => 'Complete leadership training module',
                'owner' => 'employee',
                'due_date' => date('Y-m-d', strtotime("+2 weeks")),
                'status' => 'pending'
            ];
        }
        
        if ($week % 6 == 0) {
            $actions[] = [
                'item' => 'Schedule feedback session with project stakeholders',
                'owner' => 'manager',
                'due_date' => date('Y-m-d', strtotime("+1 week")),
                'status' => 'pending'
            ];
        }
        
        if (rand(0, 10) < 3) {
            $actions[] = [
                'item' => 'Follow up on development goal progress',
                'owner' => 'both',
                'due_date' => date('Y-m-d', strtotime("+1 week")),
                'status' => 'pending'
            ];
        }
        
        return $actions;
    }
    
    private function createRealisticFeedback() {
        echo "Creating realistic feedback linked to sessions...\n";
        
        // Get all completed sessions
        $stmt = $this->pdo->query("
            SELECT session_id, employee_id, manager_id, actual_date 
            FROM one_to_one_sessions 
            WHERE status = 'completed'
            ORDER BY actual_date DESC
        ");
        $sessions = $stmt->fetchAll();
        
        $feedbackStmt = $this->pdo->prepare("
            INSERT INTO one_to_one_feedback 
            (session_id, given_by, receiver_id, feedback_type, content, 
             related_competency_id, related_kpi_id, related_value_id, 
             urgency, requires_follow_up)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $feedbackCreated = 0;
        
        foreach ($sessions as $session) {
            // Get manager's user_id
            $managerStmt = $this->pdo->prepare("SELECT user_id FROM employees WHERE employee_id = ?");
            $managerStmt->execute([$session['manager_id']]);
            $managerUserId = $managerStmt->fetchColumn();
            
            if (!$managerUserId) continue;
            
            // Create 1-3 feedback items per session
            $feedbackCount = rand(1, 3);
            
            for ($i = 0; $i < $feedbackCount; $i++) {
                $feedback = $this->generateRealisticFeedback($session);
                
                $feedbackStmt->execute([
                    $session['session_id'],
                    $managerUserId,
                    $session['employee_id'],
                    $feedback['type'],
                    $feedback['content'],
                    $feedback['competency_id'],
                    $feedback['kpi_id'],
                    $feedback['value_id'],
                    $feedback['urgency'],
                    $feedback['requires_follow_up']
                ]);
                
                $feedbackCreated++;
            }
        }
        
        echo "✓ Created {$feedbackCreated} feedback items\n\n";
    }
    
    private function generateRealisticFeedback($session) {
        $feedbackTypes = ['positive', 'constructive', 'development', 'goal_progress'];
        $type = $feedbackTypes[array_rand($feedbackTypes)];
        
        // Select random competency, KPI, or value to link to
        $competencyId = !empty($this->competencies) ? $this->competencies[array_rand($this->competencies)]['id'] : null;
        $kpiId = !empty($this->kpis) ? $this->kpis[array_rand($this->kpis)]['id'] : null;
        $valueId = !empty($this->values) ? $this->values[array_rand($this->values)]['id'] : null;
        
        // Randomly choose which to link (or none for general feedback)
        $linkChoice = rand(1, 4);
        $linkedCompetency = $linkChoice == 1 ? $competencyId : null;
        $linkedKpi = $linkChoice == 2 ? $kpiId : null;
        $linkedValue = $linkChoice == 3 ? $valueId : null;
        
        $content = $this->generateFeedbackContent($type, $linkedCompetency, $linkedKpi, $linkedValue);
        
        return [
            'type' => $type,
            'content' => $content,
            'competency_id' => $linkedCompetency,
            'kpi_id' => $linkedKpi,
            'value_id' => $linkedValue,
            'urgency' => $type == 'constructive' ? 'medium' : 'low',
            'requires_follow_up' => $type == 'development' || ($type == 'constructive' && rand(0, 10) < 3)
        ];
    }
    
    private function generateFeedbackContent($type, $competencyId, $kpiId, $valueId) {
        $templates = [
            'positive' => [
                'Excellent work on the recent project delivery. Your attention to detail and proactive communication kept the team aligned.',
                'Really impressed with how you handled the client presentation. Your preparation and confidence showed through.',
                'Great job mentoring the new team member. Your patience and clear explanations are helping them ramp up quickly.',
                'Your problem-solving approach on the technical challenge was innovative and effective.'
            ],
            'constructive' => [
                'Consider improving the frequency of status updates during project execution. More regular communication would help the team stay aligned.',
                'The presentation content was solid, but working on delivery confidence would make an even stronger impact.',
                'While the solution works, exploring more scalable approaches could benefit future similar challenges.',
                'Documentation quality could be enhanced to help team members better understand the implementation.'
            ],
            'development' => [
                'Opportunity to develop leadership skills by taking point on the next cross-functional initiative.',
                'Consider pursuing advanced training in data analysis to support your career growth goals.',
                'Would benefit from shadowing senior team members during client interactions to build consulting skills.',
                'Recommend focusing on strategic thinking development through involvement in planning sessions.'
            ],
            'goal_progress' => [
                'Making solid progress on Q3 objectives. On track to exceed the target metrics by month end.',
                'Development goal around public speaking is progressing well. Recent presentation showed marked improvement.',
                'Technical certification study plan is on schedule. Exam preparation appears thorough.',
                'Leadership development activities are yielding positive results. Team feedback has been consistently positive.'
            ]
        ];
        
        $baseContent = $templates[$type][array_rand($templates[$type])];
        
        // Add specific context based on what's linked
        if ($competencyId) {
            $comp = array_filter($this->competencies, function($c) use ($competencyId) {
                return $c['id'] == $competencyId;
            });
            if (!empty($comp)) {
                $compName = array_values($comp)[0]['competency_name'];
                $baseContent .= " This relates to your development in {$compName}.";
            }
        }
        
        if ($kpiId) {
            $kpi = array_filter($this->kpis, function($k) use ($kpiId) {
                return $k['id'] == $kpiId;
            });
            if (!empty($kpi)) {
                $kpiName = array_values($kpi)[0]['kpi_name'];
                $baseContent .= " This impacts your performance on {$kpiName}.";
            }
        }
        
        if ($valueId) {
            $value = array_filter($this->values, function($v) use ($valueId) {
                return $v['id'] == $valueId;
            });
            if (!empty($value)) {
                $valueName = array_values($value)[0]['value_name'];
                $baseContent .= " This demonstrates our value of {$valueName}.";
            }
        }
        
        return $baseContent;
    }
    
    private function createFollowUpSessions() {
        echo "Creating follow-up sessions for high-priority feedback...\n";
        
        // Find feedback that requires follow-up
        $stmt = $this->pdo->query("
            SELECT f.*, s.employee_id, s.manager_id 
            FROM one_to_one_feedback f
            JOIN one_to_one_sessions s ON f.session_id = s.session_id
            WHERE f.requires_follow_up = 1 
            AND f.follow_up_completed = 0
            ORDER BY f.created_at DESC
            LIMIT 10
        ");
        $followUpNeeded = $stmt->fetchAll();
        
        $sessionStmt = $this->pdo->prepare("
            INSERT INTO one_to_one_sessions 
            (employee_id, manager_id, scheduled_date, actual_date, duration_minutes, status, 
             meeting_notes, agenda_items, follow_up_required)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $followUpStmt = $this->pdo->prepare("
            UPDATE one_to_one_feedback 
            SET follow_up_completed = 1, follow_up_notes = ?
            WHERE feedback_id = ?
        ");
        
        $followUpSessions = 0;
        
        foreach ($followUpNeeded as $feedback) {
            // Schedule follow-up session 1-2 weeks after original feedback
            $scheduledDate = date('Y-m-d H:i:s', strtotime($feedback['created_at'] . ' +1 week'));
            $actualDate = date('Y-m-d H:i:s', strtotime($scheduledDate . ' +1 hour'));
            
            $agenda = [
                ['section' => 'Follow-up Discussion', 'time_minutes' => 20],
                ['section' => 'Action Plan Review', 'time_minutes' => 10]
            ];
            
            $notes = "Follow-up session to discuss: " . substr($feedback['content'], 0, 100) . "...";
            
            $sessionStmt->execute([
                $feedback['employee_id'],
                $feedback['manager_id'],
                $scheduledDate,
                $actualDate,
                30,
                'completed',
                $notes,
                json_encode($agenda),
                false
            ]);
            
            // Mark original feedback as followed up
            $followUpStmt->execute([
                "Addressed in follow-up session on " . date('Y-m-d', strtotime($actualDate)),
                $feedback['feedback_id']
            ]);
            
            $followUpSessions++;
        }
        
        echo "✓ Created {$followUpSessions} follow-up sessions\n\n";
    }
    
    private function demonstrateEvidenceAggregation() {
        echo "Demonstrating evidence aggregation capabilities...\n";
        
        // Test the stored procedure for evidence aggregation
        if (!empty($this->managers)) {
            $employee = $this->managers[0];
            $periodStart = date('Y-m-d', strtotime('-3 months'));
            $periodEnd = date('Y-m-d');
            
            echo "Testing evidence aggregation for {$employee['first_name']} {$employee['last_name']}...\n";
            
            $stmt = $this->pdo->prepare("CALL sp_aggregate_1to1_evidence(?, ?, ?)");
            $stmt->execute([$employee['employee_id'], $periodStart, $periodEnd]);
            $result = $stmt->fetch();
            
            if ($result) {
                echo "✓ Evidence aggregation successful:\n";
                echo "  - Total sessions: {$result['total_sessions']}\n";
                echo "  - Total feedback items: {$result['total_feedback_items']}\n";
                echo "  - Competency evidence: " . substr($result['competency_evidence'] ?? 'None', 0, 100) . "...\n";
                echo "  - KPI evidence: " . substr($result['kpi_evidence'] ?? 'None', 0, 100) . "...\n";
            }
        }
        
        echo "\n";
    }
    
    private function createSampleManagerRelationships() {
        // Create basic manager relationships if none exist
        $employees = array_slice($this->employees, 0, 6);
        
        if (count($employees) >= 2) {
            // Make first employee a manager of others
            $managerId = $employees[0]['employee_id'];
            
            $updateStmt = $this->pdo->prepare("UPDATE employees SET manager_id = ? WHERE employee_id = ?");
            
            for ($i = 1; $i < count($employees); $i++) {
                $updateStmt->execute([$managerId, $employees[$i]['employee_id']]);
                $employees[$i]['manager_id'] = $managerId;
            }
            
            $this->managers = array_slice($employees, 1);
            echo "✓ Created sample manager relationships\n";
        }
    }
    
    private function printSummary() {
        echo "\n=== Test Data Summary ===\n";
        
        // Count sessions
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM one_to_one_sessions");
        $sessionCount = $stmt->fetchColumn();
        echo "1:1 Sessions: {$sessionCount}\n";
        
        // Count feedback
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM one_to_one_feedback");
        $feedbackCount = $stmt->fetchColumn();
        echo "Feedback Items: {$feedbackCount}\n";
        
        // Count by feedback type
        $stmt = $this->pdo->query("
            SELECT feedback_type, COUNT(*) as count 
            FROM one_to_one_feedback 
            GROUP BY feedback_type
        ");
        $feedbackTypes = $stmt->fetchAll();
        echo "\nFeedback by Type:\n";
        foreach ($feedbackTypes as $type) {
            echo "  - {$type['feedback_type']}: {$type['count']}\n";
        }
        
        // Show evidence aggregation example
        echo "\nSample Evidence Aggregation:\n";
        $stmt = $this->pdo->query("
            SELECT 
                e.first_name, e.last_name,
                COUNT(DISTINCT s.session_id) as sessions,
                COUNT(f.feedback_id) as feedback_items
            FROM employees e
            JOIN one_to_one_sessions s ON e.employee_id = s.employee_id
            LEFT JOIN one_to_one_feedback f ON s.session_id = f.session_id
            WHERE s.status = 'completed'
            GROUP BY e.employee_id, e.first_name, e.last_name
            LIMIT 3
        ");
        $examples = $stmt->fetchAll();
        
        foreach ($examples as $example) {
            echo "  - {$example['first_name']} {$example['last_name']}: {$example['sessions']} sessions, {$example['feedback_items']} feedback items\n";
        }
        
        echo "\n✓ Phase 1 continuous performance foundation is ready for testing!\n";
    }
}

// Run the population script
try {
    $populator = new Phase1TestDataPopulator();
    $populator->populate();
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    exit(1);
}