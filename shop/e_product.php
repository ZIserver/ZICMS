<?php
session_start();

// 包含数据库连接信息
require_once '../db.php';

// 强制登录验证
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // 替换为你的登录页面
    exit();
}

// 获取商品 ID
$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 查询商品信息
try {
    $sql = "SELECT id, title, price, description FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        echo "<div class='alert alert-danger'>数据库查询错误，请联系管理员。</div>";
        exit;
    }

    $stmt->bind_param("i", $productId);

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        echo "<div class='alert alert-danger'>数据库执行错误，请联系管理员。</div>";
        exit;
    }

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo "<div class='alert alert-danger'>程序发生异常，请联系管理员。</div>";
    exit;
}

if (!$product) {
    // 商品不存在
    echo "<div class='alert alert-warning'>商品不存在。</div>";
    exit();
}

// 获取用户信息
try {
    $userId = $_SESSION['user_id'];
    $sql = "SELECT username, password, apikey FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        echo "<div class='alert alert-danger'>数据库查询错误，请联系管理员。</div>";
        exit;
    }

    $stmt->bind_param("i", $userId);

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        echo "<div class='alert alert-danger'>数据库执行错误，请联系管理员。</div>";
        exit;
    }

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    echo "<div class='alert alert-danger'>程序发生异常，请联系管理员。</div>";
    exit;
}

if (!$user) {
    echo "<div class='alert alert-danger'>用户信息获取失败，请重新登录。</div>";
    exit;
}

// 处理购买授权请求
$message = ""; // 初始化消息变量
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qq = isset($_POST['qq']) ? $_POST['qq'] : '';
    $url = isset($_POST['url']) ? $_POST['url'] : '';
    $endtime = isset($_POST['endtime']) ? intval($_POST['endtime']) : 1; // 默认时间套餐 ID 为 1

    // 从 config.php 获取配置信息
    require_once 'config.php';

    $api_url = $apiurl . "api.php?user=" . urlencode($user['username']) . "&pass=" . urlencode($user['password']) . "&apikey=" . urlencode($user['apikey']) . "&app_uid=" . urlencode($app_uid) . "&endtime=" . urlencode($endtime) . "&qq=" . urlencode($qq) . "&url=" . urlencode($url);

    // 调用购买授权 API
    $response = @file_get_contents($api_url); // 使用 @ 抑制错误

    if ($response === false) {
        $message = "<div class='alert alert-danger'>连接授权服务器失败，请稍后重试。</div>";
    } else {
        $result = json_decode($response, true);

        if ($result && $result['code'] == 0) {
            $message = "<div class='alert alert-success'>授权成功！ 授权应用: " . htmlspecialchars($result['授权应用']) . ", QQ: " . htmlspecialchars($result['qq']) . ", 域名: " . htmlspecialchars($result['url']) . ", 授权时长: " . htmlspecialchars($result['授权时长']) . "</div>";
        } else {
            $message = "<div class='alert alert-danger'>授权失败！ 失败原因: " . htmlspecialchars($result['msg']) . "</div>";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1><?= htmlspecialchars($product['title']) ?></h1>
        <p>价格：<?= htmlspecialchars($product['price']) ?></p>
        <p>描述：<?= htmlspecialchars($product['description']) ?></p>

        <?= $message ?>

        <form method="post">
            <input type="hidden" name="product_id" value="<?= $productId ?>">
            <div class="mb-3">
                <label for="qq" class="form-label">授权 QQ</label>
                <input type="text" class="form-control" id="qq" name="qq" required>
            </div>
            <div class="mb-3">
                <label for="url" class="form-label">授权域名</label>
                <input type="text" class="form-control" id="url" name="url" required>
            </div>
            <button type="submit" class="btn btn-primary">购买授权</button>
        </form>

        <a href="index.php" class="btn btn-secondary mt-3">返回商品列表</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
