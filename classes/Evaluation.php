<?php
/**
 * Evidence-Based Evaluation Management Class
 * Continuous Performance System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Employee.php';
require_once __DIR__ . '/EvaluationPeriod.php';
require_once __DIR__ . '/GrowthEvidenceJournal.php';
require_once __DIR__ . '/JobTemplate.php';

class Evaluation {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDbConnection();
    }
    
    /**
     * Create new evidence-based evaluation
     * @param array $evaluationData
     * @return int|false
     */
    public function createEvaluation($evaluationData) {
        try {
            // Validate required fields
            $required = ['employee_id', 'evaluator_id', 'period_id'];
            foreach ($required as $field) {
                if (empty($evaluationData[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            // Check if evaluation already exists for this employee and period
            if ($this->evaluationExists($evaluationData['employee_id'], $evaluationData['period_id'])) {
                throw new Exception("Evaluation already exists for this employee and period");
            }
            
            // Get employee's manager
            $employeeClass = new Employee();
            $employee = $employeeClass->getEmployeeById($evaluationData['employee_id']);
            
            if (!$employee) {
                throw new Exception("Employee not found");
            }
            
            // Get the employee's manager_id for direct relationship
            $managerId = $employee['manager_id'];
            if (empty($managerId)) {
                error_log("WARNING: Employee {$evaluationData['employee_id']} has no manager assigned");
            }
            
            // Insert evaluation with manager_id for direct relationship
            $sql = "INSERT INTO evaluations (employee_id, evaluator_id, manager_id, period_id, status)
                    VALUES (?, ?, ?, ?, 'draft')";
            $evaluationId = insertRecord($sql, [
                $evaluationData['employee_id'],
                $evaluationData['evaluator_id'],
                $managerId,
                $evaluationData['period_id']
            ]);
            
            // Get evaluation period for evidence aggregation
            $periodClass = new EvaluationPeriod();
            $period = $periodClass->getPeriodById($evaluationData['period_id']);
            
            if ($period) {
                // Automatically aggregate evidence for the new evaluation
                $this->aggregateEvidence($evaluationId, $evaluationData['employee_id'], $period);
                error_log("Evidence aggregated for new evaluation ID: $evaluationId");
            } else {
                error_log("WARNING: Could not find period {$evaluationData['period_id']} for evidence aggregation");
            }
            
            // Log evaluation creation
            logActivity($_SESSION['user_id'] ?? null, 'evaluation_created', 'evaluations', $evaluationId, null, $evaluationData);
            
            return $evaluationId;
        } catch (Exception $e) {
            error_log("Create evaluation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create evaluation from evidence journal
     * @param int $employeeId
     * @param int $periodId
     * @param int $evaluatorId
     * @return int
     */
    public function createFromEvidenceJournal(int $employeeId, int $periodId, int $evaluatorId): int {
        return $this->createEvaluation([
            'employee_id' => $employeeId,
            'evaluator_id' => $evaluatorId,
            'period_id' => $periodId
        ]);
    }
    
    /**
     * Aggregate evidence for an evaluation with enhanced algorithms
     * @param int $evaluationId
     * @param int $employeeId
     * @param array $period
     * @return bool
     */
    public function aggregateEvidence(int $evaluationId, int $employeeId, $period): bool {
        try {
            $startTime = microtime(true);
            error_log("Starting evidence aggregation for evaluation $evaluationId, employee $employeeId");
            
            // Validate inputs
            if (!$this->validateAggregationInputs($evaluationId, $employeeId, $period)) {
                throw new Exception("Invalid aggregation inputs");
            }
            
            // Get evidence journal data for the period
            $journalClass = new GrowthEvidenceJournal();
            $evidenceByDimension = $journalClass->getEvidenceByDimension($employeeId, $period['start_date'], $period['end_date']);
            
            if (empty($evidenceByDimension)) {
                error_log("No evidence found for employee $employeeId in period {$period['start_date']} to {$period['end_date']}");
                // Create empty results for all dimensions to maintain consistency
                $this->createEmptyEvidenceResults($evaluationId);
                return true;
            }
            
            // Clear existing results for this evaluation
            $this->clearExistingEvidenceResults($evaluationId);
            
            $aggregationResults = [];
            $totalWeightedScore = 0;
            $totalWeight = 0;
            
            // Process each dimension with enhanced calculations
            foreach ($evidenceByDimension as $dimensionData) {
                $enhancedResult = $this->calculateEnhancedDimensionMetrics($dimensionData);
                $aggregationResults[] = $enhancedResult;
                
                // Store aggregated results with enhanced metrics
                $sql = "INSERT INTO evidence_evaluation_results
                        (evaluation_id, dimension, evidence_count, avg_star_rating, total_positive_entries, total_negative_entries, calculated_score)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                insertRecord($sql, [
                    $evaluationId,
                    $dimensionData['dimension'],
                    $enhancedResult['entry_count'],
                    $enhancedResult['avg_rating'],
                    $enhancedResult['positive_count'],
                    $enhancedResult['negative_count'],
                    $enhancedResult['calculated_score']
                ]);
                
                // Calculate weighted contribution to overall score
                $dimensionWeight = $this->getDimensionWeight($dimensionData['dimension']);
                $totalWeightedScore += $enhancedResult['calculated_score'] * $dimensionWeight;
                $totalWeight += $dimensionWeight;
            }
            
            // Calculate overall evidence rating with confidence metrics
            $overallMetrics = $this->calculateOverallEvidenceMetrics($evaluationId, $aggregationResults);
            
            // Update evaluation with comprehensive evidence data
            $this->updateEvaluation($evaluationId, [
                'evidence_rating' => $overallMetrics['overall_rating'],
                'evidence_summary' => $this->generateEnhancedEvidenceSummary($evaluationId, $overallMetrics)
            ]);
            
            // Log performance metrics
            $executionTime = microtime(true) - $startTime;
            error_log("Evidence aggregation completed for evaluation $evaluationId in {$executionTime}s");
            
            return true;
        } catch (Exception $e) {
            error_log("Aggregate evidence error for evaluation $evaluationId: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Validate aggregation inputs
     * @param int $evaluationId
     * @param int $employeeId
     * @param array $period
     * @return bool
     */
    private function validateAggregationInputs(int $evaluationId, int $employeeId, array $period): bool {
        if ($evaluationId <= 0 || $employeeId <= 0) {
            error_log("Invalid evaluation ID ($evaluationId) or employee ID ($employeeId)");
            return false;
        }
        
        if (empty($period['start_date']) || empty($period['end_date'])) {
            error_log("Invalid period dates: start={$period['start_date']}, end={$period['end_date']}");
            return false;
        }
        
        if (strtotime($period['start_date']) > strtotime($period['end_date'])) {
            error_log("Start date cannot be after end date");
            return false;
        }
        
        return true;
    }
    
    /**
     * Clear existing evidence results for re-aggregation
     * @param int $evaluationId
     * @return bool
     */
    private function clearExistingEvidenceResults(int $evaluationId): bool {
        try {
            $sql = "DELETE FROM evidence_evaluation_results WHERE evaluation_id = ?";
            updateRecord($sql, [$evaluationId]);
            return true;
        } catch (Exception $e) {
            error_log("Error clearing existing evidence results: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create empty evidence results for all dimensions when no evidence exists
     * @param int $evaluationId
     * @return bool
     */
    private function createEmptyEvidenceResults(int $evaluationId): bool {
        try {
            $dimensions = ['responsibilities', 'kpis', 'competencies', 'values'];
            
            foreach ($dimensions as $dimension) {
                $sql = "INSERT INTO evidence_evaluation_results
                        (evaluation_id, dimension, evidence_count, avg_star_rating, total_positive_entries, total_negative_entries, calculated_score)
                        VALUES (?, ?, 0, 0.00, 0, 0, 0.00)";
                
                insertRecord($sql, [$evaluationId, $dimension]);
            }
            
            // Update evaluation with zero evidence rating
            $this->updateEvaluation($evaluationId, ['evidence_rating' => 0.00]);
            
            return true;
        } catch (Exception $e) {
            error_log("Error creating empty evidence results: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate enhanced dimension metrics with advanced algorithms
     * @param array $dimensionData
     * @return array
     */
    private function calculateEnhancedDimensionMetrics(array $dimensionData): array {
        $entryCount = (int)$dimensionData['entry_count'];
        $avgRating = (float)$dimensionData['avg_rating'];
        $positiveCount = (int)$dimensionData['positive_count'];
        $negativeCount = (int)$dimensionData['negative_count'];
        
        // Calculate confidence factor based on sample size
        $confidenceFactor = $this->calculateConfidenceFactor($entryCount);
        
        // Calculate trend factor (positive vs negative ratio)
        $trendFactor = $this->calculateTrendFactor($positiveCount, $negativeCount, $entryCount);
        
        // Calculate recency factor (if we had entry dates, we could weight recent entries more)
        $recencyFactor = 1.0; // Default to neutral for now
        
        // Enhanced score calculation with multiple factors
        $baseScore = $avgRating;
        $enhancedScore = $baseScore * $confidenceFactor * $trendFactor * $recencyFactor;
        
        // Ensure score stays within valid range (0-5)
        $enhancedScore = max(0, min(5, $enhancedScore));
        
        return [
            'entry_count' => $entryCount,
            'avg_rating' => round($avgRating, 2),
            'positive_count' => $positiveCount,
            'negative_count' => $negativeCount,
            'calculated_score' => round($enhancedScore, 2),
            'confidence_factor' => round($confidenceFactor, 3),
            'trend_factor' => round($trendFactor, 3),
            'recency_factor' => round($recencyFactor, 3)
        ];
    }
    
    /**
     * Calculate confidence factor based on sample size
     * @param int $entryCount
     * @return float
     */
    private function calculateConfidenceFactor(int $entryCount): float {
        if ($entryCount == 0) return 0.0;
        if ($entryCount == 1) return 0.5; // Low confidence with single entry
        if ($entryCount <= 3) return 0.7; // Moderate confidence
        if ($entryCount <= 7) return 0.85; // Good confidence
        if ($entryCount <= 15) return 0.95; // High confidence
        return 1.0; // Maximum confidence with 15+ entries
    }
    
    /**
     * Calculate trend factor based on positive/negative ratio
     * @param int $positiveCount
     * @param int $negativeCount
     * @param int $totalCount
     * @return float
     */
    private function calculateTrendFactor(int $positiveCount, int $negativeCount, int $totalCount): float {
        if ($totalCount == 0) return 1.0;
        
        $neutralCount = $totalCount - $positiveCount - $negativeCount;
        
        // Calculate weighted trend score
        $trendScore = ($positiveCount * 1.0 + $neutralCount * 0.5 + $negativeCount * 0.0) / $totalCount;
        
        // Convert to factor (0.8 to 1.2 range)
        return 0.8 + ($trendScore * 0.4);
    }
    
    /**
     * Get dimension weight for overall calculation
     * @param string $dimension
     * @return float
     */
    private function getDimensionWeight(string $dimension): float {
        $weights = [
            'kpis' => 0.30,           // 30% - Key Performance Indicators
            'competencies' => 0.25,   // 25% - Skills and Competencies
            'responsibilities' => 0.25, // 25% - Key Responsibilities
            'values' => 0.20          // 20% - Company Values
        ];
        
        return $weights[$dimension] ?? 0.25; // Default equal weight
    }
    
    /**
     * Calculate overall evidence metrics with confidence indicators
     * @param int $evaluationId
     * @param array $aggregationResults
     * @return array
     */
    private function calculateOverallEvidenceMetrics(int $evaluationId, array $aggregationResults): array {
        if (empty($aggregationResults)) {
            return [
                'overall_rating' => 0.00,
                'confidence_level' => 'none',
                'total_entries' => 0,
                'coverage_score' => 0.0
            ];
        }
        
        $totalWeightedScore = 0;
        $totalWeight = 0;
        $totalEntries = 0;
        $dimensionsWithEvidence = 0;
        $totalConfidence = 0;
        
        foreach ($aggregationResults as $result) {
            $dimension = $result['dimension'] ?? '';
            $weight = $this->getDimensionWeight($dimension);
            $entryCount = $result['entry_count'] ?? 0;
            $confidenceFactor = $result['confidence_factor'] ?? 0;
            
            $totalWeightedScore += $result['calculated_score'] * $weight;
            $totalWeight += $weight;
            $totalEntries += $entryCount;
            
            if ($entryCount > 0) {
                $dimensionsWithEvidence++;
                $totalConfidence += $confidenceFactor;
            }
        }
        
        $overallRating = $totalWeight > 0 ? $totalWeightedScore / $totalWeight : 0;
        $coverageScore = $dimensionsWithEvidence / 4.0; // 4 total dimensions
        $avgConfidence = $dimensionsWithEvidence > 0 ? $totalConfidence / $dimensionsWithEvidence : 0;
        
        // Determine confidence level
        $confidenceLevel = $this->determineConfidenceLevel($avgConfidence, $coverageScore, $totalEntries);
        
        return [
            'overall_rating' => round($overallRating, 2),
            'confidence_level' => $confidenceLevel,
            'total_entries' => $totalEntries,
            'coverage_score' => round($coverageScore, 2),
            'dimensions_with_evidence' => $dimensionsWithEvidence,
            'average_confidence' => round($avgConfidence, 3)
        ];
    }
    
    /**
     * Determine confidence level based on multiple factors
     * @param float $avgConfidence
     * @param float $coverageScore
     * @param int $totalEntries
     * @return string
     */
    private function determineConfidenceLevel(float $avgConfidence, float $coverageScore, int $totalEntries): string {
        if ($totalEntries == 0) return 'none';
        if ($totalEntries < 3 || $coverageScore < 0.5) return 'low';
        if ($totalEntries < 8 || $coverageScore < 0.75 || $avgConfidence < 0.7) return 'moderate';
        if ($totalEntries < 15 || $avgConfidence < 0.85) return 'good';
        return 'high';
    }
    
    /**
     * Calculate dimension score based on evidence (legacy compatibility)
     * @param array $dimensionData
     * @return float
     */
    private function calculateDimensionScore(array $dimensionData): float {
        $enhanced = $this->calculateEnhancedDimensionMetrics($dimensionData);
        return $enhanced['calculated_score'];
    }
    
    /**
     * Calculate overall evidence rating with enhanced weighting
     * @param int $evaluationId
     * @return float
     */
    public function calculateOverallEvidenceRating(int $evaluationId): float {
        try {
            $sql = "SELECT dimension, calculated_score, evidence_count FROM evidence_evaluation_results WHERE evaluation_id = ?";
            $results = fetchAll($sql, [$evaluationId]);
            
            if (empty($results)) {
                return 0.0;
            }
            
            $totalWeightedScore = 0;
            $totalWeight = 0;
            
            foreach ($results as $result) {
                $weight = $this->getDimensionWeight($result['dimension']);
                $score = (float)$result['calculated_score'];
                
                // Apply evidence count factor for reliability
                $evidenceCountFactor = $this->calculateConfidenceFactor((int)$result['evidence_count']);
                $adjustedScore = $score * $evidenceCountFactor;
                
                $totalWeightedScore += $adjustedScore * $weight;
                $totalWeight += $weight;
            }
            
            return $totalWeight > 0 ? round($totalWeightedScore / $totalWeight, 2) : 0.0;
        } catch (Exception $e) {
            error_log("Calculate overall evidence rating error: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Generate enhanced evidence summary for evaluation
     * @param int $evaluationId
     * @param array $overallMetrics
     * @return string
     */
    public function generateEnhancedEvidenceSummary(int $evaluationId, array $overallMetrics = null): string {
        try {
            $sql = "SELECT dimension, evidence_count, avg_star_rating, total_positive_entries, total_negative_entries, calculated_score
                    FROM evidence_evaluation_results
                    WHERE evaluation_id = ?
                    ORDER BY calculated_score DESC, avg_star_rating DESC";
            
            $results = fetchAll($sql, [$evaluationId]);
            
            if (empty($results)) {
                return "No evidence entries found for this evaluation period. Consider collecting more feedback data for a comprehensive assessment.";
            }
            
            // Get overall metrics if not provided
            if ($overallMetrics === null) {
                $overallMetrics = $this->calculateOverallEvidenceMetrics($evaluationId, $results);
            }
            
            $summary = "=== EVIDENCE-BASED EVALUATION SUMMARY ===\n\n";
            
            // Overall performance section
            $summary .= "OVERALL PERFORMANCE:\n";
            $summary .= "• Overall Rating: " . $overallMetrics['overall_rating'] . "/5.0\n";
            $summary .= "• Confidence Level: " . ucfirst($overallMetrics['confidence_level']) . "\n";
            $summary .= "• Total Evidence Entries: " . $overallMetrics['total_entries'] . "\n";
            $summary .= "• Dimension Coverage: " . ($overallMetrics['dimensions_with_evidence'] ?? 0) . "/4 areas\n\n";
            
            // Performance by dimension
            $summary .= "PERFORMANCE BY DIMENSION:\n";
            
            foreach ($results as $result) {
                $dimensionName = ucfirst(str_replace('_', ' ', $result['dimension']));
                $weight = $this->getDimensionWeight($result['dimension']) * 100;
                
                $summary .= "\n{$dimensionName} (Weight: {$weight}%):\n";
                $summary .= "  • Score: " . $result['calculated_score'] . "/5.0\n";
                $summary .= "  • Evidence Entries: " . $result['evidence_count'] . "\n";
                
                if ($result['evidence_count'] > 0) {
                    $summary .= "  • Average Rating: " . round($result['avg_star_rating'], 2) . "/5.0\n";
                    $summary .= "  • Positive Feedback: " . $result['total_positive_entries'] . " entries\n";
                    $summary .= "  • Development Areas: " . $result['total_negative_entries'] . " entries\n";
                    
                    // Performance indicator
                    $performance = $this->getPerformanceIndicator($result['calculated_score']);
                    $summary .= "  • Performance Level: {$performance}\n";
                } else {
                    $summary .= "  • Status: No evidence collected for this dimension\n";
                }
            }
            
            // Recommendations section
            $summary .= "\n" . $this->generateRecommendations($results, $overallMetrics);
            
            return $summary;
        } catch (Exception $e) {
            error_log("Generate enhanced evidence summary error: " . $e->getMessage());
            return "Error generating evidence summary. Please contact system administrator.";
        }
    }
    
    /**
     * Generate evidence summary for evaluation (legacy compatibility)
     * @param int $evaluationId
     * @return string
     */
    public function generateEvidenceSummary(int $evaluationId): string {
        return $this->generateEnhancedEvidenceSummary($evaluationId);
    }
    
    /**
     * Get performance indicator based on score
     * @param float $score
     * @return string
     */
    private function getPerformanceIndicator(float $score): string {
        if ($score >= 4.5) return "Exceptional";
        if ($score >= 4.0) return "Exceeds Expectations";
        if ($score >= 3.5) return "Meets Expectations";
        if ($score >= 2.5) return "Below Expectations";
        if ($score >= 1.0) return "Needs Significant Improvement";
        return "No Evidence Available";
    }
    
    /**
     * Generate recommendations based on evidence analysis
     * @param array $results
     * @param array $overallMetrics
     * @return string
     */
    private function generateRecommendations(array $results, array $overallMetrics): string {
        $recommendations = "RECOMMENDATIONS:\n";
        
        // Overall confidence recommendations
        switch ($overallMetrics['confidence_level']) {
            case 'none':
            case 'low':
                $recommendations .= "• Increase feedback frequency - more evidence needed for reliable assessment\n";
                break;
            case 'moderate':
                $recommendations .= "• Continue regular feedback collection to improve assessment reliability\n";
                break;
            case 'good':
            case 'high':
                $recommendations .= "• Excellent evidence collection - assessment is highly reliable\n";
                break;
        }
        
        // Dimension-specific recommendations
        $strengthAreas = [];
        $developmentAreas = [];
        $noEvidenceAreas = [];
        
        foreach ($results as $result) {
            $dimension = ucfirst(str_replace('_', ' ', $result['dimension']));
            
            if ($result['evidence_count'] == 0) {
                $noEvidenceAreas[] = $dimension;
            } elseif ($result['calculated_score'] >= 4.0) {
                $strengthAreas[] = $dimension;
            } elseif ($result['calculated_score'] < 3.0) {
                $developmentAreas[] = $dimension;
            }
        }
        
        if (!empty($strengthAreas)) {
            $recommendations .= "• Strengths to leverage: " . implode(', ', $strengthAreas) . "\n";
        }
        
        if (!empty($developmentAreas)) {
            $recommendations .= "• Areas for development: " . implode(', ', $developmentAreas) . "\n";
        }
        
        if (!empty($noEvidenceAreas)) {
            $recommendations .= "• Collect evidence for: " . implode(', ', $noEvidenceAreas) . "\n";
        }
        
        return $recommendations;
    }
    
    /**
     * Update evaluation status and metadata
     * @param int $evaluationId
     * @param array $evaluationData
     * @return bool
     */
    public function updateEvaluation($evaluationId, $evaluationData) {
        try {
            // Get current evaluation data for logging
            $currentEvaluation = $this->getEvaluationById($evaluationId);
            if (!$currentEvaluation) {
                throw new Exception("Evaluation not found");
            }
            
            $updateFields = [];
            $params = [];
            
            // Overall evaluation
            if (isset($evaluationData['evidence_summary'])) {
                $updateFields[] = "evidence_summary = ?";
                $params[] = $evaluationData['evidence_summary'];
            }
            
            if (isset($evaluationData['evidence_rating'])) {
                $updateFields[] = "evidence_rating = ?";
                $params[] = $evaluationData['evidence_rating'];
            }
            
            // Goals and development
            if (isset($evaluationData['goals_next_period'])) {
                $updateFields[] = "goals_next_period = ?";
                $params[] = $evaluationData['goals_next_period'];
            }
            
            if (isset($evaluationData['development_areas'])) {
                $updateFields[] = "development_areas = ?";
                $params[] = $evaluationData['development_areas'];
            }
            
            if (isset($evaluationData['strengths'])) {
                $updateFields[] = "strengths = ?";
                $params[] = $evaluationData['strengths'];
            }
            
            // Status
            if (isset($evaluationData['status'])) {
                $updateFields[] = "status = ?";
                $params[] = $evaluationData['status'];
                
                // Set timestamps based on status
                if ($evaluationData['status'] === 'submitted') {
                    $updateFields[] = "submitted_at = NOW()";
                } elseif ($evaluationData['status'] === 'reviewed') {
                    $updateFields[] = "reviewed_at = NOW()";
                } elseif ($evaluationData['status'] === 'approved') {
                    $updateFields[] = "approved_at = NOW()";
                }
            }
            
            if (empty($updateFields)) {
                return true; // Nothing to update
            }
            
            $params[] = $evaluationId;
            $sql = "UPDATE evaluations SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE evaluation_id = ?";
            
            $affected = updateRecord($sql, $params);
            
            // Log evaluation update (even if no rows were affected, the operation was successful)
            logActivity($_SESSION['user_id'] ?? null, 'evaluation_updated', 'evaluations', $evaluationId, $currentEvaluation, $evaluationData);
            
            return true; // Return true if the query executed without errors, regardless of affected rows
        } catch (Exception $e) {
            error_log("Update evaluation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get evaluation by ID
     * @param int $evaluationId
     * @return array|false
     */
    public function getEvaluationById($evaluationId) {
        $sql = "SELECT e.*,
                       emp.first_name as employee_first_name, emp.last_name as employee_last_name,
                       emp.employee_number, emp.position, emp.department,
                       eval_user.username as evaluator_username,
                       eval_emp.first_name as evaluator_first_name, eval_emp.last_name as evaluator_last_name,
                       p.period_name, p.start_date, p.end_date
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                JOIN users eval_user ON e.evaluator_id = eval_user.user_id
                LEFT JOIN employees eval_emp ON eval_user.user_id = eval_emp.user_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                WHERE e.evaluation_id = ?";
        
        $evaluation = fetchOne($sql, [$evaluationId]);
        
        if ($evaluation) {
            // Check if evidence has been aggregated for this evaluation
            $evidenceResults = $this->getEvidenceResults($evaluationId);
            
            // If no evidence results exist, try to aggregate evidence
            if (empty($evidenceResults)) {
                $period = [
                    'start_date' => $evaluation['start_date'],
                    'end_date' => $evaluation['end_date']
                ];
                
                $aggregated = $this->aggregateEvidence($evaluationId, $evaluation['employee_id'], $period);
                if ($aggregated) {
                    error_log("Evidence auto-aggregated for evaluation ID: $evaluationId");
                }
            }
        }
        
        return $evaluation;
    }
    
    /**
     * Get evidence-based evaluation data
     * @param int $evaluationId
     * @return array|false
     */
    public function getEvidenceEvaluation(int $evaluationId) {
        $evaluation = $this->getEvaluationById($evaluationId);
        if (!$evaluation) {
            return false;
        }
        
        // Get evidence results by dimension
        $evaluation['evidence_results'] = $this->getEvidenceResults($evaluationId);
        
        // Get evidence summary
        $evaluation['evidence_summary_text'] = $this->generateEvidenceSummary($evaluationId);
        
        // Get job template data for this employee
        $jobTemplateData = $this->getJobTemplateDataForEvaluation($evaluation['employee_id']);
        
        // Initialize the results arrays with job template data
        $evaluation['kpi_results'] = $jobTemplateData['kpis'] ?? [];
        $evaluation['competency_results'] = $jobTemplateData['competencies'] ?? [];
        $evaluation['responsibility_results'] = $jobTemplateData['responsibilities'] ?? [];
        $evaluation['value_results'] = $jobTemplateData['values'] ?? [];
        $evaluation['section_weights'] = $jobTemplateData['section_weights'] ?? [
            'kpis' => 25,
            'competencies' => 25,
            'responsibilities' => 25,
            'values' => 25
        ];
        
        // Create evidence summary entries for dimensions that have evidence
        if (!empty($evaluation['evidence_results'])) {
            foreach ($evaluation['evidence_results'] as $result) {
                // Create evidence summary entries that show evidence-based ratings
                switch ($result['dimension']) {
                    case 'kpis':
                        // Add evidence summary at the beginning of KPIs
                        array_unshift($evaluation['kpi_results'], [
                            'kpi_id' => 'evidence_' . $result['dimension'],
                            'kpi_name' => 'Evidence-Based KPI Summary',
                            'category' => 'Performance Evidence',
                            'target_value' => 5.0,
                            'measurement_unit' => 'Stars',
                            'achieved_value' => $result['avg_star_rating'],
                            'score' => $result['calculated_score'],
                            'comments' => "Based on {$result['evidence_count']} evidence entries (Positive: {$result['total_positive_entries']}, Areas for improvement: {$result['total_negative_entries']})"
                        ]);
                        break;
                    case 'competencies':
                        // Add evidence summary at the beginning of competencies
                        array_unshift($evaluation['competency_results'], [
                            'competency_id' => 'evidence_' . $result['dimension'],
                            'competency_name' => 'Evidence-Based Competency Summary',
                            'category_name' => 'Performance Evidence',
                            'competency_type' => 'evidence_based',
                            'module_type' => 'technical',
                            'required_level' => 'Level 3',
                            'technical_display_level' => 3,
                            'technical_level_name' => 'Evidence Expectation',
                            'technical_symbol_pattern' => '🧩🧩🧩⚪️⚪️',
                            'achieved_level' => $this->getAchievedLevel($result['avg_star_rating']),
                            'score' => $result['calculated_score'],
                            'comments' => "Based on {$result['evidence_count']} evidence entries (Positive: {$result['total_positive_entries']}, Areas for improvement: {$result['total_negative_entries']})"
                        ]);
                        break;
                    case 'responsibilities':
                        // Add evidence summary at the beginning of responsibilities
                        array_unshift($evaluation['responsibility_results'], [
                            'responsibility_id' => 'evidence_' . $result['dimension'],
                            'sort_order' => 0,
                            'responsibility_text' => 'Evidence-Based Responsibility Summary',
                            'score' => $result['calculated_score'],
                            'comments' => "Based on {$result['evidence_count']} evidence entries (Positive: {$result['total_positive_entries']}, Areas for improvement: {$result['total_negative_entries']})"
                        ]);
                        break;
                    case 'values':
                        // Add evidence summary at the beginning of values
                        array_unshift($evaluation['value_results'], [
                            'value_id' => 'evidence_' . $result['dimension'],
                            'value_name' => 'Evidence-Based Values Summary',
                            'description' => 'Assessment based on evidence of living company values',
                            'score' => $result['calculated_score'],
                            'comments' => "Based on {$result['evidence_count']} evidence entries (Positive: {$result['total_positive_entries']}, Areas for improvement: {$result['total_negative_entries']})"
                        ]);
                        break;
                }
            }
        }
        
        return $evaluation;
    }
    
    /**
     * Get job template data for evaluation
     * @param int $employeeId
     * @return array
     */
    private function getJobTemplateDataForEvaluation(int $employeeId): array {
        try {
            // Get employee and their job template
            $employeeClass = new Employee();
            $employee = $employeeClass->getEmployeeById($employeeId);
            
            if (!$employee || empty($employee['job_template_id'])) {
                // Return empty structure if no job template
                return [
                    'kpis' => [],
                    'competencies' => [],
                    'responsibilities' => [],
                    'values' => [],
                    'section_weights' => [
                        'kpis' => 25,
                        'competencies' => 25,
                        'responsibilities' => 25,
                        'values' => 25
                    ]
                ];
            }
            
            // Get job template data
            $jobTemplateClass = new JobTemplate();
            $template = $jobTemplateClass->getCompleteJobTemplate($employee['job_template_id']);
            
            if (!$template) {
                return [
                    'kpis' => [],
                    'competencies' => [],
                    'responsibilities' => [],
                    'values' => [],
                    'section_weights' => [
                        'kpis' => 25,
                        'competencies' => 25,
                        'responsibilities' => 25,
                        'values' => 25
                    ]
                ];
            }
            
            // Calculate section weights based on job template
            $sectionWeights = $this->calculateSectionWeights($template);
            
            // Normalize job template data to match evaluation edit page expectations
            $normalizedData = [
                'kpis' => $this->normalizeKPIs($template['kpis'] ?? []),
                'competencies' => $this->normalizeCompetencies($template['competencies'] ?? []),
                'responsibilities' => $this->normalizeResponsibilities($template['responsibilities'] ?? []),
                'values' => $this->normalizeValues($template['values'] ?? []),
                'section_weights' => $sectionWeights
            ];
            
            return $normalizedData;
        } catch (Exception $e) {
            error_log("Error getting job template data for evaluation: " . $e->getMessage());
            return [
                'kpis' => [],
                'competencies' => [],
                'responsibilities' => [],
                'values' => [],
                'section_weights' => [
                    'kpis' => 25,
                    'competencies' => 25,
                    'responsibilities' => 25,
                    'values' => 25
                ]
            ];
        }
    }
    
    /**
     * Calculate section weights based on job template
     * @param array $template
     * @return array
     */
    private function calculateSectionWeights(array $template): array {
        $weights = [
            'kpis' => 0,
            'competencies' => 0,
            'responsibilities' => 0,
            'values' => 0
        ];
        
        // Calculate weights based on job template items
        if (!empty($template['kpis'])) {
            foreach ($template['kpis'] as $kpi) {
                $weights['kpis'] += floatval($kpi['weight_percentage'] ?? 0);
            }
        }
        
        if (!empty($template['competencies'])) {
            foreach ($template['competencies'] as $competency) {
                $weights['competencies'] += floatval($competency['weight_percentage'] ?? 0);
            }
        }
        
        if (!empty($template['responsibilities'])) {
            foreach ($template['responsibilities'] as $responsibility) {
                $weights['responsibilities'] += floatval($responsibility['weight_percentage'] ?? 0);
            }
        }
        
        if (!empty($template['values'])) {
            foreach ($template['values'] as $value) {
                $weights['values'] += floatval($value['weight_percentage'] ?? 0);
            }
        }
        
        // If all weights are 0, use default equal weights
        $totalWeight = array_sum($weights);
        if ($totalWeight == 0) {
            return [
                'kpis' => 25,
                'competencies' => 25,
                'responsibilities' => 25,
                'values' => 25
            ];
        }
        
        // Normalize to 100%
        foreach ($weights as $key => $weight) {
            $weights[$key] = round(($weight / $totalWeight) * 100, 1);
        }
        
        return $weights;
    }
    
    /**
     * Get evidence results for evaluation
     */
    private function getEvidenceResults(int $evaluationId): array {
        $sql = "SELECT * FROM evidence_evaluation_results WHERE evaluation_id = ? ORDER BY dimension";
        return fetchAll($sql, [$evaluationId]);
    }
    
    /**
     * Get detailed evidence entries for a specific dimension
     * @param int $evaluationId
     * @param string $dimension
     * @return array
     */
    public function getEvidenceEntriesByDimension(int $evaluationId, string $dimension): array {
        try {
            // Get evaluation details to find employee and period
            $evaluation = $this->getEvaluationById($evaluationId);
            if (!$evaluation) {
                return [];
            }
            
            // Get period details
            $periodClass = new EvaluationPeriod();
            $period = $periodClass->getPeriodById($evaluation['period_id']);
            if (!$period) {
                return [];
            }
            
            // Get evidence entries for the dimension within the evaluation period
            $sql = "SELECT gee.*,
                           emp.first_name as manager_first_name,
                           emp.last_name as manager_last_name
                    FROM growth_evidence_entries gee
                    LEFT JOIN employees emp ON gee.manager_id = emp.user_id
                    WHERE gee.employee_id = ?
                    AND gee.dimension = ?
                    AND gee.entry_date >= ?
                    AND gee.entry_date <= ?
                    ORDER BY gee.entry_date DESC";
            
            return fetchAll($sql, [
                $evaluation['employee_id'],
                $dimension,
                $period['start_date'],
                $period['end_date']
            ]);
        } catch (Exception $e) {
            error_log("Get evidence entries by dimension error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get evaluations with pagination and filtering
     * @param int $page
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getEvaluations($page = 1, $limit = RECORDS_PER_PAGE, $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['employee_id'])) {
            $whereClause .= " AND e.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        if (!empty($filters['evaluator_id'])) {
            $whereClause .= " AND e.evaluator_id = ?";
            $params[] = $filters['evaluator_id'];
        }
        
        if (!empty($filters['period_id'])) {
            $whereClause .= " AND e.period_id = ?";
            $params[] = $filters['period_id'];
        }
        
        if (!empty($filters['status'])) {
            $whereClause .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['department'])) {
            $whereClause .= " AND emp.department = ?";
            $params[] = $filters['department'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (emp.first_name LIKE ? OR emp.last_name LIKE ? OR emp.employee_number LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params = array_merge($params, array_fill(0, 3, $searchTerm));
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total 
                     FROM evaluations e
                     JOIN employees emp ON e.employee_id = emp.employee_id
                     $whereClause";
        $totalResult = fetchOne($countSql, $params);
        $total = $totalResult['total'];
        
        // Get evaluations
        $sql = "SELECT e.*, 
                       emp.first_name as employee_first_name, emp.last_name as employee_last_name, 
                       emp.employee_number, emp.position, emp.department,
                       eval_user.username as evaluator_username,
                       eval_emp.first_name as evaluator_first_name, eval_emp.last_name as evaluator_last_name,
                       p.period_name, p.start_date, p.end_date
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                JOIN users eval_user ON e.evaluator_id = eval_user.user_id
                LEFT JOIN employees eval_emp ON eval_user.user_id = eval_emp.user_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                $whereClause 
                ORDER BY e.created_at DESC 
                LIMIT $limit OFFSET $offset";
        
        $evaluations = fetchAll($sql, $params);
        
        return [
            'evaluations' => $evaluations,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'current_page' => $page
        ];
    }
    
    /**
     * Check if evaluation exists for employee and period
     * @param int $employeeId
     * @param int $periodId
     * @return bool
     */
    private function evaluationExists($employeeId, $periodId) {
        $sql = "SELECT COUNT(*) as count FROM evaluations WHERE employee_id = ? AND period_id = ?";
        $result = fetchOne($sql, [$employeeId, $periodId]);
        return $result['count'] > 0;
    }
    
    /**
     * Delete evaluation
     * @param int $evaluationId
     * @return bool
     */
    public function deleteEvaluation($evaluationId) {
        try {
            // Get current evaluation data for logging
            $evaluation = $this->getEvaluationById($evaluationId);
            if (!$evaluation) {
                throw new Exception("Evaluation not found");
            }
            
            // Delete evaluation (cascade will handle related records)
            $sql = "DELETE FROM evaluations WHERE evaluation_id = ?";
            $affected = updateRecord($sql, [$evaluationId]);
            
            if ($affected > 0) {
                // Log evaluation deletion
                logActivity($_SESSION['user_id'] ?? null, 'evaluation_deleted', 'evaluations', $evaluationId, $evaluation, null);
            }
            
            return $affected > 0;
        } catch (Exception $e) {
            error_log("Delete evaluation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get evaluation statistics
     * @param array $filters
     * @return array
     */
    public function getEvaluationStats($filters = []) {
        $stats = [];
        
        $whereClause = "WHERE 1=1";
        $params = [];
        
        // Apply filters
        if (!empty($filters['period_id'])) {
            $whereClause .= " AND e.period_id = ?";
            $params[] = $filters['period_id'];
        }
        
        if (!empty($filters['department'])) {
            $whereClause .= " AND emp.department = ?";
            $params[] = $filters['department'];
        }
        
        // Total evaluations
        $sql = "SELECT COUNT(*) as count FROM evaluations e JOIN employees emp ON e.employee_id = emp.employee_id $whereClause";
        $result = fetchOne($sql, $params);
        $stats['total_evaluations'] = $result['count'];
        
        // Evaluations by status
        $sql = "SELECT status, COUNT(*) as count FROM evaluations e JOIN employees emp ON e.employee_id = emp.employee_id $whereClause GROUP BY status";
        $result = fetchAll($sql, $params);
        $stats['by_status'] = [];
        foreach ($result as $row) {
            $stats['by_status'][$row['status']] = $row['count'];
        }
        
        // Average evidence rating
        $sql = "SELECT AVG(evidence_rating) as avg_rating FROM evaluations e JOIN employees emp ON e.employee_id = emp.employee_id $whereClause AND evidence_rating IS NOT NULL";
        $result = fetchOne($sql, $params);
        $stats['average_rating'] = $result['avg_rating'] ? round($result['avg_rating'], 2) : 0;
        
        // Rating distribution
        $sql = "SELECT
                    CASE
                        WHEN evidence_rating >= 4.5 THEN 'Excellent (4.5-5.0)'
                        WHEN evidence_rating >= 3.5 THEN 'Good (3.5-4.4)'
                        WHEN evidence_rating >= 2.5 THEN 'Satisfactory (2.5-3.4)'
                        WHEN evidence_rating >= 1.5 THEN 'Needs Improvement (1.5-2.4)'
                        ELSE 'Unsatisfactory (1.0-1.4)'
                    END as rating_range,
                    COUNT(*) as count
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                $whereClause AND evidence_rating IS NOT NULL
                GROUP BY rating_range
                ORDER BY MIN(evidence_rating) DESC";
        $result = fetchAll($sql, $params);
        $stats['rating_distribution'] = $result;
        
        return $stats;
    }
    
    /**
     * Get evaluations where user is the evaluator
     * @param int $evaluatorId
     * @param array $filters
     * @return array
     */
    public function getEvaluatorEvaluations($evaluatorId, $filters = []) {
        $whereClause = "WHERE e.evaluator_id = ?";
        $params = [$evaluatorId];
        
        // Apply additional filters
        if (!empty($filters['status'])) {
            $whereClause .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['period_id'])) {
            $whereClause .= " AND e.period_id = ?";
            $params[] = $filters['period_id'];
        }
        
        $sql = "SELECT e.*,
                       emp.first_name as employee_first_name, emp.last_name as employee_last_name,
                       emp.employee_number, emp.position, emp.department,
                       p.period_name, p.start_date, p.end_date
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                $whereClause
                ORDER BY e.created_at DESC";
        
        return fetchAll($sql, $params);
    }
    
    /**
     * Get evaluations for a specific manager (direct relationship)
     * @param int $managerId
     * @param array $filters
     * @return array
     */
    public function getManagerEvaluations($managerId, $filters = []) {
        $whereClause = "WHERE e.manager_id = ?";
        $params = [$managerId];
        
        // Apply additional filters
        if (!empty($filters['status'])) {
            $whereClause .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['period_id'])) {
            $whereClause .= " AND e.period_id = ?";
            $params[] = $filters['period_id'];
        }
        
        if (!empty($filters['employee_id'])) {
            $whereClause .= " AND e.employee_id = ?";
            $params[] = $filters['employee_id'];
        }
        
        $sql = "SELECT e.*,
                       emp.first_name as employee_first_name, emp.last_name as employee_last_name,
                       emp.employee_number, emp.position, emp.department,
                       mgr.first_name as manager_first_name, mgr.last_name as manager_last_name,
                       p.period_name, p.start_date, p.end_date
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                LEFT JOIN employees mgr ON e.manager_id = mgr.employee_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                $whereClause
                ORDER BY e.created_at DESC";
        
        $evaluations = fetchAll($sql, $params);
        
        return $evaluations;
    }
    
    /**
     * Get evaluations for a specific employee (where they are being evaluated)
     * @param int $employeeId
     * @param array $filters
     * @return array
     */
    public function getEmployeeEvaluations($employeeId, $filters = []) {
        $whereClause = "WHERE e.employee_id = ?";
        $params = [$employeeId];
        
        // Apply additional filters
        if (!empty($filters['status'])) {
            $whereClause .= " AND e.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['period_id'])) {
            $whereClause .= " AND e.period_id = ?";
            $params[] = $filters['period_id'];
        }
        
        $sql = "SELECT e.*, e.evidence_rating,
                       emp.first_name as employee_first_name, emp.last_name as employee_last_name,
                       emp.employee_number, emp.position, emp.department,
                       eval_user.username as evaluator_username,
                       eval_emp.first_name as evaluator_first_name, eval_emp.last_name as evaluator_last_name,
                       p.period_name, p.start_date, p.end_date
                FROM evaluations e
                JOIN employees emp ON e.employee_id = emp.employee_id
                JOIN users eval_user ON e.evaluator_id = eval_user.user_id
                LEFT JOIN employees eval_emp ON eval_user.user_id = eval_emp.user_id
                JOIN evaluation_periods p ON e.period_id = p.period_id
                $whereClause
                ORDER BY e.created_at DESC";
        
        $evaluations = fetchAll($sql, $params);
        
        return $evaluations;
    }
    
    /**
     * Compatibility method for legacy job template-based evaluations
     * This method provides backward compatibility for existing evaluation pages
     * @param int $evaluationId
     * @return array|false
     */
    public function getJobTemplateEvaluation($evaluationId) {
        // First try to get the evaluation using the new evidence-based system
        $evaluation = $this->getEvaluationById($evaluationId);
        if (!$evaluation) {
            return false;
        }
        
        // Add evidence-based data to make it compatible with the edit page
        $evaluation['kpi_results'] = [];
        $evaluation['competency_results'] = [];
        $evaluation['responsibility_results'] = [];
        $evaluation['value_results'] = [];
        $evaluation['section_weights'] = [
            'kpis' => 25,
            'competencies' => 25,
            'responsibilities' => 25,
            'values' => 25
        ];
        
        // Get evidence results if they exist
        $evidenceResults = $this->getEvidenceResults($evaluationId);
        if (!empty($evidenceResults)) {
            // Convert evidence results to the format expected by the edit page
            foreach ($evidenceResults as $result) {
                // For backward compatibility, we'll create dummy entries that show evidence-based ratings
                switch ($result['dimension']) {
                    case 'kpis':
                        $evaluation['kpi_results'][] = [
                            'kpi_id' => 1, // dummy ID
                            'kpi_name' => 'Evidence-Based KPI Rating',
                            'category' => 'Performance',
                            'target_value' => 5.0,
                            'measurement_unit' => 'Stars',
                            'achieved_value' => $result['avg_star_rating'],
                            'score' => $result['calculated_score'],
                            'comments' => "Evidence-based rating from {$result['evidence_count']} entries"
                        ];
                        break;
                    case 'competencies':
                        $evaluation['competency_results'][] = [
                            'competency_id' => 1, // dummy ID
                            'competency_name' => 'Evidence-Based Competency Rating',
                            'category_name' => 'Performance',
                            'competency_type' => 'core',
                            'module_type' => 'technical',
                            'required_level' => 'Level 3',
                            'technical_display_level' => 3,
                            'technical_level_name' => 'Evidence Expectation',
                            'technical_symbol_pattern' => '🧩🧩🧩⚪️⚪️',
                            'achieved_level' => 'Level 4',
                            'score' => $result['calculated_score'],
                            'comments' => "Evidence-based rating from {$result['evidence_count']} entries"
                        ];
                        break;
                    case 'responsibilities':
                        $evaluation['responsibility_results'][] = [
                            'responsibility_id' => 1, // dummy ID
                            'sort_order' => 1,
                            'responsibility_text' => 'Evidence-Based Responsibility Rating',
                            'score' => $result['calculated_score'],
                            'comments' => "Evidence-based rating from {$result['evidence_count']} entries"
                        ];
                        break;
                    case 'values':
                        $evaluation['value_results'][] = [
                            'value_id' => 1, // dummy ID
                            'value_name' => 'Evidence-Based Value Rating',
                            'description' => 'Evidence-based company value assessment',
                            'score' => $result['calculated_score'],
                            'comments' => "Evidence-based rating from {$result['evidence_count']} entries"
                        ];
                        break;
                }
            }
        }
        
        return $evaluation;
    }
    
    /**
     * Update KPI result - compatibility method for legacy system
     * @param int $evaluationId
     * @param int $kpiId
     * @param array $data
     * @return bool
     */
    public function updateKPIResult($evaluationId, $kpiId, $data) {
        // In the new evidence-based system, we don't have separate KPI results
        // This is a compatibility method that does nothing but returns true
        return true;
    }
    
    /**
     * Update Competency result - compatibility method for legacy system
     * @param int $evaluationId
     * @param int $competencyId
     * @param array $data
     * @return bool
     */
    public function updateCompetencyResult($evaluationId, $competencyId, $data) {
        // In the new evidence-based system, we don't have separate competency results
        // This is a compatibility method that does nothing but returns true
        return true;
    }
    
    /**
     * Update Responsibility result - compatibility method for legacy system
     * @param int $evaluationId
     * @param int $responsibilityId
     * @param array $data
     * @return bool
     */
    public function updateResponsibilityResult($evaluationId, $responsibilityId, $data) {
        // In the new evidence-based system, we don't have separate responsibility results
        // This is a compatibility method that does nothing but returns true
        return true;
    }
    
    /**
     * Update Value result - compatibility method for legacy system
     * @param int $evaluationId
     * @param int $valueId
     * @param array $data
     * @return bool
     */
    public function updateValueResult($evaluationId, $valueId, $data) {
        // In the new evidence-based system, we don't have separate value results
        // This is a compatibility method that does nothing but returns true
        return true;
    }
    
    /**
     * Check workflow status for an employee - compatibility method
     * @param int $employeeId
     * @return array
     */
    public function checkWorkflowStatus($employeeId) {
        // In the new evidence-based system, we don't have the same workflow checks
        // This is a compatibility method that returns a valid status
        return [
            'valid' => true,
            'step' => 'evaluation_ready',
            'message' => 'Evaluation system ready for evidence-based assessments',
            'action' => 'Proceed with evidence collection',
            'job_template_title' => 'Evidence-Based Evaluation'
        ];
    }
    
    /**
     * Convert star rating to achievement level
     * @param float $starRating
     * @return string
     */
    private function getAchievedLevel(float $starRating): string {
        if ($starRating >= 4.5) return 'expert';
        if ($starRating >= 3.5) return 'advanced';
        if ($starRating >= 2.5) return 'intermediate';
        return 'basic';
    }
    
    /**
     * Batch aggregate evidence for multiple evaluations (performance optimization)
     * @param array $evaluationIds
     * @return array Results array with success/failure for each evaluation
     */
    public function batchAggregateEvidence(array $evaluationIds): array {
        $results = [];
        $startTime = microtime(true);
        
        error_log("Starting batch evidence aggregation for " . count($evaluationIds) . " evaluations");
        
        try {
            // Begin transaction for consistency
            $this->pdo->beginTransaction();
            
            foreach ($evaluationIds as $evaluationId) {
                try {
                    // Get evaluation details
                    $evaluation = $this->getEvaluationById($evaluationId);
                    if (!$evaluation) {
                        $results[$evaluationId] = ['success' => false, 'error' => 'Evaluation not found'];
                        continue;
                    }
                    
                    $period = [
                        'start_date' => $evaluation['start_date'],
                        'end_date' => $evaluation['end_date']
                    ];
                    
                    // Aggregate evidence
                    $success = $this->aggregateEvidence($evaluationId, $evaluation['employee_id'], $period);
                    $results[$evaluationId] = ['success' => $success];
                    
                } catch (Exception $e) {
                    $results[$evaluationId] = ['success' => false, 'error' => $e->getMessage()];
                    error_log("Batch aggregation error for evaluation $evaluationId: " . $e->getMessage());
                }
            }
            
            // Commit transaction
            $this->pdo->commit();
            
            $executionTime = microtime(true) - $startTime;
            $successCount = count(array_filter($results, fn($r) => $r['success']));
            
            error_log("Batch evidence aggregation completed: $successCount/" . count($evaluationIds) . " successful in {$executionTime}s");
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Batch evidence aggregation failed: " . $e->getMessage());
            
            // Mark all as failed
            foreach ($evaluationIds as $evaluationId) {
                if (!isset($results[$evaluationId])) {
                    $results[$evaluationId] = ['success' => false, 'error' => 'Transaction failed'];
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Re-aggregate evidence for all evaluations in a period (maintenance function)
     * @param int $periodId
     * @return array
     */
    public function reAggregateEvidenceForPeriod(int $periodId): array {
        try {
            // Get all evaluations for the period
            $sql = "SELECT evaluation_id FROM evaluations WHERE period_id = ?";
            $evaluations = fetchAll($sql, [$periodId]);
            
            if (empty($evaluations)) {
                return ['success' => true, 'message' => 'No evaluations found for period', 'processed' => 0];
            }
            
            $evaluationIds = array_column($evaluations, 'evaluation_id');
            $results = $this->batchAggregateEvidence($evaluationIds);
            
            $successCount = count(array_filter($results, fn($r) => $r['success']));
            
            return [
                'success' => true,
                'message' => "Re-aggregated evidence for $successCount/" . count($evaluationIds) . " evaluations",
                'processed' => $successCount,
                'total' => count($evaluationIds),
                'details' => $results
            ];
            
        } catch (Exception $e) {
            error_log("Re-aggregate evidence for period error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get evidence aggregation statistics for monitoring
     * @param int $periodId Optional period filter
     * @return array
     */
    public function getEvidenceAggregationStats(int $periodId = null): array {
        try {
            $whereClause = "";
            $params = [];
            
            if ($periodId) {
                $whereClause = "WHERE e.period_id = ?";
                $params[] = $periodId;
            }
            
            // Get evaluation counts
            $sql = "SELECT
                        COUNT(*) as total_evaluations,
                        COUNT(e.evidence_rating) as evaluations_with_evidence,
                        AVG(e.evidence_rating) as avg_evidence_rating,
                        MIN(e.evidence_rating) as min_evidence_rating,
                        MAX(e.evidence_rating) as max_evidence_rating
                    FROM evaluations e
                    $whereClause";
            
            $stats = fetchOne($sql, $params);
            
            // Get evidence entry counts
            $evidenceSql = "SELECT
                               COUNT(*) as total_evidence_results,
                               AVG(evidence_count) as avg_evidence_per_dimension,
                               SUM(evidence_count) as total_evidence_entries
                           FROM evidence_evaluation_results eer
                           JOIN evaluations e ON eer.evaluation_id = e.evaluation_id
                           $whereClause";
            
            $evidenceStats = fetchOne($evidenceSql, $params);
            
            // Get dimension coverage
            $dimensionSql = "SELECT
                                dimension,
                                COUNT(*) as evaluation_count,
                                AVG(calculated_score) as avg_score,
                                AVG(evidence_count) as avg_evidence_count
                            FROM evidence_evaluation_results eer
                            JOIN evaluations e ON eer.evaluation_id = e.evaluation_id
                            $whereClause
                            GROUP BY dimension
                            ORDER BY avg_score DESC";
            
            $dimensionStats = fetchAll($dimensionSql, $params);
            
            return [
                'evaluation_stats' => $stats,
                'evidence_stats' => $evidenceStats,
                'dimension_stats' => $dimensionStats,
                'coverage_percentage' => $stats['total_evaluations'] > 0 ?
                    round(($stats['evaluations_with_evidence'] / $stats['total_evaluations']) * 100, 2) : 0
            ];
            
        } catch (Exception $e) {
            error_log("Get evidence aggregation stats error: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Validate evidence data integrity for an evaluation
     * @param int $evaluationId
     * @return array
     */
    public function validateEvidenceIntegrity(int $evaluationId): array {
        try {
            $issues = [];
            
            // Check if evaluation exists
            $evaluation = $this->getEvaluationById($evaluationId);
            if (!$evaluation) {
                return ['valid' => false, 'issues' => ['Evaluation not found']];
            }
            
            // Check evidence results exist
            $evidenceResults = $this->getEvidenceResults($evaluationId);
            if (empty($evidenceResults)) {
                $issues[] = "No evidence results found";
            }
            
            // Check for missing dimensions
            $expectedDimensions = ['responsibilities', 'kpis', 'competencies', 'values'];
            $foundDimensions = array_column($evidenceResults, 'dimension');
            $missingDimensions = array_diff($expectedDimensions, $foundDimensions);
            
            if (!empty($missingDimensions)) {
                $issues[] = "Missing dimensions: " . implode(', ', $missingDimensions);
            }
            
            // Check for invalid scores
            foreach ($evidenceResults as $result) {
                if ($result['calculated_score'] < 0 || $result['calculated_score'] > 5) {
                    $issues[] = "Invalid calculated score for {$result['dimension']}: {$result['calculated_score']}";
                }
                
                if ($result['avg_star_rating'] < 0 || $result['avg_star_rating'] > 5) {
                    $issues[] = "Invalid average rating for {$result['dimension']}: {$result['avg_star_rating']}";
                }
                
                if ($result['evidence_count'] < 0) {
                    $issues[] = "Invalid evidence count for {$result['dimension']}: {$result['evidence_count']}";
                }
            }
            
            // Check overall rating consistency
            $calculatedOverall = $this->calculateOverallEvidenceRating($evaluationId);
            $storedOverall = (float)$evaluation['evidence_rating'];
            
            if (abs($calculatedOverall - $storedOverall) > 0.01) {
                $issues[] = "Overall rating mismatch: calculated=$calculatedOverall, stored=$storedOverall";
            }
            
            return [
                'valid' => empty($issues),
                'issues' => $issues,
                'evaluation_id' => $evaluationId,
                'evidence_results_count' => count($evidenceResults)
            ];
            
        } catch (Exception $e) {
            error_log("Validate evidence integrity error: " . $e->getMessage());
            return ['valid' => false, 'issues' => ['Validation error: ' . $e->getMessage()]];
        }
    }
    
    /**
     * Performance monitoring for evidence aggregation
     * @param int $evaluationId
     * @param float $executionTime
     * @param array $metrics
     */
    private function logPerformanceMetrics(int $evaluationId, float $executionTime, array $metrics = []): void {
        try {
            $logData = [
                'evaluation_id' => $evaluationId,
                'execution_time' => round($executionTime, 4),
                'timestamp' => date('Y-m-d H:i:s'),
                'metrics' => $metrics
            ];
            
            // Log to application log
            error_log("Evidence aggregation performance: " . json_encode($logData));
            
            // Could also log to a dedicated performance table if needed
            // This would be useful for monitoring and optimization
            
        } catch (Exception $e) {
            error_log("Performance logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Normalize KPI data for evaluation edit page
     * @param array $kpis
     * @return array
     */
    private function normalizeKPIs(array $kpis): array {
        $normalized = [];
        foreach ($kpis as $kpi) {
            $normalized[] = [
                'kpi_id' => $kpi['kpi_id'] ?? 'kpi_' . ($kpi['id'] ?? uniqid()),
                'kpi_name' => $kpi['kpi_name'] ?? $kpi['kpi_name'] ?? 'Unnamed KPI',
                'category' => $kpi['category'] ?? 'General',
                'target_value' => $kpi['target_value'] ?? 0,
                'measurement_unit' => $kpi['measurement_unit'] ?? 'units',
                'achieved_value' => null,
                'score' => null,
                'comments' => null
            ];
        }
        return $normalized;
    }
    
    /**
     * Normalize competency data for evaluation edit page
     * @param array $competencies
     * @return array
     */
    private function normalizeCompetencies(array $competencies): array {
        $normalized = [];
        foreach ($competencies as $competency) {
            $moduleType = $competency['module_type'] ?? ($competency['competency_type'] ?? 'technical');
            $entry = [
                'competency_id' => $competency['competency_id'] ?? 'comp_' . ($competency['id'] ?? uniqid()),
                'competency_name' => $competency['competency_name'] ?? $competency['competency_name'] ?? 'Unnamed Competency',
                'category_name' => $competency['category_name'] ?? $competency['category_name'] ?? 'General',
                'competency_type' => $competency['competency_type'] ?? ($moduleType === 'soft_skill' ? 'soft_skill' : 'technical'),
                'module_type' => $moduleType,
                'competency_key' => $competency['competency_key'] ?? null,
                'required_level' => $competency['required_level'] ?? 'Level 1',
                'weight_percentage' => $competency['weight_percentage'] ?? 100,
                'achieved_level' => null,
                'achieved_soft_skill_level' => null,
                'score' => null,
                'comments' => null
            ];
            
            if ($moduleType === 'soft_skill') {
                $entry['soft_skill_level'] = $competency['soft_skill_level'] ?? null;
                $entry['soft_skill_level_title'] = $competency['soft_skill_level_title'] ?? null;
                $entry['soft_skill_symbol_pattern'] = $competency['soft_skill_symbol_pattern'] ?? null;
                $entry['soft_skill_meaning'] = $competency['soft_skill_meaning'] ?? null;
                $entry['soft_skill_behaviors'] = $competency['soft_skill_behaviors'] ?? [];
            } else {
                $entry['technical_level_id'] = $competency['technical_level_id'] ?? null;
                $entry['technical_level_name'] = $competency['technical_level_name'] ?? null;
                $entry['technical_display_level'] = $competency['technical_display_level'] ?? null;
                $entry['technical_symbol_pattern'] = $competency['technical_symbol_pattern'] ?? null;
            }
            
            $normalized[] = $entry;
        }
        return $normalized;
    }
    
    /**
     * Normalize responsibility data for evaluation edit page
     * @param array $responsibilities
     * @return array
     */
    private function normalizeResponsibilities(array $responsibilities): array {
        $normalized = [];
        foreach ($responsibilities as $responsibility) {
            $normalized[] = [
                'responsibility_id' => $responsibility['responsibility_id'] ?? 'resp_' . ($responsibility['id'] ?? uniqid()),
                'sort_order' => $responsibility['sort_order'] ?? count($normalized) + 1,
                'responsibility_text' => $responsibility['responsibility_text'] ?? $responsibility['responsibility_text'] ?? 'Unnamed Responsibility',
                'score' => null,
                'comments' => null
            ];
        }
        return $normalized;
    }
    
    /**
     * Normalize value data for evaluation edit page
     * @param array $values
     * @return array
     */
    private function normalizeValues(array $values): array {
        $normalized = [];
        foreach ($values as $value) {
            $normalized[] = [
                'value_id' => $value['value_id'] ?? 'val_' . ($value['id'] ?? uniqid()),
                'value_name' => $value['value_name'] ?? $value['value_name'] ?? 'Unnamed Value',
                'description' => $value['description'] ?? '',
                'score' => null,
                'comments' => null
            ];
        }
        return $normalized;
    }
}
?>
