<?php
require_once("config.php");
session_start(); // 启动 session

// 定义订单信息类
class OrderInfo {
    public $orderId;
    public $outTradeNo;
    public $userId;
    public $tradeNo; // 添加 tradeNo 属性

    public function __construct($orderId, $outTradeNo, $userId, $tradeNo = null) {
        $this->orderId = $orderId;
        $this->outTradeNo = $outTradeNo;
        $this->userId = $userId;
        $this->tradeNo = $tradeNo;
    }
}

// 获取请求参数
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'create_order':
        // 创建订单并返回支付链接
        createOrderAndReturnPayUrl();
        break;
    case 'notify':
        // 异步回调处理
        handleNotify();
        break;
    case 'return':
        // 同步回调处理
        handleReturn();
        break;
    case 'query':
        // 订单查询
        queryOrder();
        break;
    case 'refund':
        // 订单退款
        refundOrder();
        break;
    default:
        // 无效操作
        echo json_encode(['code' => -1, 'msg' => 'Invalid action']);
        break;
}

/**
 * 创建订单并返回支付链接
 */
function createOrderAndReturnPayUrl() {
    global $apiurl, $pid, $key, $conn; // 引入数据库连接

    $host = $_SERVER['HTTP_HOST'];
    // 获取请求数据
    $requestBody = file_get_contents('php://input');
    $requestData = json_decode($requestBody, true);

    // 从请求数据中获取购物车数据和总价
    $cartData = isset($requestData['cartData']) ? $requestData['cartData'] : [];
    $totalPrice = isset($requestData['totalPrice']) ? $requestData['totalPrice'] : 0.01;
    $paymentType = isset($requestData['paymentType']) ? $requestData['paymentType'] : 'alipay'; // 获取支付类型

    // 支付类型
    $type = $paymentType == 'wechat' ? 'wxpay' : 'alipay'; // 根据 paymentType 确定 type
    // 异步回调地址
    $notify_url = 'http://'.$host.'/shop/pay/api.php?action=notify'; // 替换成你的异步回调地址
    // 同步回调地址
    $return_url = 'http://'.$host.'/shop/pay/api.php?action=return'; // 替换成你的同步回调地址
    // 商户端订单号
    $out_trade_no = date("YmdHis") . rand(1000, 9999); // 生成唯一订单号
    // 商品名称
    $name = '商品'; // 默认商品
    // 订单金额
    $money = $totalPrice; // 使用从请求中获取的总价
    // 签名方式
    $sign_type = "MD5"; // 目前仅支持MD5
    // 附加信息
    $param = '';

    // **1. 从 session 中获取用户ID**
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else {
        // 如果 session 中没有用户ID，说明用户未登录
        echo json_encode(['code' => -1, 'msg' => '用户未登录']);
        exit;
    }

    $total_amount = $totalPrice;
    $status = 0; // 初始状态为 0

    // **2. 插入订单数据到 orders 表**
    $sql = "INSERT INTO orders (user_id, total_amount, status) VALUES ($user_id, $total_amount, $status)";

    if ($conn->query($sql) === TRUE) {
        $order_id = $conn->insert_id; // 获取新插入的订单ID
        error_log("订单创建成功，订单ID: " . $order_id);

        // **3. 创建 OrderInfo 对象并存储到 session 中**
        $orderInfo = new OrderInfo($order_id, $out_trade_no, $user_id);
        $_SESSION['order_info'] = $orderInfo;

        // **4. 写入订单详情**
        error_log("cartData: " . print_r($cartData, true)); // 打印 cartData

        foreach ($cartData as $item) {
            $product_id = $item['productId'];
            $quantity = $item['quantity'];
            $price = $item['price'];

            $sql = "INSERT INTO order_details (order_id, product_id, quantity, price) VALUES ($order_id, $product_id, $quantity, $price)";
            error_log("SQL: " . $sql); // 打印 SQL 语句

            if ($conn->query($sql) === TRUE) {
                error_log("订单详情写入成功，商品ID: " . $product_id);
            } else {
                error_log("订单详情写入失败，商品ID: " . $product_id . ", 错误: " . $conn->error);
            }
        }

    } else {
        error_log("订单创建失败: " . $conn->error);
        echo json_encode(['code' => -1, 'msg' => '订单创建失败']);
        exit;
    }

    $arr = array(
        "pid" => $pid,
        "type" => $type,
        "notify_url" => $notify_url,
        "return_url" => $return_url,
        "out_trade_no" => $out_trade_no,
        "name" => $name,
        "money" => $money,
        "param" => $param,
        "sign_type" => $sign_type
    );

    $sign = get_sign($arr, $key);

    // 生成支付链接
    $pay_url = $apiurl . "submit.php?pid=$pid&type=$type&notify_url=$notify_url&return_url=$return_url&out_trade_no=$out_trade_no&name=$name&money=$money&param=$param&sign_type=$sign_type&sign=$sign";

    // 返回 JSON 数据
    echo json_encode(['code' => 0, 'msg' => '订单创建成功', 'pay_url' => $pay_url]);
    exit;
}

