<?php
require_once 'function/pay.php';
require_once 'db.php';

// 获取订单信息
$orderId = $_GET['order_id'];
$order = $conn->query("SELECT * FROM orders WHERE id = $orderId")->fetch_assoc();

// 码支付配置
$codepayConfig = [
    'pid' => 'YOUR_PID',
    'key' => 'YOUR_KEY',
    'notify_url' => 'http://yourdomain.com/notify.php',
    'return_url' => 'http://yourdomain.com/profile.php?page=orders'
];

// 构造请求参数
$params = [
    'pid' => $codepayConfig['pid'],
    'type' => 'alipay', // 默认支付方式
    'out_trade_no' => $order['order_no'],
    'notify_url' => $codepayConfig['notify_url'],
    'return_url' => $codepayConfig['return_url'],
    'name' => '商品订单-' . $order['order_no'],
    'money' => $order['total_amount'],
    'sign_type' => 'MD5'
];

// 生成签名
$params['sign'] = generate_code_pay_sign($params, $codepayConfig['key']);

// 提交到码支付
?>
<form id="codepay" action="https://pay.zhsui.top/submit.php" method="post">
    <?php foreach ($params as $name => $value): ?>
    <input type="hidden" name="<?= $name ?>" value="<?= $value ?>">
    <?php endforeach; ?>
</form>
<script>document.getElementById('codepay').submit();</script>
