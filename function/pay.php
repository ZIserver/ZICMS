<?php
// 码支付签名生成
function generate_code_pay_sign($params, $key) {
    ksort($params);
    $signStr = '';
    foreach ($params as $k => $v) {
        if ($k != 'sign' && $k != 'sign_type' && $v != '') {
            $signStr .= $k . '=' . $v . '&';
        }
    }
    $signStr = rtrim($signStr, '&') . $key;
    return md5($signStr);
}

// 验证码支付回调
function verify_code_pay_callback($params, $key) {
    $sign = $params['sign'];
    unset($params['sign'], $params['sign_type']);
    $calculatedSign = generate_code_pay_sign($params, $key);
    return $sign === $calculatedSign;
}
?>
