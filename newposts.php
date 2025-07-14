<?php
session_start();

// 引入配置文件
require_once 'install/config.php';

// 数据库连接
$conn = new mysqli(db_host, db_user, db_pass, db_name, db_port);
$conn->set_charset("utf8");
function dbConnect() {
    $conn = new mysqli(db_host, db_user, db_pass, db_name, db_port);
    $conn->set_charset("utf8");
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }
    return $conn;
};
// 检查连接
if ($conn->connect_error) {
    die("数据库连接失败: " . $conn->connect_error);
}
function getArticleById($id) {
    global $pdo; // 假设 $pdo 是数据库连接对象

    // 检查 $id 是否为有效的正整数
    if (!is_int($id) || $id <= 0) {
        error_log("无效的 ID: " . $id);
        return null; // 返回 null 表示无效 ID
    }

    try {
        $sql = "SELECT * FROM articles WHERE id = :id LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC); // 获取单条记录

        if ($article === false) {
            return null; // 如果文章不存在，返回 null
        }

        return $article;
    } catch (PDOException $e) {
        error_log("数据库查询失败: " . $e->getMessage());
        return null; // 查询失败时返回 null
    }
}
if (!function_exists('get_beian_number')) {
    function get_beian_number() {
        // 创建数据库连接
        $conn = new mysqli(db_host, db_user, db_pass, db_name);
        
        // 检查连接是否成功
        if ($conn -> connect_error) {
            error_log("数据库连接失败: " . $conn -> connect_error);
            return '数据库连接异常';
        }

        // 初始化默认返回值
        $beian_number = '默认备案号';

        try {
            // 创建预处理语句
            $stmt = $conn -> prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'beian_number'");
            
            if (!$stmt) {
                
                throw new Exception("预处理语句准备失败: " . $conn -> error);
            }

            // 执行查询
            $stmt -> execute();
            
            // 绑定结果
            $stmt -> bind_result($value);
            
            // 获取结果
            if ($stmt -> fetch()) {
                $beian_number = $value;
            }
            
        } catch (Exception $e) {
            error_log("备案号查询错误: " . $e -> getMessage());
        } finally {
            // 清理资源
            $stmt -> close();
            $conn -> close();
        }

        return $beian_number;
    }
}

