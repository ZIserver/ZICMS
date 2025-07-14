<?php
header("Content-Type: application/json");
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $articleId = $data['articleId'] ?? null;

    try {
        if (!$articleId || !is_numeric($articleId)) {
            throw new Exception("Invalid article ID");
        }

        $conn->begin_transaction();

        // 锁定文章记录并获取作者信息
        $stmt = $conn->prepare("SELECT username FROM articles WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $articleId);
        $stmt->execute();
        $article = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$article) {
            throw new Exception("文章不存在");
        }

        // 更新文章点赞数
        $stmt = $conn->prepare("UPDATE articles SET like_count = like_count + 1 WHERE id = ?");
        $stmt->bind_param("i", $articleId);
        $stmt->execute();
        $stmt->close();

        // 更新作者获赞数
        $stmt = $conn->prepare("UPDATE users SET likes = likes + 1 WHERE username = ?");
        $stmt->bind_param("s", $article['username']);
        $stmt->execute();
        $stmt->close();

        // 获取最新数据
        $stmt = $conn->prepare("SELECT a.like_count, u.likes 
                              FROM articles a 
                              JOIN users u ON a.username = u.username 
                              WHERE a.id = ?");
        $stmt->bind_param("i", $articleId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'like_count' => $result['like_count'],
            //'author_likes' => $result['author_likes']
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
