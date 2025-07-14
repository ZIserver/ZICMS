<?php
// 获取用户选择的主题
if (isset($_POST['theme'])) {
    $theme = $_POST['theme'];
    // 设置 cookie，有效期为 30 天
    setcookie('theme', $theme, time() + (86400 * 30), '/'); // 86400 = 1 day
}
?>
