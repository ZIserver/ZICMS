<?php
if (!file_exists('install/install.lock')) {
    header('Location: install/install.php');
    exit;
}

session_start();
require_once 'db.php';
require_once 'install/config.php';
require_once 'function/functions.php';

// 文章分页逻辑
$perPage = 1000;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

$sql = "SELECT SQL_CALC_FOUND_ROWS 
            articles.*,
            categories_art.name AS category_name
        FROM
            articles
        LEFT JOIN
            categories_art ON articles.category_id = categories_art.id
        ORDER BY
            articles.created_at DESC 
        LIMIT $perPage OFFSET $offset";

$result = $conn->query($sql);
$articles = $result->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT * FROM users  ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);

$totalResult = $conn->query("SELECT FOUND_ROWS()");
$totalArticles = $totalResult->fetch_row()[0];
$totalPages = ceil($totalArticles / $perPage);

// 用户信息获取
$userId = $_SESSION['user_id'] ?? 0;
$user = [
    'username' => '游客',
    'avatar' => 'default-avatar.jpg',
    'bio' => '欢迎来到我们的社区',
    'post_count' => 0,
    'likes' => 0
];

if ($userId) {
    $stmt = $conn->prepare("SELECT username, avatar, post_count, likes, bio FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows) {
        $user = $result->fetch_assoc();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    
    <link rel="stylesheet" href="css/index.css">
    <style>
        :root {
            --primary: #2d8cf0;
            --secondary: #19be6b;
            --text: #2c3e50;
            --text-light: #7f8c8d;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --shadow: 0 6px 20px rgba(0,0,0,0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --text: #f8f9fa;
                --text-light: #adb5bd;
                --bg: #1a1d1f;
                --card-bg: #2d3235;
                --shadow: 0 6px 20px rgba(0,0,0,0.2);
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'HarmonyOS Sans', 'PingFang SC', '微软雅黑', sans-serif;
        }

        body {
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            display: flex;
            gap: 40px;
            /*max-width: 1440px;*/
            margin: 0 auto;
            padding: 30px 40px;
        }

        /* 侧边栏样式 */
        .user-sidebar {
  width: 320px;
  position: sticky;
  top: 100px;  /* 距离页面顶部 100px 时触发其跟随页面移动 */
  background: var(--card-bg);
  padding: 30px;
  box-shadow: var(--shadow);
  transition: var(--transition);
  align-self: flex-start; /* 在 flex 容器中防止高度塌陷 */
  max-height: 100vh; /* 防止溢出可视区域 */
  overflow-y: auto;
}


        .user-avatar2 {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 3px solid var(--card-bg);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 25px 0;
        }

        .stat-item {
            background: rgba(255,255,255,0.05);
            padding: 18px;
            text-align: center;
            backdrop-filter: blur(5px);
            transition: var(--transition);
        }

        /* 主内容区 */
        .main-content {
            flex: 1;
            min-width: 0;
        }

        .article-list {
            display: grid;
            gap: 25px;
        }

        .art-list {
            background: var(--card-bg);
            border: 1px solid #e0e0e0; /* 添加 1px 的灰色边框 */
            padding: 25px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .article-title {
            font-size: 1.4em;
            color: var(--primary);
            margin-bottom: 15px;
        }

        .meta-info {
            display: flex;
            gap: 15px;
            color: var(--text-light);
            font-size: 0.9em;
            margin-bottom: 15px;
        }

        .article-content {
            color: var(--text-light);
            line-height: 1.8;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .full-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-out;
        }

        .show-more {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary) !important;
            padding: 8px 15px;
            background: rgba(45,140,240,0.1);
            margin-top: 15px;
            cursor: pointer;
            transition: var(--transition);
        }

        /* 响应式设计 */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                padding: 15px;
            }

            .user-sidebar {
                width: 100%;
                position: static;
            }

            .article-title {
                font-size: 1.2em;
            }
        }
        .user-sidebar {
    border: 1px solid rgba(0,0,0,0.08);
    background: linear-gradient(145deg, rgba(255,255,255,0.96) 0%, rgba(248,249,250,0.98) 100%);
    backdrop-filter: blur(12px);
    
    box-shadow: 0 12px 40px -12px rgba(0,0,0,0.05);
}
/* 暗黑模式适配 */
@media (prefers-color-scheme: dark) {
    .user-sidebar {
        border-color: rgba(255,255,255,0.08);
        background: linear-gradient(145deg, rgba(45,50,53,0.95) 0%, rgba(40,44,47,0.97) 100%);
    }
}
/* 用户卡片 */
.user-card {
    position: relative;
    padding-bottom: 24px;
    margin-bottom: 24px;
}
/* 底部装饰线 */
.user-card::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80%;
    height: 1px;
    background: linear-gradient(90deg, 
        transparent 0%, 
        rgba(0,0,0,0.08) 20%, 
        rgba(0,0,0,0.08) 80%, 
        transparent 100%
    );
}
/* 统计项边框 */
.stat-item {
    border: 1px solid rgba(0,0,0,0.05);
    background: rgba(255,255,255,0.6);
    box-shadow: 
        inset 0 2px 4px rgba(0,0,0,0.02),
        0 4px 12px -4px rgba(0,0,0,0.05);
}
/* 操作按钮容器 */
.user-actions {
    border-top: 1px solid rgba(0,0,0,0.05);
    padding-top: 24px;
    margin-top: 24px;
}
/* 按钮边框动画 */
.btn {
    position: relative;
    overflow: hidden;
    border: 1px solid transparent;
    transition: all 0.3s ease;
}
.btn::after {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    border: 2px solid rgba(45,140,240,0.2);
    opacity: 0;
    transition: opacity 0.3s;
}
.btn:hover::after {
    opacity: 1;
}
/* 响应式调整 */
@media (max-width: 768px) {
    .user-sidebar {
        
        border-width: 1px;
    }
    
    .user-card::after {
        width: 90%;
    }
} 
.btn {
            display: block;
            padding: 10px 15px;
            text-align: center;
            transition: all 0.2s;
            text-decoration: none;
        }
