<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background-color: #e9ecef;
            color: #333;
        }
        .container {
            width: 100%;
            max-width: 960px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            margin-top: 50px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #0069d9;
            font-size: 24px;
        }
        .header p {
            color: #6c757d;
            font-size: 14px;
            margin-top: 10px;
        }
        .notice-list {
            margin: 0;
            padding: 0;
            list-style-type: none;
        }
        .notice-list .notice {
            padding: 15px;
            border: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        .notice .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .notice .content {
            font-size: 14px;
            line-height: 1.6;
            color: #495057;
        }
        .notice .date {
            font-size: 12px;
            color: #868e96;
            margin-top: 10px;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ZICMS公告</h1>
            <p>最新动态与公告信息</p>
        </div>

        <ul class="notice-list">
            <?php
            $appUID = '1'; // 请替换为实际的app_uid
            $url = 'https://auth.moeres.cn/ajax.php?act=notice&app_uid=' . $appUID;

            // 执行HTTP GET请求
            $jsonData = file_get_contents($url);

            if ($jsonData === false) {
                echo '<li class="notice">Failed to fetch data.</li>';
            } else {
                // 解码JSON数据
                $data = json_decode($jsonData, true);

                if (isset($data['code']) && $data['code'] == 0 && isset($data['data'])) {
                    foreach ($data['data'] as $notice) {
                        echo '<li class="notice">';
                        echo '<div class="title">' . htmlspecialchars($notice['title']) . '</div>';
                        echo '<div class="content">' . htmlspecialchars($notice['content']) . '</div>';
                        echo '<div class="date">发布时间：' . htmlspecialchars($notice['date']) . '</div>';
                        echo '</li>';
                    }
                } else {
                    echo '<li class="notice">暂无公告信息。</li>';
                }
            }
            ?>
        </ul>


    </div>
</body>
</html>
