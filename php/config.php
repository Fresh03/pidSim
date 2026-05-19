<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pid_simulator');
define('DB_PORT', 3306);

define('APP_NAME', 'PID Simulator');
define('APP_VERSION', '1.1.0');
define('SESSION_LIFETIME', 3600);

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4;port=" . DB_PORT;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500); die(json_encode(['error' => 'Eroare conexiune DB: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code); header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE); exit;
}

function requireAuth() {
    session_start();
    if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Neautentificat', 'redirect' => 'index.php'], 401);
    return $_SESSION['user_id'];
}