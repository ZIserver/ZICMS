<?php
session_start();


require_once 'db.php';

// 强制登录验证
if (!isset($_SESSION['user_id'])) {
    header("Location: admin/login.php");
    exit();
}
$uploadDir = 'uploads/avatars';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
    // 创建防爬虫保护文件（可选）
    file_put_contents("$uploadDir/.htaccess", "Options -Indexes\nDeny from all");
}
$userId = $_SESSION['user_id'];
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'profile';

// 自动创建关注关系表（如果不存在）
$conn->query("CREATE TABLE IF NOT EXISTS follows (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    follower_id INT UNSIGNED NOT NULL,
    following_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (follower_id, following_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 获取用户基础信息
// 获取用户基础信息和总阅读量
$user = [];
$stmt = $conn->prepare("SELECT 
    users.username, 
    users.avatar, 
    users.bio, 
    users.email, 
    users.post_count, 
    (SELECT SUM(views) FROM articles WHERE user_id = users.id) AS views
FROM users 
WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 获取粉丝数和关注数
$followerCountStmt = $conn->prepare("SELECT COUNT(*) AS count FROM follows WHERE following_id = ?");
$followerCountStmt->bind_param("i", $userId);
$followerCountStmt->execute();
$followerCount = $followerCountStmt->get_result()->fetch_assoc()['count'];
$followingCountStmt = $conn->prepare("SELECT COUNT(*) AS count FROM follows WHERE follower_id = ?");
$followingCountStmt->bind_param("i", $userId);
$followingCountStmt->execute();
$followingCount = $followingCountStmt->get_result()->fetch_assoc()['count'];
// 处理表单提交
$errors = [];
$success = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 头像上传处理
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 定义上传目录
    $uploadDir = __DIR__ . '/uploads/avatars'; // 使用相对路径
    if (!file_exists($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
        die(json_encode(['success' => false, 'error' => '上传目录创建失败']));
    }
    if (isset($_POST['action']) && $_POST['action'] === 'avatar_upload') {
        // 使用输出缓冲防止任何意外输出
        ob_start();
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
            // 返回新的头像路径并添加防缓存随机参数
            $response['success'] = true;
            $response['newAvatar'] = "{$uploadDir}/{$filename}?v=" . time();
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
            // 确保缓冲区清理
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode($response, JSON_UNESCAPED_SLASHES);
            exit; // 确保脚本终止
        }
    }
}
}


    // 个人简介更新
    if (isset($_POST['bio'])) {
        $bio = trim($_POST['bio']);
        if (strlen($bio) > 500) {
            $errors[] = "简介不能超过500字";
        } else {
            $stmt = $conn->prepare("UPDATE users SET bio = ? WHERE id = ?");
            $stmt->bind_param("si", $bio, $userId);
            if ($stmt->execute()) {
                $user['bio'] = $bio;
                $success[] = "个人简介已更新";
            } else {
                $errors[] = "简介更新失败";
            }
            $stmt->close();
        }
    }

    // 密码修改
    if (isset($_POST['current_password'])) {
    $currentPass = $_POST['current_password'];
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];

    if ($newPass !== $confirmPass) {
        $errors[] = "两次输入的新密码不一致";
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        // 使用MD5验证当前密码
        if (md5($currentPass) === $row['password']) {
            // 使用MD5生成新密码哈希
            $newHash = md5($newPass);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $newHash, $userId);
            if ($stmt->execute()) {
                $success[] = "密码修改成功";
            } else {
                $errors[] = "密码更新失败";
            }
            $stmt->close();
        } else {
            $errors[] = "当前密码错误";
        }
    }
}



