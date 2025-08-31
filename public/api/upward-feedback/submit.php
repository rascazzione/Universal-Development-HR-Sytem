<?php
/**
 * public/api/upward-feedback/submit.php
 * Submit anonymous feedback using one-time token (POST)
 *
 * POST body (JSON):
 * {
 *   "token": "PLAINTOKEN",
 *   "responses": { "q1": {"score":5, "comment":"..."}, ... },
 *   "anonymity_level": "anonymous"|"identified_if_allowed"   // optional
 * }
 *
 * Response:
 * {
 *   "success": true|false,
 *   "data": { "submitted": true },
 *   "message": "...",
 *   "timestamp": "ISO datetime"
 * }
 *
 * This endpoint intentionally does not require authentication (respondents may be external).
 * It verifies token validity, expiry and prevents double submissions.
 */
 
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../classes/UpwardFeedbackManager.php';
require_once __DIR__ . '/../../../includes/auth.php'; // used only for optional audit logging if user is logged in

function json_response($success, $data = null, $message = '', $status = 200) {
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('c')
    ]);
    exit;
}

try {
    // Allow CORS preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(false, null, 'Method Not Allowed. Use POST.', 405);
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($input)) {
        json_response(false, null, 'Invalid JSON payload.', 400);
    }

    if (empty($input['token']) || !is_string($input['token'])) {
        json_response(false, null, 'Missing or invalid token.', 400);
    }
    $token = $input['token'];

    if (empty($input['responses']) || !is_array($input['responses'])) {
        json_response(false, null, "Missing 'responses' object or invalid format.", 400);
    }
    $responses = $input['responses'];

    // Optional anonymity level
    $anonymityLevel = isset($input['anonymity_level']) && in_array($input['anonymity_level'], ['anonymous','identified_if_allowed'])
        ? $input['anonymity_level']
        : 'anonymous';

    $ufm = new UpwardFeedbackManager();

    try {
        $result = $ufm->submitAnonymousFeedback($token, [
            'responses' => $responses,
            'anonymity_level' => $anonymityLevel
        ]);
    } catch (Exception $e) {
        // Distinguish common errors
        $msg = $e->getMessage();
        if (strpos(strtolower($msg), 'expired') !== false) {
            json_response(false, null, 'Token expired or invalid.', 410);
        } elseif (strpos(strtolower($msg), 'already submitted') !== false) {
            json_response(false, null, 'Feedback for this token has already been submitted.', 409);
        } else {
            json_response(false, null, 'Failed to submit feedback: ' . $msg, 400);
        }
    }

    if ($result === true) {
        // Optional audit: if an authenticated user submitted and anonymity allows, tracking happens inside manager
        $auditUser = isAuthenticated() ? ($_SESSION['user_id'] ?? null) : null;
        if (function_exists('logActivity')) {
            logActivity($auditUser, 'api_anonymous_feedback_submitted', 'upward_feedback_responses', null, null, ['anonymity_level' => $anonymityLevel]);
        }
        json_response(true, ['submitted' => true], 'Feedback submitted successfully.', 200);
    } else {
        json_response(false, null, 'Feedback submission failed (no records updated).', 500);
    }

} catch (Throwable $e) {
    error_log("Anonymous feedback submit endpoint error: " . $e->getMessage() . " | payload: " . ($raw ?? ''));
    json_response(false, null, 'Server error', 500);
}