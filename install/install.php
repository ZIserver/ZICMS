<?php
session_start(); // 确保 session_start() 在最顶部调用

require 'functions.php'; // 引入自定义功能
//include '.././auth/index.php';
// 检查是否已安装
if (file_exists('install.lock')) {
    header('Location: ../index.php'); // 如果已安装，重定向到首页
    exit;
}

// 初始化环境检测结果
$env_check = [
    'php_version' => version_compare(PHP_VERSION, '7.0.0', '>='),
    'file_permissions' => [], // 存储详细的文件权限信息
    'database_extension' => extension_loaded('pdo_mysql'),
    'fileinfo_extension' => extension_loaded('fileinfo'),
    'exif_extension' => extension_loaded('exif'),
];

// 详细的文件权限检测
$directories = ['.', 'config.php', 'install.sql']; // 要检查的目录和文件
foreach ($directories as $dir) {
    $is_writable = is_writable($dir);
    $env_check['file_permissions'][$dir] = [
        'writable' => $is_writable,
        'readable' => is_readable($dir),
        'path' => realpath($dir), // 获取真实路径
    ];
}

// 获取 URL 参数
if (isset($_GET['page'])) {
    $_SESSION['page'] = (int)$_GET['page']; // 强制转换为整数
}

// Page number
$page = isset($_SESSION['page']) ? $_SESSION['page'] : 1;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($page == 2) { // Database and Authcode Submission
        $db_host = $_POST['db_host'];
        $db_port = $_POST['db_port'];
        $db_name = $_POST['db_name'];
        $db_user = $_POST['db_user'];
        $db_pass = $_POST['db_pass'];
        $authcode1 = $_POST['authcode1'];

        $_SESSION['db_host'] = $db_host;
        $_SESSION['db_port'] = $db_port;
        $_SESSION['db_name'] = $db_name;
        $_SESSION['db_user'] = $db_user;
        $_SESSION['db_pass'] = $db_pass;
        $_SESSION['authcode1'] = $authcode1;

        try {
            // 尝试连接数据库
            $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 验证数据库连接
            $env_check['database_connection'] = true;

            // Import SQL
            $sql_file = __DIR__ . '/install.sql';
            if (file_exists($sql_file)) {
                $sql = file_get_contents($sql_file);
                $pdo->exec($sql);
            } else {
                throw new Exception('SQL 文件不存在');
            }

            // Save configuration
            $config_content = <<<EOT
<?php
// 数据库连接信息
if (!defined('db_host')) define('db_host', '$db_host'); // 数据库主机
if (!defined('db_port')) define('db_port', '$db_port'); // 数据库端口
if (!defined('db_name')) define('db_name', '$db_name'); // 数据库名称
if (!defined('db_user')) define('db_user', '$db_user'); // 数据库用户名
if (!defined('db_pass')) define('db_pass', '$db_pass'); // 数据库密码
\$authcode='{$authcode1}';
?>
EOT;
            file_put_contents('config.php', $config_content);

            // Create install lock file
            file_put_contents('install.lock', 'ZICMS系统已安装，请创建名字为admin的账号，使用管理员账号（admin）登录后台更改网站名称');

            $_SESSION['page'] = 3; // Move to the final page
            header('Location: install.php'); // Refresh to show the final page
            exit;
        } catch (PDOException $e) {
            $env_check['database_connection'] = false;
            $error = "数据库连接失败： " . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装ZICMS系统</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #007bff;
            margin-bottom: 30px;
        }
        h2 {
            color: #343a40;
            margin-bottom: 20px;
        }
        .env-check {
            margin-bottom: 30px;
        }
        .env-check h3 {
            color: #555;
            margin-bottom: 15px;
        }
        .env-check ul {
            list-style: none;
            padding: 0;
        }
        .env-check li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .env-check li:last-child {
            border-bottom: none;
        }
        .env-check .status {
            font-weight: bold;
        }
        .env-check .success {
            color: green;
        }
        .env-check .error {
            color: red;
        }
        .form-group {            margin-bottom: 20px;
        }
        label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            margin-top: 10px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .navigation a { /* Changed button to a */
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none; /* Remove underline from links */
            color: white; /* Set default text color for links */
        }
        .navigation .prev {
            background-color: #6c757d;
            color: white;
        }
        .navigation .next {
            background-color: #007bff;
            color: white;
        }
        .navigation .prev:hover {
            background-color: #5a6268;
        }
        .navigation .next:hover {
            background-color: #0056b3;
        }
        /* 详细文件权限样式 */
        .env-check .file-permissions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .env-check .file-permissions-table th,
        .env-check .file-permissions-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .env-check .file-permissions-table th {
            background-color: #f2f2f2;
        }
        .env-check .file-permissions-table .path {
            font-family: monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>欢迎安装ZICMS系统</h1>

        <?php
        if ($page == 1): ?>
            <!-- 环境检测 -->
            <div class="env-check">
                <h2>环境检测</h2>

                <h3>基本环境</h3>
                <ul>
                    <li>
                        PHP版本 (要求 >= 7.0.0):
                        <span class="status <?php echo $env_check['php_version'] ? 'success' : 'error'; ?>">
                            <?php echo $env_check['php_version'] ? '通过' : '未通过'; ?>
                        </span>
                    </li>
                </ul>

                <h3>扩展检测</h3>
                <ul>
                    <li>
                        数据库扩展 (PDO MySQL):
                        <span class="status <?php echo $env_check['database_extension'] ? 'success' : 'error'; ?>">
                            <?php echo $env_check['database_extension'] ? '已安装' : '未安装'; ?>
                        </span>
                    </li>
                    <li>
                        Fileinfo 扩展:
                        <span class="status <?php echo $env_check['fileinfo_extension'] ? 'success' : 'error'; ?>">
                            <?php echo $env_check['fileinfo_extension'] ? '已安装' : '未安装'; ?>
                        </span>
                    </li>
                </ul>

                <h3>目录权限检测</h3>
                <table class="file-permissions-table">
                    <thead>
                        <tr>
                            <th>目录/文件</th>
                            <th>路径</th>
                            <th>可读</th>
                            <th>可写</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($env_check['file_permissions'] as $dir => $permissions): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($dir); ?></td>
                                <td class="path"><?php echo htmlspecialchars($permissions['path']); ?></td>
                                <td>
                                    <span class="status <?php echo $permissions['readable'] ? 'success' : 'error'; ?>">
                                        <?php echo $permissions['readable'] ? '是' : '否'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status <?php echo $permissions['writable'] ? 'success' : 'error'; ?>">
                                        <?php echo $permissions['writable'] ? '是' : '否'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="navigation">
                <div></div>
                <a href="?page=2" class="next">下一步</a>
            </div>
            <?php  ?>

        <?php elseif ($page == 2): ?>
            <!-- 数据库信息填写和导入 -->
            <h2>数据库配置</h2>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form action="" method="post">
                <div class="form-group">
                    <label for="db_host">数据库主机:</label>
                    <input type="text" id="db_host" name="db_host" value="<?php echo isset($_SESSION['db_host']) ? $_SESSION['db_host'] : '127.0.0.1'; ?>" required>
                </div>
                <div class="form-group">
                    <label for="db_port">数据库端口:</label>
                    <input type="text" id="db_port" name="db_port" value="<?php echo isset($_SESSION['db_port']) ? $_SESSION['db_port'] : '3306'; ?>" required>
                </div>
                <div class="form-group">
                    <label for="db_name">数据库名称:</label>
                    <input type="text" id="db_name" name="db_name" value="<?php echo isset($_SESSION['db_name']) ? $_SESSION['db_name'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="db_user">数据库用户名:</label>
                    <input type="text" id="db_user" name="db_user" value="<?php echo isset($_SESSION['db_user']) ? $_SESSION['db_user'] : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label for="db_pass">数据库密码:</label>
                    <input type="password" id="db_pass" name="db_pass" value="<?php echo isset($_SESSION['db_pass']) ? $_SESSION['db_pass'] : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="authcode1">授权码:</label>
                    <input type="text" id="authcode1" name="authcode1" value="<?php echo isset($_SESSION['authcode1']) ? $_SESSION['authcode1'] : ''; ?>">
                </div>
                <div class="navigation">
                    <a href="?page=1" class="prev">上一步</a>
                    <input type="submit" value="安装">
                </div>
            </form>
            <?php  ?>

        <?php elseif ($page == 3): ?>
            <!-- 欢迎使用页面 -->
            <h2>安装完成!</h2>
            <p>ZICMS系统已成功安装。请创建名字为admin的账号，使用管理员账号（admin）登录后台更改网站名称。</p>
            <p><a href="../index.php" class="btn btn-primary">访问首页</a></p>
            <?php session_destroy(); // Clear session data after installation ?>
        <?php endif; ?>
    </div>
</body>
</html>
