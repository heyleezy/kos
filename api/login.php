<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['username']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Brak wymaganych danych']);
        exit;
    }

    $username = trim($data['username']);
    $password = $data['password'];

    // Tymczasowe proste logowanie - usuń to później
    if ($username === 'admin' && $password === 'admin123') {
        session_start();
        $_SESSION['admin_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['session_token'] = bin2hex(random_bytes(32));
        $_SESSION['expires_at'] = time() + 3600;

        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO sessions (admin_id, session_token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([1, $_SESSION['session_token'], date('Y-m-d H:i:s', $_SESSION['expires_at'])]);
        } catch(Exception $e) {
            // Ignoruj błędy bazy na razie
        }

        echo json_encode([
            'success' => true,
            'message' => 'Zalogowano pomyślnie',
            'username' => 'admin'
        ]);
        exit;
    }

    try {
        $pdo = getDBConnection();

        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM administrators WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            session_start();
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['session_token'] = bin2hex(random_bytes(32));
            $_SESSION['expires_at'] = time() + SESSION_TIMEOUT;

            $stmt = $pdo->prepare("INSERT INTO sessions (admin_id, session_token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$admin['id'], $_SESSION['session_token'], date('Y-m-d H:i:s', $_SESSION['expires_at'])]);

            echo json_encode([
                'success' => true,
                'message' => 'Zalogowano pomyślnie',
                'username' => $admin['username']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Błędna nazwa użytkownika lub hasło']);
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Błąd serwera: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Metoda nieobsługiwana']);
}
?>