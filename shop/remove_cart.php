<?php
session_start();

// 引入数据库连接文件
require_once __DIR__ . '/../db.php';

// 获取用户 ID 和商品 ID
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'];
$product_id = $data['product_id'];

// 查询当前购物车中该商品的数量
$query = "SELECT quantity FROM carts WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($query);

// 检查 prepare 是否成功
if (!$stmt) {
    // 如果 prepare 失败，输出错误信息
    die("Prepare failed: " . $conn->error);
}

// 绑定参数
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();

// 获取查询结果
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row) {
    $quantity = $row['quantity'];

    // 如果商品数量为 1，则直接删除该记录
    if ($quantity == 1) {
        $query = "DELETE FROM carts WHERE user_id = ? AND product_id = ?";
    } else {
        // 如果商品数量大于 1，则减少数量
        $query = "UPDATE carts SET quantity = quantity - 1 WHERE user_id = ? AND product_id = ?";
    }

    // 执行更新或删除操作
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        // 如果 prepare 失败，输出错误信息
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
}

// 返回成功信息
echo json_encode([
    'status' => 'success'
]);

// 关闭语句和连接
$stmt->close();
$conn->close();
?>
