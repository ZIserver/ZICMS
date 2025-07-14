<?php
// 保存配置文件
function save_config($config) {
    $config_content = "<?php\n";
    foreach ($config as $key => $value) {
        $config_content .= "define('$key', '$value');\n";
    }
    file_put_contents('config.php', $config_content);
}

// 获取文章的示例函数
function get_posts($limit = 5) {
    // 这里模拟从数据库获取文章
    $posts = [
        ['id' => 1, 'title' => '第一篇文章'],
        ['id' => 2, 'title' => '第二篇文章'],
        ['id' => 3, 'title' => '第三篇文章'],
        ['id' => 4, 'title' => '第四篇文章'],
        ['id' => 5, 'title' => '第五篇文章'],
    ];
    return array_slice($posts, 0, $limit);
}
?>
