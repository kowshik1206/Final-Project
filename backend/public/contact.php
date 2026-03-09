<?php
/**
 * contact.php - Save contact form messages
 * POST /contact.php
 * Body: { name, email, message }
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    json_response(['ok' => false, 'error' => 'Invalid JSON'], 400);
}

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$message = trim($input['message'] ?? '');

if (!$name || !$email || !$message) {
    json_response(['ok' => false, 'error' => 'Missing required fields (name, email, message)'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'error' => 'Invalid email address'], 400);
}

if (strlen($message) < 10) {
    json_response(['ok' => false, 'error' => 'Message must be at least 10 characters'], 400);
}

try {
    $db = get_db_connection();
    
    $stmt = $db->prepare("
        INSERT INTO contact_messages (name, email, message, created_at) 
        VALUES (:name, :email, :message, NOW())
    ");
    
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':message' => $message,
    ]);
    
    $messageId = $db->lastInsertId();
    
    json_response([
        'ok' => true,
        'message_id' => (int)$messageId,
        'status' => 'Message received. We will get back to you soon.',
    ], 201);
    
} catch (Exception $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
