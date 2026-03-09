<?php
/**
 * Database connection loader with PDO
 * Reads .env file and provides DB connection
 */

function load_env($filePath) {
    if (!file_exists($filePath)) {
        return [];
    }
    $env = [];
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
    return $env;
}

function get_db_connection() {
    $envPath = __DIR__ . '/../.env';
    $env = load_env($envPath);
    
    $driver = $env['DB_CONNECTION'] ?? 'mysql';
    
    if ($driver === 'sqlite') {
        $dbPath = $env['DB_DATABASE'] ?? __DIR__ . '/../database.sqlite';
        try {
            $pdo = new PDO("sqlite:$dbPath", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            return $pdo;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
            exit(1);
        }
    }
    
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? 3306;
    $dbname = $env['DB_DATABASE'] ?? 'routeiq';
    $user = $env['DB_USERNAME'] ?? 'root';
    $pass = $env['DB_PASSWORD'] ?? '';
    
    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
        exit(1);
    }
}

function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function validate_coordinates($lat, $lng) {
    if (!is_numeric($lat) || !is_numeric($lng)) {
        return false;
    }
    return ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180);
}
