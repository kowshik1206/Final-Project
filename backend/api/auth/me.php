<?php
// backend/api/auth/me.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../cors.php';
$conn = require_once __DIR__ . '/../../db.php';

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';
$token = str_replace('Bearer ', '', $authHeader);

if (!$token) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'message'=>'No token provided']);
    exit;
}

// Decode token
$decoded = base64_decode($token);
$parts = explode(':', $decoded);
if (count($parts) !== 2) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'message'=>'Invalid token format']);
    exit;
}

$userId = $parts[0];
// In a real app, verify the hash too.

$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
} else {
    http_response_code(401);
    echo json_encode(['success'=>false, 'message'=>'User not found']);
}
?>
