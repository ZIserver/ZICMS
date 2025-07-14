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

        // 更新文章阅读量
        $stmt = $conn->prepare("UPDATE articles SET views = views + 1 WHERE id = ?");
        $stmt->bind_param("i", $articleId);
        $stmt->execute();
        
        // 获取更新后的阅读量
        $stmt = $conn->prepare("SELECT views FROM articles WHERE id = ?");
        $stmt->bind_param("i", $articleId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'status' => 'success',
            'views' => $result['views']
        ]);

    } catch (Exception $e) {
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
