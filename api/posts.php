<?php
require_once 'config.php';

$pdo = getDBConnection();

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        try {
            $stmt = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC");
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($posts as &$post) {
                $post['images'] = $post['images'] ? json_decode($post['images'], true) : [];
            }

            echo json_encode($posts);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd podczas pobierania postów: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        if (!verifySession()) {
            http_response_code(401);
            echo json_encode(['error' => 'Wymagane logowanie']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['content']) && (!isset($data['images']) || empty($data['images']))) {
            http_response_code(400);
            echo json_encode(['error' => 'Post musi zawierać treść lub zdjęcia']);
            exit;
        }

        try {
            $content = isset($data['content']) ? trim($data['content']) : '';
            $images = isset($data['images']) ? json_encode($data['images']) : json_encode([]);
            $author = $_SESSION['username'];

            $stmt = $pdo->prepare("INSERT INTO posts (content, author, images) VALUES (?, ?, ?)");
            $stmt->execute([$content, $author, $images]);

            $postId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $newPost = $stmt->fetch(PDO::FETCH_ASSOC);
            $newPost['images'] = $newPost['images'] ? json_decode($newPost['images'], true) : [];

            echo json_encode($newPost);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd podczas dodawania posta: ' . $e->getMessage()]);
        }
        break;

    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['post_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID posta']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE posts SET likes = likes + 1 WHERE id = ?");
            $stmt->execute([$data['post_id']]);
            echo json_encode(['success' => true]);
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd podczas aktualizacji posta: ' . $e->getMessage()]);
        }
        break;

    case 'DELETE':
        if (!verifySession()) {
            http_response_code(401);
            echo json_encode(['error' => 'Wymagane logowanie']);
            exit;
        }

        $postId = isset($_GET['id']) ? $_GET['id'] : null;

        if (!$postId) {
            http_response_code(400);
            echo json_encode(['error' => 'Brak ID posta']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT images FROM posts WHERE id = ?");
            $stmt->execute([$postId]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($post) {
                $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                $stmt->execute([$postId]);

                if ($post['images']) {
                    $images = json_decode($post['images'], true);
                    foreach ($images as $imagePath) {
                        if (file_exists('../' . $imagePath)) {
                            unlink('../' . $imagePath);
                        }
                    }
                }

                echo json_encode(['success' => true, 'message' => 'Post usunięty pomyślnie']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Post nie znaleziony']);
            }
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Błąd podczas usuwania posta: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Metoda nieobsługiwana']);
        break;
}
?>