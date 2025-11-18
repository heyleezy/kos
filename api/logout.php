<?php
require_once 'config.php';

session_start();

if (isset($_SESSION['session_token'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_token = ?");
        $stmt->execute([$_SESSION['session_token']]);
    } catch(PDOException $e) {
        error_log("Błąd podczas wylogowywania: " . $e->getMessage());
    }
}

session_destroy();
echo json_encode(['success' => true, 'message' => 'Wylogowano pomyślnie']);
?>