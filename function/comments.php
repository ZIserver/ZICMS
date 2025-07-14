<?php
require '.././install/config.php';
require '.././function/functions.php';

function getComments($content_id) {
    try {
        $pdo = new PDO("mysql:host=".db_host.";port=".db_port.";dbname=".db_name, db_user, db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("
            SELECT c.*, u.username 
            FROM comments c 
            JOIN users u ON c.user_id = u.id 
            WHERE c.content_id = ? AND c.status = 1
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$content_id]);
        
        $comments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $comments[] = $row;
        }
        
        // 处理嵌套评论
        return buildCommentTree($comments);
    } catch (PDOException $e) {
        return [];
    }
}

function buildCommentTree($comments, $parent_id = 0) {
    $tree = [];
    foreach ($comments as $comment) {
        if ($comment['parent_id'] == $parent_id) {
            $children = buildCommentTree($comments, $comment['id']);
            if ($children) {
                $comment['children'] = $children;
            }
            $tree[] = $comment;
        }
    }
    return $tree;
}
?>
