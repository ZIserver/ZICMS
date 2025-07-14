<?php
include 'common/header.php';

// 获取文章 ID
$articleId = $_GET['id'] ?? null;

// 连接数据库
require_once 'db.php'; // 引入数据库连接文件

// 检查连接
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}

// 查询文章详情
$article = null;
if ($articleId) {
    $sql = "SELECT 
            articles.id, 
            articles.title, 
            articles.content, 
            articles.created_at, 
            articles.username, 
            articles.like_count, 
            articles.user_id, 
            articles.views,
            categories_art.name AS category_name  -- 获取分类名称
        FROM articles
        LEFT JOIN categories_art ON articles.category_id = categories_art.id
        WHERE articles.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $articleId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $article = $result->fetch_assoc();
    }
    $stmt->close();
}

// 关闭数据库连接
$conn->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentConfig['site_name']); ?> - 文章详情</title>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    const articleId = document.getElementById('contentId')?.value;
    if (articleId) {
        updateViewCount(articleId);
    }
});

// 更新阅读量
function updateViewCount(articleId) {
    fetch('/function/view_count.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ articleId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // 更新页面上的阅读量显示
            document.getElementById('view-count').textContent = formatNumber(data.views);
        }
    })
    .catch(error => console.error('Error updating view count:', error));
}

