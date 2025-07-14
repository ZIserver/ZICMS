<?php
// 连接数据库
include '../db.php';

// 检查 $conn 是否成功初始化
if (!isset($conn) || !$conn instanceof mysqli) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// 获取请求体数据
$requestBody = file_get_contents('php://input');
$data = json_decode($requestBody, true);

// 获取请求参数
$user_id = isset($data['user_id']) ? intval($data['user_id']) : 0; // 用户 ID
$product_id = isset($data['product_id']) ? intval($data['product_id']) : 0; // 商品 ID
$quantity = isset($data['quantity']) ? intval($data['quantity']) : 1; // 购买数量

// 检查用户 ID 和商品 ID 是否有效
if ($user_id <= 0 || $product_id <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid user_id or product_id']);
    exit;
}

// 开启事务
$conn->begin_transaction();

try {
    // 查询商品库存
    $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product || $product['stock'] < $quantity) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Insufficient stock']);
        $conn->rollback();
        exit;
    }

    // 更新库存
    $new_stock = $product['stock'] - $quantity;
    $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_stock, $product_id);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update stock: ' . $conn->error]);
        $conn->rollback();
        exit;
    }

    // 检查购物车中是否已有该商品
    $stmt = $conn->prepare("SELECT id, quantity FROM carts WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart_item = $result->fetch_assoc();

    if ($cart_item) {
        // 如果已有商品，更新数量
        $new_quantity = $cart_item['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE carts SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update cart quantity: ' . $conn->error]);
            $conn->rollback();
            exit;
        }
    } else {
        // 如果没有该商品，插入新记录
        $stmt = $conn->prepare("INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        if (!$stmt->execute()) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to insert cart item: ' . $conn->error]);
            $conn->rollback();
            exit;
        }
    }

    // 提交事务
    $conn->commit();

    // 返回成功响应
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Item added to cart successfully',
        'data' => [
            'new_stock' => $new_stock,
            'cart_quantity' => $new_quantity ?? $quantity
        ]
    ]);
} catch (Exception $e) {
    // 回滚事务
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}
?>
