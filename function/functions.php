<?php
// 自定义功能

// 获取文章的示例函数
function get_posts($limit = 5) {
    // 这里模拟从数据库获取文章
    $posts = [
        ['id' => 1, 'title' => '第一篇文章'],
        ['id' => 2, 'title' => '第二篇文章'],
        ['id' => 3, 'title' => '第三篇文章'],
        ['id' => 4, 'title' => '第四篇文章'],
        ['id' => 5, 'title' => '第五篇文章'],
    ];
    return array_slice($posts, 0, $limit);
}
?>
<?php
// 启动会话
//session_start();

// 检查用户是否已登录
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// 获取当前登录的用户名
function get_current_username() {
    return isset($_SESSION['username']) ? $_SESSION['username'] : '游客';
}
?>

<?php
// 检查用户是否为管理员
function isAdmin($username) {
    return $username === 'admin';
}

// 检查用户是否已登录
function isLoggedIn() {
    return isset($_SESSION['username']);
}
// 添加用户信息获取函数
function getUserInfo($userId) {
    global $conn;

    try {
        $sql = "SELECT 
            u.id,
            u.username,
            u.avatar,
            COALESCE(u.title, '普通用户') AS title,
            u.bio,
            COUNT(a.id) AS post_count,
            (SELECT COUNT(*) FROM user_follow WHERE follower_id = u.id) AS following,
            (SELECT COUNT(*) FROM user_follow WHERE following_id = u.id) AS fans,
            COALESCE(SUM(a.likes), 0) AS likes,
            (SELECT COUNT(*) FROM drafts WHERE author_id = u.id) AS draft_count
        FROM users u
        LEFT JOIN articles a ON u.id = a.author_id
        WHERE u.id = ?";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);
        
        $stmt->bind_param("i", $userId);
        if (!$stmt->execute()) throw new Exception("Execute failed: " . $stmt->error);
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
        
    } catch (Exception $e) {
        error_log("[".date('Y-m-d H:i:s')."] UserInfo Error: ".$e->getMessage());
        return [
            'username' => '未知用户',
            'avatar' => 'images/default-avatar.jpg',
            'title' => '用户',
            'bio' => '资料加载失败',
            'post_count' => 0,
            'following' => 0,
            'fans' => 0,
            'likes' => 0,
            'draft_count' => 0
        ];
    }
}



// 原有其他函数保持不变...
function safe_output($content) {
    static $purifier;
    if (!$purifier) {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,a[href|title],strong,em,img[src|alt]');
        $config->set('URI.AllowedSchemes', ['http', 'https']);
        $purifier = new HTMLPurifier($config);
    }
    return $purifier->purify($content);
}
// includes/functions.php
function handleAddToCart($productId, $quantity) {
    global $conn;
    
    $product = getProduct($productId);
    if (!$product) return setFlash('error', '商品不存在');
    
    if ($product['stock'] < $quantity) {
        return setFlash('error', "库存不足，剩余{$product['stock']}件");
    }
    
    $_SESSION['cart'][$productId] = [
        'title' => $product['title'],
        'price' => $product['price'],
        'quantity' => $quantity,
        'image' => $product['image']
    ];
    
    setFlash('success', '商品已加入购物车');
}

function getProducts($page, $perPage) {
    global $conn;
    $offset = ($page - 1) * $perPage;
    return $conn->query("SELECT * FROM products ORDER BY id DESC LIMIT $offset, $perPage");
}
function pagination($currentPage, $totalItems, $perPage) {
    $totalPages = ceil($totalItems / $perPage);
    ob_start(); ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php return ob_get_clean();
}


?>