// 数字格式化函数 (1,000格式)
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
    </script>
    <style>
     /* ========= 总体样式 ========= */
        body {
            font-family: 'Inter', Arial, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            display: flex;
            min-height: auto;
            background-color: white;
        }
        
        /* 主内容区 */
        .main-content {
            flex: 1;
            margin-right: 350px; /* 为右侧边栏留出空间 */
        }
        .article-title{
            font-weight: bold;
            font-size: 50px;
            text-align: center;
        }
        /* 右侧边栏 */
        .sidebar {
            margin-top: 60px;
            margin-block-end: 60px;
            width: 350px;
            background: #fff;
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            overflow-y: auto;
            border-left: 1px solid #e0e0e0;
            padding: 20px;
            box-shadow: -2px 0 10px rgba(0, 0, 0, 0.05);
        }
        
        
        
        
        .author-info {
            margin-top: 20px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .author-info a {
            color: #004e8c;
        }
        
        .post-time {
            margin-left: 20px;
            font-size: 14px;
            color: #6c757d;
        }
        
        .like-section {
            margin-top: 20px;
            display: flex;
            align-items: center;
        }
        
        .like-btn {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 6px 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            color: #495057;
        }
        
        .like-btn:hover {
            background: #e9ecef;
            transform: translateY(-1px);
        }
        
        .like-btn svg {
            color: #adb5bd;
            transition: all 0.3s ease;
        }
        
        .like-btn.liked svg {
            color: #ff6b6b;
            animation: heartBeat 0.5s;
        }
        
        .like-btn.liked {
            border-color: #ff6b6b;
            color: #ff6b6b;
        }
        
        .like-count {
            font-weight: bold;
        }
        
        @keyframes heartBeat {
            0% { transform: scale(1); }
            14% { transform: scale(1.3); }
            28% { transform: scale(1); }
            42% { transform: scale(1.3); }
            70% { transform: scale(1); }
        }
        
        /* 右侧边栏样式 */
        .sidebar-section {
            margin-bottom: 30px;
        }
        
        .sidebar-title {
            font-size: 18px;
            color: #004e8c;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        /* 评论表单 */
        .comment-form {
            margin-bottom: 20px;
        }
        
        .comment-form textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            font-size: 14px;
            border: 1px solid #e0e0e0;
            resize: vertical;
        }
        
        #submitComment {
            padding: 8px 16px;
            background: #004e8c;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }
        
        /* 评论列表 */
        .comment-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .comment-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .comment-author {
            font-weight: bold;
            color: #004e8c;
            font-size: 14px;
        }
        
        .comment-time {
            font-size: 12px;
            color: #888;
        }
        
        .comment-content {
            font-size: 14px;
            color: #555;
        }
        
        /* 数据统计区域 */
        .stats {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #e0e0e0;
        }
        
        .stat-label {
            color: #6c757d;
        }
        
        .stat-value {
            font-weight: bold;
            color: #004e8c;
        }
        
        /* 响应式设计 */
        @media (max-width: 1200px) {
            .main-content {
                margin-right: 300px;
            }
            
            .sidebar {
                width: 300px;
            }
        }
        
        @media (max-width: 992px) {
            body {
                flex-direction: column;
            }
            
            .main-content {
                margin-right: 0;
                max-width: 100%;
            }
            
            .sidebar {
                position: static;
                width: 100%;
                border-left: none;
                border-top: 1px solid #e0e0e0;
            }
        }
        .fenge{
            border-top: 1px solid #d0d0d5;
        }
        .article-content{
            font-size: 20px;
        }
    </style>
    
</head>
<body>
    <!-- 主内容区 -->
<div class="main-content" style="background-color: white;">
    <?php if ($article): ?>
        <!-- 添加直角容器 -->
        <div class="article-container" style="background-color: white;margin-top: 60px;">
            <div class="article-title"><?php echo htmlspecialchars($article['title']); ?></div>
            <div class="fenge"></div>
            <div class="article-content">
                <?php echo nl2br($article['content']); ?>
            </div>
        </div>
    <?php else: ?>
        <div class="article-title">未找到文章</div>
        <div class="article-content">文章不存在或已被删除。</div>
    <?php endif; ?>
</div>




    <!-- 右侧边栏 -->
    <div class="sidebar">
        <!-- 数据统计区 -->
        <div class="sidebar-section stats">
            <div class="sidebar-title">文章数据</div>
            <div class="author-info">
                作者：<a href="user.php?id=<?= $article['user_id'] ?>"><?php echo htmlspecialchars($article['username'] ?? '未知作者'); ?></a>
                <span class="post-time">
                    发布于 <?= date('Y-m-d H:i', strtotime($article['created_at'])) ?>
                </span>
            </div>
            <div class="stat-item">
                <span class="stat-label">阅读量</span>
                <span class="stat-value" id="view-count"><?php echo number_format($article['views'] ?? 0); ?></span>
            </div>
            
            <div class="stat-item">
                <span class="stat-label">点赞数</span>
                <span class="stat-value" id="sidebar-like-count"><?php echo $article ? $article['like_count'] : '0'; ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">评论数</span>
                <span class="stat-value" id="comment-count">0</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">收藏数</span>
                <span class="stat-value" id="favorite-count">328</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">分类</span>
                <span class="stat-value" id="favorite-count"><?php echo htmlspecialchars($article['category_name'] ?? '未分类'); ?></span>
            </div>
        </div>
        <div class="like-section">
                <h2>点赞数: <span id="like-count-<?php echo $article['id']; ?>"><?php echo $article['like_count']; ?></span></h2>
                <button id="like-button-<?php echo $article['id']; ?>" 
                        onclick="likeArticle(<?php echo $article['id']; ?>)"
                        class="like-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M8 1.314C12.438-3.248 23.534 4.735 8 15-7.534 4.736 3.562-3.248 8 1.314z"/>
                    </svg>
                    <span class="like-text">点赞</span>
                </button>
            </div>
        <!-- 评论表单 -->
        <div class="sidebar-section comment-form">
            <div class="sidebar-title">发表评论</div>
            <textarea id="commentText" placeholder="分享你的想法..." rows="3"></textarea>
            <input type="hidden" id="contentId" value="<?php echo $article['id']; ?>">
            <button id="submitComment">发表评论</button>
        </div>

        <!-- 评论列表 -->
        <div class="sidebar-section">
            <div class="sidebar-title">最新评论 <span id="commentTotal">(0)</span></div>
            <div class="comment-list" id="commentList">
                <!-- 评论将通过 AJAX 动态加载 -->
            </div>
        </div>
        
    </div>

    <script>
// 点赞功能封装
class LikeHandler {
    constructor() {
        this.initLikeButtons();
        this.setupCSRFToken();
    }

    // 初始化所有点赞按钮
    initLikeButtons() {
        document.querySelectorAll('[id^="like-button-"]').forEach(button => {
            const articleId = this.getArticleId(button);
            if (localStorage.getItem(`liked_${articleId}`)) {
                this.updateButtonState(button, true);
            }
            
            button.addEventListener('click', () => this.handleLike(articleId));
        });
    }

    // 设置CSRF令牌
    setupCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        if (!token) console.warn('CSRF token not found');
    }

    // 处理点赞请求
    async handleLike(articleId) {
        const button = document.getElementById(`like-button-${articleId}`);
        const countElements = [
            document.getElementById(`like-count-${articleId}`),
            document.getElementById('sidebar-like-count')
        ].filter(Boolean);

        // 防止重复点击
        if (button.classList.contains('processing')) return;
        button.classList.add('processing');
        
        try {
            // 显示加载状态
            this.updateButtonState(button, false, true);
            
            // 发送AJAX请求
            const response = await fetch('/function/like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({ articleId })
            });

            // 处理响应
            const data = await this.parseResponse(response);
            
            if (data.status === 'success') {
                // 更新所有计数器
                countElements.forEach(el => el.textContent = data.like_count);
                
                // 更新按钮状态
                this.updateButtonState(button, true);
                localStorage.setItem(`liked_${articleId}`, 'true');
                
                // 显示成功反馈
                this.showFeedback('点赞成功', 'success');
            } else {
                throw new Error(data.message || '操作失败');
            }
        } catch (error) {
            console.error('点赞错误:', error);
            this.showFeedback(error.message || '点赞失败，请稍后重试', 'error');
            this.updateButtonState(button, false);
        } finally {
            button.classList.remove('processing');
        }
    }

    // 解析响应
    async parseResponse(response) {
        if (response.status === 401) {
            window.location.href = '/login?return=' + encodeURIComponent(window.location.pathname);
            return;
        }
        
        if (!response.ok) {
            throw new Error(`HTTP错误! 状态码: ${response.status}`);
        }
        
        return response.json();
    }

    // 更新按钮状态
    updateButtonState(button, isLiked, isLoading = false) {
        if (isLoading) {
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 处理中';
            button.disabled = true;
        } else if (isLiked) {
            button.innerHTML = '<i class="fas fa-heart"></i> 已点赞';
            button.disabled = true;
            button.classList.add('active');
        } else {
            button.innerHTML = '<i class="far fa-heart"></i> 点赞';
            button.disabled = false;
            button.classList.remove('active');
        }
    }

    // 显示反馈通知
    showFeedback(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <i class="icon ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // 获取文章ID
    getArticleId(element) {
        return element.id.split('-')[2];
    }
}

