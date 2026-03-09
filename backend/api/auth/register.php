<?php
// backend/api/auth/register.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../cors.php';
$conn = require_once __DIR__ . '/../../db.php';

$input = json_decode(file_get_contents("php://input"), true);
$name = $input['name'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (!$name || !$email || !$password) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'All fields are required']);
    exit;
}

// Check exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success'=>false, 'message'=>'Email already exists']);
    exit;
}

// Insert
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $hash);

if ($stmt->execute()) {
    $userId = $stmt->insert_id;
    $token = base64_encode($userId . ':' . md5($email . 'secret_salt'));
    
    echo json_encode([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $userId,
            'name' => $name,
            'email' => $email
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Registration failed']);
}
?>
