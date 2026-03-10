<?php
// backend/api/auth/login.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../cors.php';
$conn = require_once __DIR__ . '/../../db.php';

$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (!$email || !$password) {
    http_response_code(400);
    echo json_encode(['success'=>false, 'message'=>'Email and password required']);
    exit;
}

$stmt = $conn->prepare("SELECT id, name, email, password_hash FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user['password_hash'])) {
        // Generate a simple token (in prod use JWT)
        // For this MVP, we'll store a random session token in DB or just return a dummy token that the frontend trusts
        // A better approach for PHP without JWT lib is just using a hashed session ID.
        // Let's create a 'token' column in users or a sessions table?
        // Simpler: Just return the user ID base64 encoded as a "token" for now (INSECURE but functional for MVP)
        // OR: Use PHP native sessions? Frontend is SPA.
        // Let's do: base64(user_id:random_hash)
        
        $token = base64_encode($user['id'] . ':' . md5($user['email'] . 'secret_salt'));
        
        unset($user['password_hash']); // Remove password hash from response
        echo json_encode([
            'success' => true,
            'token' => $token,
            'user' => $user
        ]);
        exit;
    }
}

http_response_code(401);
echo json_encode(['success'=>false, 'message'=>'Invalid credentials']);
?>
