<?php
/**
 * Report Generator Class
 * Phase 3: Advanced Features - Reporting System
 * Growth Evidence System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/EvidenceManager.php';

class ReportGenerator {
    private $pdo;
    private $evidenceManager;
    
    public function __construct() {
        $this->pdo = getDbConnection();
        $this->evidenceManager = new EvidenceManager();
    }
    
    /**
     * Generate evidence summary report
     * @param array $parameters
     * @return array
     */
    public function generateEvidenceSummaryReport($parameters) {
        try {
            $startDate = $parameters['start_date'] ?? date('Y-m-01');
            $endDate = $parameters['end_date'] ?? date('Y-m-t');
            $employeeId = $parameters['employee_id'] ?? null;
            $managerId = $parameters['manager_id'] ?? null;
            $dimension = $parameters['dimension'] ?? null;
            
            $report = [
                'title' => 'Evidence Summary Report',
                'period' => "$startDate to $endDate",
                'generated_at' => date('Y-m-d H:i:s'),
                'parameters' => $parameters,
                'summary' => [],
                'details' => [],
                'charts' => []
            ];
            
            // Overall statistics
            $filters = array_filter([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'employee_id' => $employeeId,
                'manager_id' => $managerId
            ]);
            
            $report['summary'] = $this->evidenceManager->getEvidenceStatistics($filters);
            
            // Evidence by dimension
            $dimensionStats = $this->evidenceManager->getDimensionStatistics($startDate, $endDate);
            $report['details']['by_dimension'] = $dimensionStats;
            
            // Evidence entries
            $evidenceEntries = $this->evidenceManager->getEntriesByDateRange($startDate, $endDate, $filters);
            $report['details']['entries'] = $evidenceEntries;
            
            // Chart data
            $report['charts']['dimension_distribution'] = $this->prepareDimensionChart($dimensionStats);
            $report['charts']['rating_distribution'] = $this->prepareRatingChart($evidenceEntries);
            $report['charts']['timeline'] = $this->prepareTimelineChart($evidenceEntries);
            
            return $report;
            
        } catch (Exception $e) {
            error_log("Generate evidence summary report error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate performance trends report
     * @param array $parameters
     * @return array
     */
    public function generatePerformanceTrendsReport($parameters) {
        try {
            $employeeId = $parameters['employee_id'];
            $startDate = $parameters['start_date'] ?? date('Y-01-01');
            $endDate = $parameters['end_date'] ?? date('Y-12-31');
            
            $report = [
                'title' => 'Performance Trends Report',
                'employee_id' => $employeeId,
                'period' => "$startDate to $endDate",
                'generated_at' => date('Y-m-d H:i:s'),
                'trends' => [],
                'analysis' => [],
                'recommendations' => []
            ];
            
            // Get employee info
            $employee = fetchOne("SELECT * FROM employees WHERE employee_id = ?", [$employeeId]);
            $report['employee'] = $employee;
            
            // Get trend data
            $trendData = $this->evidenceManager->getEvidenceTrendAnalysis($employeeId, $startDate, $endDate);
            $report['trends'] = $this->processTrendData($trendData);
            
            // Performance analysis
            $report['analysis'] = $this->analyzePerformanceTrends($trendData);
            
            // Generate recommendations
            $report['recommendations'] = $this->generateRecommendations($report['analysis']);
            
            return $report;
            
        } catch (Exception $e) {
            error_log("Generate performance trends report error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate manager overview report
     * @param array $parameters
     * @return array
     */
    public function generateManagerOverviewReport($parameters) {
        try {
            $managerId = $parameters['manager_id'];
            $startDate = $parameters['start_date'] ?? date('Y-m-01');
            $endDate = $parameters['end_date'] ?? date('Y-m-t');
            
            $report = [
                'title' => 'Manager Overview Report',
                'manager_id' => $managerId,
                'period' => "$startDate to $endDate",
                'generated_at' => date('Y-m-d H:i:s'),
                'team_summary' => [],
                'individual_summaries' => [],
                'insights' => []
            ];
            
            // Get manager info
            $manager = fetchOne("SELECT * FROM employees WHERE employee_id = ?", [$managerId]);
            $report['manager'] = $manager;
            
            // Get team members
            $teamMembers = fetchAll("SELECT * FROM employees WHERE manager_id = ? AND active = TRUE", [$managerId]);
            $report['team_members'] = $teamMembers;
            
            // Team summary statistics
            $teamStats = $this->evidenceManager->getEvidenceStatistics([
                'manager_id' => $managerId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            $report['team_summary'] = $teamStats;
            
            // Individual summaries
            foreach ($teamMembers as $member) {
                $memberStats = $this->evidenceManager->getEvidenceStatistics([
                    'employee_id' => $member['employee_id'],
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]);
                
                $memberSummary = $this->evidenceManager->getEvidenceSummary($member['employee_id'], $startDate, $endDate);
                
                $report['individual_summaries'][] = [
                    'employee' => $member,
                    'statistics' => $memberStats,
                    'summary' => $memberSummary
                ];
            }
            
            // Generate insights
            $report['insights'] = $this->generateManagerInsights($report);
            
            return $report;
            
        } catch (Exception $e) {
            error_log("Generate manager overview report error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate custom report based on parameters
     * @param array $parameters
     * @return array
     */
    public function generateCustomReport($parameters) {
        try {
            $report = [
                'title' => $parameters['title'] ?? 'Custom Report',
                'generated_at' => date('Y-m-d H:i:s'),
                'parameters' => $parameters,
                'data' => []
            ];
            
            // Build custom query based on parameters
            $sql = $this->buildCustomQuery($parameters);
            $report['data'] = fetchAll($sql['query'], $sql['params']);
            
            // Apply aggregations if specified
            if (!empty($parameters['aggregations'])) {
                $report['aggregations'] = $this->applyAggregations($report['data'], $parameters['aggregations']);
            }
            
            // Apply grouping if specified
            if (!empty($parameters['group_by'])) {
                $report['grouped_data'] = $this->groupData($report['data'], $parameters['group_by']);
            }
            
            return $report;
            
        } catch (Exception $e) {
            error_log("Generate custom report error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Export report to PDF
     * @param array $reportData
     * @param string $filename
     * @return string File path
     */
    public function exportToPDF($reportData, $filename = null) {
        try {
            if (!$filename) {
                $filename = 'report_' . date('Y-m-d_H-i-s') . '.pdf';
            }
            
            $filePath = 'uploads/reports/' . $filename;
            
            // Create directory if it doesn't exist
            $directory = dirname($filePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Generate HTML content
            $html = $this->generateReportHTML($reportData);
            
            // For now, save as HTML (in production, use a PDF library like TCPDF or DomPDF)
            file_put_contents($filePath . '.html', $html);
            
            return $filePath . '.html';
            
        } catch (Exception $e) {
            error_log("Export to PDF error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Export report to Excel
     * @param array $reportData
     * @param string $filename
     * @return string File path
     */
    public function exportToExcel($reportData, $filename = null) {
        try {
            if (!$filename) {
                $filename = 'report_' . date('Y-m-d_H-i-s') . '.csv';
            }
            
            $filePath = 'uploads/reports/' . $filename;
            
            // Create directory if it doesn't exist
            $directory = dirname($filePath);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Generate CSV content
            $csv = $this->generateReportCSV($reportData);
            file_put_contents($filePath, $csv);
            
            return $filePath;
            
        } catch (Exception $e) {
            error_log("Export to Excel error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Schedule report generation
     * @param array $scheduleData
     * @return int|false
     */
    public function scheduleReport($scheduleData) {
        try {
            $required = ['report_name', 'report_type', 'parameters', 'recipients', 'schedule_frequency', 'created_by'];
            foreach ($required as $field) {
                if (empty($scheduleData[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            // Calculate next run time
            $nextRun = $this->calculateNextRunTime($scheduleData['schedule_frequency'], $scheduleData);
            
            $sql = "INSERT INTO scheduled_reports (report_name, report_type, parameters, recipients, 
                                                  schedule_frequency, schedule_day_of_week, schedule_day_of_month, 
                                                  next_run_at, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            return insertRecord($sql, [
                $scheduleData['report_name'],
                $scheduleData['report_type'],
                json_encode($scheduleData['parameters']),
                json_encode($scheduleData['recipients']),
                $scheduleData['schedule_frequency'],
                $scheduleData['schedule_day_of_week'] ?? null,
                $scheduleData['schedule_day_of_month'] ?? null,
                $nextRun,
                $scheduleData['created_by']
            ]);
            
        } catch (Exception $e) {
            error_log("Schedule report error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Process scheduled reports
     * @return array
     */
    public function processScheduledReports() {
        try {
            $results = ['processed' => 0, 'failed' => 0, 'errors' => []];
            
            // Get reports due for execution
            $sql = "SELECT * FROM scheduled_reports 
                    WHERE is_active = TRUE AND next_run_at <= NOW()";
            
            $scheduledReports = fetchAll($sql);
            
            foreach ($scheduledReports as $schedule) {
                try {
                    // Generate report
                    $parameters = json_decode($schedule['parameters'], true);
                    $reportData = $this->generateReportByType($schedule['report_type'], $parameters);
                    
                    // Export report
                    $filePath = $this->exportToPDF($reportData);
                    
                    // Save to history
                    $this->saveReportHistory($schedule, $filePath, 'completed');
                    
                    // Update next run time
                    $nextRun = $this->calculateNextRunTime($schedule['schedule_frequency'], $schedule);
                    $updateSql = "UPDATE scheduled_reports SET last_run_at = NOW(), next_run_at = ? WHERE schedule_id = ?";
                    updateRecord($updateSql, [$nextRun, $schedule['schedule_id']]);
                    
                    $results['processed']++;
                    
                } catch (Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Schedule {$schedule['schedule_id']}: " . $e->getMessage();
                    
                    // Save error to history
                    $this->saveReportHistory($schedule, null, 'failed', $e->getMessage());
                }
            }
            
            return $results;
            
        } catch (Exception $e) {
            error_log("Process scheduled reports error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Helper methods
    
    private function prepareDimensionChart($dimensionStats) {
        $labels = [];
        $data = [];
        
        foreach ($dimensionStats as $stat) {
            $labels[] = ucfirst($stat['dimension']);
            $data[] = $stat['entry_count'];
        }
        
        return ['labels' => $labels, 'data' => $data];
    }
    
    private function prepareRatingChart($entries) {
        $ratings = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        
        foreach ($entries as $entry) {
            $ratings[$entry['star_rating']]++;
        }
        
        return [
            'labels' => ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
            'data' => array_values($ratings)
        ];
    }
    
    private function prepareTimelineChart($entries) {
        $timeline = [];
        
        foreach ($entries as $entry) {
            $month = date('Y-m', strtotime($entry['entry_date']));
            if (!isset($timeline[$month])) {
                $timeline[$month] = 0;
            }
            $timeline[$month]++;
        }
        
        ksort($timeline);
        
        return [
            'labels' => array_keys($timeline),
            'data' => array_values($timeline)
        ];
    }
    
    private function processTrendData($trendData) {
        $processed = [];
        
        foreach ($trendData as $data) {
            $key = $data['month_year'] . '_' . $data['dimension'];
            $processed[$key] = $data;
        }
        
        return $processed;
    }
    
    private function analyzePerformanceTrends($trendData) {
        // Implement trend analysis logic
        return [
            'improving_dimensions' => [],
            'declining_dimensions' => [],
            'stable_dimensions' => []
        ];
    }
    
    private function generateRecommendations($analysis) {
        // Generate recommendations based on analysis
        return [
            'focus_areas' => [],
            'strengths_to_leverage' => [],
            'development_suggestions' => []
        ];
    }
    
    private function generateManagerInsights($report) {
        // Generate insights for manager report
        return [
            'top_performers' => [],
            'areas_for_attention' => [],
            'team_strengths' => []
        ];
    }
    
    private function buildCustomQuery($parameters) {
        // Build custom SQL query based on parameters
        $baseQuery = "SELECT gee.*, e.first_name, e.last_name FROM growth_evidence_entries gee JOIN employees e ON gee.employee_id = e.employee_id WHERE 1=1";
        $params = [];
        
        // Add filters based on parameters
        // This is a simplified version - expand based on needs
        
        return ['query' => $baseQuery, 'params' => $params];
    }
    
    private function applyAggregations($data, $aggregations) {
        // Apply aggregation functions to data
        return [];
    }
    
    private function groupData($data, $groupBy) {
        // Group data by specified field
        return [];
    }
    
    private function generateReportHTML($reportData) {
        // Generate HTML representation of report
        $html = "<html><head><title>{$reportData['title']}</title></head><body>";
        $html .= "<h1>{$reportData['title']}</h1>";
        $html .= "<p>Generated: {$reportData['generated_at']}</p>";
        // Add more HTML generation logic
        $html .= "</body></html>";
        
        return $html;
    }
    
    private function generateReportCSV($reportData) {
        // Generate CSV representation of report
        $csv = "Report: {$reportData['title']}\n";
        $csv .= "Generated: {$reportData['generated_at']}\n\n";
        // Add more CSV generation logic
        
        return $csv;
    }
    
    private function calculateNextRunTime($frequency, $scheduleData) {
        $now = new DateTime();
        
        switch ($frequency) {
            case 'daily':
                return $now->modify('+1 day')->format('Y-m-d H:i:s');
            case 'weekly':
                return $now->modify('+1 week')->format('Y-m-d H:i:s');
            case 'monthly':
                return $now->modify('+1 month')->format('Y-m-d H:i:s');
            case 'quarterly':
                return $now->modify('+3 months')->format('Y-m-d H:i:s');
            default:
                return $now->modify('+1 day')->format('Y-m-d H:i:s');
        }
    }
    
    private function generateReportByType($type, $parameters) {
        switch ($type) {
            case 'evidence_summary':
                return $this->generateEvidenceSummaryReport($parameters);
            case 'performance_trends':
                return $this->generatePerformanceTrendsReport($parameters);
            case 'manager_overview':
                return $this->generateManagerOverviewReport($parameters);
            case 'custom':
                return $this->generateCustomReport($parameters);
            default:
                throw new Exception("Unknown report type: $type");
        }
    }
    
    private function saveReportHistory($schedule, $filePath, $status, $errorMessage = null) {
        $sql = "INSERT INTO report_history (schedule_id, report_name, report_type, parameters, 
                                           file_path, file_format, status, error_message, generated_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        insertRecord($sql, [
            $schedule['schedule_id'],
            $schedule['report_name'],
            $schedule['report_type'],
            $schedule['parameters'],
            $filePath,
            'pdf',
            $status,
            $errorMessage,
            $schedule['created_by']
        ]);
    }
}
?>