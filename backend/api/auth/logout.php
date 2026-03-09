<?php
// backend/api/auth/logout.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../cors.php';

// Stateless JWT/Token auth doesn't really need a backend logout unless we blacklist tokens.
// For now, just return success.
echo json_encode(['success' => true]);
?>
