<?php
require_once 'core.php';

// Jika sudah login, langsung bypass ke dashboard
redirect_if_logged_in();

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
    // 1. VERIFIKASI CLOUDFLARE TURNSTILE
    $turnstile_secret = "0x4AAAAAAC1TWqrszAPAcxjxPLzv3DNZjQU";
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';

    if (empty($turnstile_response)) {
        $error_msg = "SYS_ERR: Missing anti-bot validation.";
    } else {
        // Tembak API Cloudflare untuk verifikasi
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://challenges.cloudflare.com/turnstile/v0/siteverify");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'secret' => $turnstile_secret,
            'response' => $turnstile_response,
            'remoteip' => get_ip()
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $cf_verify = curl_exec($ch);
        curl_close($ch);

        $cf_outcome = json_decode($cf_verify);

        if (!$cf_outcome || !$cf_outcome->success) {
            $error_msg = "SYS_ERR: Anti-bot challenge failed.";
        } else {
            // 2. JIKA LOLOS TURNSTILE, LANJUTKAN PROSES LOGIN BAWAANMU
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
                    log_access($pdo, $ip, $ua, 'SUCCESS'); 
                    header("Location: dashboard.php"); 
                    exit;
                } else {
                    log_access($pdo, $ip, $ua, 'FAILED'); 
                    $error_msg = "SYS_ERR: Authentication failed. Unrecognized signal.";
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title>TERMINAL LOGIN - Bunker</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/jeannesbryan/terminal/terminal.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <link rel="manifest" href="manifest.json">
    <style>
        .login-card {
            box-shadow: 0 0 20px rgba(0,0,0,0.8);
            border-top: 3px solid var(--t-green-dim);
        }

        #pwa-install-banner { 
            display: none; 
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--bg-surface);
            border: 1px dashed var(--t-green); 
            padding: 15px; 
            align-items: center; 
            justify-content: space-between; 
            width: 90%; 
            max-width: 400px; 
            box-shadow: 0 0 15px rgba(0,255,65,0.15); 
            z-index: 1000;
        }
    </style>
</head>
<body class="t-crt"> 
    <div id="splash-overlay" class="t-splash">
        <div class="font-bold text-success" style="font-size: 1.1rem; letter-spacing: 2px; text-shadow: 0 0 8px currentColor;">
            > BOOTING_MAIN_OS_ENVIRONMENT<span class="t-loading-dots"></span>
        </div>
    </div>

    <div class="t-center-screen flex-column">
        
        <div class="t-center-box">
            
            <div class="t-card login-card mb-0 text-left">
                
                <div class="t-card-header text-center pb-3">
                    <h2 class="mb-1 text-success" style="font-size: 1.5rem; letter-spacing: 2px;">[ BUNKER O.S ]</h2>
                    <div class="text-muted fs-small fw-normal">> SYSTEM AUTHENTICATION REQ.</div>
                </div>

                <?php if ($error_msg): ?>
                    <div class="t-alert danger mb-4">> <?= $error_msg ?></div>
                <?php endif; ?>

                <form method="POST">
                    <label class="t-form-label">>> Operator_Signal_ID</label>
                    <input type="email" name="operator_id" class="t-input mb-4" required autofocus autocomplete="off" spellcheck="false">
                    
                    <label class="t-form-label">>> Decryption_Cipher</label>
                    
                    <div class="t-input-group mb-4">
                        <input type="password" id="cipherInput" name="cipher" class="t-input" required autocomplete="off">
                        <button type="button" class="t-input-action-btn" onclick="Terminal.toggleInputAction('cipherInput', this)">[ SHOW ]</button>
                    </div>

                    <div class="cf-turnstile mb-3" data-sitekey="0x4AAAAAAC1TWn2hGeXno7_k" data-theme="dark"></div>

                    <button type="submit" class="t-btn t-btn-block mt-3">[ INITIATE UPLINK ]</button>
                </form>

                <div class="text-center mt-4 fs-small text-muted" style="opacity: 0.6;">
                    UNIDENTIFIED SIGNALS WILL BE LOGGED AND TRACED
                </div>
            </div>
            
        </div>

    </div>

    <div id="pwa-install-banner">
        <div style="text-align: left;" class="d-flex flex-column gap-1">
            <strong class="text-success" style="letter-spacing: 1px;">> INSTALL_BUNKER_OS</strong>
            <span class="fs-small text-muted" style="font-size: 0.75rem;">Add to home screen for native access.</span>
        </div>
        <button id="btn-install-pwa" class="t-btn t-btn-sm">[ INSTALL ]</button>
    </div>

    <script src="https://cdn.jsdelivr.net/gh/jeannesbryan/terminal/terminal.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            if (typeof Terminal !== 'undefined' && Terminal.splash) {
                Terminal.splash.close();
            }
        });

        // Logika PWA
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

        if ('serviceWorker' in navigator) { 
            navigator.serviceWorker.register('sw.js').catch(err => console.log('SW Reg Failed:', err)); 
        }
    </script>
</body>
</html>