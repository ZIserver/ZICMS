<?php
session_start();
require_once '.././db.php';
require_once '.././function/functions.php';

// 检查是否已登录
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '请先登录']);
    exit;
}

// 获取提交的数据
$title = $conn->real_escape_string($_POST['title']);
$content = $conn->real_escape_string($_POST['content']);
$author = $_SESSION['username']; // 作者为用户名

// 插入文章到数据库
$sql = "INSERT INTO articles (title, content, author) VALUES ('$title', '$content', '$author')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(['success' => true, 'message' => '文章保存成功']);
} else {
    echo json_encode(['success' => false, 'message' => '文章保存失败: ' . $conn->error]);
}

$conn->close();
?>