// 初始化点赞处理器
document.addEventListener('DOMContentLoaded', () => new LikeHandler());

// 先定义所有工具函数
function formatTime(timestamp) {
    if (!timestamp) return '未知时间';
    try {
        const date = new Date(timestamp);
        return date.toLocaleString('zh-CN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        }).replace(/\//g, '-');
    } catch (e) {
        console.error('时间格式错误:', timestamp, e);
        return timestamp;
    }
}

function renderComments(comments) {
    if (!comments || !Array.isArray(comments) || comments.length === 0) {
        return `<div class="no-comments">
            <i class="far fa-comment-dots"></i> 暂无评论，快来发表第一条评论吧~
        </div>`;
    }

    return comments.map(comment => `
        <div class="comment-item" data-comment-id="${comment.id}">
            <div class="comment-header">
                <span class="comment-author">
                    <i class="fas fa-user-circle"></i> ${comment.username || '匿名用户'}
                </span>
                <span class="comment-time">${formatTime(comment.created_at)}</span>
            </div>
            <div class="comment-content">${escapeHtml(comment.content)}</div>
        </div>
    `).join('');
}

// 防止XSS攻击的HTML转义
function escapeHtml(unsafe) {
    return unsafe?.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;") || '';
}

// 然后定义主功能函数
function loadComments(contentId) {
    const commentList = document.getElementById('commentList');
    if (!commentList) {
        console.error('评论容器不存在');
        return;
    }

    commentList.innerHTML = '<div class="loading-state"><i class="fas fa-spinner fa-spin"></i> 加载评论中...</div>';

    fetch(`/function/get_comments.php?content_id=${contentId}&t=${Date.now()}`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP错误 ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.status !== 'success') throw new Error(data.message || '数据格式错误');
            
            // 更新DOM
            document.querySelectorAll('.comment-count').forEach(el => {
                el.textContent = data.total || 0;
            });
            
            commentList.innerHTML = renderComments(data.comments || []);
        })
        .catch(error => {
            console.error('加载评论失败:', error);
            commentList.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i> 评论加载失败
                    <button onclick="loadComments(${contentId})" class="retry-btn">
                        <i class="fas fa-redo"></i> 重试
                    </button>
                </div>`;
        });
}
document.getElementById('submitComment')?.addEventListener('click', function() {
    const content = document.getElementById('commentText').value.trim();
    const contentId = document.getElementById('contentId').value;
    const submitBtn = this;
    
    if (!content) {
        alert('评论内容不能为空');
        return;
    }
    // 禁用按钮防止重复提交
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 提交中...';
    fetch('/function/comment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            content_id: contentId,
            content: content
        })
    })
    .then(async response => {
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || '提交失败');
        return data;
    })
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('commentText').value = '';
            loadComments(contentId);
            showToast('评论成功', 'success');
        } else {
            throw new Error(data.message);
        }
    })
    .catch(error => {
        console.error('评论提交错误:', error);
        showToast(error.message || '评论提交失败', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '发表评论';
    });
});
// 最后绑定事件
document.addEventListener('DOMContentLoaded', () => {
    // 假设contentId从页面某个元素获取
    const contentId = document.getElementById('contentId')?.value;
    if (contentId) loadComments(contentId);
    
    // 评论提交事件绑定
    document.getElementById('submitComment')?.addEventListener('click', submitComment);
});


    </script>
    <?php include 'common/footer.php'; ?>
</body>
</html>
