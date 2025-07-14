<?php

session_start();
require_once '.././db.php';
require_once '.././function/functions.php';

// 检查是否为管理员
if (!isLoggedIn() || !isAdmin($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => '权限不足']);
    exit;
}

// 获取用户 ID
if (isset($_POST['user_id'])) {
    $user_id = $conn->real_escape_string($_POST['user_id']);
    // 删除用户的 SQL 查询
    $sql = "DELETE FROM users WHERE id = '$user_id'";
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['success' => true, 'message' => '用户删除成功']);
    } else {
        echo json_encode(['success' => false, 'message' => '用户删除失败: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => '未提供用户 ID']);
}
?>
