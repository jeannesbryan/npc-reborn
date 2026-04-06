<?php
// ==========================================
// 0. ANTI DIRECT-ACCESS (Mencegah orang iseng buka core.php)
// ==========================================
if (basename($_SERVER['PHP_SELF']) === 'core.php') {
    header("HTTP/1.1 403 Forbidden");
    die("> SYS_ERR: DIRECT ACCESS PROHIBITED.");
}

// ==========================================
// 1. KEAMANAN SESSION TINGKAT TINGGI
// ==========================================
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); 
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();
date_default_timezone_set('Asia/Jakarta');

// ==========================================
// 2. SENTRALISASI PENGECEKAN LOGIN
// ==========================================
function require_login() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: index.php"); 
        exit;
    }
}

// ==========================================
// 2.5 SENTRALISASI BYPASS LOGIN
// ==========================================
function redirect_if_logged_in() {
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        header("Location: dashboard.php"); 
        exit;
    }
}

// ==========================================
// 3. SENTRALISASI & VALIDASI CSRF TOKEN
// ==========================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verify_csrf($token_from_post) {
    if (!isset($token_from_post) || !hash_equals($_SESSION['csrf_token'], $token_from_post)) {
        die("> SYS_ERR: SECURITY BREACH DETECTED. INVALID CSRF TOKEN.");
    }
}
?>