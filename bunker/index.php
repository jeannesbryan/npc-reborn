<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$db_file = __DIR__ . '/bunker_data.sqlite';
$error_msg = '';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("SYS_HALT: Main database connection lost.");
}

function get_ip() {
    return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
}

function log_access($pdo, $ip, $ua, $status) {
    $stmt = $pdo->prepare("INSERT INTO access_logs (ip_address, user_agent, status) VALUES (?, ?, ?)");
    $stmt->execute([$ip, $ua, $status]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = get_ip();
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN_ENTITY';
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM access_logs WHERE ip_address = ? AND status = 'FAILED' AND created_at >= datetime('now', '-15 minutes', 'localtime')");
    $stmt->execute([$ip]);
    $failed_attempts = $stmt->fetchColumn();

    if ($failed_attempts >= 5) {
        $error_msg = "PROTOCOL LOCKDOWN: Too many anomalies. Signal temporarily blocked.";
    } else {
        $email = $_POST['operator_id'] ?? '';
        $password = $_POST['cipher'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            log_access($pdo, $ip, $ua, 'SUCCESS');
            header("Location: dashboard.php");
            exit;
        } else {
            log_access($pdo, $ip, $ua, 'FAILED');
            $error_msg = "SYS_ERR: Authentication failed. Unrecognized signal.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TERMINAL LOGIN - Bunker</title>
    <meta name="theme-color" content="#0a0a0a">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body { background-color: #050505; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background-image: radial-gradient(circle, #1a1a1a 1px, transparent 1px); background-size: 30px 30px; }
        .terminal-box { background: #0a0a0a; border: 1px solid var(--border-color); padding: 30px; width: 100%; max-width: 400px; box-shadow: 0 0 20px rgba(0,0,0,0.8); position: relative; }
        .terminal-box::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--text-muted); }
        .terminal-header { text-align: center; margin-bottom: 30px; border-bottom: 1px dashed var(--border-color); padding-bottom: 15px; }
        .terminal-label { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; display: block; }
        .terminal-input { background: transparent; border: none; border-bottom: 1px solid var(--border-color); color: var(--text-main); font-family: 'JetBrains Mono', monospace; width: 100%; padding: 8px 0; margin-bottom: 20px; outline: none; border-radius: 0; }
        .terminal-input:focus { border-bottom-color: var(--text-main); box-shadow: none; }
        .terminal-btn { background: var(--text-main); color: var(--bg-dark); border: none; width: 100%; padding: 10px; font-family: 'JetBrains Mono', monospace; font-weight: bold; text-transform: uppercase; cursor: pointer; transition: all 0.2s; margin-top: 10px; }
        .terminal-btn:hover { background: var(--text-muted); }
        .sys-error { color: var(--danger); font-size: 0.85rem; margin-bottom: 20px; border-left: 2px solid var(--danger); padding-left: 10px; background: rgba(255, 107, 107, 0.1); padding: 10px; }
    </style>
</head>
<body>
    <div class="terminal-box">
        <div class="terminal-header">
            <h2 class="mb-1" style="font-size: 1.5rem;">BUNKER O.S.</h2>
            <div class="text-muted fs-small">SYSTEM AUTHENTICATION REQ.</div>
        </div>

        <?php if ($error_msg): ?>
            <div class="sys-error"><?= $error_msg ?></div>
        <?php endif; ?>

        <form method="POST">
            <label class="terminal-label">>> Operator_Signal_ID</label>
            <input type="email" name="operator_id" class="terminal-input" required autofocus autocomplete="off" spellcheck="false">

            <label class="terminal-label">>> Decryption_Cipher</label>
            <input type="password" name="cipher" class="terminal-input" required autocomplete="off">

            <button type="submit" class="terminal-btn">[ INITIATE UPLINK ] <span class="blinking-cursor"></span></button>
        </form>

        <div class="text-center mt-4 fs-small" style="color: #444;">
            UNIDENTIFIED SIGNALS WILL BE LOGGED AND TRACED
        </div>
    </div>
</body>
</html>