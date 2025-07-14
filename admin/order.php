<?php
// 数据库连接信息


// 检查连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 查询订单列表
$sql = "SELECT id, user_id, total_amount, created_at, status, trade_no FROM orders";
$result = $conn->query($sql);

if ($result === false) {
    die("数据库查询失败: " . $conn->error);
}

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// 关闭数据库连接
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>订单管理</title>
    <style>

        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .order-status {
            padding: 5px 10px;
            border-radius: 5px;
            color: white;
        }
        .status-unpaid {
            background-color: #ff9800; /* Orange */
        }
        .status-paid {
            background-color: #4caf50; /* Green */
        }
    </style>
</head>
<body>
    <h1>订单管理</h1>

    <table>
        <thead>
            <tr>
                <th>订单ID</th>
                <th>支付ID</th>
                <th>用户ID</th>
                <th>总金额</th>
                <th>创建时间</th>
                <th>状态</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?php echo $order['id']; ?></td>
                    <td><?php echo $order['trade_no']?></td>
                    <td><?php echo $order['user_id']; ?></td>
                    <td><?php echo $order['total_amount']; ?></td>
                    <td><?php echo $order['created_at']; ?></td>
                    <td>
                        <?php
                            $statusClass = '';
                            $statusText = '';
                            if ($order['status'] == 0) {
                                $statusClass = 'status-unpaid';
                                $statusText = '未支付';
                            } else {
                                $statusClass = 'status-paid';
                                $statusText = '已支付';
                            }
                        ?>
                        <span class="order-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
