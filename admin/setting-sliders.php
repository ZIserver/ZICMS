<?php
// setting-sliders.php

// ================ 初始化阶段 ================
// 开启严格错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "admin_header.php";
// 初始化会话


// 强制HTTPS访问（生产环境启用）
/*
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
*/

// ================ 依赖加载 ================
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../function/functions.php';

// ================ 安全验证 ================
// 验证登录状态和管理员权限
if (!isLoggedIn() || !isAdmin($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

if (!isAdmin($_SESSION['username'])) {
    header('HTTP/1.1 403 Forbidden');
    die('权限不足');
}

// ================ 全局变量初始化 ================
$slideId = null;
$error = null;
$success = null;
$currentConfig = [
    'site_url' => 'https://example.com' // 从实际配置加载
];
$sliders = [];
$uploadDir = __DIR__ . '/../uploads/sliders/';

// ================ CSRF令牌管理 ================
if (empty($_SESSION['form_key'])) {
    $_SESSION['form_key'] = bin2hex(random_bytes(32));
}

// ================ 文件系统初始化 ================
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        die("无法创建上传目录，请检查权限");
    }
    file_put_contents($uploadDir . '.htaccess', "Deny from all"); // 禁止直接访问
}

// ================ 数据库操作函数 ================
/**
 * 安全执行查询
 */
function executeQuery($conn, $sql, $params = [], $types = '') {
    while ($conn->more_results()) {
        $conn->next_result();
        if ($res = $conn->store_result()) {
            $res->free();
        }
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("预处理失败: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception("执行失败: " . $stmt->error);
    }

    return $stmt;
}

// ================ 表单处理逻辑 ================
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF验证
        if (!isset($_POST['form_key']) || !hash_equals($_SESSION['form_key'], $_POST['form_key'])) {
            throw new Exception("安全令牌验证失败，请刷新页面后重试");
        }

        $action = $_POST['slider_action'] ?? '';
        $slideId = filter_input(INPUT_POST, 'slide_id', FILTER_VALIDATE_INT);

        switch ($action) {
            case 'save':
                // 输入验证
                $title = trim(htmlspecialchars($_POST['slide_title'] ?? ''));
                $description = trim(htmlspecialchars($_POST['slide_desc'] ?? ''));
                $url = filter_input(INPUT_POST, 'slide_url', FILTER_SANITIZE_URL);
                $sort = filter_input(INPUT_POST, 'slide_sort', FILTER_VALIDATE_INT) ?: 0;

                if (mb_strlen($title) < 2 || mb_strlen($title) > 100) {
                    throw new Exception("标题长度需在2-100字符之间");
                }

                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    throw new Exception("请输入有效的URL地址");
                }

                // 文件上传处理
                $filename = null;
                // 文件上传处理
if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] === UPLOAD_ERR_OK) {
    // 基础验证
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($_FILES['slide_image']['size'] > $maxSize) {
        throw new Exception("文件大小超过限制");
    }

    // MIME 类型验证
    $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    
    // 检测扩展可用性
    if (!function_exists('mime_content_type') && !class_exists('finfo')) {
        throw new Exception("系统需要 fileinfo 扩展支持");
    }

    // 获取 MIME
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['slide_image']['tmp_name']);
    } else {
        $mime = mime_content_type($_FILES['slide_image']['tmp_name']);
    }

    // 二次验证
    $imageInfo = getimagesize($_FILES['slide_image']['tmp_name']);
    if (!isset($allowedMime[$mime]) || $imageInfo['mime'] !== $mime) {
        throw new Exception("仅允许上传 JPG/PNG/WEBP 格式图片");
    }

    // 生成安全文件名
    $ext = $allowedMime[$mime];
    $filename = bin2hex(random_bytes(16)) . ".$ext";
    $targetPath = $uploadDir . $filename;

    // 移动文件
    if (!move_uploaded_file($_FILES['slide_image']['tmp_name'], $targetPath)) {
        throw new Exception("文件保存失败");
    }
}


                // 数据库操作
                if ($slideId) {
                    // 更新操作
                    if ($filename) {
                        // 获取旧文件名
                        $stmt = executeQuery($conn, "SELECT image FROM sliders WHERE id = ?", [$slideId], 'i');
                        $oldImage = $stmt->get_result()->fetch_assoc()['image'];
                        
                        // 删除旧文件
                        if ($oldImage && file_exists($uploadDir . $oldImage)) {
                            unlink($uploadDir . $oldImage);
                        }

                        // 更新带图片
                        executeQuery(
                            $conn,
                            "UPDATE sliders SET image=?, title=?, description=?, url=?, sort=? WHERE id=?",
                            [$filename, $title, $description, $url, $sort, $slideId],
                            'ssssii'
                        );
                    } else {
                        // 仅更新文本
                        executeQuery(
                            $conn,
                            "UPDATE sliders SET title=?, description=?, url=?, sort=? WHERE id=?",
                            [$title, $description, $url, $sort, $slideId],
                            'sssii'
                        );
                    }
                } else {
                    // 新增操作
                    if (!$filename) {
                        throw new Exception("必须上传幻灯片图片");
                    }
                    $stmt = executeQuery(
                        $conn,
                        "INSERT INTO sliders (image, title, description, url, sort) VALUES (?, ?, ?, ?, ?)",
                        [$filename, $title, $description, $url, $sort],
                        'ssssi'
                    );
                    $slideId = $conn->insert_id;
                }

                $success = "操作成功保存";
                break;

            case 'delete':
                if (!$slideId) {
                    throw new Exception("无效的幻灯片ID");
                }

                // 获取图片路径
                $stmt = executeQuery($conn, "SELECT image FROM sliders WHERE id = ?", [$slideId], 'i');
                $image = $stmt->get_result()->fetch_assoc()['image'] ?? '';

                // 删除记录
                executeQuery($conn, "DELETE FROM sliders WHERE id = ?", [$slideId], 'i');

                // 删除文件
                if ($image && file_exists($uploadDir . $image)) {
                    unlink($uploadDir . $image);
                }

                $success = "删除成功";
                break;

            default:
                throw new Exception("无效的操作类型");
        }

        // 生成新CSRF令牌
        $_SESSION['form_key'] = bin2hex(random_bytes(32));

        // 防止重复提交
        //header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
        //exit;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// ================ 数据获取 ================
