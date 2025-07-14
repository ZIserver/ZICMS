<?php
require_once 'db.php';
include 'auth/index.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
$perPage = 10;
$offset = ($page - 1) * $perPage;

try {
    $cleanSearch = str_replace(['%', '_'], ['\%', '\_'], $searchQuery);
    $searchTerm = "%{$cleanSearch}%";

    $sql = "SELECT 
            a.id, 
            a.title, 
            a.content, 
            a.created_at,
            u.username,
            u.avatar
        FROM articles a
        JOIN users u ON a.user_id = u.id
        WHERE a.title LIKE ?
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $searchTerm, $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $articles = [];
    while ($row = $result->fetch_assoc()) {
        $row['excerpt'] = mb_substr(strip_tags($row['content']), 0, 150) . '...';
        $articles[] = $row;
    }

    $countSql = "SELECT COUNT(*) AS total 
                FROM articles 
                WHERE title LIKE ?";
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param("s", $searchTerm);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];

} catch (mysqli_sql_exception $e) {
    die("搜索出错: " . $e->getMessage());
}

$totalPages = ceil($total / $perPage);
$startPage = max(1, $page - 2);
$endPage = min($totalPages, $page + 2);
?>
<?php include 'common/header.php'?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentConfig['site_name']); ?> - 搜索「<?php echo htmlspecialchars($searchQuery); ?>」- 文章标题检索</title>
    <style>
        :root {
            --primary: #0062cc;
            --primary-hover: #0056b3;
            --text-main: #212529;
            --text-secondary: #6c757d;
            --bg-main: #ffffff;
            --border: #dee2e6;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            background: var(--bg-main);
            color: var(--text-main);
        }

        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 3rem 2rem;
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }

        .article-list {
            display: grid;
            gap: 2rem;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            margin-bottom: 3rem;
        }

        .article-card {
            background: white;
            padding: 1.75rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid var(--border);
            text-decoration: none;
            display: block;
            color: inherit;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .article-card h2 {
            font-size: 1.375rem;
            margin-bottom: 1rem;
            line-height: 1.4;
            color: var(--text-main);
        }

        .excerpt {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-bottom: 1.5rem;
            line-height: 1.625;
        }

        .user-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border: 2px solid var(--border);
        }

        .meta-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .username {
            font-weight: 600;
            color: var(--text-main);
        }

        .post-time {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .read-more {
            color: var(--primary);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }

        .article-card:hover .read-more {
            color: var(--primary-hover);
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin: 4rem 0;
            flex-wrap: wrap;
        }

        .pagination a {
            padding: 0.75rem 1.25rem;
            background: white;
            color: var(--text-secondary);
            text-decoration: none;
            border: 1px solid var(--border);
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
            border-color: transparent;
        }

        .pagination .active {
            background: var(--primary);
            color: white;
            border-color: transparent;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            margin: 3rem 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .search-tips {
            background: var(--bg-main);
            padding: 1.75rem;
            margin: 2rem auto;
            max-width: 640px;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 0 1rem;
            }
            .page-header {
                padding: 2rem 1.5rem;
                margin-bottom: 2rem;
            }
            .page-header h1 {
                font-size: 1.875rem;
            }
            .article-list {
                grid-template-columns: 1fr;
            }
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
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text-main);
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
        [data-theme="dark"] .empty-state {
            background: #343a40;
            color: #ffffff;
        }
        [data-theme="dark"] .search-tips {
            background: #343a40;
            color: #ffffff;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <header class="page-header">
            <h1>「<?php echo htmlspecialchars($searchQuery); ?>」的搜索结果</h1>
            <p class="total-results">共找到 <?php echo $total; ?> 篇相关文章</p>
        </header>
        <center><div class="search-container">
        <form class="search-form" action="/search.php" method="GET">
            <input type="text" 
                    class="search-input" 
                        name="q" 
                        placeholder="搜索文章..."
                        aria-label="搜索文章"
                        value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>">
                    <button type="submit" class="search-button">🔍</button>
                </form>
            </div></center>
        <?php if (!empty($articles)): ?>
            <div class="article-list">
                <?php foreach ($articles as $article): ?>
                    <a href="/articles.php?id=<?php echo $article['id']; ?>" class="article-card">
                        <div class="user-meta">
                            <img src="<?php echo htmlspecialchars($article['avatar']); ?>" 
                                 class="user-avatar"
                                 alt="<?php echo htmlspecialchars($article['username']); ?>"
                                 loading="lazy">
                            <div class="meta-info">
                                <div class="username"><?php echo htmlspecialchars($article['username']); ?></div>
                                <time class="post-time"><?php echo date('Y/m/d', strtotime($article['created_at'])); ?></time>
                            </div>
                        </div>
                        <h2><?php echo htmlspecialchars($article['title']); ?></h2>
                        <p class="excerpt"><?php echo $article['excerpt']; ?></p>
                        <div class="read-more">
                            阅读全文
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <nav class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?q=<?= urlencode($searchQuery) ?>&page=1">« 首页</a>
                    <a href="?q=<?= urlencode($searchQuery) ?>&page=<?= $page - 1 ?>">‹ 上一页</a>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <a href="?q=<?= urlencode($searchQuery) ?>&page=<?= $i ?>" 
                       class="<?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?q=<?= urlencode($searchQuery) ?>&page=<?= $page + 1 ?>">下一页 ›</a>
                    <a href="?q=<?= urlencode($searchQuery) ?>&page=<?= $totalPages ?>">尾页 »</a>
                <?php endif; ?>
            </nav>
        <?php else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                    <path d="M11 8v6M8 11h6"/>
                </svg>
                <p style="margin: 1.5rem 0 2rem;">未找到与「<?php echo htmlspecialchars($searchQuery); ?>」相关的文章</p>
                <div class="search-tips" >
                    <p style="font-weight: 500; margin-bottom: 1rem;">搜索建议：</p>
                    <ul class="search-tipss">
                        <li >使用精确的关键词组合</li>
                        <li>检查输入法的中英文状态</li>
                        <li>尝试减少关键词数量</li>
                    </ul>
                </div>
                <a href="/" style="margin-top: 2rem; display: inline-flex; padding: 0.75rem 1.5rem; background: var(--primary); color: white; text-decoration: none; transition: background 0.2s; border-radius: 0;" 
                   onmouseover="this.style.background='var(--primary-hover)'" 
                   onmouseout="this.style.background='var(--primary)'">
                   返回首页
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php include 'common/footer.php'?>
