<?php
session_start();
require_once '../db.php';

// 检查是否已登录
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => '请先登录']);
    exit;
}

// 获取提交的数据（兼容JSON和FormData）
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $input = json_decode(file_get_contents('php://input'), true);
    $contentId = $input['content_id'] ?? null;
    $content = $input['content'] ?? null;
} else {
    $contentId = $_POST['content_id'] ?? null;
    $content = $_POST['content'] ?? null;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? ''; // 根据你的session结构调整

// 验证数据完整性
if (!$contentId || !$content) {
    error_log("Error: Missing parameters user:$userId");
    echo json_encode(['status' => 'error', 'message' => '评论内容不能为空']);
    exit;
}

// 防止SQL注入
if (!filter_var($contentId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
    error_log("Invalid content_id: $contentId");
    echo json_encode(['status' => 'error', 'message' => '无效的文章ID']);
    exit;
}

// 插入评论
try {
    $sql = "INSERT INTO comments (content_id, user_id, username, content) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $contentId, $userId, $username, $content);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => '评论发布成功']);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => '服务器异常：' . $e->getMessage()]);
} finally {
    $stmt->close();
    $conn->close();
}
?>
