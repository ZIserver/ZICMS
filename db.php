<?php
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

