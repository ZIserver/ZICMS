<?php
session_start();
require_once '../db.php'; // 假设这是数据库连接文件

// 配置信息 - 建议从配置文件或环境变量加载
define('CLIENT_ID', 'app_1234567890abcdef');
define('CLIENT_SECRET', 'secret_abcdef1234567890');
define('REDIRECT_URI', 'https://example.com/callback.php');
define('TOKEN_URL', 'https://your-oauth-server.com/oauth/token.php');
define('USER_INFO_URL', 'https://your-oauth-server.com/api/user.php');

// 安全验证
try {
    // 验证 CSRF 令牌
    if (empty($_GET['state']) || $_GET['state'] !== $_SESSION['state']) {
        throw new Exception('Invalid state parameter', 400);
    }
    
    // 验证授权码
    if (empty($_GET['code'])) {
        throw new Exception('Authorization code missing', 400);
    }
    
    // 清除已使用的 state
    unset($_SESSION['state']);
    
    // 步骤 1: 使用授权码获取访问令牌
    $tokenData = fetchAccessToken($_GET['code']);
    
    // 步骤 2: 使用访问令牌获取用户信息
    $userData = fetchUserInfo($tokenData['access_token']);
    
    // 调试：打印接收到的用户数据
    error_log("User data received: " . json_encode($userData));
    
    // 步骤 3: 处理用户登录或注册逻辑
    processUserLogin($userData, $tokenData);
    
    // 步骤 4: 重定向到应用首页或目标页面
    header('Location: /dashboard.php');
    exit;
    
} catch (Exception $e) {
    // 记录错误日志
    error_log("OAuth callback error: " . $e->getMessage());
    
    // 显示友好的错误页面
    http_response_code($e->getCode() ?: 500);
    echo();
    exit;
}

/**
 * 使用授权码获取访问令牌
 * @param string $authCode 授权码
 * @return array 包含访问令牌的数组
 * @throws Exception 如果请求失败或响应无效
 */
function fetchAccessToken($authCode) {
    $data = [
        'grant_type' => 'authorization_code',
        'code' => $authCode,
        'client_id' => CLIENT_ID,
        'client_secret' => CLIENT_SECRET,
        'redirect_uri' => REDIRECT_URI,
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'ignore_errors' => true, // 获取错误响应内容
        ],
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents(TOKEN_URL, false, $context);
    
    // 检查响应状态
    $httpResponse = $http_response_header[0] ?? '';
    if (strpos($httpResponse, '200') === false || $response === false) {
        $errorMsg = $response ? json_decode($response, true)['error'] ?? 'Unknown error' : 'Network error';
        throw new Exception("获取访问令牌失败: $errorMsg", 500);
    }
    
    $tokenData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($tokenData['access_token'])) {
        throw new Exception('无效的令牌响应格式: ' . json_last_error_msg(), 500);
    }
    
    return $tokenData;
}

/**
 * 使用访问令牌获取用户信息
 * @param string $accessToken 访问令牌
 * @return array 用户信息
 * @throws Exception 如果请求失败或响应无效
 */
function fetchUserInfo($accessToken) {
    $options = [
        'http' => [
            'method'  => 'GET',
            'header'  => "Authorization: Bearer $accessToken\r\n",
            'ignore_errors' => true, // 获取错误响应内容
        ],
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents(USER_INFO_URL, false, $context);
    
    // 检查响应状态
    $httpResponse = $http_response_header[0] ?? '';
    if (strpos($httpResponse, '200') === false || $response === false) {
        $errorMsg = $response ? json_decode($response, true)['error'] ?? 'Unknown error' : 'Network error';
        throw new Exception("获取用户信息失败: $errorMsg", 500);
    }
    
    $userData = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || !isset($userData['id'])) {
        throw new Exception('无效的用户信息格式: ' . json_last_error_msg(), 500);
    }
    
    return $userData;
}

/**
 * 处理用户登录或注册逻辑
 * @param array $userData 用户信息
 * @param array $tokenData 令牌信息
 * @throws Exception 如果数据库操作失败
 */
function processUserLogin($userData, $tokenData) {
    global $conn; // 假设 $conn 是数据库连接对象
    
    try {
        // 检查用户是否已存在
        $stmt = $conn->prepare("SELECT * FROM users WHERE oauth_id = ?");
        $stmt->execute([$userData['id']]);
        
        if ($stmt->rowCount() > 0) {
            // 用户已存在，更新信息
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $conn->prepare("
                UPDATE users 
                SET username = ?, email = ?, access_token = ?, expires_at = ? 
                WHERE id = ?
            ");
            
            $expiresAt = time() + $tokenData['expires_in'];
            $stmt->execute([
                $userData['username'] ?? $user['username'],
                $userData['email'] ?? $user['email'],
                $tokenData['access_token'],
                date('Y-m-d H:i:s', $expiresAt),
                $user['id']
            ]);
            
            $userId = $user['id'];
        } else {
            // 用户不存在，创建新记录
            $stmt = $conn->prepare("
                INSERT INTO users (oauth_id, username, email, access_token, expires_at, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $expiresAt = time() + ($tokenData['expires_in'] ?? 3600); // 默认1小时
            $stmt->execute([
                $userData['id'],
                $userData['username'] ?? 'user_' . substr(md5($userData['id']), 0, 8),
                $userData['email'] ?? '',
                $tokenData['access_token'],
                date('Y-m-d H:i:s', $expiresAt)
            ]);
            
            $userId = $conn->lastInsertId();
        }
        
        // 设置用户会话
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $userData['username'] ?? '未命名用户';
        $_SESSION['email'] = $userData['email'] ?? '';
        $_SESSION['oauth_id'] = $userData['id'];
        $_SESSION['access_token'] = $tokenData['access_token'];
        $_SESSION['token_expires'] = $expiresAt;
        $_SESSION['login_time'] = time();
        
        // 记录登录日志
        $stmt = $conn->prepare("
            INSERT INTO login_logs (user_id, ip_address, user_agent, login_time)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $_SERVER['REMOTE_ADDR'],
            substr($_SERVER['HTTP_USER_AGENT'], 0, 255)
        ]);
        
    } catch (PDOException $e) {
        throw new Exception("数据库操作失败: " . $e->getMessage(), 500);
    }
}