/**
 * 异步回调处理
 */
function handleNotify() {
    global $key, $conn;

    // 获取回调参数
    $money = isset($_GET["money"]) ? $_GET["money"] : '';
    $name = isset($_GET["name"]) ? $_GET["name"] : '';
    $pid = isset($_GET["pid"]) ? $_GET["pid"] : '';
    $out_trade_no = isset($_GET["out_trade_no"]) ? $_GET["out_trade_no"] : '';
    $trade_no = isset($_GET["trade_no"]) ? $_GET["trade_no"] : '';
    $trade_status = isset($_GET["trade_status"]) ? $_GET["trade_status"] : '';
    $type = isset($_GET["type"]) ? $_GET["type"] : '';
    $param = isset($_GET["param"]) ? $_GET["param"] : '';
    $trade_status = isset($_GET["trade_status"]) ? $_GET["trade_status"] : '';
    $sign = isset($_GET["sign"]) ? $_GET["sign"] : '';
    $sign_type = isset($_GET["sign_type"]) ? $_GET["sign_type"] : '';

    $arr = array(
        "pid" => $pid,
        "type" => $type,
        "out_trade_no" => $out_trade_no,
        "trade_no" => $trade_no,
        "name" => $name,
        "money" => $money,
        "param" => $param,
        "trade_status" => $trade_status,
        "sign_type" => $sign_type
    );

    // 验证签名
    $calculatedSign = get_sign($arr, $key); // 计算签名

    error_log("Notify URL parameters: " . print_r($_GET, true)); // 记录到错误日志
    error_log("Calculated sign: " . $calculatedSign); // 记录计算出的签名
    error_log("Received sign: " . $sign); // 记录接收到的签名

    if ($sign == $calculatedSign) {
        echo "success"; // 返回success说明通知成功，不要删除本行

        // 在这里处理您的网站逻辑
        // 例如：
        // 1. 验证订单金额是否正确
        // 2. 更新订单状态为已支付
        // 3. 发送商品
        // 4. 记录日志
        // 示例：更新订单状态
        //updateOrderStatus($out_trade_no, '已支付'); // 异步回调不更新状态，在同步回调中更新

    } else {
        echo "error";
    }
}

/**
 * 同步回调处理
 */
/**
 * 同步回调处理
 */
