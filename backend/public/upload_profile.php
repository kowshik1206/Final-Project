<?php
/**
 * upload_profile.php - Upload sandbox for files
 * POST /upload_profile.php (multipart/form-data with 'file' field)
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

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    json_response(['ok' => false, 'error' => 'No file uploaded or upload error'], 400);
}

$file = $_FILES['file'];
$fileName = $file['name'];
$fileTmp = $file['tmp_name'];
$fileSize = $file['size'];
$fileMime = $file['type'];

// Validate file
$maxSize = 5 * 1024 * 1024; // 5MB
if ($fileSize > $maxSize) {
    json_response(['ok' => false, 'error' => 'File size exceeds 5MB limit'], 400);
}

$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($fileMime, $allowedMimes)) {
    json_response(['ok' => false, 'error' => 'Only image files allowed (JPEG, PNG, GIF, WebP)'], 400);
}

// Create uploads directory if not exists
$uploadDir = __DIR__ . '/../../uploads/test/';
if (!is_dir($uploadDir)) {
    if (!@mkdir($uploadDir, 0755, true)) {
        json_response(['ok' => false, 'error' => 'Failed to create upload directory'], 500);
    }
}

// Generate unique filename
$ext = pathinfo($fileName, PATHINFO_EXTENSION);
$uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;
$uploadPath = $uploadDir . $uniqueName;

// Move file
if (!move_uploaded_file($fileTmp, $uploadPath)) {
    json_response(['ok' => false, 'error' => 'Failed to move uploaded file'], 500);
}

// Save to database
try {
    $db = get_db_connection();
    
    $stmt = $db->prepare("
        INSERT INTO uploads (filename, path, mime_type, size_bytes, uploaded_at) 
        VALUES (:filename, :path, :mime_type, :size_bytes, NOW())
    ");
    
    $stmt->execute([
        ':filename' => $fileName,
        ':path' => 'uploads/test/' . $uniqueName,
        ':mime_type' => $fileMime,
        ':size_bytes' => $fileSize,
    ]);
    
    $uploadId = $db->lastInsertId();
    
    json_response([
        'ok' => true,
        'upload_id' => (int)$uploadId,
        'filename' => $uniqueName,
        'url' => '/uploads/test/' . $uniqueName,
        'size' => $fileSize,
    ], 201);
    
} catch (Exception $e) {
    // Clean up file if DB insert fails
    @unlink($uploadPath);
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
