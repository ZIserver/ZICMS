<?php
session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '请先登录！']);
    exit;
}

$loggedInUserId = $_SESSION['user_id'];
$profileId = intval($_POST['profile_id']);
$action = $_POST['action'];

// 连接数据库
require '../db.php';

if ($action === 'follow') {
    // 检查是否已经关注
    $checkStmt = $conn->prepare("
        SELECT 1
        FROM follows
        WHERE follower_id = ? AND following_id = ?
    ");
    $checkStmt->bind_param("ii", $loggedInUserId, $profileId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => '你已经关注了该用户！']);
        exit;
    }

    // 插入关注记录
    $insertStmt = $conn->prepare("
        INSERT INTO follows (follower_id, following_id)
        VALUES (?, ?)
    ");
    $insertStmt->bind_param("ii", $loggedInUserId, $profileId);

    if ($insertStmt->execute()) {
        echo json_encode(['success' => true, 'message' => '关注成功！']);
    } else {
        echo json_encode(['success' => false, 'message' => '关注失败，请重试！']);
    }
} elseif ($action === 'unfollow') {
    // 删除关注记录
    $deleteStmt = $conn->prepare("
        DELETE FROM follows
        WHERE follower_id = ? AND following_id = ?
    ");
    $deleteStmt->bind_param("ii", $loggedInUserId, $profileId);

    if ($deleteStmt->execute()) {
        echo json_encode(['success' => true, 'message' => '取消关注成功！']);
    } else {
        echo json_encode(['success' => false, 'message' => '取消关注失败，请重试！']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '无效的操作！']);
}
?>
