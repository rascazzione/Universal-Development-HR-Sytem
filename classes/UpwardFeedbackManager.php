<?php
/**
 * UpwardFeedbackManager Class
 * Manager Evaluation & Upward Feedback
 *
 * Responsibilities:
 *  - initiateManagerEvaluation($managerId, $periodId)
 *  - generateAnonymousTokens($evaluationId)
 *  - submitAnonymousFeedback($token, $feedbackData)
 *  - aggregateFeedback($evaluationId)
 *  - generateDevelopmentActions($evaluationId)
 *
 * Follows existing project patterns: uses getDbConnection(), fetchOne(), fetchAll(), insertRecord(), updateRecord()
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Employee.php';
require_once __DIR__ . '/NotificationManager.php';
require_once __DIR__ . '/EvaluationPeriod.php';

class UpwardFeedbackManager {
    private $pdo;

    public function __construct() {
        $this->pdo = getDbConnection();
    }

    /**
     * Initiate or get a manager evaluation record for a manager and period
     * @param int $managerId Employee ID of the manager
     * @param int $periodId Evaluation period id
     * @return int manager_eval_id
     * @throws Exception
     */
    public function initiateManagerEvaluation($managerId, $periodId) {
        try {
            if (!is_numeric($managerId) || $managerId <= 0) {
                throw new Exception("Invalid managerId");
            }
            if (!is_numeric($periodId) || $periodId <= 0) {
                throw new Exception("Invalid periodId");
            }

            // Check existing
            $existing = fetchOne("SELECT manager_eval_id FROM manager_evaluations WHERE manager_employee_id = ? AND period_id = ?", [$managerId, $periodId]);
            if ($existing && !empty($existing['manager_eval_id'])) {
                return intval($existing['manager_eval_id']);
            }

            // Create record
            $sql = "INSERT INTO manager_evaluations (manager_employee_id, period_id, status) VALUES (?, ?, 'draft')";
            $id = insertRecord($sql, [$managerId, $periodId]);

            logActivity($_SESSION['user_id'] ?? null, 'manager_evaluation_initiated', 'manager_evaluations', $id, null, ['manager_id' => $managerId, 'period_id' => $periodId]);

            return intval($id);
        } catch (Exception $e) {
            error_log("Initiate manager evaluation error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate anonymous one-time tokens for responders for a manager evaluation
     * Creates placeholder upward_feedback_responses rows and corresponding anonymous_response_tracking rows.
     * Returns an array of plain tokens mapped to response_id for distribution to respondents.
     *
     * @param int $evaluationId manager_eval_id
     * @return array ['response_id' => int, 'token' => string][]
     * @throws Exception
     */
    public function generateAnonymousTokens($evaluationId) {
        try {
            if (!is_numeric($evaluationId) || $evaluationId <= 0) {
                throw new Exception("Invalid evaluationId");
            }

            $eval = fetchOne("SELECT manager_employee_id, period_id FROM manager_evaluations WHERE manager_eval_id = ?", [$evaluationId]);
            if (!$eval) {
                throw new Exception("Manager evaluation not found");
            }

            $managerId = $eval['manager_employee_id'];
            $periodId = $eval['period_id'];

            // Get team members (exclude the manager)
            $empClass = new Employee();
            $team = $empClass->getTeamMembers($managerId);
            $tokens = [];

            // Determine expiry: period end date + 30 days if available
            $expiresAt = null;
            $period = fetchOne("SELECT start_date, end_date FROM evaluation_periods WHERE period_id = ?", [$periodId]);
            if ($period && !empty($period['end_date'])) {
                $expiresAt = date('Y-m-d H:i:s', strtotime($period['end_date'] . ' +30 days'));
            } else {
                $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
            }

            foreach ($team as $member) {
                // Create a pre-created upward_feedback_responses row with empty responses and a random respondent_hash
                $respondentHash = hash('sha256', bin2hex(random_bytes(16)) . uniqid('', true));
                $responsesPlaceholder = json_encode(new stdClass()); // empty JSON object

                $sql = "INSERT INTO upward_feedback_responses (manager_employee_id, period_id, respondent_hash, responses, anonymity_level) VALUES (?, ?, ?, ?, 'anonymous')";
                $responseId = insertRecord($sql, [$managerId, $periodId, $respondentHash, $responsesPlaceholder]);

                if (!$responseId) continue;

                // Generate a secure one-time token (plain) and store its hashed version (sha512) in anonymous_response_tracking
                $plainToken = bin2hex(random_bytes(32)); // 64 hex chars
                $hashedToken = hash('sha512', $plainToken); // 128 hex chars, fits CHAR(128)

                $sql2 = "INSERT INTO anonymous_response_tracking (response_id, recipient_user_id, sent_token, created_at, expires_at) VALUES (?, ?, ?, NOW(), ?)";
                // recipient_user_id is kept null for anonymity (could be used by admin if needed)
                insertRecord($sql2, [$responseId, null, $hashedToken, $expiresAt]);

                // Return mapping so caller (HR) can distribute the plain token to the intended recipient
                $tokens[] = [
                    'response_id' => intval($responseId),
                    'employee_id' => intval($member['employee_id']),
                    'token' => $plainToken
                ];
            }

            logActivity($_SESSION['user_id'] ?? null, 'anonymous_tokens_generated', 'anonymous_response_tracking', $evaluationId, null, ['generated_count' => count($tokens)]);

            return $tokens;
        } catch (Exception $e) {
            error_log("Generate anonymous tokens error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Submit anonymous feedback using a one-time token
     * @param string $token Plain token provided to respondent
     * @param array $feedbackData ['responses' => array, 'anonymity_level' => 'anonymous'|'identified_if_allowed']
     * @return bool
     * @throws Exception
     */
    public function submitAnonymousFeedback($token, $feedbackData) {
        try {
            if (empty($token) || !is_string($token)) {
                throw new Exception("Invalid token");
            }
            if (empty($feedbackData) || !is_array($feedbackData) || empty($feedbackData['responses'])) {
                throw new Exception("Feedback data with 'responses' is required");
            }

            $hashedToken = hash('sha512', $token);
            $tracking = fetchOne("SELECT tracking_id, response_id, expires_at FROM anonymous_response_tracking WHERE sent_token = ?", [$hashedToken]);
            if (!$tracking) {
                throw new Exception("Invalid or unknown token");
            }

            // Check expiry
            if (!empty($tracking['expires_at']) && strtotime($tracking['expires_at']) < time()) {
                throw new Exception("Token has expired");
            }

            $responseId = $tracking['response_id'];

            // Fetch response row
            $responseRow = fetchOne("SELECT * FROM upward_feedback_responses WHERE response_id = ?", [$responseId]);
            if (!$responseRow) {
                throw new Exception("Associated response record not found");
            }

            // Prevent double-submission: check if responses field is not empty object
            $existing = $responseRow['responses'];
            // if existing is not empty object {} then treat as already submitted
            $isSubmitted = false;
            if (!empty($existing) && $existing !== '{}' && $existing !== 'null') {
                $isSubmitted = true;
            }
            if ($isSubmitted) {
                throw new Exception("Feedback already submitted for this token");
            }

            // Validate responses structure (basic)
            $responses = $feedbackData['responses'];
            if (!is_array($responses)) {
                throw new Exception("Invalid responses format");
            }

            $anonymityLevel = in_array($feedbackData['anonymity_level'] ?? 'anonymous', ['anonymous','identified_if_allowed']) ? $feedbackData['anonymity_level'] : 'anonymous';

            // Update upward_feedback_responses with submitted responses and anonymity_level
            $sql = "UPDATE upward_feedback_responses SET responses = ?, anonymity_level = ?, created_at = NOW() WHERE response_id = ?";
            $affected = updateRecord($sql, [json_encode($responses), $anonymityLevel, $responseId]);

            if ($affected > 0) {
                // Optionally record which user (if logged in and anonymity level permits) processed the submission into tracking recipient_user_id for audit
                $recipientUserId = $_SESSION['user_id'] ?? null;
                // Do not override recipient_user_id if anonymity must be preserved; record only processing user for admin audit (nullable)
                updateRecord("UPDATE anonymous_response_tracking SET recipient_user_id = ? WHERE tracking_id = ?", [$recipientUserId, $tracking['tracking_id']]);

                logActivity($_SESSION['user_id'] ?? null, 'anonymous_feedback_submitted', 'upward_feedback_responses', $responseId, null, ['anonymity_level' => $anonymityLevel]);

                return true;
            }

            return false;
        } catch (Exception $e) {
            error_log("Submit anonymous feedback error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Aggregate feedback for a manager evaluation
     * Collates responses and stores summary in manager_feedback_summary and manager_evaluations.summary
     *
     * @param int $evaluationId manager_eval_id
     * @return array aggregated metrics
     * @throws Exception
     */
    public function aggregateFeedback($evaluationId) {
        try {
            if (!is_numeric($evaluationId) || $evaluationId <= 0) {
                throw new Exception("Invalid evaluationId");
            }

            $eval = fetchOne("SELECT manager_employee_id, period_id FROM manager_evaluations WHERE manager_eval_id = ?", [$evaluationId]);
            if (!$eval) {
                throw new Exception("Manager evaluation not found");
            }

            $managerId = $eval['manager_employee_id'];
            $periodId = $eval['period_id'];

            // Fetch all submitted responses for that manager and period
            $sql = "SELECT responses FROM upward_feedback_responses WHERE manager_employee_id = ? AND period_id = ? AND (responses IS NOT NULL AND responses != '{}' AND responses != 'null')";
            $rows = fetchAll($sql, [$managerId, $periodId]);

            $totalResponses = count($rows);
            if ($totalResponses === 0) {
                // Write empty summary and return
                $summary = [
                    'total_responses' => 0,
                    'avg_score' => null,
                    'top_themes' => [],
                    'details' => []
                ];

                // Upsert manager_feedback_summary
                $existing = fetchOne("SELECT summary_id FROM manager_feedback_summary WHERE manager_employee_id = ? AND period_id = ?", [$managerId, $periodId]);
                if ($existing) {
                    updateRecord("UPDATE manager_feedback_summary SET total_responses = ?, avg_score = ?, top_themes = ?, last_aggregated_at = NOW() WHERE summary_id = ?", [0, null, null, $existing['summary_id']]);
                } else {
                    insertRecord("INSERT INTO manager_feedback_summary (manager_employee_id, period_id, total_responses, avg_score, top_themes, last_aggregated_at) VALUES (?, ?, ?, ?, ?, NOW())", [$managerId, $periodId, 0, null, null]);
                }

                // Update manager_evaluations.summary
                $this->updateManagerEvaluationSummary($evaluationId, $summary);

                return $summary;
            }

            // Aggregate numeric scores and free-text themes
            $numericTotals = [];
            $numericCounts = [];
            $themes = [];

            foreach ($rows as $r) {
                $resp = json_decode($r['responses'], true);
                if (!is_array($resp)) continue;

                // Expect schema: responses = { "q1": {"score": X, "comment": "..."}, "q2": {...} }
                foreach ($resp as $qid => $qdata) {
                    if (isset($qdata['score']) && is_numeric($qdata['score'])) {
                        $score = floatval($qdata['score']);
                        if (!isset($numericTotals[$qid])) {
                            $numericTotals[$qid] = 0;
                            $numericCounts[$qid] = 0;
                        }
                        $numericTotals[$qid] += $score;
                        $numericCounts[$qid] += 1;
                    }
                    if (!empty($qdata['comment'])) {
                        $themes[] = trim($qdata['comment']);
                    }
                }
            }

            // Compute averages per question and overall average
            $questionAverages = [];
            $sumAll = 0;
            $countAll = 0;
            foreach ($numericTotals as $qid => $total) {
                $count = $numericCounts[$qid];
                $avg = $count > 0 ? round($total / $count, 2) : null;
                $questionAverages[$qid] = $avg;
                if ($avg !== null) {
                    $sumAll += $avg;
                    $countAll++;
                }
            }
            $overallAvg = $countAll > 0 ? round($sumAll / $countAll, 2) : null;

            // Extract top themes by simple frequency (basic text processing)
            $themeCount = [];
            foreach ($themes as $t) {
                $key = strtolower(substr($t, 0, 200)); // normalize
                if (!isset($themeCount[$key])) $themeCount[$key] = 0;
                $themeCount[$key]++;
            }
            arsort($themeCount);
            $topThemes = array_slice(array_keys($themeCount), 0, 5);

            $summary = [
                'total_responses' => $totalResponses,
                'avg_score' => $overallAvg,
                'question_averages' => $questionAverages,
                'top_themes' => $topThemes,
                'details_count' => count($rows)
            ];

            // Upsert manager_feedback_summary
            $existing = fetchOne("SELECT summary_id FROM manager_feedback_summary WHERE manager_employee_id = ? AND period_id = ?", [$managerId, $periodId]);
            if ($existing) {
                updateRecord("UPDATE manager_feedback_summary SET total_responses = ?, avg_score = ?, top_themes = ?, last_aggregated_at = NOW() WHERE summary_id = ?", [$summary['total_responses'], $summary['avg_score'], json_encode($summary['top_themes']), $existing['summary_id']]);
            } else {
                insertRecord("INSERT INTO manager_feedback_summary (manager_employee_id, period_id, total_responses, avg_score, top_themes, last_aggregated_at) VALUES (?, ?, ?, ?, ?, NOW())", [$managerId, $periodId, $summary['total_responses'], $summary['avg_score'], json_encode($summary['top_themes'])]);
            }

            // Update manager_evaluations with aggregated summary and set status to finalized if appropriate
            $this->updateManagerEvaluationSummary($evaluationId, $summary);

            logActivity($_SESSION['user_id'] ?? null, 'manager_feedback_aggregated', 'manager_feedback_summary', $evaluationId, null, $summary);

            return $summary;
        } catch (Exception $e) {
            error_log("Aggregate feedback error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper: update the manager_evaluations.summary and overall_score
     * @param int $managerEvalId
     * @param array $summary
     */
    private function updateManagerEvaluationSummary($managerEvalId, $summary) {
        try {
            // store summary JSON in manager_evaluations.summary and overall_score in overall_score
            $summaryJson = json_encode($summary);
            $overall = $summary['avg_score'] ?? null;
            $sql = "UPDATE manager_evaluations SET summary = ?, overall_score = ?, status = 'finalized', updated_at = NOW() WHERE manager_eval_id = ?";
            updateRecord($sql, [$summaryJson, $overall, $managerEvalId]);
        } catch (Exception $e) {
            error_log("Update manager evaluation summary error: " . $e->getMessage());
        }
    }

    /**
     * Generate development action items based on aggregated feedback
     * This will create manager_development_actions rows for low-scoring question themes
     *
     * @param int $evaluationId
     * @return array created actions
     * @throws Exception
     */
    public function generateDevelopmentActions($evaluationId) {
        try {
            if (!is_numeric($evaluationId) || $evaluationId <= 0) {
                throw new Exception("Invalid evaluationId");
            }

            $eval = fetchOne("SELECT manager_employee_id, period_id, summary FROM manager_evaluations WHERE manager_eval_id = ?", [$evaluationId]);
            if (!$eval) {
                throw new Exception("Manager evaluation not found");
            }

            $managerId = $eval['manager_employee_id'];
            $summary = is_string($eval['summary']) ? json_decode($eval['summary'], true) : $eval['summary'];

            if (empty($summary) || empty($summary['question_averages'])) {
                // Try to re-aggregate
                $this->aggregateFeedback($evaluationId);
                $eval = fetchOne("SELECT manager_employee_id, period_id, summary FROM manager_evaluations WHERE manager_eval_id = ?", [$evaluationId]);
                $summary = is_string($eval['summary']) ? json_decode($eval['summary'], true) : $eval['summary'];
            }

            $created = [];
            if (!empty($summary['question_averages'])) {
                foreach ($summary['question_averages'] as $qid => $avg) {
                    // Threshold for action: average below 3.0
                    if ($avg !== null && floatval($avg) < 3.0) {
                        $actionText = "Improve area related to question {$qid} (average score: {$avg}). Suggested activities: coaching, training, targeted feedback.";
                        $dueDate = date('Y-m-d', strtotime('+90 days'));
                        $sql = "INSERT INTO manager_development_actions (manager_employee_id, created_by_user_id, action_text, due_date, status, related_manager_eval_id) VALUES (?, ?, ?, ?, 'open', ?)";
                        $actionId = insertRecord($sql, [$managerId, $_SESSION['user_id'] ?? null, $actionText, $dueDate, $evaluationId]);

                        if ($actionId) {
                            $created[] = [
                                'action_id' => $actionId,
                                'text' => $actionText,
                                'due_date' => $dueDate
                            ];
                            logActivity($_SESSION['user_id'] ?? null, 'development_action_created', 'manager_development_actions', $actionId, null, ['evaluation_id' => $evaluationId, 'qid' => $qid]);
                        }
                    }
                }
            }

            return $created;
        } catch (Exception $e) {
            error_log("Generate development actions error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Submit feedback directly (non-token) - helper for managers or HR (keeps respondent hash)
     * @param int $managerId
     * @param int $periodId
     * @param array $responses
     * @param int|null $respondentUserId
     * @param string $anonymityLevel
     * @return int response_id
     * @throws Exception
     */
    public function submitDirectFeedback($managerId, $periodId, $responses, $respondentUserId = null, $anonymityLevel = 'identified_if_allowed') {
        try {
            if (empty($responses) || !is_array($responses)) throw new Exception("Responses required");

            $respondentHash = hash('sha256', bin2hex(random_bytes(16)) . uniqid('', true));
            $sql = "INSERT INTO upward_feedback_responses (manager_employee_id, period_id, respondent_hash, responses, anonymity_level) VALUES (?, ?, ?, ?, ?)";
            $rid = insertRecord($sql, [$managerId, $periodId, $respondentHash, json_encode($responses), $anonymityLevel]);

            // Log but do not store any link to user if anonymity requested
            logActivity($_SESSION['user_id'] ?? null, 'direct_feedback_submitted', 'upward_feedback_responses', $rid, null, ['manager_id' => $managerId, 'period_id' => $periodId]);

            return intval($rid);
        } catch (Exception $e) {
            error_log("Submit direct feedback error: " . $e->getMessage());
            throw $e;
        }
    }
}

?>