<?php
session_start();

// 引入数据库连接文件
require_once __DIR__ . '/../db.php';

// 获取用户 ID
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'];

// 查询购物车中的商品
$query = "SELECT 
            carts.product_id, 
            carts.quantity, 
            products.title, 
            products.price, 
            products.image 
        FROM 
            carts 
        INNER JOIN 
            products 
        ON 
            carts.product_id = products.id 
        WHERE 
            carts.user_id = ?";

// 准备查询
$stmt = $conn->prepare($query);

// 检查 prepare 是否成功
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// 绑定参数
$stmt->bind_param("i", $user_id);

// 执行查询
$stmt->execute();

// 获取查询结果
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);

// 返回 JSON 数据
echo json_encode([
    'status' => 'success',
    'cart_items' => $cart_items
]);

// 关闭语句和连接
$stmt->close();
$conn->close();
?>
