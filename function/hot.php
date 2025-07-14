<?php
header("Content-Type: application/json");
require_once '../db.php';

// 获取热门文章 (按阅读量+点赞数综合排序)
function getHotArticles($limit = 5) {
    global $conn;
    
    $sql = "SELECT 
              a.id, 
              a.title, 
              a.views, 
              a.like_count,
              a.created_at,
              u.username
            FROM articles a
            JOIN users u ON a.user_id = u.id
            ORDER BY (a.views * 0.6 + a.like_count * 0.4) DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $articles = [];
    while ($row = $result->fetch_assoc()) {
        $articles[] = $row;
    }
    
    return $articles;
}

// 直接输出JSON格式的热门文章
if (isset($_GET['get_hot_articles'])) {
    echo json_encode([
        'status' => 'success',
        'articles' => getHotArticles(10) // 取前10条
    ]);
    exit;
}
?>
