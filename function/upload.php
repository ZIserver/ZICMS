<?php
session_start();
require_once '../db.php';
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => '未知错误'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = '请登录后再上传';
    $response['redirect'] = '/login';
    echo json_encode($response);
    exit;
}

// 获取文章ID
$articleId = filter_input(INPUT_POST, 'article_id', FILTER_VALIDATE_INT);
if (!$articleId) {
    $response['message'] = '无效的文章ID';
    echo json_encode($response);
    exit;
}

if (!isset($_FILES['fileToUpload']) || $_FILES['fileToUpload']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = '文件上传失败';
    echo json_encode($response);
    exit;
}

$file = $_FILES['fileToUpload'];
$userId = $_SESSION['user_id'];

// 验证文章权限
$stmt = $conn->prepare("SELECT COUNT(*) FROM articles WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $articleId, $userId);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count === 0 && $_SESSION['user_type'] !== 'admin') {
    $response['message'] = '你没有权限为这篇文章上传文件';
    echo json_encode($response);
    exit;
}

// 文件类型验证
$allowedTypes = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'images',
    'image/png' => 'images',
    'image/gif' => 'images'
];

if (!array_key_exists($file['type'], $allowedTypes)) {
    $response['message'] = '不支持的文件类型: ' . $file['type'];
    echo json_encode($response);
    exit;
}

// 文件大小限制 10MB
if ($file['size'] > 10 * 1024 * 1024) {
    $response['message'] = '文件大小不能超过 10MB';
    echo json_encode($response);
    exit;
}

// 上传文件
$originalName = substr($file['name'], 0, 250); // 限制原始文件名长度
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$newFileName = bin2hex(random_bytes(6)) . '-' . time() . '.' . $ext;
$subDir = $allowedTypes[$file['type']];
$targetPath = "uploads/{$subDir}/" . $newFileName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // 数据库记录
    $stmt = $conn->prepare("INSERT INTO article_attachments (article_id, user_id, file_path, original_name, size, mime_type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissis", 
        $articleId, 
        $userId, 
        $targetPath,
        $originalName,
        $file['size'],
        $file['type']
    );
    
    if ($stmt->execute()) {
        $stmt->close();
        $response = [
            'status' => 'success',
            'data' => [
                'file_path' => $targetPath,
                'original_name' => $originalName
            ]
        ];
    } else {
        $response['message'] = '数据库写入失败: ' . $conn->error;
    }
} else {
    $response['message'] = '文件移动失败: ' . error_get_last();
}

echo json_encode($response);