if (!function_exists('update_beian_number')) {
    function update_beian_number($new_number) {
        // 创建数据库连接
        $conn = new mysqli(db_host, db_user, db_pass, db_name);
        
        // 检查连接是否成功
        if ($conn -> connect_error) {
            error_log("数据库连接失败: " . $conn -> connect_error);
            return false;
        }

        $success = false;

        try {
            // 创建预处理语句
            $stmt = $conn -> prepare("INSERT INTO system_settings (setting_key, setting_value) 
                                   VALUES ('beian_number', ?) 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            
            if (!$stmt ) {
                throw new Exception("预处理语句准备失败: " . $conn -> error);
            }

            // 绑定参数并执行
            $stmt -> bind_param("ss", $new_number , $new_number);
            $success = $stmt -> execute();
            
        } catch (Exception $e) {
            error_log("备案号更新错误: " . $e -> getMessage());
        } finally {
            // 清理资源
            $stmt -> close();
            $conn -> close();
        }

        return $success;
    }
}

// 强制关闭页面错误显示
function insertCustomCode($custom_css, $custom_js) {
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO customcodes (custom_css, custom_js) VALUES (?, ?)");
    $stmt->bind_param("ss", $custom_css, $custom_js);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $result;
}
function getAllCustomCodes() {
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT custom_css, custom_js FROM customcodes");

    // 检查prepare是否成功
    if ($stmt === false) {
        error_log("Error preparing statement: (" . $conn->errno . ") " . $conn->error);
        $conn->close();
        return [];
    }

    // 检查execute是否成功
    if (!$stmt->execute()) {
        error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
        $stmt->close();
        $conn->close();
        return [];
    }

    // 获取结果
    $result = $stmt->get_result();
    $customCodes = [];
    while ($row = $result->fetch_assoc()) {
        $customCodes[] = $row;
    }

    $stmt->close();
    $conn->close();
    return $customCodes;
}

function getCategories() {
    $conn = dbConnect();
    $sql = "SELECT id, name FROM categories_art ORDER BY name";
    $result = $conn->query($sql);
    $categories = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    $conn->close();
    return $categories;
}

require_once 'function/functions.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: admin/login.php");
    exit();
}
// 表单令牌生成
if (!isset($_SESSION['form_key'])) {
    $_SESSION['form_key'] = bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['form_key']) || $_POST['form_key'] !== $_SESSION['form_key']) {
        echo "<script>alert('非法请求'); window.history.back();</script>";
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        echo "<script>alert('请先登录'); window.location.href='login.php';</script>";
        exit;
    }

    if (empty($_POST['title']) || empty($_POST['content'])) {
        echo "<script>alert('标题和内容不能为空'); window.history.back();</script>";
        exit;
    }

    // 数据清洗
    $allowedTags = '<p><br><a><strong><em><ul><ol><li><h1><h2><h3><img>';
    $cleanContent = strip_tags($_POST['content'], $allowedTags);

    // 处理封面上传
    $targetDir = "uploads/";
    $featuredImage = null;

    if (!empty($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['featured_image']['tmp_name'];
        $originalName = basename($_FILES['featured_image']['name']);
        $fileType = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $targetFile = $targetDir . uniqid('cover_', true) . '.' . $fileType;

        $imageCheck = getimagesize($tmp_name);
        if ($imageCheck === false) {
            echo "<script>alert('文件不是图片'); window.history.back();</script>";
            exit;
        }

        if ($_FILES['featured_image']['size'] > 20480000) {
            echo "<script>alert('封面图片不能超过2M'); window.history.back();</script>";
            exit;
        }

        if (!in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            echo "<script>alert('仅支持 JPG/JPEG/PNG/GIF 图片'); window.history.back();</script>";
            exit;
        }

        if (move_uploaded_file($tmp_name, $targetFile)) {
            $featuredImage = $targetFile;
        } else {
            echo "<script>alert('封面上传失败'); window.history.back();</script>";
            exit;
        }
    }

    // 插入数据库
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $title = htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8');
    $categoryId = $_POST['category_id'] ?? null; // 获取分类 ID

    $stmt = $conn->prepare("INSERT INTO articles (user_id, title, content, username, featured_image, category_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssi", $userId, $title, $cleanContent, $username, $featuredImage, $categoryId);

    if ($stmt->execute()) {
        $conn->query("UPDATE users SET post_count = post_count + 1 WHERE id = $userId");
        header("Location: index.php");
        exit;
    } else {
        echo "<script>alert('保存失败: " . addslashes($stmt->error) . "'); window.history.back();</script>";
    }
}

// 获取分类列表
$categories = getCategories();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentConfig['site_name']); ?> - 发布文章</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
/* ==== 文章发布页自定义样式 ==== */
.post-container {
    position: absolute;    /* 绝对定位撑满 */
    top: 60px;             /* 导航栏高度，视你具体情况修改 */
    left: 0;
    right: 0;
    bottom: 60px;          /* 给按钮预留高度 */
    padding: 30px;
    background: #fff;
    overflow-y: auto;      /* 内容滚动，页面整体不滚动 */
}

.post-container h1 {
    font-size: 24px;
    color: #333;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
}

.form-group input[type="text"],
.form-group input[type="file"],
.form-group select { /* 添加 select 样式 */
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    font-size: 14px;
}

/* 封面上传预览 */
.cover-preview {
    margin-top: 10px;
    max-width: 100%;
    max-height: 300px;
    display: none;
}

/* 富文本工具栏 */
.toolbar {
    display: flex;
    flex-wrap: wrap;
    border: 1px solid #ddd;
    background: #f8f8f8;
    padding: 6px;
}

.toolbar button {
    border: none;
    background: #f1f1f1;
    padding: 8px 12px;
    margin: 4px 5px;
    cursor: pointer;
    font-size: 14px;
    color: #333;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toolbar button:hover {
    background: #e0e0e0;
}

.toolbar .icon {
    width: 12px;
    height: 12px;
    margin-right: 5px;
}

/* 编辑器区域 */
.editable-area {
    min-height: 300px;
    padding: 15px;
    border: 1px solid #ddd;
    border-top: none;
    font-size: 15px;
    line-height: 1.5;
    outline: none;
    background-color: #fafafa;
    overflow-y: auto;
}

