<?php
// 连接数据库
include '../db.php';

// 查询商品列表
$stmt = $conn->query("SELECT id, title, price, stock, image FROM products WHERE status = 1");

// 获取所有结果
$products = $stmt->fetch_all(MYSQLI_ASSOC);

// 返回 JSON 数据
echo json_encode(['status' => 'success', 'products' => $products]);
?>
