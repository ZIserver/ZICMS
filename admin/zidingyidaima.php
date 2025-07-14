<?php
$message = '';
$current_image = get_setting('login_page_image');
$current_title = get_setting('login_page_title');
$current_desc = get_setting('login_page_description');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process image upload
    if (isset($_FILES['login_image']) && $_FILES['login_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/login_page/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['login_image']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_ext = pathinfo($_FILES['login_image']['name'], PATHINFO_EXTENSION);
            $file_name = 'login_bg_' . time() . '.' . $file_ext;
            $file_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['login_image']['tmp_name'], $file_path)) {
                if ($current_image && file_exists('../' . $current_image)) {
                    unlink('../' . $current_image);
                }
                
                update_setting('login_page_image', 'uploads/login_page/' . $file_name);
                $current_image = 'uploads/login_page/' . $file_name;
                $message = '图片上传成功!';
            } else {
                $message = '图片上传失败!';
            }
        } else {
            $message = '只允许上传 JPG, PNG 或 GIF 格式的图片!';
        }
    }
    
    if (isset($_POST['title'])) {
        update_setting('login_page_title', $_POST['title']);
        $current_title = $_POST['title'];
    }
    
    if (isset($_POST['description'])) {
        update_setting('login_page_description', $_POST['description']);
        $current_desc = $_POST['description'];
    }
}

function update_setting($key, $value) {
    global $conn;
    
    $sql = "SELECT COUNT(*) FROM system_settings WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_fetch_row($result)[0];
    
    if ($exists) {
        $sql = "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?";
    } else {
        $sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)";
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $value, $key);
    mysqli_stmt_execute($stmt);
}

function get_setting($key) {
    global $conn;
    
    $sql = "SELECT setting_value FROM system_settings WHERE setting_key = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_row($result)[0];
    }
    return null;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录页图片管理</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
        }

        .custom_container {
            margin: 40px auto;
            padding: 30px;
            background-color: #fff;
            border: 1px solid #e8e8e8;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0,0,0,0.1);
            max-width: 600px;
            text-align: center;
        }
        
        .custom_header {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .custom_message {
            margin: 10px 0;
            padding: 10px;
            font-size: 14px;
            border-radius: 5px;
        }
        
        .custom_success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .custom_error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .custom_form_group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .custom_label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        
        .custom_input, .custom_textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            margin-top: 5px;
        }
        
        .custom_textarea {
            height: 120px;
        }
        
        .custom_image_preview {
            max-width: 100%;
            height: auto;
            margin-top: 10px;
            border: 1px solid #ddd;
            padding: 5px;
            border-radius: 6px;
            box-shadow: 0px 2px 5px rgba(0,0,0,0.1);
        }
        
        .custom_button {
            padding: 12px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .custom_button:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>
<div class="custom_container">
    <h2 class="custom_header">登录页图片管理</h2>
    
    <?php if ($message): ?>
        <div class="custom_message <?php echo strpos($message, '成功') ? 'custom_success' : 'custom_error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    <form action="" method="post" enctype="multipart/form-data">
        <div class="custom_form_group">
            <label class="custom_label" for="login_image">登录页背景图片</label>
            <input type="file" name="login_image" id="login_image" class="custom_input" accept=".jpg, .jpeg, .png, .gif">
            <?php if ($current_image): ?>
                <div class="custom_image_preview">
                    <img src="../<?php echo htmlspecialchars($current_image); ?>" alt="当前图片" style="max-height: 200px; width: auto;">
                </div>
            <?php endif; ?>
        </div>
        
        <div class="custom_form_group">
            <label class="custom_label" for="custom_title">欢迎标题</label>
            <input type="text" name="title" id="custom_title" value="<?php echo htmlspecialchars($current_title); ?>" class="custom_input" placeholder="请输入欢迎标题">
        </div>
        
        <div class="custom_form_group">
            <label class="custom_label" for="custom_description">欢迎描述</label>
            <textarea name="description" id="custom_description" class="custom_textarea" placeholder="请输入欢迎描述"><?php echo htmlspecialchars($current_desc); ?></textarea>
        </div>
        
        <button type="submit" class="custom_button">保存设置</button>
    </form>
</div>
</body>
</html>
