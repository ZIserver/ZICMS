<?php
require_once 'admin_header.php';

// 获取用户列表
$sql = "SELECT * FROM users ORDER BY id DESC";
$result = $conn->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="admin-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="h5 mb-0">
            <i class="bi bi-people me-2"></i>
            用户列表
        </h5>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-plus-lg me-2"></i>
            添加用户
        </button>
    </div>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>角色</th>
                            <th>注册时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user) { ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-person-circle"></i>
                                    <?= htmlspecialchars($user['username']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?= $user['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                    <?= $user['role'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-muted">
                                    <?= date('Y-m-d H:i', strtotime($user['created_at'])) ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-danger btn-sm" 
                                        onclick="confirmDelete(<?= $user['id'] ?>)">
                                    <i class="bi bi-trash me-1"></i>
                                    删除
                                </button>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
function confirmDelete(userId) {
    if (confirm('确定要删除此用户吗？')) {
        fetch('delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'user_id=' + userId
        }).then(response => {
            if(response.ok) location.reload();
            else alert('操作失败');
        });
    }
}
</script>