.btn-edit {
    background: #3498db;
    color:  white;
}
[data-theme="dark"] .btn-edit {
    background: #3498db;
    color:  #FF7C3E;
}
.btn-collect {
    background: #e67e22;
    color: white;
}

.btn-draft {
    background: #95a5a6;
    color: white;
}
.user-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
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
.container {
    display: flex;
    gap: 20px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 30px 40px;
}

.user-sidebar {
    width: 300px;
    background: #fff;
    border: 1px solid #ddd;
    padding: 20px;
    
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.user-avatar2 {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 20px;
    border: 3px solid #fff;
}

.user-card h3 {
    font-size: 18px;
    margin: 0;
}

.user-card p {
    font-size: 14px;
    color: #666;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-top: 20px;
}

.stat-item {
    background: #f9f9f9;
    padding: 15px;
    text-align: center;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
}

.stat-label {
    font-size: 14px;
    color: #666;
}

.user-actions .btn {
    display: block;
    width: 100%;
    background: #007bff;
    color: white;
    padding: 10px 0;
    text-align: center;
    margin-top: 20px;
    border: none;
    text-decoration: none;
    font-weight: bold;
}

.hot-articles {
    width: 300px;
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.hot-articles h2 {
    margin: 0 0 20px;
    font-size: 20px;
    color: #007bff;
}

.hot-articles ul {
    list-style: none;
    padding: 0;
}

.hot-articles ul li {
    margin-bottom: 10px;
}

.hot-articles ul li a {
    color: #333;
    text-decoration: none;
    font-size: 16px;
}
/* 热榜容器 */
.hot-articles-box {
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 15px;
    margin-bottom: 20px;
}

.hot-title {
    font-size: 18px;
    margin: 0 0 15px 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #333;
}

#refreshHot {
    background: none;
    border: none;
    color: #666;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s;
}

#refreshHot:hover {
    color: #1890ff;
    transform: rotate(180deg);
}

/* 列表样式 */
#hotArticlesList {
    list-style: none;
    padding: 0;
    margin: 0;
}

.hot-item {
    display: flex;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s;
}

.hot-item:hover {
    background: #f9f9f9;
}

/* 排名样式 */
.rank-num {
    flex: 0 0 30px;
    text-align: center;
    font-weight: bold;
    font-size: 16px;
}

