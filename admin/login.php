<?php
session_start();

require '.././install/config.php';
require '.././function/functions.php';

if (is_logged_in()) {
    header('Location: ../index.php');
    exit;
}

if (!file_exists('.././install/install.lock')) {
    header('Location: .././install/install.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['captcha']) || strtolower($_POST['captcha']) !== strtolower($_SESSION['captcha'])) {
        die("<script>alert('验证码错误❌'); window.location.href='login.php';</script>");
    }

    $username = $_POST['username'];
    $password = md5($_POST['password']);

    try {
        $pdo = new PDO("mysql:host=" . db_host . ";port=" . db_port . ";dbname=" . db_name, db_user, db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
        $stmt->execute([$username, $password]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: .././index.php');
            exit;
        } else {
            $error = "用户名或密码错误。";
        }
    } catch (PDOException $e) {
        $error = "数据库错误: " . $e->getMessage();
    }
}
require '.././common/header.php';
$login_image = function_exists('get_setting') ? get_setting('login_page_image') : '';
$login_title = function_exists('get_setting') ? get_setting('login_page_title') : '欢迎使用'.site_name.'系统';
$login_desc = function_exists('get_setting') ? get_setting('login_page_description') : '专业的企业级解决方案，助力您的业务发展';
// 处理可能的null值
$login_image = $login_image ?? '';
$login_title = $login_title ?? '欢迎使用'.site_name.'系统';
$login_desc = $login_desc ?? '专业的企业级解决方案，助力您的业务发展';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentConfig['site_name']); ?> - 文章社区</title>
    
    <link rel="stylesheet" href=".././css/index.css">
    <style>
    input[type="text"], input[type="password"] {
    width: 100%;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    box-sizing: border-box;
}

input[type="submit"] {
    width: 50%;
    padding: 10px;
    background-color: #007BFF;
    color: #fff;
    border: none;
    cursor: pointer;
    font-size: 16px;
}
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', 'Microsoft YaHei', sans-serif;
        }
        
        body {
            background-color: #f8fafc;
            color: #333;
            line-height: 1.6;
            height: 100vh;
            overflow: hidden;
        }
        
        .login-wrapper {
            display: flex;
            height: 100%;
        }
        
        .login-image {
            flex: 1;
            background: url('<?php echo !empty($login_image) ? '../'.$login_image : ''; ?>') center/cover no-repeat;
            position: relative;
        }
        
        .login-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
        }
        
        .login-image-content {
            position: absolute;
            bottom: 60px;
            left: 60px;
            color: white;
            max-width: 500px;
        }
        
        .login-image-content h2 {
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        
        .login-image-content p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .login-form-container {
            width: 480px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 60px;
        }
        
        .login-header {
            margin-bottom: 40px;
        }
        
        .login-header h1 {
            font-size: 24px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .login-header p {
            font-size: 14px;
            color: #718096;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #4a5568;
            font-weight: 500;
        }
        
        .form-control {
            width: 200%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            font-size: 15px;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            border-color: #4299e1;
            box-shadow: 0 0 0 2px rgba(66, 153, 225, 0.2);
            outline: none;
        }
        
        .error-message {
            color: #e53e3e;
            background-color: #fff5f5;
            padding: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            border: 1px solid #fed7d7;
        }
        
        .captcha-container {
            margin: 25px 0;
        }
        
        .captcha-box {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .captcha-image-wrapper {
            position: relative;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            width: 180px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
        }
        
        .captcha-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .captcha-refresh-text {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            text-align: center;
            font-size: 12px;
            padding: 3px 0;
        }
        
        .captcha-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            font-size: 15px;
            transition: all 0.2s;
            box-sizing: border-box;
        }
        
        .captcha-input:focus {
            border-color: #4299e1;
            box-shadow: 0 0 0 2px rgba(66, 153, 225, 0.2);
        }
        
        .btn-login {
            display: block;
            width: 100%;
            padding: 14px;
            background: #4299e1;
            color: white;
            border: none;
            font-size: 16px;
            font-weight: 500;
            cursor: button;
            transition: all 0.2s;
            margin-top: 30px;
        }
        
        .btn-login:hover {
            background: #3182ce;
        }
        
        .form-footer {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
            color: #718096;
        }
        
        .form-footer a {
            color: #4299e1;
            text-decoration: none;
            font-weight: 500;
            margin-left: 5px;
        }
        
        .form-footer a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 992px) {
            .login-wrapper {
                flex-direction: column;
            }
            
            .login-image {
                display: none;
            }
            
            .login-form-container {
                width: 100%;
                padding: 40px;
            }
        }
        
        @media (max-width: 576px) {
            .login-form-container {
                padding: 30px;
            }
            
            .captcha-box {
                flex-direction: column;
            }
            
            .captcha-image-wrapper {
                width: 100%;
                margin-bottom: 10px;
            }
            
            .captcha-input {
                width: 100%;
            }
        }
        
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-image">
            <div class="login-image-content">
                <h2><?php echo htmlspecialchars($login_title); ?></h2>
                <p><?php echo htmlspecialchars($login_desc); ?></p>
            </div>
        </div>
        
        <div class="login-form-container">
            <div class="login-header">
                <h1>登录您的账户</h1>
                <p>请输入您的凭证以访问系统</p>
            </div>
            
            <?php if (isset($error)) { ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php } ?>
            
            <form action="" method="post">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="请输入用户名" required>
                </div>
                
                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="请输入密码" required>
                </div>
                
                <div class="form-group captcha-container">
                    <label>验证码</label>
                    <div class="captcha-box">
                        <div class="captcha-image-wrapper">
                            <img src="../function/captcha.php" 
                                onclick="this.src='../function/captcha.php?'+Math.random()" 
                                alt="验证码" 
                                class="captcha-image">
                            
                        </div>
                        <input type="text" name="captcha" placeholder="请输入验证码" required class="captcha-input">
                    </div>
                </div>
                
                <button type="submit" class="btn-login">登 录</button>
            </form>
            
            <div class="form-footer">
                还没有账户? <a href="register.php">立即注册</a>
            </div>
        </div>
    </div>
    
    <?php include '.././common/footer.php'; ?>
</body>
</html>
