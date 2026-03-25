<?php
session_start();

// --- PERBAIKAN: Jika sudah login, langsung arahkan ke dalam Bunker ---
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}
// ---------------------------------------------------------------------

// 1. Konfigurasi Awal
$db_file = __DIR__ . '/bunker_data.sqlite';
$error_message = ''; // Variabel untuk menampung pesan error

// 2. Anti Brute-Force (Rate Limiting)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

// 3. Generate CSRF Token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 4. Logika Pemrosesan Form (Hanya berjalan jika ada request POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Cek percobaan login
    if ($_SESSION['login_attempts'] >= 5) {
        $error_message = "Sistem terkunci sementara. Terlalu banyak anomali terdeteksi.";
    } else {
        // Verifikasi CSRF Token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $error_message = "Validasi integritas gagal (CSRF Mismatch).";
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            try {
                $pdo = new PDO("sqlite:" . $db_file);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Anti SQL Injection
                $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verifikasi Password
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Mencegah Session Fixation Attack
                    session_regenerate_id(true); 
                    
                    // Set parameter sesi keberhasilan
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['logged_in'] = true;
                    $_SESSION['login_attempts'] = 0; // Reset counter

                    // Menuju Void
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $_SESSION['login_attempts']++;
                    $error_message = "Akses Ditolak: Entitas tidak dikenali.";
                }

            } catch (PDOException $e) {
                error_log("Kesalahan Database SQLite: " . $e->getMessage());
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
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <style>
            .alert-bunker {
                background-color: #2b0f0f;
                color: #ff6b6b;
                border: 1px solid #ff6b6b;
            }
        </style>
    </head>
    <body class="container font-monospace" data-bs-theme="dark">
        <div class="row mt-5">
            <div class="col-sm-3"></div>
            <div class="col-sm-6">
                
                <div class="my-4 text-bg-dark border-bottom border-secondary text-center pb-3">
                    <figure class="text-center mb-0">
                        <blockquote class="blockquote">
                            <h1 class="display-5 fw-bold">NPC</h1>
                        </blockquote>
                        <figcaption class="blockquote-footer mt-1 mb-0">
                            Bunker Entry Interface
                        </figcaption>
                    </figure>
                </div>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-bunker text-center py-2" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label text-secondary">Identification (Email)</label>
                        <input type="email" class="form-control bg-dark text-light border-secondary" id="email" name="email" required autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label text-secondary">Passphrase</label>
                        <input type="password" class="form-control bg-dark text-light border-secondary" id="password" name="password" required>
                    </div>
                    <div class="d-grid mt-2">
                        <button class="btn btn-outline-light" type="submit">Initialize Sequence</button>
                    </div>
                </form>

            </div>
            <div class="col-sm-3"></div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    </body>
</html>