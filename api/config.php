<?php
// Konfiguracja bazy danych
define('DB_HOST', 'localhost');
define('DB_NAME', 'zgrupowanie_kos');
define('DB_USER', 'root');
define('DB_PASS', '');

// Konfiguracja sesji
define('SESSION_TIMEOUT', 3600);

function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Błąd połączenia z bazą danych: ' . $e->getMessage()]);
        exit;
    }
}

function verifySession() {
    session_start();

    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['session_token']) || !isset($_SESSION['expires_at'])) {
        return false;
    }

    if (time() > $_SESSION['expires_at']) {
        session_destroy();
        return false;
    }

    $_SESSION['expires_at'] = time() + SESSION_TIMEOUT;
    return true;
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>