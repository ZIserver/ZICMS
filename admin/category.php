<!DOCTYPE html>
<html>
<head>
    <title>分类管理</title>
    <style>
        body {
            font-family: sans-serif;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .form-container {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 4px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"],
        textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        textarea {
            height: 100px;
        }
        button, .button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        button:hover, .button:hover {
            background-color: #3e8e41;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
        .delete-button {
            background-color: #f44336;
        }
        .delete-button:hover {
            background-color: #da190b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>分类管理</h1>

        <?php
        require_once '../db.php'; // 确保路径正确

        $message = '';
        $messageClass = '';

        // 获取当前页面的 URL
        $current_url = htmlspecialchars($_SERVER["PHP_SELF"]) . '?page=category';

        // 处理添加分类
        if (isset($_POST['add_category'])) {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';

            if (empty($name)) {
                $message = '分类名称不能为空。';
                $messageClass = 'error';
            } else {
                $conn = dbConnect();
                $sql = "INSERT INTO categories_art (name, description) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    $message = '准备语句失败: ' . $conn->error;
                    $messageClass = 'error';
                } else {
                    $stmt->bind_param("ss", $name, $description);
                    if ($stmt->execute()) {
                        $message = '分类添加成功!';
                        $messageClass = 'success';
                        // 添加成功后刷新页面
                        echo('<script>window.location.reload();</script>');
                        exit;
                    } else {
                        $messageClass = 'error';
                    }
                    $stmt->close();
                }
                $conn->close();
            }
        }

        // 处理编辑分类
        if (isset($_POST['edit_category'])) {
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';

            if (empty($name)) {
                $message = '分类名称不能为空。';
                $messageClass = 'error';
            } else {
                $conn = dbConnect();
                $sql = "UPDATE categories_art SET name = ?, description = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    $message = '准备语句失败: ' . $conn->error;
                    $messageClass = 'error';
                } else {
                    $stmt->bind_param("ssi", $name, $description, $id);
                    if ($stmt->execute()) {
                        $message = '分类更新成功!';
                        $messageClass = 'success';
                    } else {
                        $message = '更新分类失败: ' . $stmt->error;
                        $messageClass = 'error';
                    }
                    $stmt->close();
                }
                $conn->close();
            }
        }

        // 处理删除分类
        if (isset($_GET['delete'])) {
            $id = $_GET['delete'] ?? 0;

            $conn = dbConnect();
            $sql = "DELETE FROM categories_art WHERE id = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                $message = '准备语句失败: ' . $conn->error;
                $messageClass = 'error';
            } else {
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $message = '分类删除成功!';
                    $messageClass = 'success';
                } else {
                    $message = '删除分类失败: ' . $stmt->error;
                    $messageClass = 'error';
                }
                $stmt->close();
            }
            $conn->close();
        }

        // 获取所有分类 (用于初始加载)
        function getCategories() {
            $conn = dbConnect();
            $sql = "SELECT * FROM categories_art ORDER BY name";
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

        // 生成分类列表 HTML
        function generateCategoryListHTML($categories) {
            global $current_url; // 访问全局变量
            $html = '';
            foreach ($categories as $category):
                $html .= '<tr>
                    <td>' . htmlspecialchars($category['id']) . '</td>
                    <td>' . htmlspecialchars($category['name']) . '</td>
                    <td>' . htmlspecialchars($category['description']) . '</td>
                    <td>
                        <a href="' . $current_url . '&edit=' . htmlspecialchars($category['id']) . '" class="button">编辑</a>
                        <a href="' . $current_url . '&delete=' . htmlspecialchars($category['id']) . '" class="button delete-button" onclick="return confirm(\'确定要删除吗？\')">删除</a>
                    </td>
                </tr>';
            endforeach;
            return $html;
        }

        $categories = getCategories();

        if (!empty($message)) {
            echo '<div class="' . htmlspecialchars($messageClass) . '">' . htmlspecialchars($message) . '</div>';
        }

        ?>

        <!-- 添加分类表单 -->
        <div class="form-container">
            <h2>添加分类</h2>
            <form method="POST" action="<?php echo htmlspecialchars($current_url); ?>">
                <input type="hidden" name="add_category" value="1">
                <label for="name">分类名称:</label>
                <input type="text" id="name" name="name" required>

                <label for="description">描述:</label>
                <textarea id="description" name="description"></textarea>

                <button type="submit">添加</button>
            </form>
        </div>

        <!-- 分类列表 -->
        <h2>分类列表</h2>
        <table id="categoryTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>描述</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php echo generateCategoryListHTML($categories); ?>
            </tbody>
        </table>

        <!-- 编辑分类表单 -->
        <?php if (isset($_GET['edit'])):
            $edit_id = $_GET['edit'];
            $conn = dbConnect();
            $sql = "SELECT * FROM categories_art WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $edit_category = $result->fetch_assoc();
            $conn->close();
            if ($edit_category):
        ?>
            <div class="form-container">
                <h2>编辑分类</h2>
                <form method="POST" action="<?php echo htmlspecialchars($current_url); ?>">
                    <input type="hidden" name="edit_category" value="1">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_category['id']); ?>">
                    <label for="name">分类名称:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_category['name']); ?>" required>

                    <label for="description">描述:</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($edit_category['description']); ?></textarea>

                    <button type="submit">更新</button>
                    <a href="<?php echo htmlspecialchars($current_url); ?>" class="button">取消</a>
                </form>
            </div>
        <?php endif; endif; ?>
    </div>

    <script>
        // 移除 AJAX 相关的 JavaScript 代码
    </script>
</body>
</html>