// 加载动态数据
switch ($currentPage) {
    case 'articles':
        $page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $stmt = $conn->prepare("SELECT SQL_CALC_FOUND_ROWS id, title, views, like_count, created_at 
                              FROM articles 
                              WHERE user_id = ? 
                              ORDER BY created_at DESC 
                              LIMIT ? OFFSET ?");
        $stmt->bind_param("iii", $userId, $perPage, $offset);
        $stmt->execute();
        $articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $totalResult = $conn->query("SELECT FOUND_ROWS() AS total");
        $total = $totalResult->fetch_assoc()['total'];
        $totalPages = ceil($total / $perPage);
        break;

    case 'followers':
        $stmt = $conn->prepare("SELECT u.id, u.username, u.avatar 
                              FROM follows f 
                              JOIN users u ON f.follower_id = u.id 
                              WHERE f.following_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $followers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        break;

    case 'following':
        $stmt = $conn->prepare("SELECT u.id, u.username, u.avatar 
                              FROM follows f 
                              JOIN users u ON f.following_id = u.id 
                              WHERE f.follower_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $following = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        break;
}
include 'common/header.php';
$conn->close();

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentConfig['site_name']); ?> - 用户中心 - <?= htmlspecialchars($user['username']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .dashboard-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }
        .sidebar {
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
            padding: 2rem 1rem;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            color: #495057;
            transition: all 0.2s;
        }
        .nav-link:hover {
            background-color: #e9ecef;
        }
        .nav-link.active {
            background-color: #e3f2fd;
            color: #0d6efd;
            font-weight: 500;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .article-item {
            transition: transform 0.2s;
        }
        .article-item:hover {
            transform: translateY(-2px);
        }
        /* 美化按钮 */
.upload-btn {
    background-color: #4caf50; /* 绿色背景 */
    color: white; /* 白色文字 */
    padding: 10px 20px; /* 内边距 */
    border: none; /* 无边框 */
    border-radius: 5px; /* 圆角 */
    cursor: pointer; /* 鼠标指针 */
    font-size: 16px; /* 字体大小 */
    transition: background-color 0.3s ease; /* 过渡动画 */
}

.upload-btn:hover {
    background-color: #45a049; /* 鼠标悬停时颜色 */
}

.upload-btn:active {
    background-color: #3d8b40; /* 点击时颜色 */
}

/* 进度条 */
#progressBar {
    margin-top: 10px;
    border-radius: 5px;
    overflow: hidden;
}

#progress {
    transition: width 0.3s ease;
}

    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- 侧边栏 -->
        <aside class="sidebar">
            <div class="text-center mb-4">
                <div class="position-relative d-inline-block">
    <img src="<?= htmlspecialchars($user['avatar']) ?>" 
         class="rounded-circle mb-3 shadow" 
         style="width: 120px; height: 120px; object-fit: cover;">
    
</div>
                <h5 class="mb-1"><?= htmlspecialchars($user['username']) ?></h5>
                <small class="text-muted">用户ID: <?= $userId ?></small>
            </div>

            <nav class="nav flex-column gap-2">
                <a href="?page=profile" class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
                    <i class="fas fa-user-circle me-2"></i>个人中心
                </a>
                <a href="?page=articles" class="nav-link <?= $currentPage === 'articles' ? 'active' : '' ?>">
                    <i class="fas fa-file-alt me-2"></i>我的文章
                    
                </a>
                <a href="?page=followers" class="nav-link <?= $currentPage === 'followers' ? 'active' : '' ?>">
                    <i class="fas fa-users me-2"></i>我的粉丝
                    
                </a>
                <a href="?page=following" class="nav-link <?= $currentPage === 'following' ? 'active' : '' ?>">
                    <i class="fas fa-heart me-2"></i>我的关注
                    
                </a>
                <a href="?page=orders" class="nav-link <?= $currentPage === 'orders' ? 'active' : '' ?>">
                    <i class="fas fa-list-alt me-2"></i>我的订单
                </a>

                <a href="?page=settings" class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog me-2"></i>账户设置
                </a>
            </nav>
        </aside>

        <!-- 主内容区 -->
        <main class="p-4" style="background-color: #f8f9fa;">
            <?php if ($currentPage === 'profile'): ?>
                <div class="row g-4">
                    <div class="col-12">
                        <div class="stat-card">
                            <h4 class="mb-4"><i class="fas fa-chart-line me-2"></i>数据概览</h4>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center p-3 bg-light rounded">
                                        <div class="h3 text-primary"><?= $user['post_count'] ?></div>
                                        <small class="text-muted">发布文章</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-3 bg-light rounded">
                                        <div class="h3 text-success"><?= $user['views'] ?? 0 ?></div>
                                        <small class="text-muted">总阅读量</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="text-center p-3 bg-light rounded">
                                        <div class="h3 text-success"><?= $followerCount ?></div>
                                        <small class="text-muted">总粉丝量</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="stat-card">
                            <h4 class="mb-4"><i class="fas fa-user-edit me-2"></i>编辑资料</h4>
                            <form method="post">
                                <div class="mb-4">
                                    <label class="form-label">个人简介</label>
                                    <textarea name="bio" class="form-control" rows="4"
                                              maxlength="500"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">保存修改</button>
                            </form>
                        </div>
                    </div>
                </div>

            <?php elseif ($currentPage === 'articles'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0"><i class="fas fa-file-alt me-2"></i>文章管理</h2>
                    <a href="edit.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>新建文章
                    </a>
                </div>

                <?php if (!empty($articles)): ?>
                <div class="row g-4">
                    <?php foreach ($articles as $article): ?>
                    <div class="col-md-6">
                        <div class="article-item card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($article['title']) ?></h5>
                                <div class="d-flex justify-content-between text-muted small mb-3">
                                    <span>
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('Y-m-d', strtotime($article['created_at'])) ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-eye me-1"></i><?= $article['views'] ?>
                                        <i class="fas fa-heart ms-2 me-1"></i><?= $article['like_count'] ?>
                                    </span>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="article.php?id=<?= $article['id'] ?>" 
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <a href="edit.php?id=<?= $article['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                            <a class="page-link" href="?page=articles&p=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php else: ?>
                <div class="alert alert-info">您还没有发布任何文章</div>
                <?php endif; ?>

            <?php elseif ($currentPage === 'followers' || $currentPage === 'following'): ?>
                <h2 class="mb-4">
                    <i class="fas fa-<?= $currentPage === 'followers' ? 'users' : 'heart' ?> me-2"></i>
                    <?= $currentPage === 'followers' ? '粉丝列表' : '我的关注' ?>
                </h2>

                <div class="row g-3">
                    <?php foreach (${$currentPage} as $user): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <img src="<?= htmlspecialchars($user['avatar']) ?>" 
                                         class="rounded-circle me-3"
                                         style="width: 60px; height: 60px; object-fit: cover;">
                                    <div>
                                        <h5 class="mb-0"><?= htmlspecialchars($user['username']) ?></h5>
                                        <small class="text-muted">ID: <?= $user['id'] ?></small>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary w-50">
                                        <i class="fas fa-envelope me-1"></i>私信
                                    </button>
                                    <?php if ($currentPage === 'followers'): ?>
                                    <button class="btn btn-sm btn-success w-50">
                                        <i class="fas fa-plus me-1"></i>关注
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-sm btn-danger w-50">
                                        <i class="fas fa-times me-1"></i>取消
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

<?php elseif ($currentPage === 'settings'): ?>
<div class="row">
    <!-- 独立头像上传表单 -->
    <div class="col-md-6">
    <div class="stat-card mb-4 p-4 shadow-sm bg-white rounded">
        <h4 class="mb-4"><i class="fas fa-portrait me-2"></i>头像设置</h4>
        <form id="avatarUploadForm" enctype="multipart/form-data">
            <div class="mb-3">
                <input type="file" id="avatarInput" name="avatar" accept="image/jpeg, image/png" style="display: none;">
                <button type="button" id="uploadButton" class="btn btn-primary">
                    <i class="fas fa-upload me-2"></i>上传头像
                </button>
            </div>
            <div id="progressBar" class="progress mb-3" style="display: none;">
                <div id="progress" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <p id="statusMessage" class="text-danger mb-3"></p>
            <!-- 显示新头像 -->
            <div id="avatarPreview" class="text-center" style="display: none;">
                <img id="newAvatar" src="" alt="新头像" class="img-fluid rounded-circle" style="max-width: 200px;">
            </div>
        </form>
   
    </div>
<div class="col-md-6">
    <div class="stat-card mb-4">
    <h5 class="mt-4 mb-3">密码变更</h5>
                <div class="mb-3">
                    <label class="form-label">当前密码</label>
                    <input type="password" name="current_password" 
                           class="form-control" placeholder="如需修改密码请填写">
                </div>
                <div class="mb-3">
                    <label class="form-label">新密码</label>
                    <input type="password" name="new_password" 
                           class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">确认新密码</label>
                    <input type="password" name="confirm_password" 
                           class="form-control">
                </div>
                <input type="hidden" name="action" value="update_settings">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-save me-2"></i>保存所有设置
                </button>
</div>
</div>
<?php endif; ?>

            <!-- 消息提示 -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger mt-4">
                <?php foreach ($errors as $error): ?>
                <div><?= $error ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="alert alert-success mt-4">
                <?php foreach ($success as $msg): ?>
                <div><?= $msg ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('uploadButton').addEventListener('click', function () {
    // 触发文件选择
    document.getElementById('avatarInput').click();
});

document.getElementById('avatarInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;

    // 验证文件类型和大小
    const allowedTypes = ['image/jpeg', 'image/png'];
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (!allowedTypes.includes(file.type)) {
        alert('仅支持 JPG 和 PNG 格式的图片');
        return;
    }
    if (file.size > maxSize) {
        alert('文件大小不能超过 5MB');
        return;
    }

    // 显示预览图像
    const reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById('newAvatar').src = e.target.result;
        document.getElementById('newAvatar').style.display = 'block';
    };
    reader.readAsDataURL(file);

    // 开始上传
    uploadAvatar(file);
});