.top-1 .rank-num { color: #ff4d4f; }
.top-2 .rank-num { color: #fa8c16; }
.top-3 .rank-num { color: #faad14; }

/* 标题样式 */
.title {
    flex: 1;
    color: #333;
    text-decoration: none;
    font-size: 15px;
    display: block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding-right: 10px;
}

.title:hover {
    color: #1890ff;
}

/* 元信息 */
.meta {
    font-size: 12px;
    color: #999;
    margin-top: 3px;
    display: flex;
    gap: 10px;
}

.meta i {
    margin-right: 3px;
}

.meta .author {
    color: #666;
}

/* 热度值 */
.hot-value {
    flex: 0 0 50px;
    text-align: right;
    font-size: 13px;
    color: #ff4d4f;
    font-weight: bold;
}

/* 状态样式 */
.loading-item {
    text-align: center;
    padding: 20px;
    color: #888;
}

.empty-item {
    text-align: center;
    padding: 20px;
    color: #888;
}

.error-item {
    text-align: center;
    padding: 15px;
    color: #ff4d4f;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.retry-btn {
    background: #ff4d4f;
    color: white;
    border: none;
    padding: 3px 10px;
    font-size: 12px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .container {
        flex-direction: column;
    }

    .user-sidebar, .hot-articles {
        width: 100%;
    }

    .user-avatar2 {
        width: 80px;
        height: 80px;
    }

    .article-list li {
        padding: 15px;
    }
}

        /*.slider-top {
            max-width: 1200px;
            margin: 80px auto 30px;
            padding: 0 20px;
        }
        .slide-item {
            scroll-snap-align: start;
            min-width: 100%;
            border-radius: 12px;
            position: relative;
        }
        .slide-cover {
            width: 100%;
            aspect-ratio: 4 / 1;
            object-fit: cover;
            display: block;
        }
        .slide-caption {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(0,0,0,0.45);
            color: #fff;
            padding: 14px 24px;
            font-size: 14px;
            border-bottom-left-radius: 12px;
            border-bottom-right-radius: 12px;
            backdrop-filter: blur(12px);
        }
        @media (max-width: 768px) {
            .slider-top {
                margin-top: 60px;
            }
            
            .slide-caption {
                padding: 10px 16px;
                font-size: 12px;
            }*/
    </style>
    
<?php
$sliderResult = $conn->query("SELECT id, title, description, image, url FROM sliders ORDER BY sort ASC LIMIT 5");

if (mysqli_num_rows($sliderResult) > 0):
?>
<div class="slider-top">
    <div class="carousel-container">
        <div class="carousel-inner">
            <?php
            $index = 0;
            while ($slide = $sliderResult->fetch_assoc()) :
                $active = $index === 0 ? 'active' : '';
            ?>
                <div class="carousel-item <?= $active ?>">
                    <a href="<?= htmlspecialchars($slide['url'] ?: 'javascript:;') ?>" class="slide-link">
                        <img class="slide-cover" src="uploads/sliders/<?= htmlspecialchars($slide['image']) ?>" alt="顶部图片">
                        <div class="slide-caption">
                            <?= htmlspecialchars($slide['title']) ?>
                            <?php if ($slide['description']) : ?>
                                <div><?= htmlspecialchars($slide['description']) ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php $index++; endwhile; ?>
        </div>
        <button class="carousel-control prev" onclick="prevSlide()">❮</button>
        <button class="carousel-control next" onclick="nextSlide()">❯</button>
        <div class="carousel-indicators">
            <?php for($i=0; $i<mysqli_num_rows($sliderResult); $i++): 
                $active = $i === 0 ? 'active' : '';
            ?>
                <span class="indicator <?= $active ?>" onclick="goToSlide(<?= $i ?>)"></span>
            <?php endfor; ?>
        </div>
    </div>
</div>
<?php endif; ?>


<style>
/* ========= 总体样式 ========= */
.slider-top {
    margin-top: 70px;
    position: relative;
    overflow: hidden;
    background-color: #f0f0f0;
    width: 100%; /* 默认100%宽度 */
    padding: 0; /* 移除内边距 */
}

/* 幻灯片容器 */
.carousel-container {
    position: relative;
    width: 100%;
    max-width: 100000px; /* 比原来的1200px更宽，但不会无限拉伸 */
    aspect-ratio: 16 / 9;
    overflow: hidden;
    margin: 0 auto; /* 保持居中 */
    height: 500px; /* 固定高度为500px */
}

/* 幻灯片内容容器 */
.carousel-inner {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* 单个幻灯片 */
.carousel-item {
    position: absolute;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.8s ease-in-out;
    pointer-events: none;
}

.carousel-item.active {
    opacity: 1;
    pointer-events: auto;
}

/* 幻灯片图像 */
.slide-cover {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* 幻灯片标题 */
.slide-caption {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    padding: 20px 40px;
    font-size: 36px;
    text-align: center;
}
/*通用样式设置*/
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f5f5f5;
}

/* 文章列表样式 */
.article-list {
    margin: 20px;
}

.art-list, .art-card {
    background: #ffffff;
    margin: 10px auto;
    padding: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

/* 文章卡片样式 */
.article-cards {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-around;
    margin: 20px;
}

.art-card {
    width: calc(33% - 30px);
    margin: 10px;
}

h3.article-title, h3.card-title {
    color: #333;
    font-size: 1.3em;
}

.article-content, .card-content {
    color: #666;
    font-size: 1em;
    margin: 10px 0;
}

.meta-info, .card-meta {
    font-size: 0.9em;
    color: #999;
}

.shuju {
    font-size: 0.9em;
    color: #777;
}

.show-more, .card-show-more {
    cursor: pointer;
    font-size: 0.9em;
    color: #007BFF;
}

/*媒体查询,对于小屏幕调整排列方式*/

/* 主容器设置 */
.art-list {
    width: 100%;
     /* 可以依据需求调整 */
    margin: 0 auto;
    padding: 20px;
    box-sizing: border-box;
}

/* 单个文章条目设置 */
.art-list article {
    background: #fff;
    margin: 20px 0;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

/* 文章标题样式 */
.article-title {
    font-size: 22px;
    margin-bottom: 10px;
    color: #333;
}

/* 元数据样式 - 发布时间 */
.meta-info time {
    display: block;
    color: #888;
    margin-bottom: 10px;
    font-size: 14px;
}

/* 文章内容部分内容样式 */
.article-content {
    font-size: 16px;
    line-height: 1.5;
    color: #555;
}

/* 统计数据样式 - 点赞与浏览 */
.shuju {
    margin-top: 20px;
    color: #555;
    font-size: 14px;
}

/* 当内容过长，未全部展示时的扩展样式 */
.full-content {
    display: none;
    color: #333;
    margin-top: 10px;
}

.show-more {
    cursor: pointer;
    color: #007BFF;
    font-size: 14px;
    margin-top: 5px;
}

.show-more:hover {
    text-decoration: underline;
    text-decoration-color: #0056b3;
}



</style>

<script>
let currentSlide = 0;
const slides = document.querySelectorAll('.carousel-item');
const indicators = document.querySelectorAll('.indicator');
const totalSlides = slides.length;

function showSlide(index) {
    slides.forEach(slide => slide.classList.remove('active'));
    indicators.forEach(ind => ind.classList.remove('active'));
    
    slides[index].classList.add('active');
    indicators[index].classList.add('active');
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % totalSlides;
    showSlide(currentSlide);
}

function prevSlide() {
    currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
    showSlide(currentSlide);
}

// 自动播放
setInterval(nextSlide, 10000); // 5秒切换

// 添加点击指示器的激活效果
document.querySelectorAll('.indicator').forEach((indicator, index) => {
    indicator.addEventListener('click', () => {
        currentSlide = index;
        showSlide(currentSlide);
    });
});
</script>

</head>
<body>
    <?php include "common/header.php" ?>
    <title><?= htmlspecialchars($currentConfig['site_name']); ?> - 文章社区</title>
    <div class="container">
    <aside class="user-sidebar">
        <div class="user-card">
            <center><img class="user-avatar2" src="<?= htmlspecialchars($user['avatar'] ?? 'default-avatar.jpg') ?>" alt="用户头像" onerror="this.src='default-avatar.jpg'">
            <h3><?= htmlspecialchars($user['username'] ?? '游客') ?></h3>
            <p class="user-bio"><?= nl2br(htmlspecialchars($user['bio'] ?? '欢迎来到我们的社区')) ?></p></center>
        </div>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value"><?= $user['post_count'] ?></div>
                <div class="stat-label">文章发布</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $user['likes'] ?></div>
                <div class="stat-label">累计获赞</div>
            </div>
        </div>
        <div class="user-actions">
            <?php if (!isLoggedIn()): ?>
                <a href="/admin/login.php" class="btn btn-edit">登录</a>
                <a href="/admin/register.php" class="btn btn-edit">注册</a>
            <?php endif; ?>
            <?php if (isLoggedIn()): ?>
            <div class="user-actions">
                
                <a href="/profile.php" class="btn btn-edit">个人主页</a>
                <a href="/newposts.php" class="btn btn-edit">发布文章</a>
                <a href="/shop" class="btn btn-edit">前往商城</a>
                <?php if (isAdmin($_SESSION['username'])): ?>
                    <a href="admin" class="btn btn-edit">后台管理</a>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </aside>
    <main class="main-content">
        <h1>最新文章</h1>
        <div class="search-container">
            <form class="search-form" action="/search.php" method="GET">
                <input type="text" class="search-input" name="q" placeholder="搜索文章..." aria-label="搜索文章" value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
                <button type="submit" class="search-button">🔍</button>
            </form>
        </div>
    
        <div class="article-list">
    <?php foreach ($articles as $article): 
        $content = strip_tags(htmlspecialchars_decode($article['content']));
        $preview = mb_substr($content, 0, 150, 'UTF-8');
        if (mb_strlen($content, 'UTF-8') > 150) {
            $preview = preg_replace('/[,，。!?！？]?$/', '', $preview) . '...';
        }
        if($currentConfig['is_card'] == 0):
    ?>
    <?php if($article['status'] == 1):?>
    <article class="art-list">
        <a href="articles.php?id=<?= $article['id'] ?>">
            <h3 class="article-title"><?= htmlspecialchars($article['title']) ?></h3>
            <div class="meta-info">
                <time><?= date('Y/m/d H:i', strtotime($article['created_at'])) ?></time>
            </div>
            <p class="article-content"><?= $article['content'] ?></p>
            <p class="shuju">点赞量:<?= htmlspecialchars($article['like_count']) ?> 浏览量:<?= htmlspecialchars($article['views']) ?></p>
            <span class="category-name">分类：<?= htmlspecialchars($article['category_name'] ?? '未分类') ?></span>
            <?php if (mb_strlen($content) > 50): ?>
                <div class="full-content"><?= nl2br($content) ?></div>
                <div class="show-more">展开全文 →</div>
            <?php endif; ?>
        </a>
    </article>
    <?php endif ?>
    <?php else: ?>
    <?php if($article['status'] == 1):?>
    <article class="art-card">
        <a href="articles.php?id=<?= $article['id'] ?>">
            <div class="card-image">
                <img src="<?= htmlspecialchars($article['featured_image'] ?? '/images/s.svg') ?>" alt="图片">
            </div>
            <div class="card-meta">
                <h3 class="card-title"><?= htmlspecialchars($article['title']) ?></h3>
                <div></div>
                <time><?= date('Y/m/d H:i', strtotime($article['created_at'])) ?></time>
                <p class="shuju">点赞量:<?= htmlspecialchars($article['like_count']) ?> 浏览量:<?= htmlspecialchars($article['views']) ?></p>
                <span class="category-name">分类：<?= htmlspecialchars($article['category_name'] ?? '未分类') ?></span>
            </div>
        </a>
    </article>
    <?php endif; ?>
    <?php endif; ?>
    <?php endforeach; ?>
</div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>" aria-label="第<?= $i ?>页">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
    <?php if ((int)(get_setting('hot_articles')) === 1) : ?>
    <aside class="hot-articles">
    <div class="hot-articles-box">
    <h3 class="hot-title">
        热门文章
        <button id="refreshHot" title="刷新"><i class="fas fa-sync-alt"></i></button>
    </h3>
    <ul id="hotArticlesList"></ul>
</div>
<?php endif; ?>
<style>
/* 文章列表容器设置 */
.article-list {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: left; /* 对齐方式可调整 */
}

/* 卡片样式 */
.art-card {
    flex: 0 0 calc(32% - 2rem);
    /* 32%的宽度，加上2rem的间距（gap），使每行能放置三个卡片 */
    border: 1px solid #e0e0e0;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    margin: 0.5rem;
}
.art-card a {
    display: block;
    padding: 1rem;
    text-decoration: none;
}

.art-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

/* 响应式设计 */
@media only screen and (max-width: 1200px) {
    .art-card {
        flex: 0 0 48%; /* 在大屏幕设备上，每行显示两个卡片 */
    }
}

@media only screen and (max-width: 768px) {
    .art-card {
        flex: 0 0 100%; /* 在小屏幕设备上，每行显示一个卡片 */
    }
}

/* 图片设置 */
.card-image {
    max-width: 100%;
    margin-bottom: 10px;
}
.card-image img {
    width: 100%;
    height: auto;
    object-fit: cover;
}

/* 元数据和标题等设置 */
.card-title {
    font-size: 1.2em;
    color: #007bff;
    margin: 0 0 0.5rem;
}
.card-meta {
    font-size: 0.9em;
    color: #666;
}

.shuju {
    font-size: 0.9em;
    margin-top: 10px;
    color: #666;
}

time {
    font-size: 0.9em;
    color: #666;
}

</style>
</aside>

<script>
/**
 * 加载热门文章列表
 */
async function loadHotArticles() {
    const container = document.getElementById('hotArticlesList');
    if (!container) {
        console.error('热榜容器不存在');
        return;
    }

    // 显示加载状态
    container.innerHTML = `
        <li class="loading-item">
            <i class="fas fa-spinner fa-spin"></i> 加载热门文章中...
        </li>`;

    try {
        // 强制不缓存请求
        const apiUrl = `/function/hot.php?get_hot_articles=1&t=${Date.now()}`;
        
        const response = await fetch(apiUrl, {
            headers: {
                'Cache-Control': 'no-cache'
            }
        });
        
        if (!response.ok) {
            throw new Error(`网络错误: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('API响应:', data);  // 调试用
        
        // 关键修正点：使用 data.articles 而不是 data.data
        if (data.status !== 'success' || !Array.isArray(data.articles)) {
            throw new Error(data.message || '数据格式错误');
        }

        renderHotArticles(data.articles);
        
    } catch (error) {
        console.error('加载失败:', error);
        container.innerHTML = `
            <li class="error-item">
                <i class="fas fa-exclamation-triangle"></i> ${error.message}
                <button onclick="loadHotArticles()" class="retry-btn">
                    <i class="fas fa-redo"></i> 刷新
                </button>
            </li>`;
    }
}

/**
 * 渲染热门文章列表
 * @param {Array} articles 文章数组
 */
function renderHotArticles(articles) {
    const container = document.getElementById('hotArticlesList');
    
    if (!articles || articles.length === 0) {
        container.innerHTML = `
            <li class="empty-item">
                <i class="far fa-folder-open"></i> 暂无热门文章
            </li>`;
        return;
    }

    container.innerHTML = articles.map((article, index) => {
        // 计算热度值（可根据需求调整算法）
        const hotValue = calculateHotScore(article.views, article.like_count);
        
        // 前三名特殊样式
        const rankClass = index < 3 ? `top-${index + 1}` : '';
        const rankIcon = getRankIcon(index);
        
        return `
        <li class="hot-item ${rankClass}" data-id="${article.id}">
            <span class="rank-num">${rankIcon}</span>
            <a href="/articles.php?id=${article.id}" class="title">
                ${escapeHtml(article.title)}
                <span class="meta">
                    <span><i class="far fa-eye"></i> ${article.views}</span>
                    <span><i class="far fa-heart"></i> ${article.like_count}</span>
                    <span class="author">${article.username || '匿名'}</span>
                </span>
            </a>
            <span class="hot-value">${hotValue}</span>
        </li>`;
    }).join('');
}

// 热度计算算法
function calculateHotScore(views, likes) {
    return Math.round(views * 0.6 + likes * 0.4);
}

// 获取排名图标
function getRankIcon(index) {
    return ['🥇', '🥈', '🥉'][index] || `${index + 1}`;
}

// HTML转义防止XSS
function escapeHtml(unsafe) {
    return unsafe?.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;") || '';
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', () => {
    loadHotArticles();
    
    // 绑定刷新按钮事件
    document.getElementById('refreshHot')?.addEventListener('click', () => {
        loadHotArticles();
        // 添加旋转动画
        const icon = document.querySelector('#refreshHot i');
        icon.classList.add('fa-spin');
        setTimeout(() => icon.classList.remove('fa-spin'), 1000);
    });
});



</script>
    </div>

    

    <script>
        // 展开/收起功能
        document.querySelectorAll('.show-more').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const article = e.target.closest('.art-list');
                const fullContent = article.querySelector('.full-content');
                
                fullContent.style.maxHeight = 
                    fullContent.style.maxHeight ? null : fullContent.scrollHeight + 'px';
                
                btn.textContent = fullContent.style.maxHeight ? '收起全文 ↑' : '阅读全文→';
            });
        });

        // 平滑滚动
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
<?php include 'common/footer.php'; ?>
