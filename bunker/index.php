<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) { header("Location: dashboard.php"); exit; }

$db_file = __DIR__ . '/bunker_data.sqlite';
$error_msg = '';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5); 
} catch (PDOException $e) { die("SYS_HALT: Main database connection lost."); }

function get_ip() { return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN_IP'; }

function log_access($pdo, $ip, $ua, $status) {
    $time = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO access_logs (ip_address, user_agent, status, created_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$ip, $ua, $status, $time]);
    
    $purge_time = date('Y-m-d H:i:s', strtotime('-7 days'));
    $pdo->prepare("DELETE FROM access_logs WHERE created_at < ?")->execute([$purge_time]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = get_ip(); $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN_ENTITY';
    $time_limit = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM access_logs WHERE ip_address = ? AND status = 'FAILED' AND created_at >= ?");
    $stmt->execute([$ip, $time_limit]);
    $failed_attempts = $stmt->fetchColumn();

    if ($failed_attempts >= 5) {
        $error_msg = "PROTOCOL LOCKDOWN: Too many anomalies. Signal temporarily blocked.";
    } else {
        $email = $_POST['operator_id'] ?? ''; $password = $_POST['cipher'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?"); $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['logged_in'] = true; $_SESSION['user_id'] = $user['id'];
            log_access($pdo, $ip, $ua, 'SUCCESS'); header("Location: dashboard.php"); exit;
        } else {
            log_access($pdo, $ip, $ua, 'FAILED'); $error_msg = "SYS_ERR: Authentication failed. Unrecognized signal.";
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
    <meta name="theme-color" content="#050505">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="manifest" href="manifest.json">
    <style>
        body { background-color: #050505; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; background-image: radial-gradient(circle, #1a1a1a 1px, transparent 1px); background-size: 30px 30px; }
        .terminal-box { background: #0a0a0a; border: 1px solid var(--border-color); padding: 30px; width: 90%; max-width: 400px; box-shadow: 0 0 20px rgba(0,0,0,0.8); position: relative; }
        .terminal-box::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: var(--text-muted); }
        .terminal-header { text-align: center; margin-bottom: 30px; border-bottom: 1px dashed var(--border-color); padding-bottom: 15px; }
        .terminal-label { color: var(--text-muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; display: block; }
        .terminal-input { background: transparent; border: none; border-bottom: 1px solid var(--border-color); color: var(--text-main); font-family: 'JetBrains Mono', monospace; width: 100%; padding: 8px 0; margin-bottom: 20px; outline: none; border-radius: 0; }
        .terminal-input:focus { border-bottom-color: var(--text-main); box-shadow: none; }
        .sys-error { color: var(--danger); font-size: 0.85rem; margin-bottom: 20px; border-left: 2px solid var(--danger); padding-left: 10px; background: rgba(255, 107, 107, 0.1); padding: 10px; }
    </style>
</head>
<body>

    <div id="splash-overlay">
        <div class="splash-content">
            > BOOTING_MAIN_OS_ENVIRONMENT<span class="loading-dots"></span>
        </div>
    </div>

    <div class="terminal-box">
        <div class="terminal-header">
            <h2 class="mb-1 text-main" style="font-size: 1.5rem; letter-spacing: 2px;">[ BUNKER O.S ]</h2>
            <div class="text-muted fs-small">> SYSTEM AUTHENTICATION REQ.</div>
        </div>

        <?php if ($error_msg): ?>
            <div class="sys-error"><?= $error_msg ?></div>
        <?php endif; ?>

        <form method="POST">
            <label class="terminal-label">>> Operator_Signal_ID</label>
            <input type="email" name="operator_id" class="terminal-input" required autofocus autocomplete="off" spellcheck="false">
            <label class="terminal-label">>> Decryption_Cipher</label>
            <input type="password" name="cipher" class="terminal-input" required autocomplete="off">
            <button type="submit" class="btn btn-main btn-block mt-3">[ INITIATE UPLINK ]</button>
        </form>

        <div class="text-center mt-4 fs-small" style="color: #444;">UNIDENTIFIED SIGNALS WILL BE LOGGED AND TRACED</div>
    </div>

    <div id="pwa-install-banner">
        <div style="text-align: left;">
            <strong class="text-main" style="letter-spacing: 1px;">> INSTALL_BUNKER_OS</strong><br>
            <span class="fs-small text-muted" style="font-size: 0.75rem;">Add to home screen for native access.</span>
        </div>
        <button id="btn-install-pwa" class="btn btn-main btn-sm">INSTALL</button>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            setTimeout(() => { document.getElementById('splash-overlay').classList.add('splash-hidden'); }, 3000);
        });

        let deferredPrompt;
        const pwaBanner = document.getElementById('pwa-install-banner');
        const installBtn = document.getElementById('btn-install-pwa');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault(); deferredPrompt = e; pwaBanner.style.display = 'flex';
        });

        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') { pwaBanner.style.display = 'none'; }
                deferredPrompt = null;
            }
        });

        if ('serviceWorker' in navigator) { navigator.serviceWorker.register('sw.js').catch(err => console.log('SW Reg Failed:', err)); }
    </script>
</body>
</html>