try {
    $stmt = executeQuery($conn, "SELECT * FROM sliders ");
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['title'] = htmlspecialchars($row['title']);
        $row['description'] = nl2br(htmlspecialchars($row['description']));
        $row['url'] = htmlspecialchars($row['url']);
        $sliders[] = $row;
    }
    $result->free();
    $stmt->close();
} catch (Exception $e) {
    $error = $e->getMessage();
}

// ================ 视图输出 ================

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>幻灯片管理 - 控制面板</title>
    <link href="../assets/bootstrap.min.css" rel="stylesheet">
    <style>
        .slider-card {
            transition: transform 0.2s;
            break-inside: avoid-column;
        }
        .slider-card:hover {
            transform: translateY(-3px);
        }
        .upload-box {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            background: #f8f9fa;
        }
        .preview-img {
            max-height: 200px;
            object-fit: contain;
            background: #f0f0f0;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- 操作反馈 -->
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- 表单区 -->
            <div class="col-lg-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title mb-4"><?= $slideId ? '编辑幻灯片' : '新增幻灯片' ?></h5>
                        
                        <form method="post" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="slider_action" value="save">
                            <input type="hidden" name="form_key" value="<?= htmlspecialchars($_SESSION['form_key']) ?>">
                            <input type="hidden" name="slide_id" value="<?= htmlspecialchars($slideId ?? '') ?>">

                            <div class="upload-box mb-4">
                                <div id="previewContainer" class="mb-3">
                                    <?php if ($slideId && !empty($sliders)): 
                                        $current = current(
                                            array_filter(
                                            $sliders,
                                            function($s) use ($slideId) {
                                                return $s['id'] == $slideId;
                                            }
                                        )
                                    );

                                    ?>
                                        <img src="../uploads/sliders/<?= htmlspecialchars($current['image']) ?>" 
                                             class="preview-img w-100 mb-3">
                                    <?php endif; ?>
                                </div>
                                <label class="btn btn-primary">
                                    选择图片 <input type="file" name="slide_image" 
                                        class="visually-hidden" 
                                        <?= $slideId ? '' : 'required' ?> 
                                        accept="image/jpeg, image/png, image/webp"
                                        onchange="previewImage(this)">
                                </label>
                                <div class="form-text">
                                    支持JPEG/PNG/WEBP格式，建议尺寸：1920x1080px
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">标题</label>
                                <input type="text" name="slide_title" 
                                    class="form-control" 
                                    value="<?= htmlspecialchars($_POST['slide_title'] ?? ($current['title'] ?? '')) ?>"
                                    required 
                                    minlength="2" 
                                    maxlength="100">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">描述内容</label>
                                <textarea name="slide_desc" 
                                    class="form-control" 
                                    rows="3"><?= htmlspecialchars($_POST['slide_desc'] ?? ($current['description'] ?? '')) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">跳转链接</label>
                                <input type="url" name="slide_url" 
                                    class="form-control" 
                                    value="<?= htmlspecialchars($_POST['slide_url'] ?? ($current['url'] ?? $currentConfig['site_url'])) ?>"
                                    required 
                                    pattern="https?://.+">
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">排序序号</label>
                                    <input type="number" name="slide_sort" 
                                        class="form-control" 
                                        value="<?= htmlspecialchars($_POST['slide_sort'] ?? ($current['sort'] ?? 0)) ?>" 
                                        min="0" 
                                        max="999" 
                                        required>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <?= $slideId ? '更新' : '添加' ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 列表区 -->
            <div class="col-lg-6">
                <div class="card shadow">
                    <div class="card-body">
                        <h5 class="card-title mb-4">现有幻灯片 (<?= count($sliders) ?>)</h5>
                        
                        <div class="sliders row row-cols-1 g-3">
                            <?php if (empty($sliders)): ?>
                                <div class="col">
                                    <div class="alert alert-info">暂无幻灯片</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($sliders as $slide): ?>
                                <div class="col">
                                    <div class="slider-card card h-100">
                                        <div class="card-body">
                                            <div class="d-flex gap-3">
                                                <img src="../uploads/sliders/<?= htmlspecialchars($slide['image']) ?>" 
                                                     class="preview-img flex-shrink-0" 
                                                     style="width: 120px;">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center gap-2 mb-2">
                                                        <h6 class="mb-0"><?= $slide['title'] ?></h6>
                                                        <small class="text-muted">#<?= $slide['id'] ?></small>
                                                        <span class="badge bg-secondary ms-auto">排序: <?= $slide['sort'] ?></span>
                                                    </div>
                                                    <p class="text-muted small mb-2"><?= $slide['description'] ?></p>
                                                    <a href="<?= $slide['url'] ?>" target="_blank" 
                                                       class="text-decoration-none small"><?= $slide['url'] ?></a>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-transparent d-flex gap-2">
                                            <form method="post" 
                                                  onsubmit="return confirm('确定要删除此幻灯片吗？')">
                                                <input type="hidden" name="slider_action" value="delete">
                                                <input type="hidden" name="form_key" 
                                                    value="<?= htmlspecialchars($_SESSION['form_key']) ?>">
                                                <input type="hidden" name="slide_id" 
                                                    value="<?= $slide['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">删除</button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                onclick="loadEditForm(<?= $slide['id'] ?>)">编辑</button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/bootstrap.bundle.min.js"></script>
    <script>
        // 图片预览功能
        function previewImage(input) {
            const preview = document.getElementById('previewContainer');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" class="preview-img w-100 mb-3">`;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // 加载编辑表单
        function loadEditForm(id) {
            window.location.search = `?edit=${id}`;
        }

        // 表单验证增强
        document.querySelector('form').addEventListener('submit', function(e) {
            const urlInput = this.querySelector('input[name="slide_url"]');
            try {
                new URL(urlInput.value);
            } catch {
                alert('请输入有效的URL地址');
                e.preventDefault();
                urlInput.focus();
                return;
            }

            if (this.querySelector('input[name="slide_image"]').files.length === 0 && 
                <?= $slideId ? 'false' : 'true' ?>) {
                alert('请选择要上传的图片');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
