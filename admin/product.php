<?php

// product.php
session_start();
// 确保在任何输出之前调用 header() 函数

// 验证管理员权限
// TODO: 添加管理员权限验证逻辑
// 处理AJAX请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['status' => 'error', 'message' => ''];
    try {
        // 删除商品
        if (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $productId = (int)$_POST['id'];
            
            // 获取图片路径
            $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            if ($result = $stmt->get_result()) {
                if ($row = $result->fetch_assoc()) {
                    $imagePath = "../uploads/products/" . $row['image'];
                    if (file_exists($imagePath) && !empty($row['image'])) unlink($imagePath);
                }
            }
            // 删除记录
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            
            $response['status'] = 'success';
            $response['message'] = '商品删除成功';
        }
        // 添加商品
        if (isset($_POST['action']) && $_POST['action'] === 'add') {
            $title = trim($_POST['title']);
            $price = (float)$_POST['price'];
            $stock = (int)$_POST['stock'];
            $description = trim($_POST['description']);
            $imagePath = '';
            // 处理文件上传
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png'];
                $fileType = mime_content_type($_FILES['image']['tmp_name']);
                
                if (in_array($fileType, $allowedTypes)) {
                    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = 'product_'.time().'.'.$ext;
                    $targetPath = "../uploads/products/".$filename;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $imagePath = 'uploads/products/'.$filename;
                    }
                } else {
                    $response['message'] = '文件类型不正确，只允许上传 JPEG 或 PNG 格式的图片';
                    exit(json_encode($response));
                }
            }
            // 插入数据库
            $stmt = $conn->prepare("INSERT INTO products (title, price, stock, description, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sdiss', $title, $price, $stock, $description, $imagePath);
            $stmt->execute();
            $newId = $stmt->insert_id;
            // 生成新商品行HTML
            $product = $conn->query("SELECT * FROM products WHERE id = $newId")->fetch_assoc();
            $response['html'] = generateProductRow($product);
            $response['status'] = 'success';
            $response['message'] = '商品添加成功';
            echo("<script>window.location.reload();</script>");
        }
        // 更新商品
        if (isset($_POST['action']) && $_POST['action'] === 'update') {
            $productId = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $price = (float)$_POST['price'];
            $stock = (int)$_POST['stock'];
            $description = trim($_POST['description']);
            $imagePath = $_POST['oldImage'] ?? ''; // 默认保留原有图片路径
            // 处理文件上传
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png'];
                $fileType = mime_content_type($_FILES['image']['tmp_name']);
                
                if (in_array($fileType, $allowedTypes)) {
                    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = 'product_' . time() . '.' . $ext;
                    $targetPath = "../uploads/products/" . $filename;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                        $imagePath = 'uploads/products/' . $filename;
                        
                        // 删除旧图片
                        if ($_POST['oldImage']) {
                            $oldImagePath = "../uploads/products/" . $_POST['oldImage'];
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }
                    }
                } else {
                    $response['message'] = '文件类型不正确，只允许上传 JPEG 或 PNG 格式的图片';
                    exit(json_encode($response));
                }
            }
            // 更新数据库
            $stmt = $conn->prepare("UPDATE products SET title = ?, price = ?, stock = ?, description = ?, image = ? WHERE id = ?");
            $stmt->bind_param('sdissi', $title, $price, $stock, $description, $imagePath, $productId);
            $stmt->execute();
            $response['status'] = 'success';
            $response['message'] = '商品信息已更新';
            
            // 生成更新后的商品行HTML
            $product = $conn->query("SELECT * FROM products WHERE id = $productId")->fetch_assoc();
            $response['html'] = generateProductRow($product);
        }
        // 获取商品信息
        if (isset($_POST['action']) && $_POST['action'] === 'getProduct') {
            $productId = (int)$_POST['id'];
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();
            if ($product) {
                $response['status'] = 'success';
                $response['product'] = $product;
            } else {
                $response['message'] = '未找到商品';
            }        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    exit(json_encode($response));
}
// 生成商品行HTML
function generateProductRow($product) {
    return '
    <tr data-id="'.$product['id'].'">
        <td class="align-middle">
            <div class="d-flex align-items-center">
                '.($product['image'] ? '<img src="../uploads/products/'.$product['image'].'" class="product-thumb me-3" alt="'.$product['title'].'">' : '').'
                <div>
                    <div class="fw-500">'.htmlspecialchars($product['title']).'</div>
                    <small class="text-muted">'.htmlspecialchars($product['description']).'</small>
                </div>
            </div>
        </td>
        <td class="align-middle">¥'.number_format($product['price'], 2).'</td>
        <td class="align-middle">
            <span class="badge rounded-pill bg-'.($product['stock'] > 0 ? 'success' : 'danger').'">
                '.$product['stock'].'
            </span>
        </td>
        <td class="align-middle">
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary edit-btn" data-bs-toggle="modal" data-bs-target="#editModal" data-id="'.$product['id'].'">编辑</button>
                <button class="btn btn-sm btn-outline-danger delete-btn">删除</button>
            </div>
        </td>
    </tr>';
}
// 分页查询
$perPage = 1145145141151414;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;
$products = $conn->query("SELECT * FROM products ORDER BY id DESC LIMIT $offset, $perPage");
$totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>商品管理系统</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
            font-family: sans-serif;
        }
