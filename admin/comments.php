<?php
require_once 'admin_header.php';

// 初始化变量
$error = '';
$success = '';

// 状态筛选逻辑增强
$validStatuses = ['all', 'pending', 'approved', 'spam'];
$filterStatus = in_array($_GET['status'] ?? '', $validStatuses) ? $_GET['status'] : 'all';

// 获取评论数据（带文章标题）
function fetchComments($status) {
    global $conn;
    
    $sql = "SELECT c.*, u.username, a.title AS article_title 
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            LEFT JOIN articles a ON c.content_id = a.id";

    if ($status !== 'all') {
        $sql .= " WHERE c.status = ?";
    }

    $sql .= " ORDER BY c.created_at DESC LIMIT 50"; // 添加基础分页限制

    $stmt = $conn->prepare($sql);
    
    if ($status !== 'all') {
        $stmt->bind_param("s", $status);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// 删除评论处理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_comment'])) {
        $commentId = intval($_POST['comment_id']);
        
        try {
            $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->bind_param("i", $commentId);
            
            if ($stmt->execute()) {
                $success = "评论 #$commentId 已删除";
            } else {
                throw new Exception("删除失败: " . $stmt->error);
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$comments = fetchComments($filterStatus);
?>

<div class="card">
    <div class="card-body">
        <h2 class="h5 mb-4">评论管理</h2>
        
        <!-- 状态筛选 & 搜索 -->
        <div class="row mb-4">
            <div class="col-md-4">
                <form method="GET">
                    <div class="input-group">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>全部状态</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="col-md-8">
                <form method="GET">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="搜索评论内容或作者...">
                        <button type="submit" class="btn btn-primary">搜索</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 状态提示 -->
        <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- 评论列表 -->
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>内容</th>
                        <th>作者</th>
                        <th>关联文章</th>

                        <th>时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td>#<?= $comment['id'] ?></td>
                        <td>
                            <div class="text-break" style="max-width: 300px;">
                                <?= htmlspecialchars($comment['content']) ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($comment['username']): ?>
                                <span class="badge bg-info"><?= htmlspecialchars($comment['username']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">游客</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($comment['article_title']): ?>
                                <a href="../articles.php?id=<?= $comment['content_id'] ?>" target="_blank" class="text-decoration-none">
                                    <?= htmlspecialchars($comment['article_title']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">文章已删除</span>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <small class="text-muted">
                                <?= date('m/d H:i', strtotime($comment['created_at'])) ?>
                            </small>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                                    <button type="submit" name="delete_comment" 
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('确定删除该评论？')">
                                        删除
                                    </button>
                                </form>
                                <?php if ($comment['status'] !== 'approved'): ?>
                                
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- 分页占位 -->
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled"><a class="page-link" href="#">上一页</a></li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item"><a class="page-link" href="#">下一页</a></li>
            </ul>
        </nav>
    </div>
</div>

<?php include '.././common/footer.php'?>
