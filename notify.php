<?php
require_once 'function/pay.php';
require_once 'db.php';

// 获取回调参数
$params = $_GET;
$codepayKey = 'YOUR_KEY';

// 验证签名
if (!verify_code_pay_callback($params, $codepayKey)) {
    die('签名验证失败');
}

// 处理订单状态
if ($params['trade_status'] == 'TRADE_SUCCESS') {
    $orderNo = $params['out_trade_no'];
    
    $conn->begin_transaction();
    try {
        // 更新订单状态
        $conn->query("UPDATE orders SET 
            status = 'paid',
            pay_type = '{$params['type']}',
            transaction_id = '{$params['trade_no']}'
            WHERE order_no = '$orderNo'");
        
        // 库存扣减
        $items = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = 
            (SELECT id FROM orders WHERE order_no = '$orderNo')");
        while ($item = $items->fetch_assoc()) {
            $conn->query("UPDATE products SET stock = stock - {$item['quantity']} 
                WHERE id = {$item['product_id']}");
        }
        
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        // 记录错误日志
        error_log("订单处理失败：".$e->getMessage());
    }
}

// 返回成功响应
echo 'success';
?>