function handleReturn() {
    global $key, $conn;

    // 获取回调参数
    $money = isset($_GET["money"]) ? $_GET["money"] : '';
    $name = isset($_GET["name"]) ? $_GET["name"] : '';
    $pid = isset($_GET["pid"]) ? $_GET["pid"] : '';
    $out_trade_no = isset($_GET["out_trade_no"]) ? $_GET["out_trade_no"] : '';
    $trade_no = isset($_GET["trade_no"]) ? $_GET["trade_no"] : '';
    $trade_status = isset($_GET["trade_status"]) ? $_GET["trade_status"] : '';
    $type = isset($_GET["type"]) ? $_GET["type"] : '';
    $param = isset($_GET["param"]) ? $_GET["param"] : '';
    $trade_status = isset($_GET["trade_status"]) ? $_GET["trade_status"] : '';
    $sign = isset($_GET["sign"]) ? $_GET["sign"] : '';
    $sign_type = isset($_GET["sign_type"]) ? $_GET["sign_type"] : '';

    $arr = array(
        "pid" => $pid,
        "type" => $type,
        "out_trade_no" => $out_trade_no,
        "trade_no" => $trade_no,
        "name" => $name,
        "money" => $money,
        "param" => $param,
        "trade_status" => $trade_status,
        "sign_type" => $sign_type
    );

    // 验证签名
    $calculatedSign = get_sign($arr, $key); // 计算签名

    error_log("Return URL parameters: " . print_r($_GET, true)); // 记录到错误日志
    error_log("Calculated sign: " . $calculatedSign); // 记录计算出的签名
    error_log("Received sign: " . $sign); // 记录接收到的签名

    if ($sign == $calculatedSign) {
        // **1. 从 session 中获取 OrderInfo 对象**
        if (isset($_SESSION['order_info'])) {
            $orderInfo = $_SESSION['order_info'];
            unset($_SESSION['order_info']); // 获取后立即删除，防止重复更新
            $order_id = $orderInfo->orderId;
            $out_trade_no = $orderInfo->outTradeNo;
            $user_id = $orderInfo->userId;
            $trade_no = $_GET["trade_no"]; // 从回调参数中获取 trade_no
        } else {
            error_log("无法从 session 中获取 OrderInfo 对象");
            echo "<!DOCTYPE html>
            <html>
            <head>
                <meta charset=\"utf-8\">
                <title>同步回调</title>
                <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
                <link rel=\"stylesheet\" href=\"https://cdn.staticfile.org/twitter-bootstrap/3.3.7/css/bootstrap.min.css\">
                <script src=\"https://cdn.staticfile.org/jquery/2.1.1/jquery.min.js\"></script>
                <script src=\"https://cdn.staticfile.org/twitter-bootstrap/3.3.7/js/bootstrap.min.js\"></script>
            </head>
            <body>

            <div class=\"container\">
                <h1>验证成功！</h1>
                <p>订单号：{$out_trade_no}</p>
                <p>交易号：{$trade_no}</p>
                <p>金额：{$money}</p>
                <p>请返回您的网站查看订单状态。</p>
                <p><strong>警告：无法更新订单状态，请联系管理员。</strong></p>
            </div>

            </body>
            </html>";
            exit;
        }

        // **2. 更新订单状态为 1，并写入 trade_no**
        $sql = "UPDATE orders SET status = 1, trade_no = '$trade_no' WHERE id = $order_id";
        if ($conn->query($sql) === TRUE) {
            error_log("订单 $order_id 状态更新为 1，trade_no 写入成功");
        } else {
            error_log("订单 $order_id 状态更新失败: " . $conn->error);
        }

        // **3. 清空购物车**
        $sql = "DELETE FROM carts WHERE user_id = $user_id";
        if ($conn->query($sql) === TRUE) {
            error_log("用户 $user_id 的购物车已清空");
        } else {
            error_log("清空用户 $user_id 的购物车失败: " . $conn->error);
        }

        echo "<!DOCTYPE html>
        <html>
        <head>
            <meta charset=\"utf-8\">
            <title>同步回调</title>
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
            <link rel=\"stylesheet\" href=\"https://cdn.staticfile.org/twitter-bootstrap/3.3.7/css/bootstrap.min.css\">
            <script src=\"https://cdn.staticfile.org/jquery/2.1.1/jquery.min.js\"></script>
            <script src=\"https://cdn.staticfile.org/twitter-bootstrap/3.3.7/js/bootstrap.min.js\"></script>
        </head>
        <body>

        <div class=\"container\">
            <h1>验证成功！</h1>
            <p>订单号：{$out_trade_no}</p>
            <p>交易号：{$trade_no}</p>
            <p>金额：{$money}</p>
            <p>请返回您的网站查看订单状态。</p>
        </div>

        </body>
        </html>";
        //header("Location: /shop/orders.php");

    } else {
        echo "<!DOCTYPE html>
        <html>
        <head>
            <meta charset=\"utf-8\">
            <title>同步回调</title>
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
            <link rel=\"stylesheet\" href=\"https://cdn.staticfile.org/twitter-bootstrap/3.3.7/css/bootstrap.min.css\">
            <script src=\"https://cdn.staticfile.org/jquery/2.1.1/jquery.min.js\"></script>
            <script src=\"https://cdn.staticfile.org/twitter-bootstrap/3.3.7/js/bootstrap.min.js\"></script>
        </head>
        <body>

        <div class=\"container\">
            <h1>验证失败！</h1>
            <p>签名验证失败，请联系管理员。</p>
        </div>

        </body>
        </html>";
    }
}


/**
 * 订单查询
 */
function queryOrder() {
    global $apiurl, $pid, $key;

    // 商户端订单号
    $out_trade_no = isset($_GET['out_trade_no']) ? $_GET['out_trade_no'] : '';

    if (empty($out_trade_no)) {
        echo json_encode(['code' => -1, 'msg' => 'out_trade_no不能为空']);
        return;
    }

    $info = GetBody($apiurl . "api.php?act=order&pid=$pid&key=$key&out_trade_no=$out_trade_no", "", "GET");
    die($info);
}

/**
 * 订单退款
 */
function refundOrder() {
    global $apiurl, $pid, $key;

    // 商户端订单号
    $out_trade_no = isset($_POST['out_trade_no']) ? $_POST['out_trade_no'] : '';
    // 退款金额
    $money = isset($_POST['money']) ? $_POST['money'] : '';

    if (empty($out_trade_no) || empty($money)) {
        echo json_encode(['code' => -1, 'msg' => 'out_trade_no和money不能为空']);
        return;
    }

    $info = GetBody($apiurl . "api.php?act=refund", "pid=$pid&key=$key&out_trade_no=$out_trade_no&money=$money", "POST");
    die($info);
}


?>
