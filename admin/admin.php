<?php
session_start();
require_once '.././db.php';
require_once '.././function/functions.php';
require_once '.././install/config.php';

// 检查是否为管理员
if (!isLoggedIn() || !isAdmin($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// 检查是否已安装
if (!file_exists('.././install/install.lock')) {
    header('Location: .././install/install.php'); // 如果未安装，重定向到安装页面
    exit;
}

// 处理文章发布请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['title']) && isset($_POST['content'])) {
        $title = $_POST['title'];
        $content = $_POST['content'];

        $stmt = $conn->prepare("INSERT INTO articles (title, content, status) VALUES (?, ?, 0)");
        $stmt->bind_param("ss", $title, $content);
        $stmt->execute();
    }
    //header('Location: admin.php');
    exit;
}

// 处理文章删除请求
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);

    // 检查数据库连接
    if (!$conn) {
        die("数据库连接失败: " . mysqli_connect_error());
    }

    // 开启事务保证数据一致性（需确保使用InnoDB引擎）
    $conn->begin_transaction();

    try {
        // [1] 获取被删除文章的user_id并锁定行
        //-----------------------------------------
        $stmt = $conn->prepare("SELECT user_id FROM articles WHERE id = ? FOR UPDATE");
        if (!$stmt) {
            throw new Exception("准备查询语句失败: " . $conn->error);
        }
        $stmt->bind_param("i", $deleteId);
        if (!$stmt->execute()) {
            throw new Exception("执行查询失败: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            throw new Exception("目标文章不存在或已被删除");
        }
        $userId = $row['user_id'];

        // [2] 删除关联评论
        //-----------------------------------------
        $stmt = $conn->prepare("DELETE FROM comments WHERE content_id = ?");
        if (!$stmt) {
            throw new Exception("准备删除评论语句失败: " . $conn->error);
        }
        $stmt->bind_param("i", $deleteId);
        if (!$stmt->execute()) {
            throw new Exception("执行删除评论失败: " . $stmt->error);
        }
        $stmt->close();

        // [3] 删除主文章
        //-----------------------------------------
        $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
        if (!$stmt) {
            throw new Exception("准备删除文章语句失败: " . $conn->error);
        }
        $stmt->bind_param("i", $deleteId);
        if (!$stmt->execute()) {
            throw new Exception("执行删除文章失败: " . $stmt->error);
        }
        $stmt->close();

        // [4] 更新用户文章计数
        //-----------------------------------------
        $stmt = $conn->prepare("UPDATE users SET post_count = post_count - 1 WHERE id = ?");
        if (!$stmt) {
            throw new Exception("准备更新用户语句失败: " . $conn->error);
        }
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) {
            throw new Exception("更新用户文章数失败: " . $stmt->error);
        }
        $stmt->close();

        // 提交所有操作
        $conn->commit();

    } catch (Exception $e) {
        // 出错时回滚事务
        $conn->rollback();
        die("操作失败: " . $e->getMessage());
    }

    // 操作成功后跳转
    function redirect($url) {
        echo '<script>window.location.replace("' . $url . '");</script>';
    }

    redirect('/admin/?page=articles');

    exit;
}

// 处理文章审核请求
if (isset($_GET['approve_id'])) {
    $approveId = intval($_GET['approve_id']);
    $stmt = $conn->prepare("UPDATE articles SET status = 1 WHERE id = ?");
    $stmt->bind_param("i", $approveId);
    $stmt->execute();
    $stmt->close();
    function redirect($url) {
        echo '<script>window.location.replace("' . $url . '");</script>';
    }

    redirect('/admin/?page=articles');
    exit;
}

// 处理文章拒绝请求
if (isset($_GET['reject_id'])) {
    $rejectId = intval($_GET['reject_id']);
    $stmt = $conn->prepare("UPDATE articles SET status = 2 WHERE id = ?");
    $stmt->bind_param("i", $rejectId);
    $stmt->execute();
    $stmt->close();
    function redirect($url) {
        echo '<script>window.location.replace("' . $url . '");</script>';
    }

    redirect('/admin/?page=articles');
    exit;
}


// 获取文章列表
$sql = "SELECT * FROM articles ORDER BY created_at DESC";
$result = $conn->query($sql);
$articles = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文章管理</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="h5 mb-0">
                <i class="bi bi-file-earmark-text me-2"></i>
                文章管理
            </h5>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                <?php foreach ($articles as $article) { ?>
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div>
                        <h3 class="h6 mb-1"><?= htmlspecialchars($article['title']) ?></h3>
                        <small class="text-muted"><?= $article['created_at'] ?></small>
                        <?php
                            $status_text = '';
                            $status_class = '';
                            switch ($article['status']) {
                                case 0:
                                    $status_text = '待审核';
                                    $status_class = 'secondary';
                                    break;
                                case 1:
                                    $status_text = '已通过';
                                    $status_class = 'success';
                                    break;
                                case 2:
                                    $status_text = '已拒绝';
                                    $status_class = 'danger';
                                    break;
                                default:
                                    $status_text = '未知状态';
                                    $status_class = 'warning';
                                    break;
                            }
                        ?>
                        <span class="badge bg-<?= $status_class ?> me-2">状态: <?= $status_text ?></span>
                    </div>
                    <div>
                        <?php if ($article['status'] == 0): ?>
                            <a href="?page=articles&approve_id=<?= $article['id'] ?>"
                               class="btn btn-sm btn-success">
                                <i class="bi bi-check-circle me-1"></i>
                                通过
                            </a>
                            <a href="?page=articles&reject_id=<?= $article['id'] ?>"
                               class="btn btn-sm btn-warning">
                                <i class="bi bi-x-circle me-1"></i>
                                拒绝
                            </a>
                        <?php endif; ?>
                        <a href="?page=articles&delete_id=<?= $article['id'] ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('确定删除这篇文章吗？')">
                           <i class="bi bi-trash me-1"></i>
                           删除
                        </a>
                        <a href="?page=articles&preview_id=<?= $article['id'] ?>"
                           class="btn btn-sm btn-info"
                           onclick="return confirm('确定预览这篇文章吗？')">
                           <i class="bi bi-eye me-1"></i>
                           预览
                        </a>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
    <style>
    body {
            font-family: sans-serif;
        }
    </style>
</body>
</html>
