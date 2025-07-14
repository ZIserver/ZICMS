<?php
// 数据库连接信息
include '../db.php';

// 检查连接
if ($conn->connect_error) {
    die("连接失败: " . $conn->connect_error);
}

// 设置响应头为 JSON
header('Content-Type: application/json');

// 获取当前用户的 user_id (你需要根据你的用户认证系统来获取)
session_start();

$user_id = $_SESSION['user_id'];

// API: 获取购物车信息
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT c.product_id, c.quantity, p.title AS product_title, p.price AS product_price, p.image AS product_image
            FROM carts c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = " . $user_id;

    $result = $conn->query($sql);

    if ($result === false) {
        http_response_code(500); // 服务器错误
        echo json_encode(['error' => '数据库查询失败: ' . $conn->error]);
        exit;
    }

    $cart_items = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $subtotal = $row["product_price"] * $row["quantity"];
            $row["subtotal"] = $subtotal;
            $cart_items[] = $row;
        }
    }

    echo json_encode($cart_items);
    exit;
}

// API: 结算
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 开启事务
    $conn->begin_transaction();

    try {
        // 1. 获取购物车商品
        $sql = "SELECT c.product_id, c.quantity, p.price, p.stock
                FROM carts c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = " . $user_id . " FOR UPDATE"; // 使用 FOR UPDATE 锁定行

        $result = $conn->query($sql);

        if ($result === false) {
            throw new Exception('获取购物车信息失败: ' . $conn->error);
        }

        $cart_items = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $cart_items[] = $row;
            }
        } else {
            throw new Exception('购物车为空');
        }

        // 2. 检查库存并更新库存
        foreach ($cart_items as $item) {
            if ($item['quantity'] > $item['stock']) {
                throw new Exception('商品 ' . $item['product_id'] . ' 库存不足');
            }

            $new_stock = $item['stock'] - $item['quantity'];
            $update_sql = "UPDATE products SET stock = " . $new_stock . " WHERE id = " . $item['product_id'];
            if ($conn->query($update_sql) === false) {
                throw new Exception('更新库存失败: ' . $conn->error);
            }
        }

        // 3. 创建订单 (这里只是一个示例，你需要根据你的订单表结构进行修改)
        $order_total = 0;
        foreach ($cart_items as $item) {
            $order_total += $item['price'] * $item['quantity'];
        }

        $create_order_sql = "INSERT INTO orders (user_id, total_amount, created_at) VALUES (" . $user_id . ", " . $order_total . ", NOW())";
        if ($conn->query($create_order_sql) === false) {
            throw new Exception('创建订单失败: ' . $conn->error);
        }
        $order_id = $conn->insert_id; // 获取订单ID

        // 4. 创建订单详情 (这里只是一个示例，你需要根据你的订单详情表结构进行修改)
        foreach ($cart_items as $item) {
            $create_order_detail_sql = "INSERT INTO order_details (order_id, product_id, quantity, price) VALUES (" . $order_id . ", " . $item['product_id'] . ", " . $item['quantity'] . ", " . $item['price'] . ")";
            if ($conn->query($create_order_detail_sql) === false) {
                throw new Exception('创建订单详情失败: ' . $conn->error);
            }
        }

        // 5. 清空购物车
        $clear_cart_sql = "DELETE FROM carts WHERE user_id = " . $user_id;
        if ($conn->query($clear_cart_sql) === false) {
            throw new Exception('清空购物车失败: ' . $conn->error);
        }

        // 提交事务
        $conn->commit();

        echo json_encode(['success' => true, 'message' => '结算成功，订单已创建']);

    } catch (Exception $e) {
        // 回滚事务
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

    exit;
}
?>