:root {
    --primary-color: #2c3e50;
    --secondary-color: #34495e;
    --accent-color: #3498db;
}
.management-card {
    border: 1px solid #e0e0e0;
    border-radius: 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    background: #fff;
    margin-bottom: 2rem;
}
.collapse-form {
    border: 1px solid #e0e0e0;
    border-top: none;
    border-radius: 0;
}
.table-custom {
    border-collapse: separate;
    border-spacing: 0 8px;
    margin: -8px 0;
}
.table-custom thead th {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 12px 20px;
}
.table-custom tbody tr {
    background: white;
    transition: all 0.2s ease;
    border: 1px solid #eee;
}
.table-custom tbody tr:hover {
    box-shadow: 0 3px 8px rgba(0,0,0,0.08);
}
.btn-prime {
    background: var(--accent-color);
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 2px;
    transition: all 0.2s ease;
}
.btn-prime:hover {
    background: #2980b9;
}
.product-thumb {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border: 1px solid #eee;
}
</style>
</head>
<body class="bg-light">
<div class="container py-4">
    <!-- 添加商品 -->
    <div class="management-card">
        <div class="card-header bg-white border-0 py-3">
            <button class="btn btn-prime d-flex align-items-center" 
                    type="button" 
                    data-bs-toggle="collapse" 
                    data-bs-target="#addForm">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-plus me-2" viewBox="0 0 16 16">
                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                </svg>
                添加新商品
            </button>
        </div>
        
        <div class="collapse collapse-form" id="addForm">
            <div class="card-body pt-0">
                <form id="addProductForm" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">商品名称</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">价格（元）</label>
                            <input type="number" name="price" step="0.01" class="form-control" required>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">库存数量</label>
                            <input type="number" name="stock" class="form-control" value="0" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">商品描述</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">商品图片</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-prime">
                                提交添加
                            </button>
                        </div>
                    </div>
                </form>            </div>
        </div>
    </div>
    <!-- 商品列表 -->
    <div class="management-card">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0">商品列表</h5>
        </div>
        
        <div class="card-body p-0">
            <table class="table table-custom">
                <thead>
                    <tr>
                        <th style="width:40%">商品信息</th>
                        <th>价格</th>
                        <th>库存</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody id="productList">
                    <?php 
                    if ($products) {
                        while($product = $products->fetch_assoc()): ?>
                        <tr data-id="<?= $product['id'] ?>">
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <?php if($product['image']): ?>
                                    <img src="/<?= $product['image'] ?>" class="product-thumb me-3" alt="<?= htmlspecialchars($product['title']) ?>">
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-500"><?= htmlspecialchars($product['title']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($product['description']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle">¥<?= number_format($product['price'], 2) ?></td>
                            <td class="align-middle">
                                <span class="badge rounded-pill bg-<?= $product['stock'] > 0 ? 'success' : 'danger' ?>">
                                    <?= $product['stock'] ?>
                                </span>
                            </td>
                            <td class="align-middle">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-secondary edit-btn" data-bs-toggle="modal" data-bs-target="#editModal" data-id="<?= $product['id'] ?>">编辑</button>
                                    <button class="btn btn-sm btn-outline-danger delete-btn">删除</button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; 
                    } else {
                        echo "<tr><td colspan='4'>暂无商品</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
            
            <!-- 分页 -->
            <div class="p-3 border-top">
                <nav>
                    <ul class="pagination justify-content-end mb-0">
<?php
if ($totalProducts > 0) {
    for ($i = 1; $i <= ceil($totalProducts / $perPage); $i++): ?>
        <li class="page-item <?= $page == $i ? 'active' : '' ?>">
            <a class="page-link" href="admin/?page=product&p=<?= $i ?>"><?= $i ?></a>
        </li>
    <?php endfor;
}
?>


                    </ul>
                </nav>
            </div>
        </div>
    </div>
    <!-- 商品编辑模态框 -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">编辑商品</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editProductForm" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">商品名称</label>
                                <input type="text" name="title" id="title" class="form-control" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">价格（元）</label>
                                <input type="number" name="price" id="price" step="0.01" class="form-control" required>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">库存数量</label>
                                <input type="number" name="stock" id="stock" class="form-control" value="0" required>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">商品描述</label>
                                <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">商品图片</label>
                                <div class="d-flex align-items-center">
                                    <img id="productImage" class="product-thumb me-3" alt="商品图片">
                                    <input type="file" name="image" id="image" class="form-control" accept="image/*">
                                    <input type="hidden" name="oldImage" id="oldImage">
                                </div>
                            </div>
                            
                            <input type="hidden" id="productId" name="id">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="button" id="saveEdit" class="btn btn-prime">保存更改</button>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// 添加商品
document.getElementById('addProductForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'add');
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            document.querySelector('#addForm').classList.remove('show');
            this.reset();
            document.getElementById('productList').insertAdjacentHTML('afterbegin', data.html);
            alert(data.message);
        } else {
            alert(data.message);
        }
    })
    .catch(err => console.error('Error:', err));
});
// 删除商品
document.getElementById('productList').addEventListener('click', e => {
    if (e.target.closest('.delete-btn') && confirm('确定删除该商品？')) {
        const row = e.target.closest('tr');
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=delete&id=${row.dataset.id}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                row.remove();
                alert(data.message);
            } else {
                alert(data.message);
            }
        })
        .catch(err => console.error('Error:', err));
        window.location.reload();
    }
});
// 编辑商品 - 获取商品信息
document.getElementById('productList').addEventListener('click', e => {
    if (e.target.classList.contains('edit-btn')) {
        const productId = e.target.dataset.id;
        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `action=getProduct&id=${productId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                const product = data.product;
                document.getElementById('productId').value = product.id;
                document.getElementById('title').value = product.title;
                document.getElementById('price').value = product.price;
                document.getElementById('stock').value = product.stock;
                document.getElementById('description').value = product.description;
                document.getElementById('productImage').src = product.image ? `../uploads/products/${product.image}` : '';
                document.getElementById('oldImage').value = product.image || '';
                // 显示模态框
                const editModal = new bootstrap.Modal(document.getElementById('editModal'));
                editModal.show();
            } else {
                alert(data.message);
            }
        })
        .catch(err => console.error('Error:', err));
    }
});
// 编辑商品 - 保存更改
document.getElementById('saveEdit').addEventListener('click', function() {
    const formData = new FormData(document.getElementById('editProductForm'));
    formData.append('action', 'update');
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            // 更新商品列表
            const row = document.querySelector(`tr[data-id="${data.product.id}"]`);
            row.outerHTML = data.html;
            // 关闭模态框
            const editModal = bootstrap.Modal.getInstance(document.getElementById('editModal'));
            editModal.hide();
            alert(data.message);
        } else {
            alert(data.message);
        }
    })
    .catch(err => console.error('Error:', err));
});
</script>
</body>
</html>