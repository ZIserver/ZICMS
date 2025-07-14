<?php
session_start();

function generateCsrfToken() {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

function validateCsrfToken() {
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return hash_equals($sessionToken, $headerToken);
}
?>
