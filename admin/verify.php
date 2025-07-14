<?php
require_once '.././db.php';

if (empty($_GET['token'])) die("无效请求");

$token = clean_input($_GET['token']);

try {
    $pdo = new PDO("mysql:host=".db_host.";dbname=".db_name, db_user, db_pass);
    $stmt = $pdo->prepare("SELECT id, token_expires FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    
    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch();
        if (strtotime($user['token_expires']) > time()) {
            // 验证成功
            $pdo->exec("UPDATE users SET 
                email_verified = 1,
                verification_token = NULL,
                token_expires = NULL
                WHERE id = {$user['id']}");
            
            $_SESSION['verify_success'] = true;
            header('Location: login.php');
        } else {
            header("Location: resend_verification.php?expired=1&token=".$token);
        }
    } else {
        die("无效的验证链接");
    }
} catch (PDOException $e) {
    die("数据库错误: ".$e->getMessage());
}
?>
