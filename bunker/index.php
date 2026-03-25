<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$db_file = __DIR__ . '/bunker_data.sqlite';
$error_message = ''; 

if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Auto-create tabel access_logs jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS access_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT,
        user_agent TEXT,
        status TEXT,
        created_at DATETIME
    )");
} catch (PDOException $e) {
    die("Sistem internal mengalami kegagalan.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['login_attempts'] >= 5) {
        $error_message = "Sistem terkunci sementara. Terlalu banyak anomali terdeteksi.";
    } else {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $error_message = "Validasi integritas gagal (CSRF Mismatch).";
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Catat data pengunjung
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $now = date('Y-m-d H:i:s');

            $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true); 
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_attempts'] = 0; 

                // Insert Success Log
                $stmtLog = $pdo->prepare("INSERT INTO access_logs (ip_address, user_agent, status, created_at) VALUES (?, ?, 'SUCCESS', ?)");
                $stmtLog->execute([$ip, $ua, $now]);

                // Auto-Cleanup Log (Maksimal 500 data)
                $pdo->exec("DELETE FROM access_logs WHERE id NOT IN (SELECT id FROM access_logs ORDER BY id DESC LIMIT 500)");

                header("Location: dashboard.php");
                exit;
            } else {
                $_SESSION['login_attempts']++;
                $error_message = "Akses Ditolak: Entitas tidak dikenali.";
                
                // Insert Failed Log
                $stmtLog = $pdo->prepare("INSERT INTO access_logs (ip_address, user_agent, status, created_at) VALUES (?, ?, 'FAILED', ?)");
                $stmtLog->execute([$ip, $ua, $now]);

                // Auto-Cleanup Log (Maksimal 500 data)
                $pdo->exec("DELETE FROM access_logs WHERE id NOT IN (SELECT id FROM access_logs ORDER BY id DESC LIMIT 500)");
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
    <title>Bunker - Jeannes Bryan | NPC</title>    
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#121212">
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="apple-touch-icon" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container mt-5" style="max-width: 450px;">
        <div class="text-center border-bottom pb-3 mb-4">
            <h1 class="mb-1">NPC</h1>
            <div class="text-muted">Bunker Entry Interface</div>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert-bunker text-center py-2 mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="email" class="form-label">Identification (Email)</label>
                <input type="email" class="form-control" id="email" name="email" required autofocus>
            </div>
            <div class="form-group mb-4">
                <label for="password" class="form-label">Passphrase</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button class="btn btn-light btn-block" type="submit">Initialize Sequence</button>
        </form>

        <div id="pwa-install-container" class="text-center mt-4 d-none">
            <hr>
            <div class="text-muted fs-small mb-2">Bunker System App Available</div>
            <button id="btn-install-pwa" class="btn btn-dark btn-sm border-secondary">Install to Device</button>
        </div>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .catch(error => console.log('SW fail:', error));
            });
        }
        let deferredPrompt;
        const installContainer = document.getElementById('pwa-install-container');
        const installBtn = document.getElementById('btn-install-pwa');
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault(); 
            deferredPrompt = e; 
            installContainer.classList.remove('d-none');
        });
        
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt !== null) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                deferredPrompt = null; 
                installContainer.classList.add('d-none');
            }
        });
        
        window.addEventListener('appinstalled', () => {
            installContainer.classList.add('d-none'); 
            deferredPrompt = null;
        });
    </script>
</body>
</html>