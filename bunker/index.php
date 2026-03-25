<?php
session_start();

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$db_file = __DIR__ . '/bunker_data.sqlite';
$error_message = ''; 

if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['login_attempts'] >= 5) {
        $error_message = "Sistem terkunci sementara. Terlalu banyak anomali terdeteksi.";
    } else {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $error_message = "Validasi integritas gagal (CSRF Mismatch).";
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            try {
                $pdo = new PDO("sqlite:" . $db_file);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password_hash'])) {
                    session_regenerate_id(true); 
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_attempts'] = 0; 

                    header("Location: dashboard.php");
                    exit;
                } else {
                    $_SESSION['login_attempts']++;
                    $error_message = "Akses Ditolak: Entitas tidak dikenali.";
                }
            } catch (PDOException $e) {
                $error_message = "Sistem internal mengalami kegagalan.";
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
        // 1. Mendaftarkan Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(registration => {
                        console.log('Bunker SW terdaftar dengan sukses:', registration.scope);
                    })
                    .catch(error => {
                        console.log('Pendaftaran Bunker SW gagal:', error);
                    });
            });
        }

        // 2. Menangani Prompt Instalasi
        let deferredPrompt;
        const installContainer = document.getElementById('pwa-install-container');
        const installBtn = document.getElementById('btn-install-pwa');

        // Event ini hanya ditembak oleh browser jika kriteria PWA terpenuhi (HTTPS/Localhost)
        window.addEventListener('beforeinstallprompt', (e) => {
            // Mencegah browser menampilkan prompt default secara otomatis
            e.preventDefault();
            // Simpan event agar bisa dipicu nanti dari tombol khusus kita
            deferredPrompt = e;
            // Munculkan tombol instalasi khusus di halaman login
            installContainer.classList.remove('d-none');
        });

        // Aksi ketika tombol instal diklik
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt !== null) {
                // Tampilkan prompt instalasi bawaan browser
                deferredPrompt.prompt();
                // Tunggu respons pengguna (apakah menerima atau menolak)
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    console.log('Sistem diinstal');
                }
                // Hapus referensi ke prompt karena hanya bisa digunakan sekali
                deferredPrompt = null;
                // Sembunyikan kembali area tombol instal
                installContainer.classList.add('d-none');
            }
        });

        // Deteksi jika pengguna sukses melakukan instalasi
        window.addEventListener('appinstalled', () => {
            installContainer.classList.add('d-none');
            deferredPrompt = null;
            console.log('PWA berhasil ditambahkan ke perangkat');
        });
    </script>
</body>
</html>