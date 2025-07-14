<?php
session_start();
require_once 'db.php';



$userId = $_SESSION['user_id'];

// 头像上传处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'avatar_upload') {
    $uploadDir = 'uploads/avatars';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $response = ['success' => false, 'error' => '', 'newAvatar' => null];
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    try {
        // 验证上传文件是否存在
        if (!isset($_FILES['avatar']) || !is_uploaded_file($_FILES['avatar']['tmp_name'])) {
            throw new Exception('文件上传参数错误或文件未上传');
        }

        $file = $_FILES['avatar'];

        // 错误代码处理
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => "文件超过服务器限制（最大" . ini_get('upload_max_filesize') . "）",
                UPLOAD_ERR_FORM_SIZE => "文件超过表单设定上限",
                UPLOAD_ERR_PARTIAL => "文件上传不完整",
                UPLOAD_ERR_NO_FILE => "未选择上传文件",
                UPLOAD_ERR_NO_TMP_DIR => "服务器临时目录丢失",
                UPLOAD_ERR_CANT_WRITE => "无法写入存储目录"
            ];
            throw new Exception($uploadErrors[$file['error']] ?? "未知上传错误（代码：{$file['error']}）");
        }

        // 验证文件类型（真实MIME类型）
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realType = $finfo->file($file['tmp_name']);
        if (!array_key_exists($realType, $allowedTypes)) {
            throw new Exception("不支持的文件格式：{$realType}");
        }

        // 验证文件大小
        if ($file['size'] > $maxSize) {
            $formattedSize = number_format($maxSize / 1024 / 1024, 1) . 'MB';
            throw new Exception("文件大小超过限制（最大{$formattedSize}）");
        }

        // 验证上传目录是否可写
        if (!is_writable($uploadDir)) {
            throw new Exception("上传目录不可写，请联系管理员");
        }

        // 生成唯一文件名
        $ext = $allowedTypes[$realType];
        $filename = "avatar_" . hash('sha256', uniqid('', true)) . ".$ext";
        $uploadPath = "{$uploadDir}/{$filename}";

        // 处理图像
        switch ($realType) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $image = @imagecreatefrompng($file['tmp_name']);
                break;
            default:
                throw new Exception("不支持的图片类型");
        }

        if (!$image) {
            throw new Exception("无法解析图像文件（可能已损坏）");
        }

        // 处理JPEG的EXIF方向
        if ($realType === 'image/jpeg' && function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($file['tmp_name']);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 3:
                            $image = imagerotate($image, 180, 0);
                            break;
                        case 6:
                            $image = imagerotate($image, -90, 0);
                            break;
                        case 8:
                            $image = imagerotate($image, 90, 0);
                            break;
                    }
                }
            } catch (Exception $ex) {
                // EXIF读取失败不影响主流程
            }
        }

        // 调整图像尺寸
        list($width, $height) = getimagesize($file['tmp_name']);
        $maxDimension = 2048;
        if ($width > $maxDimension || $height > $maxDimension) {
            $ratio = $width / $height;
            $newWidth = $width > $height ? $maxDimension : (int)($maxDimension * $ratio);
            $newHeight = $width > $height ? (int)($maxDimension / $ratio) : $maxDimension;
            $resized = @imagescale($image, $newWidth, $newHeight);
            if (!$resized) {
                throw new Exception("图像缩放失败");
            }
            imagedestroy($image);
            $image = $resized;
        }

        // 保存图像文件
        $saveQuality = 85; // 对于JPEG和WEBP有效
        if ($ext === 'png') {
            // PNG压缩级别：0-9（0无压缩，9最高压缩）
            $success = @imagepng($image, $uploadPath, 9);
        } else {
            $success = @imagejpeg($image, $uploadPath, $saveQuality);
        }

        if (!$success) {
            throw new Exception("无法保存图像文件（存储空间可能不足）");
        }

        // 更新数据库中的头像路径
        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->bind_param("si", $uploadPath, $userId);
        if (!$stmt->execute()) {
            throw new Exception("数据库更新失败");
        }
        $stmt->close();

        // 返回新的头像路径并添加防缓存随机参数
        $response['success'] = true;
        $response['newAvatar'] = "{$uploadPath}?v=" . time();
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        // 清理失败的临时文件
        if (isset($uploadPath) && file_exists($uploadPath)) {
            @unlink($uploadPath);
        }
    } finally {
        // 清理图像资源
        if (isset($image) && is_resource($image)) {
            imagedestroy($image);
        }
        header('Content-Type: application/json');
        echo json_encode($response, JSON_UNESCAPED_SLASHES);
        exit; // 确保脚本终止
    }
}
?>
