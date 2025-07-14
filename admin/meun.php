<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>菜单管理 - 后台系统</title>
    <style>
        :root {
            --primary: #0d6efd;
            --secondary: #495057;
            --success: #0dcaf0;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #0dcaf0;
            --light: #f8f9fa;
            --dark: #212529;
            --text-color: #495057;
            --bg-color: #f8f9fa;
            --border-color: #e9ecef;
            --shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.075);
        }
        body {
            font-family: sans-serif;
        }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: var(--shadow);
            margin-bottom: 3rem;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 1rem;
        }
        label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--secondary);
            font-size: 0.9em;
        }
        input[type="text"],
        input[type="number"] {
            padding: 0.8rem;
            border: 2px solid var(--border-color);
            border-radius: 0.3rem;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(13,110,253,0.25);
        }
        .btn {
            background: var(--primary);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 0.3rem;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn:hover {
            background: darken(var(--primary), 10%);
        }
        .table-container {
            background: white;
            border-radius: 0.5rem;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-top: 2rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }
        th, td {
            text-align: left;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        th {
            background: var(--light);
            font-weight: 600;
            color: var(--secondary);
        }
        tr:hover {
            background: var(--light);
        }
        .status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }
        .status-indicator.visible {
            background: var(--success);
        }
        .status-indicator.hidden {
            background: var(--warning);
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .btn-outline {
            background: transparent;
            border: 1px solid var(--secondary);
            color: var(--secondary);
        }
        .btn-outline:hover {
            background: var(--secondary);
            color: white;
        }
    </style>
</head>
<body>
<?php
require_once '.././db.php';
require_once '.././function/functions.php';
require_once '.././install/config.php';
require_once 'admin_header.php';

// 生成CSRF令牌
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isLoggedIn() || !isAdmin($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF验证
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF验证失败！");
    }

    // 处理删除操作
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        handleDelete($conn);
    }
    // 处理新增操作
    else {
        handleCreate($conn);
    }
}

function handleCreate($conn) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $_POST['name'];
        $url = $_POST['url'];
        $order = $_POST['order'];
        $visible = isset($_POST['visible']) ? 1 : 0;
        // 使用 MySQLi 预处理语句
        $sql = "INSERT INTO menu (name, url, `order`, visible) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $name, $url, $order, $visible);
        $stmt->execute();
        $stmt->close();
    }
}

function handleDelete($conn) {
    if (!isset($_POST['id'])) {
        die("无效请求");
    }

    $id = (int)$_POST['id'];
    try {
        $conn->begin_transaction();
        $sql = "DELETE FROM menu WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("删除失败: " . $e->getMessage());
        die("删除失败：数据库错误");
    }
}

// 获取所有菜单项
$sql = "SELECT * FROM menu ORDER BY `order` ASC";
$result = $conn->query($sql);
$menuItems = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container">
    <div class="header">
        <h1 style="font-size: 24px; margin-bottom: 1.5rem; color: var(--dark);">
            <svg style="width:2em;height:2em;fill:currentColor" viewBox="0 0 24 24">
                <path d="M3 5v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2zm16 14H5V5h14v14zM11 7h2v2h-2V7zm0 4h2v6h-2v-6z"/>
            </svg>
            菜单管理
        </h1>
    </div>
    
    <div class="form-container">
        <h2 style="margin-top:0; margin-bottom:1.5rem; font-size: 18px; color:var(--dark);">
            <svg style="width:1.5em;height:1.5em;fill:currentColor" viewBox="0 0 24 24">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            添加新菜单
        </h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">菜单名称</label>
                    <input type="text" name="name" id="name" placeholder="输入菜单名称" required>
                </div>
                <div class="form-group">
                    <label for="url">链接地址</label>
                    <input type="text" name="url" id="url" placeholder="输入URL路径" required>
                </div>
                <div class="form-group">
                    <label for="order">显示顺序</label>
                    <input type="number" name="order" id="order" min="0" value="0" required>
                </div>
                <div class="form-group">
                    <label for="visible" style="display: flex; align-items: center;">
                        <input type="checkbox" name="visible" id="visible" checked style="margin-right: 8px;">
                        是否显示
                    </label>
                </div>
            </div>
            <button type="submit" class="btn">保存菜单</button>
        </form>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>菜单名称</th>
                    <th>链接地址</th>
                    <th>显示顺序</th>
                    <th>显示状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($menuItems as $item): ?>
                    <tr>
                        <td><?= $item['id'] ?></td>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><code><?= htmlspecialchars($item['url']) ?></code></td>
                        <td><?= $item['order'] ?></td>
                        <td>
                            <span class="status-indicator <?= $item['visible'] ? 'visible' : 'hidden' ?>"></span>
                            <?= $item['visible'] ? '显示' : '隐藏' ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <form method="post" onsubmit="return confirm('确定要删除该菜单项吗？')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <button type="submit" class="btn btn-outline">
                                        删除
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
