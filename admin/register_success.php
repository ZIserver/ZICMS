<?php
session_start();
if (!isset($_SESSION['reg_success'])) {
    header('Location: register.php');
    exit;
}

$email = $_SESSION['reg_email'] ?? '';
unset($_SESSION['reg_success'], $_SESSION['reg_email']);
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        .email-notice {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 10px;
            text-align: center;
        }
        .email-icon {
            font-size: 60px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="email-notice">
        <div class="email-icon">✉️</div>
        <h2>验证邮件已发送</h2>
        <p>我们已向 <strong><?= htmlspecialchars($email) ?></strong> 发送了验证链接</p>
        <p>请检查您的收件箱并点击链接完成注册</p>
        <p>没收到邮件？<a href="resend_verification.php?email=<?= urlencode($email) ?>">重新发送</a></p>
    </div>
</body>
</html>
