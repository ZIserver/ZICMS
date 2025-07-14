<?php
session_start();
require_once '../common/header.php'; // 假设 header.php 包含导航栏等通用元素
require_once '../db.php'; // 确保 db.php 包含数据库连接信息

// 强制登录验证
if (!isset($_SESSION['user_id'])) {
    header("Location: admin/login.php"); // 替换为你的登录页面
    exit();
}

$userId = $_SESSION['user_id'];

// 分页参数
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$perPage = 10; // 每页显示10个订单
$offset = ($page - 1) * $perPage;

// 查询订单数据
$stmt = $conn->prepare("SELECT SQL_CALC_FOUND_ROWS id, total_amount, created_at, status, trade_no
                      FROM orders
                      WHERE user_id = ?
                      ORDER BY created_at DESC
                      LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $userId, $perPage, $offset);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 获取总订单数，用于分页
$totalResult = $conn->query("SELECT FOUND_ROWS() AS total");
$total = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($total / $perPage);

// 订单状态映射
$statusMap = [
    0 => '待支付',
    1 => '已支付',
    2 => '已发货',
    3 => '已完成',
    4 => '已取消',
];

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的订单</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            background-color: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.05);
        }
        h1 {
            color: #343a40;
            margin-bottom: 1.5rem;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        .pagination .page-link {
            color: #007bff;
        }
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff;
        }
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        .order-status {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1><i class="fas fa-list-alt me-2"></i>我的订单</h1>
        <a href="./index.php" class="btn btn-outline-secondary mb-3"><i class="fas fa-arrow-left me-1"></i>返回购物</a>

        <?php if (!empty($orders)): ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>订单ID</th>
                            <th>订单金额</th>
                            <th>创建时间</th>
                            <th>订单状态</th>
                            <th>交易单号</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= htmlspecialchars($order['id']) ?></td>
                                <td><?= htmlspecialchars($order['total_amount']) ?></td>
                                <td><?= htmlspecialchars($order['created_at']) ?></td>
                                <td>
                                    <span class="order-status"><?= htmlspecialchars($statusMap[$order['status']] ?? '未知状态') ?></span>
                                </td>
                                <td><?= htmlspecialchars($order['trade_no']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= ($page == 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?p=<?= ($page - 1) ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                                <a class="page-link" href="?p=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page == $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?p=<?= ($page + 1) ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">您还没有任何订单。</div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
require_once '../common/footer.php'; // 假设 footer.php 包含页脚等通用元素
$conn->close();
?>
