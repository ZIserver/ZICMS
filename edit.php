<?php
// edit.php

// 引入数据库连接文件
require_once 'db.php';

// 引入 CSRF 保护函数
session_start();
if (!isset($_SESSION['form_key'])) {
    $_SESSION['form_key'] = bin2hex(random_bytes(32));
}

// 获取文章 ID
$articleId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 获取文章详情
function getArticleDetails($conn, $articleId) {
    $stmt = $conn->prepare("SELECT id, title, content, created_at, user_id, username FROM articles WHERE id = ?");
    $stmt->bind_param("i", $articleId);
    $stmt->execute();
    $stmt->store_result();
    $article = [];
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $title, $content, $created_at, $user_id, $username);
        $stmt->fetch();
        $article = [
            'id' => $id,
            'title' => $title,
            'content' => $content,
            'created_at' => $created_at,
            'user_id' => $user_id,
            'username' => $username,
        ];
    }
    $stmt->close();
    return $article;
}

// 更新文章
function updateArticle($conn, $articleId, $title, $content) {
    $stmt = $conn->prepare("UPDATE articles SET title = ?, content = ? WHERE id = ?");
    $stmt->bind_param("ssi", $title, $content, $articleId);
    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
    $stmt->close();
}

// 更新逻辑
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 验证 CSRF 保护
    if (!isset($_POST['form_key']) || $_POST['form_key'] !== $_SESSION['form_key']) {
        die("Invalid form submission.");
    }

    $title = $_POST['title'];
    $content = $_POST['content'];

    // 更新文章
    if (updateArticle($conn, $articleId, $title, $content)) {
        header("Location: articles.php?id=$articleId"); // 跳转到文章详情页面
        exit;
    } else {
        echo "更新失败，请稍后再试。";
    }
}

// 获取文章详情
$article = getArticleDetails($conn, $articleId);
if($article['user_id'] == $_SESSION['user_id']):
?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <?php include'common/header.php' ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentConfig['site_name']); ?> - 文章编辑</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* 你的样式代码 */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --light-gray: #f8f9fa;
            --text-color: #2c3e50;
            --border-color: #e0e0e0;
        }
        
        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background: var(--light-gray);
            margin: 0;
            padding: 0;
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        h1 {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.8rem;
            margin: 0 0 25px 0;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: var(--secondary-color);
            font-size: 0.95rem;
        }

        input[type="text"] {
            padding: 12px 15px;
            width: 100%;
            border: 1px solid var(--border-color);
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        input[type="text"]:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            background: var(--light-gray);
            border: 1px solid var(--border-color);
            border-bottom: none;
            padding: 5px;
        }

        .toolbar button {
            background-color: transparent;
            border: none;
            padding: 8px 12px;
            margin: 0 2px;
            cursor: pointer;
            color: var(--secondary-color);
            font-size: 0.9rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
        }

        .toolbar button:hover {
            background-color: #e3e6e9;
            color: var(--primary-color);
        }

        .toolbar button i {
            margin-right: 5px;
        }

        .editable-area {
            border: 1px solid var(--border-color);
            min-height: 400px;
            padding: 15px;
            background-color: #ffffff;
            overflow-y: auto;
            line-height: 1.7;
            font-size: 1rem;
        }

        .editable-area:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .submit-btn {
            padding: 12px 28px;
            background: var(--accent-color);
            color: white;
            border: none;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .submit-btn:hover {
            background: #2980b9;
        }

        /* 图标样式 */
        .icon {
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-right: 6px;
            vertical-align: middle;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>文章编辑</h1>
        <form id="articleForm" method="post">
            <input type="hidden" name="form_key" value="<?= $_SESSION['form_key'] ?>">

            <div class="form-group">
                <label for="title"><i class="fas fa-heading icon"></i> 文章标题</label>
                <input type="text" id="title" name="title" required 
                       minlength="5" maxlength="255"
                       placeholder="请输入文章标题（5-255字符）"
                       value="<?= $article['title'] ?>">
            </div>

            <div class="form-group">
                <label><i class="fas fa-align-left icon"></i> 文章内容</label>
                <div class="toolbar">
                    <button type="button" onclick="format('bold')" title="加粗"><i class="fas fa-bold"></i></button>
                    <button type="button" onclick="format('italic')" title="斜体"><i class="fas fa-italic"></i></button>
                    <button type="button" onclick="format('underline')" title="下划线"><i class="fas fa-underline"></i></button>
                    <button type="button" onclick="format('insertUnorderedList')" title="无序列表"><i class="fas fa-list-ul"></i></button>
                    <button type="button" onclick="format('insertOrderedList')" title="有序列表"><i class="fas fa-list-ol"></i></button>
                    <button type="button" onclick="format('formatBlock', 'h1')" title="标题1"><i class="fas fa-heading"></i>1</button>
                    <button type="button" onclick="format('formatBlock', 'h2')" title="标题2"><i class="fas fa-heading"></i>2</button>
                    <button type="button" onclick="format('formatBlock', 'h3')" title="标题3"><i class="fas fa-heading"></i>3</button>
                    <div class="divider"></div>
                    <button type="button" onclick="format('undo')" title="撤销"><i class="fas fa-undo"></i></button>
                    <button type="button" onclick="format('redo')" title="重做"><i class="fas fa-redo"></i></button>
                    <button type="button" onclick="document.getElementById('imageInput').click()" title="插入图片"><i class="fas fa-image"></i></button>
                    <button type="button" onclick="format('createLink', prompt('请输入链接URL:'))" title="插入链接"><i class="fas fa-link"></i></button>
                </div>

                <div class="editable-area"
                    contenteditable="true"
                    id="editor"
                    name="content"
                    required><?= $article['content'] ?>
                    <p></p>
                </div>

                <textarea id="htmlContent" name="content" style="display:none;"></textarea>
            </div>

            <div class="form-group text-right">
                <button class="submit-btn"><i class="fas fa-paper-plane icon"></i> 发布文章</button>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // 格式化内容
        function format(command, value) {
            document.execCommand(command, false, value);
            updateHiddenField();
        }

        // 更新隐藏的 HTML 内容字段
        function updateHiddenField() {
            const editor = document.getElementById('editor');
            const htmlContent = document.getElementById('htmlContent');
            htmlContent.value = editor.innerHTML;
        }

        // 提交表单前更新隐藏字段
        document.getElementById('articleForm').addEventListener('submit', function() {
            updateHiddenField();
        });

        // 插入图片逻辑（如果需要）
        function handleImageUpload() {
            const fileInput = document.getElementById('imageInput');
            const editor = document.getElementById('editor');

            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = '100%';
                    editor.appendChild(img);
                    updateHiddenField();
                };

                reader.readAsDataURL(fileInput.files[0]);
            }
        }
    </script>
    <?php include'common/footer.php' ?>
</body>
</html>
<?php else: ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>没有权限</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h1 {
            color: #e74c3c; /* 红色 */
            margin-bottom: 20px;
        }

        p {
            color: #777;
            margin-bottom: 30px;
        }

        a {
            color: #3498db; /* 蓝色 */
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>没有权限</h1>
        <p>您没有权限访问修改此文章。</p>
        <p>请联系管理员或尝试使用其他账户登录。</p>
        <a href="/">返回首页</a>
    </div>
</body>
</html>
<?php endif ?>