function uploadAvatar(file) {
    const formData = new FormData();
    formData.append('avatar', file);
    formData.append('action', 'avatar_upload');

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'upload.php', true);

    // 显示进度条
    const progressBar = document.getElementById('progressBar');
    const progress = document.getElementById('progress');
    progressBar.style.display = 'block';

    // 上传进度监听
    xhr.upload.addEventListener('progress', function (e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            progress.style.width = percentComplete + '%';
        }
    });

    // 上传完成监听
    xhr.onload = function () {
        progressBar.style.display = 'none';
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                // 更新头像
                document.getElementById('newAvatar').src = response.newAvatar;
                document.getElementById('statusMessage').textContent = '头像上传成功！';
                document.getElementById('statusMessage').style.color = 'green';
                location.reload();
            } else {
                document.getElementById('statusMessage').textContent = '上传失败：' + response.error;
                document.getElementById('statusMessage').style.color = 'red';
            }
        } else {
            document.getElementById('statusMessage').textContent = '服务器错误，请稍后重试';
            document.getElementById('statusMessage').style.color = 'red';
        }
    };

    // 上传错误监听
    xhr.onerror = function () {
        progressBar.style.display = 'none';
        document.getElementById('statusMessage').textContent = '网络错误，请检查您的连接';
        document.getElementById('statusMessage').style.color = 'red';
    };

    // 发送请求
    xhr.send(formData);
}
</script>

</body>
</html>
