<?php
/**
 * Dashboard Analytics Class
 * Phase 2: Dashboard & Analytics Implementation
 * Provides evidence-based performance insights and analytics capabilities
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/GrowthEvidenceJournal.php';
require_once __DIR__ . '/Evaluation.php';
require_once __DIR__ . '/Employee.php';
require_once __DIR__ . '/EvaluationPeriod.php';

class DashboardAnalytics {
    private $pdo;
    private $evidenceJournal;
    private $evaluation;
    private $employee;
    private $period;
    
    public function __construct() {
        $this->pdo = getDbConnection();
        $this->evidenceJournal = new GrowthEvidenceJournal();
        $this->evaluation = new Evaluation();
        $this->employee = new Employee();
        $this->period = new EvaluationPeriod();
    }
    
    /**
     * Get Manager Dashboard Analytics
     * @param int $managerId
     * @param array $filters
     * @return array
     */
    public function getManagerDashboardData(int $managerId, array $filters = []): array {
        try {
            $startTime = microtime(true);
            
            // Get team members
            $teamMembers = $this->employee->getTeamMembers($managerId);
            $teamMemberIds = array_column($teamMembers, 'employee_id');
            
            if (empty($teamMemberIds)) {
                return $this->getEmptyManagerDashboard();
            }
            
            // Get current period or use filter
            $currentPeriod = $this->getCurrentOrFilteredPeriod($filters);
            if (!$currentPeriod) {
                return $this->getEmptyManagerDashboard();
            }
            
            // Get team evidence trends
            $evidenceTrends = $this->getTeamEvidenceTrends($teamMemberIds, $currentPeriod);
            
            // Get team performance insights
            $performanceInsights = $this->getTeamPerformanceInsights($teamMemberIds, $currentPeriod);
            
            // Get feedback frequency analytics
            $feedbackAnalytics = $this->getFeedbackFrequencyAnalytics($teamMemberIds, $currentPeriod);
            
            // Get evidence quality indicators
            $qualityIndicators = $this->getEvidenceQualityIndicators($teamMemberIds, $currentPeriod);
            
            // Get team comparison analytics
            $teamComparison = $this->getTeamComparisonAnalytics($teamMemberIds, $currentPeriod);
            
            // Get coaching opportunities
            $coachingOpportunities = $this->getCoachingOpportunities($teamMemberIds, $currentPeriod);
            
            $executionTime = microtime(true) - $startTime;
            error_log("Manager dashboard data generated in {$executionTime}s for manager $managerId");
            
            return [
                'team_overview' => [
                    'team_size' => count($teamMembers),
                    'active_evaluations' => $this->getActiveEvaluationsCount($teamMemberIds, $currentPeriod),
                    'evidence_entries_total' => $evidenceTrends['total_entries'],
                    'avg_team_rating' => $performanceInsights['avg_team_rating'],
                    'period_info' => $currentPeriod
                ],
                'evidence_trends' => $evidenceTrends,
                'performance_insights' => $performanceInsights,
                'feedback_analytics' => $feedbackAnalytics,
                'quality_indicators' => $qualityIndicators,
                'team_comparison' => $teamComparison,
                'coaching_opportunities' => $coachingOpportunities,
                'team_members' => $teamMembers,
                'generated_at' => date('Y-m-d H:i:s'),
                'execution_time' => round($executionTime, 3)
            ];
            
        } catch (Exception $e) {
            error_log("Manager dashboard error: " . $e->getMessage());
            return $this->getEmptyManagerDashboard();
        }
    }
    
    /**
     * Get Employee Dashboard Analytics
     * @param int $employeeId
     * @param array $filters
     * @return array
     */
    public function getEmployeeDashboardData(int $employeeId, array $filters = []): array {
        try {
            $startTime = microtime(true);
            
            // Get current period or use filter
            $currentPeriod = $this->getCurrentOrFilteredPeriod($filters);
            if (!$currentPeriod) {
                return $this->getEmptyEmployeeDashboard();
            }
            
            // Get personal feedback summary
            $feedbackSummary = $this->getPersonalFeedbackSummary($employeeId, $currentPeriod);
            
            // Get performance trend analysis
            $performanceTrends = $this->getPersonalPerformanceTrends($employeeId, $currentPeriod);
            
            // Get development recommendations
            $developmentRecommendations = $this->getDevelopmentRecommendations($employeeId, $currentPeriod);
            
            // Get goal progress tracking
            $goalProgress = $this->getGoalProgressTracking($employeeId, $currentPeriod);
            
            // Get peer comparison insights (anonymized)
            $peerComparison = $this->getPeerComparisonInsights($employeeId, $currentPeriod);
            
            // Get evidence history visualization data
            $evidenceHistory = $this->getEvidenceHistoryVisualization($employeeId, $currentPeriod);
            
            $executionTime = microtime(true) - $startTime;
            error_log("Employee dashboard data generated in {$executionTime}s for employee $employeeId");
            
            return [
                'personal_overview' => [
                    'total_evidence_entries' => $feedbackSummary['total_entries'],
                    'current_rating' => $feedbackSummary['current_rating'],
                    'rating_trend' => $performanceTrends['rating_trend'],
                    'dimensions_covered' => $feedbackSummary['dimensions_covered'],
                    'period_info' => $currentPeriod
                ],
                'feedback_summary' => $feedbackSummary,
                'performance_trends' => $performanceTrends,
                'development_recommendations' => $developmentRecommendations,
                'goal_progress' => $goalProgress,
                'peer_comparison' => $peerComparison,
                'evidence_history' => $evidenceHistory,
                'generated_at' => date('Y-m-d H:i:s'),
                'execution_time' => round($executionTime, 3)
            ];
            
        } catch (Exception $e) {
            error_log("Employee dashboard error: " . $e->getMessage());
            return $this->getEmptyEmployeeDashboard();
        }
    }
    
    /**
     * Get HR Analytics Dashboard
     * @param array $filters
     * @return array
     */
    public function getHRAnalyticsDashboard(array $filters = []): array {
        try {
            $startTime = microtime(true);
            
            // Get organizational evidence patterns
            $organizationalPatterns = $this->getOrganizationalEvidencePatterns($filters);
            
            // Get department comparison analytics
            $departmentComparison = $this->getDepartmentComparisonAnalytics($filters);
            
            // Get system usage analytics
            $usageAnalytics = $this->getSystemUsageAnalytics($filters);
            
            // Get performance distribution analysis
            $performanceDistribution = $this->getPerformanceDistributionAnalysis($filters);
            
            // Get evidence-based reporting insights
            $reportingInsights = $this->getEvidenceBasedReportingInsights($filters);
            
            // Get adoption metrics
            $adoptionMetrics = $this->getSystemAdoptionMetrics($filters);
            
            $executionTime = microtime(true) - $startTime;
            error_log("HR analytics dashboard generated in {$executionTime}s");
            
            return [
                'organizational_overview' => [
                    'total_employees' => $this->getTotalEmployeesCount(),
                    'total_evidence_entries' => $organizationalPatterns['total_entries'],
                    'active_evaluations' => $this->getActiveEvaluationsCount(),
                    'system_adoption_rate' => $adoptionMetrics['adoption_rate'],
                    'avg_organizational_rating' => $performanceDistribution['avg_rating']
                ],
                'organizational_patterns' => $organizationalPatterns,
                'department_comparison' => $departmentComparison,
                'usage_analytics' => $usageAnalytics,
                'performance_distribution' => $performanceDistribution,
                'reporting_insights' => $reportingInsights,
                'adoption_metrics' => $adoptionMetrics,
                'generated_at' => date('Y-m-d H:i:s'),
                'execution_time' => round($executionTime, 3)
            ];
            
        } catch (Exception $e) {
            error_log("HR analytics dashboard error: " . $e->getMessage());
            return $this->getEmptyHRDashboard();
        }
    }
    
    /**
     * Get team evidence trends over time
     * @param array $teamMemberIds
     * @param array $period
     * @return array
     */
    private function getTeamEvidenceTrends(array $teamMemberIds, array $period): array {
        if (empty($teamMemberIds)) {
            return ['total_entries' => 0, 'trends' => [], 'dimension_breakdown' => []];
        }
        
        $placeholders = str_repeat('?,', count($teamMemberIds) - 1) . '?';
        $params = array_merge($teamMemberIds, [$period['start_date'], $period['end_date']]);
        
        // Get monthly trends
        $sql = "SELECT 
                    DATE_FORMAT(entry_date, '%Y-%m') as month,
                    dimension,
                    COUNT(*) as entry_count,
                    AVG(star_rating) as avg_rating
                FROM growth_evidence_entries 
                WHERE employee_id IN ($placeholders)
                AND entry_date BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(entry_date, '%Y-%m'), dimension
                ORDER BY month, dimension";
        
        $trends = fetchAll($sql, $params);
        
        // Get total entries
        $totalSql = "SELECT COUNT(*) as total FROM growth_evidence_entries 
                     WHERE employee_id IN ($placeholders) AND entry_date BETWEEN ? AND ?";
        $totalResult = fetchOne($totalSql, $params);
        
        // Get dimension breakdown
        $dimensionSql = "SELECT 
                            dimension,
                            COUNT(*) as count,
                            AVG(star_rating) as avg_rating
                         FROM growth_evidence_entries 
                         WHERE employee_id IN ($placeholders) AND entry_date BETWEEN ? AND ?
                         GROUP BY dimension";
        $dimensionBreakdown = fetchAll($dimensionSql, $params);
        
        return [
            'total_entries' => $totalResult['total'],
            'trends' => $trends,
            'dimension_breakdown' => $dimensionBreakdown
        ];
    }
    
    /**
     * Get team performance insights
     * @param array $teamMemberIds
     * @param array $period
     * @return array
     */
    private function getTeamPerformanceInsights(array $teamMemberIds, array $period): array {
        if (empty($teamMemberIds)) {
            return ['avg_team_rating' => 0, 'performance_distribution' => [], 'top_performers' => []];
        }
        
        $evidenceData = $this->evidenceJournal->batchGetEvidenceByDimension($teamMemberIds, $period['start_date'], $period['end_date']);
        
        $teamRatings = [];
        $performanceDistribution = ['excellent' => 0, 'good' => 0, 'satisfactory' => 0, 'needs_improvement' => 0];
        
        foreach ($evidenceData as $employeeId => $dimensions) {
            $employeeAvg = 0;
            $dimensionCount = 0;
            
            foreach ($dimensions as $dimension) {
                $employeeAvg += $dimension['avg_rating'];
                $dimensionCount++;
            }
            
            if ($dimensionCount > 0) {
                $avgRating = $employeeAvg / $dimensionCount;
                $teamRatings[] = $avgRating;
                
                // Categorize performance
                if ($avgRating >= 4.5) $performanceDistribution['excellent']++;
                elseif ($avgRating >= 3.5) $performanceDistribution['good']++;
                elseif ($avgRating >= 2.5) $performanceDistribution['satisfactory']++;
                else $performanceDistribution['needs_improvement']++;
            }
        }
        
        $avgTeamRating = !empty($teamRatings) ? array_sum($teamRatings) / count($teamRatings) : 0;
        
        return [
            'avg_team_rating' => round($avgTeamRating, 2),
            'performance_distribution' => $performanceDistribution,
            'team_size_analyzed' => count($teamRatings)
        ];
    }
    
    /**
     * Get feedback frequency analytics
     * @param array $teamMemberIds
     * @param array $period
     * @return array
     */
    private function getFeedbackFrequencyAnalytics(array $teamMemberIds, array $period): array {
        if (empty($teamMemberIds)) {
            return ['avg_frequency' => 0, 'frequency_distribution' => []];
        }
        
        $placeholders = str_repeat('?,', count($teamMemberIds) - 1) . '?';
        $params = array_merge($teamMemberIds, [$period['start_date'], $period['end_date']]);
        
        $sql = "SELECT 
                    employee_id,
                    COUNT(*) as entry_count,
                    COUNT(DISTINCT DATE(entry_date)) as active_days,
                    DATEDIFF(MAX(entry_date), MIN(entry_date)) + 1 as period_days
                FROM growth_evidence_entries 
                WHERE employee_id IN ($placeholders) AND entry_date BETWEEN ? AND ?
                GROUP BY employee_id";
        
        $frequencyData = fetchAll($sql, $params);
        
        $frequencies = [];
        foreach ($frequencyData as $data) {
            $frequency = $data['period_days'] > 0 ? $data['entry_count'] / $data['period_days'] : 0;
            $frequencies[] = $frequency;
        }
        
        $avgFrequency = !empty($frequencies) ? array_sum($frequencies) / count($frequencies) : 0;
        
        return [
            'avg_frequency' => round($avgFrequency, 3),
            'frequency_distribution' => $frequencyData,
            'total_active_employees' => count($frequencyData)
        ];
    }
    
    /**
     * Get evidence quality indicators
     * @param array $teamMemberIds
     * @param array $period
     * @return array
     */
    private function getEvidenceQualityIndicators(array $teamMemberIds, array $period): array {
        if (empty($teamMemberIds)) {
            return ['avg_content_length' => 0, 'quality_metrics' => []];
        }
        
        $placeholders = str_repeat('?,', count($teamMemberIds) - 1) . '?';
        $params = array_merge($teamMemberIds, [$period['start_date'], $period['end_date']]);
        
        $sql = "SELECT 
                    AVG(LENGTH(content)) as avg_content_length,
                    COUNT(CASE WHEN LENGTH(content) > 100 THEN 1 END) as detailed_entries,
                    COUNT(CASE WHEN LENGTH(content) < 50 THEN 1 END) as brief_entries,
                    COUNT(*) as total_entries,
                    COUNT(DISTINCT employee_id) as employees_with_evidence,
                    COUNT(DISTINCT dimension) as dimensions_covered
                FROM growth_evidence_entries 
                WHERE employee_id IN ($placeholders) AND entry_date BETWEEN ? AND ?";
        
        $qualityData = fetchOne($sql, $params);
        
        return [
            'avg_content_length' => round($qualityData['avg_content_length'], 1),
            'quality_metrics' => [
                'detailed_entries_pct' => $qualityData['total_entries'] > 0 ? 
                    round(($qualityData['detailed_entries'] / $qualityData['total_entries']) * 100, 1) : 0,
                'brief_entries_pct' => $qualityData['total_entries'] > 0 ? 
                    round(($qualityData['brief_entries'] / $qualityData['total_entries']) * 100, 1) : 0,
                'employees_with_evidence' => $qualityData['employees_with_evidence'],
                'dimensions_covered' => $qualityData['dimensions_covered']
            ]
        ];
    }
    
    /**
     * Get team comparison analytics across dimensions
     * @param array $teamMemberIds
     * @param array $period
     * @return array
     */
    private function getTeamComparisonAnalytics(array $teamMemberIds, array $period): array {
        if (empty($teamMemberIds)) {
            return ['dimension_comparison' => [], 'employee_comparison' => []];
        }
        
        $evidenceData = $this->evidenceJournal->batchGetEvidenceByDimension($teamMemberIds, $period['start_date'], $period['end_date']);
        
        $dimensionAverages = ['responsibilities' => [], 'kpis' => [], 'competencies' => [], 'values' => []];
        $employeeComparison = [];
        
        foreach ($evidenceData as $employeeId => $dimensions) {
            $employeeData = ['employee_id' => $employeeId, 'dimensions' => []];
            
            foreach ($dimensions as $dimension) {
                $dimensionName = $dimension['dimension'];
                $avgRating = $dimension['avg_rating'];
                
                $dimensionAverages[$dimensionName][] = $avgRating;
                $employeeData['dimensions'][$dimensionName] = $avgRating;
            }
            
            $employeeComparison[] = $employeeData;
        }
        
        // Calculate dimension averages
        $dimensionComparison = [];
        foreach ($dimensionAverages as $dimension => $ratings) {
            $dimensionComparison[$dimension] = [
                'avg_rating' => !empty($ratings) ? round(array_sum($ratings) / count($ratings), 2) : 0,
                'employee_count' => count($ratings),
                'min_rating' => !empty($ratings) ? min($ratings) : 0,
                'max_rating' => !empty($ratings) ? max($ratings) : 0
            ];
        }
        
        return [
            'dimension_comparison' => $dimensionComparison,
            'employee_comparison' => $employeeComparison
        ];
    }
    
    /**
     * Get coaching opportunities based on evidence patterns
     * @param array $teamMemberIds
     * @param array $period
     * @return array
     */
    private function getCoachingOpportunities(array $teamMemberIds, array $period): array {
        if (empty($teamMemberIds)) {
            return ['opportunities' => [], 'priority_areas' => []];
        }
        
        $evidenceData = $this->evidenceJournal->batchGetEvidenceByDimension($teamMemberIds, $period['start_date'], $period['end_date']);
        
        $opportunities = [];
        $priorityAreas = ['responsibilities' => 0, 'kpis' => 0, 'competencies' => 0, 'values' => 0];
        
        foreach ($evidenceData as $employeeId => $dimensions) {
            $employeeOpportunities = [];
            
            foreach ($dimensions as $dimension) {
                $avgRating = $dimension['avg_rating'];
                $entryCount = $dimension['entry_count'];
                
                // Identify coaching opportunities
                if ($avgRating < 3.0) {
                    $employeeOpportunities[] = [
                        'dimension' => $dimension['dimension'],
                        'avg_rating' => $avgRating,
                        'entry_count' => $entryCount,
                        'priority' => 'high',
                        'reason' => 'Below expectations performance'
                    ];
                    $priorityAreas[$dimension['dimension']]++;
                } elseif ($entryCount < 3) {
                    $employeeOpportunities[] = [
                        'dimension' => $dimension['dimension'],
                        'avg_rating' => $avgRating,
                        'entry_count' => $entryCount,
                        'priority' => 'medium',
                        'reason' => 'Insufficient evidence for reliable assessment'
                    ];
                }
            }
            
            if (!empty($employeeOpportunities)) {
                $opportunities[] = [
                    'employee_id' => $employeeId,
                    'opportunities' => $employeeOpportunities
                ];
            }
        }
        
        return [
            'opportunities' => $opportunities,
            'priority_areas' => $priorityAreas
        ];
    }
    
    /**
     * Get personal feedback summary for employee
     * @param int $employeeId
     * @param array $period
     * @return array
     */
    private function getPersonalFeedbackSummary(int $employeeId, array $period): array {
        $evidenceByDimension = $this->evidenceJournal->getEvidenceByDimension($employeeId, $period['start_date'], $period['end_date']);
        $evidenceSummary = $this->evidenceJournal->getEvidenceSummary($employeeId, $period['start_date'], $period['end_date']);
        
        return [
            'total_entries' => $evidenceSummary['total_entries'],
            'current_rating' => round($evidenceSummary['overall_avg_rating'] ?? 0, 2),
            'dimensions_covered' => count($evidenceByDimension),
            'positive_entries' => $evidenceSummary['positive_entries'],
            'negative_entries' => $evidenceSummary['negative_entries'],
            'dimension_breakdown' => $evidenceByDimension,
            'first_entry_date' => $evidenceSummary['first_entry_date'],
            'last_entry_date' => $evidenceSummary['last_entry_date']
        ];
    }
    
    /**
     * Get personal performance trends
     * @param int $employeeId
     * @param array $period
     * @return array
     */
    private function getPersonalPerformanceTrends(int $employeeId, array $period): array {
        $trendData = $this->evidenceJournal->getEvidenceTrendAnalysis($employeeId, $period['start_date'], $period['end_date']);
        
        // Calculate rating trend
        $monthlyRatings = [];
        foreach ($trendData as $data) {
            $month = $data['month_year'];
            if (!isset($monthlyRatings[$month])) {
                $monthlyRatings[$month] = [];
            }
            $monthlyRatings[$month][] = $data['avg_rating'];
        }
        
        $trendDirection = 'stable';
        if (count($monthlyRatings) >= 2) {
            $months = array_keys($monthlyRatings);
            sort($months);
            $firstMonth = array_sum($monthlyRatings[$months[0]]) / count($monthlyRatings[$months[0]]);
            $lastMonth = array_sum($monthlyRatings[end($months)]) / count($monthlyRatings[end($months)]);
            
            if ($lastMonth > $firstMonth + 0.2) $trendDirection = 'improving';
            elseif ($lastMonth < $firstMonth - 0.2) $trendDirection = 'declining';
        }
        
        return [
            'rating_trend' => $trendDirection,
            'monthly_data' => $trendData,
            'trend_analysis' => $monthlyRatings
        ];
    }
    
    /**
     * Get development recommendations
     * @param int $employeeId
     * @param array $period
     * @return array
     */
    private function getDevelopmentRecommendations(int $employeeId, array $period): array {
        $evidenceByDimension = $this->evidenceJournal->getEvidenceByDimension($employeeId, $period['start_date'], $period['end_date']);
        
        $recommendations = [];
        foreach ($evidenceByDimension as $dimension) {
            $avgRating = $dimension['avg_rating'];
            $entryCount = $dimension['entry_count'];
            
            if ($avgRating < 3.0) {
                $recommendations[] = [
                    'dimension' => $dimension['dimension'],
                    'priority' => 'high',
                    'recommendation' => "Focus on improving {$dimension['dimension']} performance",
                    'current_rating' => $avgRating,
                    'target_rating' => 3.5
                ];
            } elseif ($entryCount < 3) {
                $recommendations[] = [
                    'dimension' => $dimension['dimension'],
                    'priority' => 'medium',
                    'recommendation' => "Seek more feedback in {$dimension['dimension']} area",
                    'current_rating' => $avgRating,
                    'evidence_count' => $entryCount
                ];
            } elseif ($avgRating >= 4.0) {
                $recommendations[] = [
                    'dimension' => $dimension['dimension'],
                    'priority' => 'low',
                    'recommendation' => "Continue excellence in {$dimension['dimension']} - consider mentoring others",
                    'current_rating' => $avgRating,
                    'type' => 'strength'
                ];
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Get goal progress tracking
     * @param int $employeeId
     * @param array $period
     * @return array
     */
    private function getGoalProgressTracking(int $employeeId, array $period): array {
        // This would integrate with goal-setting system if available
        // For now, we'll base it on evidence patterns
        $evidenceByDimension = $this->evidenceJournal->getEvidenceByDimension($employeeId, $period['start_date'], $period['end_date']);
        
        $goals = [];
        foreach ($evidenceByDimension as $dimension) {
            $progress = min(100, ($dimension['avg_rating'] / 5.0) * 100);
            
            $goals[] = [
                'dimension' => $dimension['dimension'],
                'target_rating' => 4.0,
                'current_rating' => $dimension['avg_rating'],
                'progress_percentage' => round($progress, 1),
                'evidence_count' => $dimension['entry_count']
            ];
        }
        
        return $goals;
    }
    
    /**
     * Get peer comparison insights (anonymized)
     * @param int $employeeId
     * @param array $period
     * @return array
     */
    private function getPeerComparisonInsights(int $employeeId, array $period): array {
        // Get employee's department for peer comparison
        $employee = $this->employee->getEmployeeById($employeeId);
        if (!$employee || !$employee['department']) {
            return ['comparison_available' => false];
        }
        
        // Get department averages
        $sql = "SELECT 
                    gee.dimension,
                    AVG(gee.star_rating) as dept_avg_rating,
                    COUNT(*) as dept_entry_count
                FROM growth_evidence_entries gee
                JOIN employees e ON gee.employee_id = e.employee_id
                WHERE e.department = ? 
                AND gee.entry_date BETWEEN ? AND ?
                AND gee.employee_id != ?
                GROUP BY gee.dimension";
        
        $deptAverages = fetchAll($sql, [$employee['department'], $period['start_date'], $period['end_date'], $employeeId]);
        
        // Get employee's ratings
        $employeeRatings = $this->evidenceJournal->getEvidenceByDimension($employeeId, $period['start_date'], $period['end_date']);
        
        $comparison = [];
        foreach ($employeeRatings as $empDimension) {
            $deptData = array_filter($deptAverages, fn($d) => $d['dimension'] === $empDimension['dimension']);
            $deptData = reset($deptData);
            
            if ($deptData) {
                $comparison[] = [
                    'dimension' => $empDimension['dimension'],
                    'employee_rating' => $empDimension['avg_rating'],
                    'department_avg' => round($deptData['dept_avg_rating'], 2),
                    'percentile' => $this->calculatePercentile($empDimension['avg_rating'], $deptData['dept_avg_rating'])
                ];
            }
        }
        
        return [
            'comparison_available' => !empty($comparison),
            'department' => $employee['department'],
            'dimension_comparisons' => $comparison
        ];
    }
    
    /**
     * Get evidence history visualization data
     * @param int $employeeId
     * @param array $period
     * @return array
     */
    private function getEvidenceHistoryVisualization(int $employeeId, array $period): array {
        $sql = "SELECT 
                    DATE(entry_date) as entry_date,
                    dimension,
                    star_rating,
                    content
                FROM growth_evidence_entries 
                WHERE employee_id = ? AND entry_date BETWEEN ? AND ?
                ORDER BY entry_date DESC";
        
        $entries = fetchAll($sql, [$employeeId, $period['start_date'], $period['end_date']]);
        
        // Group by date for timeline visualization
        $timeline = [];
        foreach ($entries as $entry) {
            $date = $entry['entry_date'];
            if (!isset($timeline[$date])) {
                $timeline[$date] = [];
            }
            $timeline[$date][] = $entry;
        }
        
        return [
            'timeline' => $timeline,
            'total_entries' => count($entries),
            'date_range' => [
                'start' => $period['start_date'],
                'end' => $period['end_date']
            ]
        ];
    }
    
    /**
     * Helper method to get current or filtered period
     * @param array $filters
     * @return array|null
     */
    private function getCurrentOrFilteredPeriod(array $filters): ?array {
        if (isset($filters['period_id'])) {
            return $this->period->getPeriodById($filters['period_id']);
        }
        
        $currentPeriod = $this->period->getCurrentPeriod();
        if ($currentPeriod) {
            return $currentPeriod;
        }
        
        // Fallback to most recent period
        // Fallback to most recent period
        $periods = $this->period->getPeriods(1, 1, ['status' => 'active']);
        return !empty($periods['periods']) ? $periods['periods'][0] : null;
    }
    
    /**
     * Calculate percentile for peer comparison
     * @param float $employeeRating
     * @param float $departmentAvg
     * @return int
     */
    private function calculatePercentile(float $employeeRating, float $departmentAvg): int {
        if ($departmentAvg == 0) return 50;
        
        $ratio = $employeeRating / $departmentAvg;
        if ($ratio >= 1.2) return 90;
        if ($ratio >= 1.1) return 80;
        if ($ratio >= 1.05) return 70;
        if ($ratio >= 0.95) return 60;
        if ($ratio >= 0.9) return 40;
        if ($ratio >= 0.8) return 30;
        return 20;
    }
    
    /**
     * Get organizational evidence patterns
     * @param array $filters
     * @return array
     */
    private function getOrganizationalEvidencePatterns(array $filters): array {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $whereClause .= " AND gee.entry_date BETWEEN ? AND ?";
            $params[] = $filters['start_date'];
            $params[] = $filters['end_date'];
        }
        
        if (isset($filters['department'])) {
            $whereClause .= " AND e.department = ?";
            $params[] = $filters['department'];
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_entries,
                    AVG(gee.star_rating) as avg_rating,
                    COUNT(DISTINCT gee.employee_id) as active_employees,
                    COUNT(DISTINCT gee.dimension) as dimensions_used,
                    COUNT(DISTINCT DATE(gee.entry_date)) as active_days
                FROM growth_evidence_entries gee
                JOIN employees e ON gee.employee_id = e.employee_id
                $whereClause";
        
        return fetchOne($sql, $params);
    }
    
    /**
     * Get department comparison analytics
     * @param array $filters
     * @return array
     */
    private function getDepartmentComparisonAnalytics(array $filters): array {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $whereClause .= " AND gee.entry_date BETWEEN ? AND ?";
            $params[] = $filters['start_date'];
            $params[] = $filters['end_date'];
        }
        
        $sql = "SELECT 
                    e.department,
                    COUNT(*) as total_entries,
                    AVG(gee.star_rating) as avg_rating,
                    COUNT(DISTINCT gee.employee_id) as active_employees,
                    COUNT(DISTINCT gee.dimension) as dimensions_covered
                FROM growth_evidence_entries gee
                JOIN employees e ON gee.employee_id = e.employee_id
                $whereClause
                GROUP BY e.department
                ORDER BY avg_rating DESC";
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Get system usage analytics
     * @param array $filters
     * @return array
     */
    private function getSystemUsageAnalytics(array $filters): array {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $whereClause .= " AND gee.entry_date BETWEEN ? AND ?";
            $params[] = $filters['start_date'];
            $params[] = $filters['end_date'];
        }
        
        // Daily usage patterns
        $dailyUsageSql = "SELECT 
                            DATE(gee.entry_date) as usage_date,
                            COUNT(*) as daily_entries,
                            COUNT(DISTINCT gee.employee_id) as active_users
                          FROM growth_evidence_entries gee
                          $whereClause
                          GROUP BY DATE(gee.entry_date)
                          ORDER BY usage_date DESC
                          LIMIT 30";
        
        $dailyUsage = fetchAll($dailyUsageSql, $params);
        
        // Manager activity
        $managerActivitySql = "SELECT 
                                COUNT(*) as entries_created,
                                COUNT(DISTINCT gee.employee_id) as employees_managed
                               FROM growth_evidence_entries gee
                               JOIN employees e ON gee.manager_id = e.employee_id
                               $whereClause";
        
        $managerActivity = fetchOne($managerActivitySql, $params);
        
        return [
            'daily_usage' => $dailyUsage,
            'manager_activity' => $managerActivity,
            'usage_trends' => $this->calculateUsageTrends($dailyUsage)
        ];
    }
    
    /**
     * Get performance distribution analysis
     * @param array $filters
     * @return array
     */
    private function getPerformanceDistributionAnalysis(array $filters): array {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $whereClause .= " AND gee.entry_date BETWEEN ? AND ?";
            $params[] = $filters['start_date'];
            $params[] = $filters['end_date'];
        }
        
        $sql = "SELECT 
                    AVG(gee.star_rating) as avg_rating,
                    CASE 
                        WHEN gee.star_rating >= 4.5 THEN 'Excellent'
                        WHEN gee.star_rating >= 3.5 THEN 'Good'
                        WHEN gee.star_rating >= 2.5 THEN 'Satisfactory'
                        WHEN gee.star_rating >= 1.5 THEN 'Needs Improvement'
                        ELSE 'Unsatisfactory'
                    END as performance_category,
                    COUNT(*) as entry_count
                FROM growth_evidence_entries gee
                $whereClause
                GROUP BY performance_category
                ORDER BY AVG(gee.star_rating) DESC";
        
        $distribution = fetchAll($sql, $params);
        
        // Calculate overall average
        $avgSql = "SELECT AVG(star_rating) as overall_avg FROM growth_evidence_entries gee $whereClause";
        $avgResult = fetchOne($avgSql, $params);
        
        return [
            'avg_rating' => round($avgResult['overall_avg'], 2),
            'distribution' => $distribution
        ];
    }
    
    /**
     * Get evidence-based reporting insights
     * @param array $filters
     * @return array
     */
    private function getEvidenceBasedReportingInsights(array $filters): array {
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $whereClause .= " AND gee.entry_date BETWEEN ? AND ?";
            $params[] = $filters['start_date'];
            $params[] = $filters['end_date'];
        }
        
        // Dimension insights
        $dimensionSql = "SELECT 
                            gee.dimension,
                            COUNT(*) as entry_count,
                            AVG(gee.star_rating) as avg_rating,
                            COUNT(DISTINCT gee.employee_id) as employees_assessed
                         FROM growth_evidence_entries gee
                         $whereClause
                         GROUP BY gee.dimension
                         ORDER BY avg_rating DESC";
        
        $dimensionInsights = fetchAll($dimensionSql, $params);
        
        // Quality insights
        $qualitySql = "SELECT 
                        AVG(LENGTH(content)) as avg_content_length,
                        COUNT(CASE WHEN LENGTH(content) > 100 THEN 1 END) as detailed_count,
                        COUNT(CASE WHEN LENGTH(content) < 50 THEN 1 END) as brief_count,
                        COUNT(*) as total_count
                       FROM growth_evidence_entries gee
                       $whereClause";
        
        $qualityInsights = fetchOne($qualitySql, $params);
        
        return [
            'dimension_insights' => $dimensionInsights,
            'quality_insights' => $qualityInsights,
            'reporting_recommendations' => $this->generateReportingRecommendations($dimensionInsights, $qualityInsights)
        ];
    }
    
    /**
     * Get system adoption metrics
     * @param array $filters
     * @return array
     */
    private function getSystemAdoptionMetrics(array $filters): array {
        // Total employees
        $totalEmployees = $this->getTotalEmployeesCount();
        
        // Active users (employees with evidence entries)
        $whereClause = "WHERE 1=1";
        $params = [];
        
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $whereClause .= " AND gee.entry_date BETWEEN ? AND ?";
            $params[] = $filters['start_date'];
            $params[] = $filters['end_date'];
        }
        
        $activeUsersSql = "SELECT COUNT(DISTINCT gee.employee_id) as active_users
                          FROM growth_evidence_entries gee
                          $whereClause";
        
        $activeUsersResult = fetchOne($activeUsersSql, $params);
        $activeUsers = $activeUsersResult['active_users'];
        
        $adoptionRate = $totalEmployees > 0 ? ($activeUsers / $totalEmployees) * 100 : 0;
        
        return [
            'total_employees' => $totalEmployees,
            'active_users' => $activeUsers,
            'adoption_rate' => round($adoptionRate, 1),
            'adoption_status' => $this->getAdoptionStatus($adoptionRate)
        ];
    }
    
    /**
     * Helper methods for calculations and utilities
     */
    private function calculateUsageTrends(array $dailyUsage): array {
        if (count($dailyUsage) < 2) {
            return ['trend' => 'stable', 'change_percentage' => 0];
        }
        
        $recent = array_slice($dailyUsage, 0, 7);
        $previous = array_slice($dailyUsage, 7, 7);
        
        $recentAvg = array_sum(array_column($recent, 'daily_entries')) / count($recent);
        $previousAvg = array_sum(array_column($previous, 'daily_entries')) / count($previous);
        
        if ($previousAvg == 0) {
            return ['trend' => 'stable', 'change_percentage' => 0];
        }
        
        $changePercentage = (($recentAvg - $previousAvg) / $previousAvg) * 100;
        
        $trend = 'stable';
        if ($changePercentage > 10) $trend = 'increasing';
        elseif ($changePercentage < -10) $trend = 'decreasing';
        
        return [
            'trend' => $trend,
            'change_percentage' => round($changePercentage, 1)
        ];
    }
    
    private function generateReportingRecommendations(array $dimensionInsights, array $qualityInsights): array {
        $recommendations = [];
        
        // Dimension recommendations
        foreach ($dimensionInsights as $dimension) {
            if ($dimension['avg_rating'] < 3.0) {
                $recommendations[] = "Focus on improving {$dimension['dimension']} performance across the organization";
            }
            if ($dimension['employees_assessed'] < 10) {
                $recommendations[] = "Increase evidence collection for {$dimension['dimension']} dimension";
            }
        }
        
        // Quality recommendations
        if ($qualityInsights['avg_content_length'] < 50) {
            $recommendations[] = "Encourage more detailed feedback entries to improve assessment quality";
        }
        
        if (($qualityInsights['brief_count'] / $qualityInsights['total_count']) > 0.5) {
            $recommendations[] = "Provide training on writing effective performance feedback";
        }
        
        return $recommendations;
    }
    
    private function getAdoptionStatus(float $adoptionRate): string {
        if ($adoptionRate >= 80) return 'excellent';
        if ($adoptionRate >= 60) return 'good';
        if ($adoptionRate >= 40) return 'moderate';
        if ($adoptionRate >= 20) return 'low';
        return 'very_low';
    }
    
    private function getTotalEmployeesCount(): int {
        $result = fetchOne("SELECT COUNT(*) as count FROM employees WHERE active = 1");
        return $result['count'];
    }
    
    private function getActiveEvaluationsCount(array $teamMemberIds = null, array $period = null): int {
        $whereClause = "WHERE e.status IN ('draft', 'submitted')";
        $params = [];
        
        if ($teamMemberIds) {
            $placeholders = str_repeat('?,', count($teamMemberIds) - 1) . '?';
            $whereClause .= " AND e.employee_id IN ($placeholders)";
            $params = array_merge($params, $teamMemberIds);
        }
        
        if ($period) {
            $whereClause .= " AND e.period_id = (SELECT period_id FROM evaluation_periods WHERE start_date = ? AND end_date = ?)";
            $params[] = $period['start_date'];
            $params[] = $period['end_date'];
        }
        
        $sql = "SELECT COUNT(*) as count FROM evaluations e $whereClause";
        $result = fetchOne($sql, $params);
        return $result['count'];
    }
    
    /**
     * Helper methods for empty dashboards
     */
    private function getEmptyManagerDashboard(): array {
        return [
            'team_overview' => ['team_size' => 0, 'active_evaluations' => 0, 'evidence_entries_total' => 0, 'avg_team_rating' => 0],
            'evidence_trends' => ['total_entries' => 0, 'trends' => [], 'dimension_breakdown' => []],
            'performance_insights' => ['avg_team_rating' => 0, 'performance_distribution' => [], 'team_size_analyzed' => 0],
            'feedback_analytics' => ['avg_frequency' => 0, 'frequency_distribution' => []],
            'quality_indicators' => ['avg_content_length' => 0, 'quality_metrics' => []],
            'team_comparison' => ['dimension_comparison' => [], 'employee_comparison' => []],
            'coaching_opportunities' => ['opportunities' => [], 'priority_areas' => []],
            'team_members' => [],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    private function getEmptyEmployeeDashboard(): array {
        return [
            'personal_overview' => ['total_evidence_entries' => 0, 'current_rating' => 0, 'rating_trend' => 'stable', 'dimensions_covered' => 0],
            'feedback_summary' => ['total_entries' => 0, 'current_rating' => 0, 'dimensions_covered' => 0],
            'performance_trends' => ['rating_trend' => 'stable', 'monthly_data' => []],
            'development_recommendations' => [],
            'goal_progress' => [],
            'peer_comparison' => ['comparison_available' => false],
            'evidence_history' => ['timeline' => [], 'total_entries' => 0],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    private function getEmptyHRDashboard(): array {
        return [
            'organizational_overview' => ['total_employees' => 0, 'total_evidence_entries' => 0, 'active_evaluations' => 0, 'system_adoption_rate' => 0, 'avg_organizational_rating' => 0],
            'organizational_patterns' => ['total_entries' => 0, 'avg_rating' => 0, 'active_employees' => 0],
            'department_comparison' => [],
            'usage_analytics' => ['daily_usage' => [], 'manager_activity' => []],
            'performance_distribution' => ['avg_rating' => 0, 'distribution' => []],
            'reporting_insights' => ['dimension_insights' => [], 'quality_insights' => []],
            'adoption_metrics' => ['total_employees' => 0, 'active_users' => 0, 'adoption_rate' => 0],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }
}
?>