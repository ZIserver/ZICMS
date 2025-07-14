<?php
// 权限验证


session_start();
require_once '../db.php';
require_once '../function/functions.php';
require_once '../install/config.php';

if (!isLoggedIn() || !isAdmin($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// 处理页面切换
$validPages = [
    'dashboard' => 'dashboard.php',
    'articles' => 'admin.php',
    'users' => 'user_management.php',
    'comments' => 'comments.php',
    'sliders' => 'setting-sliders.php',
    'menu' => 'meun.php',
    'ziding' => 'zidingyidaima.php',
    'settings' => 'setting.php',
    'notice' => 'notice.php',
    'product' => 'product.php',
    'order' => 'order.php',
    'category' => 'category.php',
    'update' => 'updata.php'
];

$currentPage = $_GET['page'] ?? 'dashboard';
$pageFile = $validPages[$currentPage] ?? 'admin.php';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZICMS - 管理后台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: sans-serif;
        }
        :root {
            --primary: #0d6efd;
            --secondary: #6C757D;
            --light: #F8F9FA;
            --border: #DEE2E6;
            --radius: 8px;
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .admin-container {
            display: flex;
            height: 100vh;
            background: white;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .sidebar {
            background: var(--primary);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1rem;
            box-shadow: var(--shadow) 0 0 20px 0 rgba(0,0,0,0.1);
        }

        .sidebar .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .sidebar .nav-menu {
            flex-grow: 1;
        }

        .sidebar .nav-menu .nav-item {
            margin: 0.5rem 0;
        }

        .sidebar .nav-menu .nav-item a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.2s;
            font-size: 1.1rem; /* 增加字体大小 */
        }

        .sidebar .nav-menu .nav-item a:hover,
        .sidebar .nav-menu .nav-item a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        .content {
            flex-grow: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        /* 手机端样式 */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
                height: auto; /* 允许内容撑开高度 */
            }

            .sidebar {
                display: none; /* 隐藏侧边栏 */
            }

            .content {
                padding: 0.5rem;
                height: auto; /* 高度自适应 */
            }

            /* 新增的手机端顶部导航栏样式 */
            .mobile-top-nav {
                background: var(--primary);
                color: white;
                padding: 0.5rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .mobile-top-nav .nav-title {
                font-size: 1rem;
                text-align: center;
                flex-grow: 1;
            }

            .mobile-top-nav .nav-arrow {
                color: white;
                cursor: pointer;
                padding: 0.2rem 0.5rem;
            }
        }

        /* 默认情况下隐藏手机端导航栏 */
        .mobile-top-nav {
            display: none;
        }

        /* 手机端显示手机端导航栏 */
        @media (max-width: 768px) {
            .mobile-top-nav {
                display: flex;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- 手机端顶部导航栏 -->
        <div class="mobile-top-nav">
            <div class="nav-arrow" onclick="goToPreviousPage()"><i class="bi bi-arrow-left"></i></div>
            <div class="nav-title">
                <?php
                    $pageTitles = [
                        'dashboard' => '仪表盘',
                        'articles' => '文章管理',
                        'users' => '用户管理',
                        'comments' => '评论管理',
                        'sliders' => '幻灯片管理',
                        'menu' => '菜单管理',
                        'ziding' => '图片管理',
                        'settings' => '系统设置',
                        'notice' => '系统公告',
                        'product' => '产品管理',
                        'order' => '订单管理',
                        'category' => '分类管理',
                        'update' => '系统更新'
                    ];
                    echo $pageTitles[$currentPage] ?? 'ZICMS';
                ?>
            </div>
            <div class="nav-arrow" onclick="goToNextPage()"><i class="bi bi-arrow-right"></i></div>
        </div>

        <!-- 左侧菜单栏 -->
        <div class="sidebar">
            <div class="logo">
                <i class="bi bi-speedometer2 fs-3"></i>
                <span>ZICMS</span>
            </div>
            <div class="nav-menu">
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" 
                           href="?page=dashboard">

                            仪表盘
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'articles' ? 'active' : '' ?>" 
                           href="?page=articles">

                            文章管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'category' ? 'active' : '' ?>" 
                           href="?page=category">

                            分类管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>" 
                           href="?page=users">

                            用户管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'comments' ? 'active' : '' ?>" 
                           href="?page=comments">

                            评论管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'sliders' ? 'active' : '' ?>" 
                           href="?page=sliders">

                            幻灯片管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'menu' ? 'active' : '' ?>" 
                           href="?page=menu">

                            菜单管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'ziding' ? 'active' : '' ?>" 
                           href="?page=ziding">

                            图片管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'product' ? 'active' : '' ?>" 
                           href="?page=product">

                             产品管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'order' ? 'active' : '' ?>" 
                           href="?page=order">

                             订单管理
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?>" 
                           href="?page=settings">

                            系统设置
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'notice' ? 'active' : '' ?>" 
                           href="?page=notice">

                            系统公告
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === 'update' ? 'active' : '' ?>" 
                           href="?page=update">

                            系统更新
                        </a>
                    </li>
                </ul>
            </div>
            <div class="d-flex gap-2">
                <a href="logout.php" class="btn btn-danger btn-sm">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
        
        <!-- 内容区 -->
        <div class="content">
            <?php include $pageFile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const validPages = [
            'dashboard',
            'articles',
            'users',
            'comments',
            'sliders',
            'menu',
            'ziding',
            'settings',
            'notice',
            'product',
            'order',
            'category'
        ];

        function getNextPage() {
            const currentPage = new URLSearchParams(window.location.search).get('page') || 'dashboard';
            const currentIndex = validPages.indexOf(currentPage);
            if (currentIndex < validPages.length - 1) {
                return validPages[currentIndex + 1];
            } else {
                return validPages[0]; // Loop back to the first page
            }
        }

        function getPreviousPage() {
            const currentPage = new URLSearchParams(window.location.search).get('page') || 'dashboard';
            const currentIndex = validPages.indexOf(currentPage);
            if (currentIndex > 0) {
                return validPages[currentIndex - 1];
            } else {
                return validPages[validPages.length - 1]; // Loop back to the last page
            }
        }

        function goToNextPage() {
            const nextPage = getNextPage();
            const url = new URL(window.location.href);
            url.searchParams.set('page', nextPage);
            window.location.href = url.toString();
        }

        function goToPreviousPage() {
            const previousPage = getPreviousPage();
            const url = new URL(window.location.href);
            url.searchParams.set('page', previousPage);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>

