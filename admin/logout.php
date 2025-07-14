<?php
include '.././auth/index.php';
session_start();
session_destroy(); // 销毁会话
header('Location: .././index.php'); // 重定向到首页
exit;