.editable-area:focus {
    box-shadow: 0 0 3px #ced4da;
}

/* 提交按钮固定在底部 */
.post-submit-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    background: #fff;
    padding: 12px;
    box-shadow: 0 -1px 5px rgba(0,0,0,0.08);
    text-align: center;
}


.submit-btn {
    background-color: #FF7C3E;
    color: #fff;
    padding: 12px 30px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s ease-in-out;
    font-size: 15px;
}

.submit-btn:hover {
    background-color: #e66f31;
}
</style>

</head>
<body>
    <main>
        
        <header>
            <i class="fas fa-edit"></i> 创建新文章
        </header>

<div class="post-container">
    <h1><i class="fas fa-edit icon"></i> 创建新文章</h1>
    <form id="articleForm" method="post" enctype="multipart/form-data">
        <input type="hidden" name="form_key" value="<?= $_SESSION['form_key'] ?>">
        
        <div class="form-group">
            <label for="title">文章标题</label>
            <input type="text" id="title" name="title" required minlength="5" maxlength="255" placeholder="请输入标题">
        </div>

        <div class="form-group">
            <label for="category_id">选择分类</label>
            <select id="category_id" name="category_id">
                <option value="">未分类</option> <!-- 默认选项 -->
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars($category['id']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="featured_image">封面图片（可选）</label>
            <input type="file" id="featured_image" name="featured_image" accept="image/*">
            <img id="coverPreview" class="cover-preview">
        </div>

        <div class="form-group">
            <label>文章内容</label>
            <div class="toolbar">
                <button type="button" onclick="format('bold')"><i class="fa fa-bold icon"></i> 加粗</button>
                <button type="button" onclick="format('italic')"><i class="fa fa-italic icon"></i> 斜体</button>
                <button type="button" onclick="format('underline')"><i class="fa fa-underline icon"></i> 下划线</button>
                <button type="button" onclick="format('insertUnorderedList')"><i class="fa fa-list-ul icon"></i> 无序列表</button>
                <button type="button" onclick="format('insertOrderedList')"><i class="fa fa-list-ol icon"></i> 有序列表</button>
                <button type="button" onclick="format('formatBlock','h1')"><i class="fa fa-heading icon"></i> H1</button>
                <button type="button" onclick="format('formatBlock','h2')"><i class="fa fa-heading icon"></i> H2</button>
                <button type="button" onclick="format('undo')"><i class="fa fa-undo icon"></i> 撤销</button>
                <button type="button" onclick="format('redo')"><i class="fa fa-redo icon"></i> 重做</button>
                <button type="button" onclick="document.getElementById('imageInput').click()"><i class="fa fa-image icon"></i> 插入图片</button>
            </div>

            <div class="editable-area" contenteditable="true" id="editor"></div>
            <input type="file" id="imageInput" accept="image/*" style="display: none;" onchange="handleImageUpload()">
            <textarea name="content" id="htmlContent" style="display: none;"></textarea>
        </div>

        
            <button type="submit" class="submit-btn">发布文章</button>
        
    </form>
</div>

    </main>

<script>
function format(cmd, value = null) {
    document.execCommand(cmd, false, value);
}

function handleImageUpload() {
    const input = document.getElementById("imageInput");
    const file = input.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        format("insertHTML", `<img src="${e.target.result}" alt="插入的图片" style="max-width: 100%; margin: 10px 0;">`);
    };
    reader.readAsDataURL(file);
    input.value = "";
}

// 封面上传预览
document.getElementById("featured_image").addEventListener("change", function (e) {
    const preview = document.getElementById("coverPreview");
    const file = e.target.files[0];

    if (file && file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = function (ev) {
            preview.src = ev.target.result;
            preview.style.display = "block";
        };
        reader.readAsDataURL(file);
    } else {
        preview.src = "";
        preview.style.display = "none";
    }
});

// submit前注入编辑器内容到textarea
document.getElementById("articleForm").addEventListener("submit", function (e) {
    const editor = document.getElementById("editor");
    const contentInput = document.getElementById("htmlContent");
    if (!editor.innerText.trim() && !editor.querySelector("img")) {
        e.preventDefault();
        alert("内容不能为空");
    } else {
        contentInput.value = editor.innerHTML;
    }
});

</script>
</body>
</html>
