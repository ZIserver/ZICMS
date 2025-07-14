<?php
// user_profile.php
session_start();
require 'db.php';

// 获取目标用户ID
$profileUserId = intval($_GET['id'] ?? 0);

// 查询用户基本信息
$userStmt = $conn->prepare("
    SELECT 
        username, avatar, created_at,
        post_count, likes, bio, title
    FROM users 
    WHERE id = ?
");
$userStmt->bind_param("i", $profileUserId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

if (!$userData) {
    header("HTTP/1.0 404 Not Found");
    exit("用户不存在");
}

// 查询当前用户是否已经关注了目标用户
$loggedInUserId = $_SESSION['user_id'] ?? null;
$isFollowing = false;

if ($loggedInUserId) {
    $followStmt = $conn->prepare("
        SELECT 1
        FROM follows
        WHERE follower_id = ? AND following_id = ?
    ");
    $followStmt->bind_param("ii", $loggedInUserId, $profileUserId);
    $followStmt->execute();
    $result = $followStmt->get_result();

    if ($result->num_rows > 0) {
        $isFollowing = true;
    }
}

// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 查询用户文章列表
$articleStmt = $conn->prepare("
    SELECT 
        id, title, 
        SUBSTRING(content, 1, 200) AS content_preview,
        created_at, like_count, featured_image
    FROM articles 
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$articleStmt->bind_param("iii", $profileUserId, $perPage, $offset);
$articleStmt->execute();
$articles = $articleStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 获取总文章数用于分页
$totalStmt = $conn->prepare("SELECT COUNT(*) FROM articles WHERE user_id = ?");
$totalStmt->bind_param("i", $profileUserId);
$totalStmt->execute();
$totalArticles = $totalStmt->get_result()->fetch_row()[0];
$totalPages = ceil($totalArticles / $perPage);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($currentConfig['site_name']); ?> - <?= htmlspecialchars($userData['username']) ?>的主页</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* 基础重置 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        body {
            background: #f8f9fa;
            line-height: 1.6;
            color: #333;
        }
        /* 内容容器 */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        /* 用户信息卡 */
        .profile-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 1.5rem;
        }
        [data-theme="dark"] .profile-card {
            background: #1f2937;
            border-radius: 0.5rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 1.5rem;
        }
        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .profile-info {
            flex: 1;
        }
        .username {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            color: #2d3436;
        }
        [data-theme="dark"] .username {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            color: #e7e7e7;
        }
        .user-title {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }
        [data-theme="dark"] .user-title {
            color: #ffffff;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }
        /* 关注按钮 */
        .follow-btn {
            margin-top: 0.5rem;
        }
        .follow-btn button {
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            border: none;
            border-radius: 0.5rem;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .follow-btn button.follow {
            background-color: #007BFF;
        }
        .follow-btn button.unfollow {
            background-color: #FF4D4D;
        }
        .follow-btn button.login-required {
            background-color: #E0E0E0;
            color: #666;
            cursor: not-allowed;
        }
        /* 文章列表 */
        .article-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 1rem;
        }
        .article-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        [data-theme="dark"] .article-card {
            background: #131313;
            border-radius: 1rem;
            overflow: hidden;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
        .article-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }
        .article-content {
            padding: 1.5rem;
        }
        /* 响应式设计 */
        @media (max-width: 768px) {
            .profile-card {
                flex-direction: column;
            }
            .avatar {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 用户信息卡 -->
        <div class="profile-card">
            <div class="profile-image">
                <img src="<?= htmlspecialchars($userData['avatar']) ?>" 
                     class="avatar"
                     alt="用户头像"
                     onerror="this.src='default-avatar.jpg'">
            </div>
            <div class="profile-info">
                <h1 class="username">
                    <?= htmlspecialchars($userData['username']) ?>
                    <span class="user-title"><?= htmlspecialchars($userData['title']) ?></span>
                </h1>
                <!-- 关注按钮 -->
                <div class="follow-btn">
                    <?php if ($loggedInUserId): ?>
                        <?php if ($isFollowing): ?>
                            <button id="follow-btn" data-action="unfollow" data-profile-id="<?= $profileUserId ?>" class="unfollow">
                                取消关注
                            </button>
                        <?php else: ?>
                            <button id="follow-btn" data-action="follow" data-profile-id="<?= $profileUserId ?>" class="follow">
                                关注
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <button id="follow-btn" data-action="login-required" class="login-required">
                            登录后关注
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 文章列表 -->
        <div class="article-grid">
            <?php foreach ($articles as $article): ?>
            <article class="article-card">
                <?php if (!empty($article['featured_image'])): ?>
                <img src="<?= htmlspecialchars($article['featured_image']) ?>"
                     class="article-image"
                     alt="文章封面"
                     loading="lazy"
                     onerror="this.style.display='none'">
                <?php endif; ?>

                <a href="articles.php?id=<?= $article['id'] ?>">
                    <div class="article-content">
                        <h3 class="article-title">
                            <?= htmlspecialchars($article['title']) ?>
                        </h3>
                        <p class="article-preview">
                            <?= htmlspecialchars(strip_tags($article['content_preview'])) ?>...
                        </p>
                        <div class="article-meta">
                            <time class="article-date">
                                <?= date('Y/m/d H:i', strtotime($article['created_at'])) ?>
                            </time>
                            <span class="article-likes">❤️ <?= $article['like_count'] ?></span>
                        </div>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
        </div>

        <!-- 分页 -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?id=<?= $profileUserId ?>&page=<?= $i ?>" 
                   class="page-link <?= $i == $page ? 'current' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- JavaScript 用于处理关注/取消关注 -->
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const followBtn = document.getElementById("follow-btn");

        followBtn.addEventListener("click", function (e) {
            e.preventDefault();

            const action = followBtn.getAttribute("data-action");
            const profileId = followBtn.getAttribute("data-profile-id");

            if (action === "login-required") {
                alert("请先登录！");
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "function/follow.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        if (action === "follow") {
                            followBtn.textContent = "取消关注";
                            followBtn.setAttribute("data-action", "unfollow");
                            followBtn.classList.remove("follow");
                            followBtn.classList.add("unfollow");
                        } else {
                            followBtn.textContent = "关注";
                            followBtn.setAttribute("data-action", "follow");
                            followBtn.classList.remove("unfollow");
                            followBtn.classList.add("follow");
                        }
                    } else {
                        alert(response.message);
                    }
                }
            };

            xhr.send(`profile_id=${profileId}&action=${action}`);
        });
    });
    </script>
</body>
</html>
