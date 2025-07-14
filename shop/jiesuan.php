<!DOCTYPE html>
<html>
<head>
    <title>结算页面</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f8f8f8;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        /* 矩形容器样式 */
        #cart-container {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            margin: 0 auto;
            max-width: 900px; /* 调整最大宽度 */
            background-color: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        #cart-items {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .cart-item {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 10px;
            width: 250px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out;
        }

        .cart-item:hover {
            transform: translateY(-3px);
        }

        .cart-item img {
            width: 100%;
            height: auto;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .cart-item h3 {
            font-size: 1.2em;
            margin-bottom: 5px;
            color: #333;
        }

        .cart-item p {
            margin: 5px 0;
            color: #666;
        }

        .total {
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
            font-size: 1.2em;
            color: #333;
        }

        .checkout-button {
            display: block;
            width: 200px;
            margin: 10px auto; /* 调整按钮间距 */
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            transition: background-color 0.3s ease;
        }

        .checkout-button:hover {
            background-color: #45a049;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-top: 20px;
        }

        .success-message {
            color: green;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>结算页面</h1>

    <!-- 矩形容器 -->
    <div id="cart-container">
        <div id="cart-items">
            <!-- 购物车商品将在这里动态生成 -->
        </div>

        <div class="total">
            总价: <span id="total-price">0.00</span>
        </div>
    </div>

    <div id="error-message" class="error-message"></div>
    <div id="success-message" class="success-message"></div>

    <button class="checkout-button" onclick="checkout('alipay')">支付宝支付</button>
    <button class="checkout-button" onclick="checkout('wechat')">微信支付</button>

    <script>
        let cartDataGlobal = []; // 保存购物车数据

        async function loadCartItems() {
            try {
                const response = await fetch('./check.php');
                const cartData = await response.json();

                cartDataGlobal = cartData; // 保存购物车数据

                const cartItemsDiv = document.getElementById('cart-items');
                let totalPrice = 0;

                if (cartData.error) {
                    document.getElementById('error-message').textContent = cartData.error;
                    return;
                }

                cartData.forEach(item => {
                    const cartItemDiv = document.createElement('div');
                    cartItemDiv.classList.add('cart-item');

                    cartItemDiv.innerHTML = `
                        <h3>${item.product_title}</h3>
                        <img src="/${item.product_image}" alt="${item.product_title}" width="100">
                        <p>单价: ${item.product_price}</p>
                        <p>数量: ${item.quantity}</p>
                        <p>小计: ${item.subtotal}</p>
                    `;

                    cartItemsDiv.appendChild(cartItemDiv);
                    totalPrice += item.subtotal;
                });

                document.getElementById('total-price').textContent = totalPrice.toFixed(2);

            } catch (error) {
                console.error('Error loading cart items:', error);
                document.getElementById('error-message').textContent = '加载购物车信息失败。';
            }
        }

        async function checkout(paymentType) {
            createOrder(paymentType);
        }

        async function createOrder(paymentType) {
            try {
                const totalPrice = document.getElementById('total-price').textContent;
                const response = await fetch('./pay/api.php?action=create_order', {  // 修改了URL
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        cartData: cartDataGlobal, // 发送购物车数据
                        totalPrice: totalPrice,
                        paymentType: paymentType // 发送支付类型
                    })
                });

                const result = await response.json();

                if (result.code === 0) {
                    // 订单创建成功，跳转到支付链接
                    window.location.href = result.pay_url;
                } else {
                    // 订单创建失败，显示错误信息
                    document.getElementById('error-message').textContent = result.msg;
                }

            } catch (error) {
                console.error('Checkout error:', error);
                document.getElementById('error-message').textContent = '结算失败，请稍后再试。';
            }
        }

        window.onload = loadCartItems;
    </script>

</body>
</html>
