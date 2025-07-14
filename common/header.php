<?php
session_start();

require_once __DIR__ . '/../db.php';

// 配置获取函数
function get_setting($key) {
    global $conn;
    if (!is_string($key)) {
        error_log("配置键名类型错误: " . gettype($key));
        return null;
    }
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    if (!$stmt || !$stmt->bind_param("s", $key) || !$stmt->execute()) {
        error_log("数据库操作失败");
        return null;
    }
    $result = $stmt->get_result();
    return $result->num_rows > 0 ? $result->fetch_assoc()['setting_value'] : null;
}

// 获取配置
$currentConfig = [
    'site_name' => get_setting('site_name') ?? '默认站点',
    'site_logo' => get_setting('site_logo') ?? '默认站点',
    'qq' => get_setting('qq') ?? '123456789',
    'is_card' => get_setting('is_card') ?? '1',
    // 其他配置项...
];

// 获取菜单项
$menuItems = $conn->query("SELECT name, url FROM menu WHERE visible = 1 ORDER BY `order` ASC")
    ->fetch_all(MYSQLI_ASSOC);

// 记录访问量
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '未知IP';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '未知User-Agent';
$page_url = $_SERVER['REQUEST_URI'] ?? '/';

$sql = "INSERT INTO visits (timestamp, ip_address, user_agent, page_url) VALUES (NOW(), ?, ?, ?)";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("sss", $ip_address, $user_agent, $page_url);
    $stmt->execute();
    $stmt->close();
} else {
    error_log("记录访问量失败: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/theme.css">
    <script src="../js/theme.js"></script>
    
    <style>
/* ========= 基础重置 ========= */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

/* ========= 导航栏样式 ========= */
.navbar {
  background: #f8f9fa; /* 浅灰色背景 */
  padding: 0 20px;
  height: 60px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: fixed;
  top: 0;
  width: 100%;
  z-index: 1000;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* 轻微阴影 */
}

/* 品牌标识 */
.brand {
  color: #333333; /* 深灰色文字 */
  font-family: "Roboto", sans-serif; /* 更正式的字体 */
  font-size: 24px;
  font-weight: 700;
  text-decoration: none;
  transition: opacity 0.3s;
}
.brand:hover {
  opacity: 0.9;
}

/* PC导航菜单 */
.desktop-menu {
  display: flex;
  gap: 30px; /* 增加间距 */
  list-style: none;
}

/* 通用菜单项样式 */
.menu-item {
  color: #333333; /* 深灰色文字 */
  text-decoration: none;
  font-family: "Roboto", sans-serif; /* 更正式的字体 */
  font-size: 16px;
  padding: 8px 12px;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
}
.menu-item:hover {
  background: #e3f2fd; /* 浅蓝色高亮 */
  transform: translateY(-2px);
}

/* 移动端汉堡按钮 */
.hamburger-btn {
  display: none;
  background: none;
  border: none;
  color: #333333; /* 深灰色文字 */
  font-size: 28px;
  cursor: pointer;
  padding: 8px;
}

/* 移动菜单系统 */
.mobile-menu-wrapper {
  position: fixed;
  top: 0;
  left: -100%;
  width: 280px;
  height: 100vh;
  background: #ffffff; /* 白色背景 */
  z-index: 1002;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 3px 0 15px rgba(0, 0, 0, 0.2);
}

/* 遮罩层 */
.menu-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1001;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
}

/* 移动菜单激活状态 */
.mobile-active .mobile-menu-wrapper {
  left: 0;
}
.mobile-active .menu-overlay {
  opacity: 1;
  visibility: visible;
}

/* 移动菜单内容 */
.mobile-menu {
  padding: 20px;
  height: calc(100% - 60px);
  overflow-y: auto;
}
.mobile-menu .menu-item {
  display: block;
  padding: 14px;
  margin: 8px 0;
  background: #f8f9fa; /* 浅灰色背景 */
  color: #333333 !important; /* 深色文字 */
}

/* 关闭按钮 */
.close-btn {
  position: absolute;
  top: 15px;
  right: 15px;
  background: none;
  border: none;
  font-size: 28px;
  color: #666;
  cursor: pointer;
  padding: 5px;
}

/* 响应式处理 */
@media (max-width: 767px) {
  .desktop-menu {
    display: none;
  }
  .hamburger-btn {
    display: block;
  }
}
@media (min-width: 768px) {
  .mobile-menu-wrapper,
  .menu-overlay {
    display: none !important;
  }
}

/* ========= 主题切换按钮 ========= */
.theme-toggle-wrapper {
  z-index: 1001;
}
.search-container {
    flex: 1;
    max-width: 600px;
    min-width: 150px;
    position: relative;
}
.search-form {
    display: flex;
    width: 100%;
}
.search-input {
    width: 100%;
    padding: 8px 35px 8px 15px;
    border: 1px solid rgba(255,124,62,0.3);
    background: rgba(255,255,255,0.1);
    color: #FF7C3E;
    transition: all 0.3s ease;
}
.search-button {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
}
    </style>
    <script>
        console.log('<?= htmlspecialchars($currentConfig['site_name']); ?>')
    </script>
</head>
<body>
    <nav class="navbar">
        <!-- 品牌标识 -->
        <a href="/" class="brand">
    <img style="width:auto; height:60px; max-height:60px; object-fit:contain" 
         src="../<?= htmlspecialchars($currentConfig['site_logo']) ?>" 
         alt="网站LOGO">
</a>


        <!-- PC端导航 -->
        <ul class="desktop-menu">
            <div class="search-container">
            <form class="search-form" action="/search.php" method="GET">
                <input type="text" class="search-input" name="q" placeholder="搜索文章..." aria-label="搜索文章" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
                <button type="submit" class="search-button">🔍</button>
            </form>
        </div>
            <?php foreach ($menuItems as $item): ?>
                <li><a href="<?= htmlspecialchars($item['url']) ?>" class="menu-item"><?= htmlspecialchars($item['name']) ?></a></li>
            <?php endforeach; ?>
            
            <?php if(!isset($_SESSION['user_id'])): ?>
                <li><a href="../admin/login.php" class="menu-item">登录</a></li>
                <li><a href="../admin/register.php" class="menu-item">注册</a></li>
            <?php else: ?>
                <li><a href="/profile.php" class="menu-item">个人中心</a></li>
                <li><a href="/admin/logout.php" class="menu-item">注销</a></li>
            <?php endif; ?>
            <li><div class="theme-toggle-wrapper">
                <input type="checkbox" id="theme-toggle" class="theme-toggle">
                <label for="theme-toggle" class="theme-label">
                    <span class="moon-icon">☀️️</span>
                    <span class="sun-icon">🌙</span>
                </label>
            </div></li>
        </ul>

        <!-- 移动端触发按钮 -->
        <button class="hamburger-btn" onclick="toggleMobileMenu()">☰</button>

        <!-- 移动端菜单系统 -->
        <div class="menu-overlay" onclick="toggleMobileMenu()"></div>
        <div class="mobile-menu-wrapper">
            <button class="close-btn" onclick="toggleMobileMenu()">&times;</button>
            <div class="mobile-menu">
                <?php foreach ($menuItems as $item): ?>
                    <a href="<?= htmlspecialchars($item['url']) ?>" class="menu-item"><?= htmlspecialchars($item['name']) ?></a>
                <?php endforeach; ?>

                <div class="mobile-auth">
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a href="../admin/login.php" class="menu-item">登录</a>
                        <a href="../admin/register.php" class="menu-item">注册</a>
                    <?php else: ?>
                        <a href="/profile.php" class="menu-item">个人中心</a>
                    <?php endif; ?>
                </div>

                <li><div class="theme-toggle-wrapper">
                <input type="checkbox" id="theme-toggle" class="theme-toggle">
                <label for="theme-toggle" class="theme-label">
                    <span class="moon-icon">☀️️</span>
                    <span class="sun-icon">🌙</span>
                </label>
            </div>
        </li>
            </div>
        </div>
    </nav>

    <script>
    // 移动菜单切换函数
    function toggleMobileMenu() {
        const body = document.body;
        body.classList.toggle('mobile-active');
        
        // 当关闭菜单时移除滚动锁定
        if (!body.classList.contains('mobile-active')) {
            document.documentElement.style.overflow = 'auto';
        } else {
            document.documentElement.style.overflow = 'hidden';
        }
    }

    // 窗口调整大小时重置状态
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            document.body.classList.remove('mobile-active');
            document.documentElement.style.overflow = 'auto';
        }
    });
    </script>
</body>
</html>
