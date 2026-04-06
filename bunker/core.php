<?php
// ==========================================
// 0. ANTI DIRECT-ACCESS (Mencegah orang iseng buka core.php)
// ==========================================
if (basename($_SERVER['PHP_SELF']) === 'core.php') {
    header("HTTP/1.1 403 Forbidden");
    die("> SYS_ERR: DIRECT ACCESS PROHIBITED.");
}

// ==========================================
// 1. KEAMANAN SESSION & HEADER TINGKAT TINGGI
// ==========================================
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); 
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// CONTENT SECURITY POLICY (CSP)
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://static.cloudflareinsights.com https://challenges.cloudflare.com; frame-src 'self' https://challenges.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; connect-src 'self' https://api.github.com https://gitlab.com;");

// BENTENG TAMBAHAN (Level Militer)
header("Strict-Transport-Security: max-age=31536000; includeSubDomains"); // Memaksa browser hanya pakai HTTPS (Anti Man-in-the-Middle)
header("X-Frame-Options: DENY"); // Mencegah web jahat membungkus Bunker-mu pakai Iframe (Anti Clickjacking)
header("X-Content-Type-Options: nosniff"); // Mencegah browser salah menebak tipe file (Anti MIME-Sniffing)

session_start();
date_default_timezone_set('Asia/Jakarta');

// ==========================================
// 1.5. SERVER-SIDE "OXYGEN" TIMEOUT (30 Menit)
// ==========================================
$timeout_duration = 1800; // 1800 detik = 30 menit

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
        // Jika AFK lebih dari 30 menit, hancurkan sesi (Putus Oksigen!)
        session_unset();
        session_destroy();
        header("Location: /bunker/index.php"); // Tendang ke halaman login utama
        exit;
    }
    // Jika masih aktif, reset timer ke 0 lagi
    $_SESSION['last_activity'] = time();
}

// ==========================================
// 2. SENTRALISASI PENGECEKAN LOGIN
// ==========================================
function require_login() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: /bunker/index.php"); // Pastikan dilempar ke login utama
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