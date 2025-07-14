<?php
header('Content-Type: application/json; charset=utf-8'); // 强制设置编码格式
require_once __DIR__ . '/../db.php';

// 安全获取参数
$contentId = isset($_GET['content_id']) ? (int)$_GET['content_id'] : 0;

// 调试日志
error_log("获取评论请求：content_id = {$contentId}");

// 验证参数
if ($contentId <= 0) {
    http_response_code(400);
    exit(json_encode([
        'status' => 'error',
        'message' => '无效的文章ID',
        'total' => 0,
        'comments' => []
    ]));
}

try {
    // 查询准备（包括用户信息关联查询）
    $sql = "SELECT 
                c.id, 
                c.content, 
                c.created_at,
                u.username,
                u.avatar
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.content_id = ?
            ORDER BY c.created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $contentId);
    $stmt->execute();
    $result = $stmt->get_result();

    $comments = [];
    while ($row = $result->fetch_assoc()) {
        // 转义特殊字符防止XSS
        $row['content'] = htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8');
        
        // 格式化时间输出
        $row['time_ago'] = time_ago($row['created_at']);
        
        $comments[] = $row;
    }

    // 返回结果时补充状态信息
    echo json_encode([
        'status' => 'success',
        'total' => count($comments),
        'comments' => $comments
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    error_log("数据库错误: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => '获取评论失败',
        'total' => 0,
        'comments' => []
    ]);
} finally {
    $stmt->close();
    $conn->close();
}

// 时间格式化函数
function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) {
        return "刚刚";
    } elseif ($diff < 3600) {
        return floor($diff/60) . "分钟前";
    } elseif ($diff < 86400) {
        return floor($diff/3600) . "小时前";
    } elseif ($diff < 604800) {
        return floor($diff/86400) . "天前";
    } else {
        return date("Y-m-d H:i", $time);
    }
}
?>
