<?php
session_start();

function generateCaptcha($width = 200, $height = 50, $length = 4) {
    // 字符集
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = substr(str_shuffle($chars), 0, $length);
    $_SESSION['captcha'] = strtolower($code); // 存储验证码到 session

    // 创建图像
    $image = imagecreatetruecolor($width, $height);

    // 设置背景颜色（白色）
    $bgColor = imagecolorallocate($image, 255, 255, 255);
    imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

    // 设置字体颜色（随机）
    $textColor = imagecolorallocate($image, rand(0, 100), rand(0, 100), rand(0, 100));

    // 设置字体文件路径（确保路径正确）
    $fontFile = __DIR__ . '/Arial.ttf';

    // 在图像上绘制验证码文本
    $fontSize = 20; // 字体大小
    $angle = rand(-10, 10); // 随机旋转角度
    $x = 10; // 起始 x 坐标
    $y = 40; // 起始 y 坐标
    for ($i = 0; $i < $length; $i++) {
        $char = $code[$i];
        imagettftext($image, $fontSize, $angle, $x, $y, $textColor, $fontFile, $char);
        $x += 30; // 每个字符间隔
        $angle = rand(-10, 10); // 随机旋转角度
    }

    // 添加干扰线段
    for ($i = 0; $i < 10; $i++) {
        $lineColor = imagecolorallocate($image, rand(100, 255), rand(100, 255), rand(100, 255));
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
    }
     for ($i = 0; $i < 10; $i++) {
        $lineColor = imagecolorallocate($image, rand(100, 255), rand(100, 255), rand(100, 255));
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
    }
    for ($i = 0; $i < 10; $i++) {
        $lineColor = imagecolorallocate($image, rand(100, 255), rand(100, 255), rand(100, 255));
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
    }
     for ($i = 0; $i < 10; $i++) {
        $lineColor = imagecolorallocate($image, rand(100, 255), rand(100, 255), rand(100, 255));
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
    }

    // 输出图像
    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
}

// 生成验证码
generateCaptcha();
