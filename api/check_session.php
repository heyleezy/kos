<?php
require_once 'config.php';

if (verifySession()) {
    echo json_encode([
        'logged_in' => true,
        'username' => $_SESSION['username']
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
?>