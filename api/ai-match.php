<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/notifier.php';
require_post_method();

$payload = json_decode(file_get_contents('php://input'), true);
$description = sanitize_input($payload['description'] ?? '');

if (empty($description)) {
    json_response(['success' => false, 'message' => 'Description required'], 422);
}

$stmt = $mysqli->prepare("SELECT id, item_name, description, location, item_type FROM items WHERE status = 'approved'");
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$threshold = defined('AI_MATCH_THRESHOLD') ? (float) AI_MATCH_THRESHOLD : 0.35;
$topN = defined('AI_MATCH_TOP_N') ? (int) AI_MATCH_TOP_N : 5;
$notifyThreshold = defined('AI_NOTIFY_THRESHOLD') ? (float) AI_NOTIFY_THRESHOLD : 0.6;

$ch = curl_init(AI_SERVICE_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'query' => $description,
        'items' => $items,
        'min_score' => $threshold,
        'top_n' => $topN,
    ]),
    CURLOPT_TIMEOUT => 15
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Check for connection errors first
if ($curlError) {
    json_response([
        'success' => false, 
        'message' => 'AI service is not running. Please start the Flask AI service.', 
        'error' => $curlError,
        'help' => 'Run START_FLASK_AI.bat in the project root to start the AI service on port 5001'
    ], 502);
}

// Check HTTP status code
if ($httpCode !== 200) {
    json_response([
        'success' => false, 
        'message' => 'AI service returned an error', 
        'http_code' => $httpCode,
        'response' => $response
    ], $httpCode >= 500 ? 502 : 400);
}

// Check if response is empty
if (empty($response)) {
    json_response([
        'success' => false, 
        'message' => 'AI service returned empty response. Make sure Flask service is running on port 5001.'
    ], 502);
}

$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    json_response([
        'success' => false, 
        'message' => 'Invalid AI response', 
        'error' => json_last_error_msg(),
        'raw_response' => substr($response, 0, 200)
    ], 500);
}

if (!empty($data['matches'])) {
    $stmtLog = $mysqli->prepare("INSERT INTO match_logs (lost_item_name, found_item_name, score) VALUES (?, ?, ?)");
    foreach ($data['matches'] as $match) {
        $lostName = $match['query_label'] ?? 'Search';
        $foundName = $match['item_name'];
        $score = (float) ($match['score'] ?? 0);
        $stmtLog->bind_param('ssd', $lostName, $foundName, $score);
        $stmtLog->execute();

        if ($score >= $notifyThreshold && isset($match['item_id'])) {
            $msg = sprintf('Potential match found: %s (score %.1f%%)', $foundName, $score * 100);
            send_notification((int) $match['item_id'], 'email', $msg);
        }
    }
}

json_response(['success' => true, 'matches' => $data['matches'] ?? []]);

