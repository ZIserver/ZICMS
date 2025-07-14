<?php
// 获取统计数据
$result = $conn->query("SELECT COUNT(*) AS count FROM visits");
if (!$result) {
    echo "查询错误 (visits): " . $conn->error;
    $total_visits = 0; // 设置一个默认值，防止后续代码出错
} else {
    $row = $result->fetch_assoc();
    $total_visits = $row['count'];
}

$result = $conn->query("SELECT SUM(views) AS total FROM articles");
if (!$result) {
    echo "查询错误 (articles - views): " . $conn->error;
    $total_page_views = 0;
} else {
    $row = $result->fetch_assoc();
    $total_page_views = $row['total'] ?? 0; // 使用 ?? 0 避免 NULL 值
}

// 获取总点赞量 (从 articles 表中获取 like_count 的总和)
$result = $conn->query("SELECT SUM(like_count) AS total FROM articles");
if (!$result) {
    echo "查询错误 (articles - like_count): " . $conn->error;
    $total_likes = 0;
} else {
    $row = $result->fetch_assoc();
    $total_likes = $row['total'] ?? 0; // 使用 ?? 0 避免 NULL 值
}

$result = $conn->query("SELECT COUNT(*) AS count FROM comments");
if (!$result) {
    echo "查询错误 (comments): " . $conn->error;
    $total_comments = 0;
} else {
    $row = $result->fetch_assoc();
    $total_comments = $row['count'];
}

// 获取最近的访问记录
$result = $conn->query("SELECT * FROM visits ORDER BY timestamp DESC LIMIT 5"); //限制显示5条
if (!$result) {
    echo "查询错误 (recent_visits): " . $conn->error;
    $recent_visits = [];
} else {
    $recent_visits = $result->fetch_all(MYSQLI_ASSOC);
}

// 获取按小时计算的访问量数据
$result = $conn->query("
    SELECT
        HOUR(timestamp) AS hour,
        COUNT(*) AS count
    FROM visits
    WHERE DATE(timestamp) = CURDATE()
    GROUP BY HOUR(timestamp)
    ORDER BY HOUR(timestamp)
");

if (!$result) {
    echo "查询错误 (hourly_visits): " . $conn->error;
    $hourly_visits = [];
} else {
    $hourly_visits = $result->fetch_all(MYSQLI_ASSOC);
}

// 将数据转换为 JSON 格式
$hourly_visits_json = json_encode($hourly_visits);
?>

<style>
    /* 仪表盘样式 */
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f4f7f9;
        color: #333;
    }

    .dashboard-container {
        padding: 30px;
        max-width: 1200px;
        margin: 0 auto;
    }

    h2 {
        color: #2c3e50;
        margin-bottom: 20px;
        border-bottom: 2px solid #ecf0f1;
        padding-bottom: 10px;
    }

    .dashboard-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background-color: #fff;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        width: 220px;
        text-align: center;
        transition: transform 0.2s ease-in-out;
        border: 1px solid #ecf0f1;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-card h3 {
        margin-top: 0;
        font-size: 1.3em;
        color: #546e7a;
        margin-bottom: 8px;
    }

    .stat-card p {
        font-size: 1.8em;
        font-weight: 600;
        color: #3498db;
    }

    /* 表格样式 */
    .recent-visits-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 30px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        border: 1px solid #ecf0f1;
    }

    .recent-visits-table th,
    .recent-visits-table td {
        padding: 15px;
        border-bottom: 1px solid #ecf0f1;
        text-align: left;
        color: #546e7a;
    }

    .recent-visits-table th {
        background-color: #f9f9f9;
        font-weight: 600;
        color: #333;
    }

    .recent-visits-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* 图表样式 */
    #hourly-visits-chart {
        width: 100%;
        height: 350px;
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        padding: 20px;
        border: 1px solid #ecf0f1;
    }

    /* 响应式布局 */
    @media (max-width: 768px) {
        .dashboard-stats {
            flex-direction: column;
        }

        .stat-card {
            width: 100%;
        }
    }
</style>

<div class="dashboard-container">
    <h2>仪表盘</h2>

    <div class="dashboard-stats">
        <div class="stat-card">
            <h3>总访问量</h3>
            <p><?= htmlspecialchars($total_visits) ?></p>
        </div>
        <div class="stat-card">
            <h3>总阅读量</h3>
            <p><?= htmlspecialchars($total_page_views) ?></p>
        </div>
        <div class="stat-card">
            <h3>总点赞量</h3>
            <p><?= htmlspecialchars($total_likes) ?></p>
        </div>
        <div class="stat-card">
            <h3>总评论量</h3>
            <p><?= htmlspecialchars($total_comments) ?></p>
        </div>
    </div>

    <h3>今日访问量</h3>
    <canvas id="hourly-visits-chart"></canvas>

    <h3>最近访问记录</h3>
    <table class="recent-visits-table">
        <thead>
            <tr>
                <th>时间</th>
                <th>IP 地址</th>
                <th>User-Agent</th>
                <th>页面 URL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_visits as $visit): ?>
                <tr>
                    <td><?= htmlspecialchars($visit['timestamp']) ?></td>
                    <td><?= htmlspecialchars($visit['ip_address']) ?></td>
                    <td><?= htmlspecialchars($visit['user_agent']) ?></td>
                    <td><?= htmlspecialchars($visit['page_url']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // 获取 PHP 传递过来的 JSON 数据
    const hourlyVisitsData = JSON.parse('<?= $hourly_visits_json ?>');

    // 准备 Chart.js 的数据
    const labels = Array.from({ length: 24 }, (_, i) => i + ':00'); // 创建 0-23 的小时标签
    const data = new Array(24).fill(0); // 初始化 24 个小时的数据为 0

    // 填充数据
    hourlyVisitsData.forEach(item => {
        const hour = parseInt(item.hour);
        data[hour] = parseInt(item.count);
    });

    // 创建 Chart.js 图表
    const ctx = document.getElementById('hourly-visits-chart').getContext('2d');
    const myChart = new Chart(ctx, {
        type: 'line', // 使用折线图更适合显示小时数据
        data: {
            labels: labels,
            datasets: [{
                label: '访问量',
                data: data,
                fill: false,
                borderColor: 'rgba(52, 152, 219, 1)', // 更商务的颜色
                tension: 0.4,
                borderWidth: 2,
                pointBackgroundColor: 'rgba(52, 152, 219, 1)',
                pointRadius: 3,
                pointHoverRadius: 5
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    },
                    grid: {
                        display: false
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
</script>


