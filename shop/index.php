<?php session_start();
require_once '../common/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /admin/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商城系统</title>
    <style>
        /* 全局样式 */
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: white;
            color: #333;
            display: flex;
            flex-direction: row;
        }

        /* 容器样式 */
        .container {
            display: flex;
            flex-direction: row;
            background-color: white;
            position : absolute;
        }

        /* 侧边栏样式 */
        .sidebar {
            width: 280px;
            background-color: #fff;
            border-right: 1px solid #e0e0e0;
            padding: 20px;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar h2 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #007BFF;
        }

        .cart-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f0f4f8;
        }

        .cart-item img {
            width: 60px;
            height: 60px;
            margin-right: 10px;
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-title {
            font-size: 16px;
            margin: 0;
        }

        .cart-item-price {
            font-size: 14px;
            color: #666;
        }

        .cart-item-quantity {
            font-size: 14px;
            color: #666;
        }

        .cart-item-remove {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 12px;
        }

        /* 主内容区域样式 */
        .main-content {
            flex: 1;
            padding: 20px;
        }

        .title {
            text-align: center;
            margin-bottom: 30px;
            color: #007BFF;
            font-size: 36px;
            font-weight: 600;
        }

        .product-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            gap: 20px;
        }

        .product {
            background-color: #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
            width: 250px;
            text-align: center;
            padding: 20px;
            cursor: pointer;
        }

        .product:hover {
            transform: scale(1.05);
        }

        .product img {
            width: 100%;
            height: auto;
        }

        .product h3 {
            font-size: 20px;
            margin: 10px 0;
            color: #333;
            font-weight: 500;
        }

        .product p {
            font-size: 16px;
            color: #666;
        }

        .product button {
            background-color: #007BFF;
            color: white;
            border: none;
            padding: 12px 24px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            margin-top: 10px;
        }

        .product button:hover {
            background-color: #0056b3;
        }

        /* 固定结算按钮样式 */
        .checkout-container {
            
            bottom: 20px;
            right: 20px;
            z-index: 999; /* 确保结算按钮在其他元素之上 */
            background-color: #fff;
            padding: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            float: left;
            
        }

        /* 响应式设计 */
        @media (max-width: 1024px) {
            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }

            .main-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- 页面容器 -->
    <div class="container">
        <!-- 侧边栏 -->
        <div class="sidebar">
            <h2>购物车</h2>
            <p>购买完成之后，请找站长发货</p>
            <div id="cart-items"></div>
            <div class="checkout-container">
            <a href="./jiesuan.php"><button id="checkout-btn" style="background-color: #007BFF; color: white; border: none; padding: 10px 20px; cursor: pointer;">去结算</button></a>
        </div>
        </div>

        <!-- 主内容区域 -->
        <div class="main-content">
            <h1 class="title">商品列表</h1>
            <a href="./orders.php"><button id="checkout-btn" style="background-color: #007BFF; color: white; border: none; padding: 10px 20px; cursor: pointer;">账单详情</button></a>
            <div>站长QQ:<?= htmlspecialchars($currentConfig['qq']); ?></div>
            <div class="product-list" id="products"></div>
        </div>
    </div>

    <!-- 固定在底部的结算按钮 -->
    

    <!-- 购物车成功提示 -->


    <script>
        // 全局变量
        const userId = <?= $_SESSION["user_id"] ?>; // 替换为实际的用户 ID

        // 初始化应用
        initApp();

        // 初始化应用
        function initApp() {
            loadProducts();
            loadCartItems();
        }

        // 加载商品列表
        function loadProducts() {
            fetch('./get.php')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        renderProducts(data.products);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // 渲染商品列表
        function renderProducts(products) {
            const productsDiv = document.getElementById('products');
            productsDiv.innerHTML = ''; // 清空原有内容

            products.forEach(product => {
                const productDiv = document.createElement('div');
                productDiv.className = 'product';
                productDiv.innerHTML = `
                    <img src="/${product.image}" alt="${product.title}">
                    <h3>${product.title}</h3>
                    <p>价格: ${product.price} 元</p>
                    <p>库存: ${product.stock}</p>
                    <button onclick="addToCart(${userId}, ${product.id}, 1)">加入购物车</button>
                `;
                productsDiv.appendChild(productDiv);
                loadCartItems();
            });
        }

        // 加载购物车商品
        function loadCartItems() {
            fetch('./get_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ user_id: userId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    renderCartItems(data.cart_items);
                } else {
                    alert(data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // 渲染购物车商品
        function renderCartItems(cartItems) {
    const cartItemsDiv = document.getElementById('cart-items');
    cartItemsDiv.innerHTML = ''; // 清空原有内容
    
    cartItems.forEach(item => {
        // 检查 item.image 是否存在
        if (!item.image) {
            console.error('item.image is undefined:', item);
            return;
        }

        const cartItemDiv = document.createElement('div');
        cartItemDiv.className = 'cart-item';
        cartItemDiv.innerHTML = `
            <img src="/${item.image}" alt="${item.title}">
            <div class="cart-item-details">
                <p class="cart-item-title">${item.title}</p>
                <p class="cart-item-price">价格: ${item.price} 元</p>
                <p class="cart-item-quantity">数量: ${item.quantity}</p>
            </div>
            <button onclick="removeFromCart(${userId}, ${item.product_id})">移除</button>
        `;
        cartItemsDiv.appendChild(cartItemDiv);
        
    });
}

function removeFromCart(userId, productId) {
    const data = {
        user_id: userId,
        product_id: productId
    };

    fetch('./remove_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            // 更新购物车列表
            loadCartItems();
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}



        // 添加到购物车
        function addToCart(userId, productId, quantity) {
            const data = {
                user_id: userId,
                product_id: productId,
                quantity: quantity
            };

            fetch('./update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    loadCartItems();
                    // 显示购物车成功提示
                    showSuccessToast('商品已成功加入购物车！');
                    // 更新购物车列表
                    loadCartItems();
                } else {
                    alert(data.message);
                    loadCartItems();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // 从购物车移除商品
        

        // 搜索商品
        function searchProducts() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase();
            const products = document.querySelectorAll('.product');
            products.forEach(product => {
                const title = product.querySelector('h3').textContent.toLowerCase();
                if (title.includes(searchInput)) {
                    product.style.display = 'block';
                } else {
                    product.style.display = 'none';
                }
            });
        }

        // 显示成功提示
        function showSuccessToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 2000);
        }
    </script>
</body>
<?php require_once '../common/footer.php'?>
</html>
