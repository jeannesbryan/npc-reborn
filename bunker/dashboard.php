<?php
session_start();

// 1. Proteksi Halaman: Jika belum login, kembalikan ke gerbang (index.php)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

// 2. Logika Pemutusan Sesi (Logout)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    // Kosongkan semua variabel array sesi
    $_SESSION = array();

    // Hapus cookie sesi di browser pengguna (Langkah pembersihan total)
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Hancurkan sesi di level server
    session_destroy();

    // Arahkan kembali ke halaman login
    header("Location: index.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Dashboard - Jeannes Bryan | Echo</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    </head>
    <body class="container font-monospace" data-bs-theme="dark">
        <div class="row">
            <div class="col-sm-2"></div>
            <div class="col-sm-8">
                
                <div class="my-3 text-bg-dark border-bottom border-secondary pb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">Echo</h3>
                        <small class="text-secondary">Thoughts transmitted into the void</small>
                    </div>
                    
                    <form method="POST" class="m-0">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn btn-outline-danger btn-sm">Disconnect</button>
                    </form>
                </div>

                <div class="mt-4">
                    <h4>Welcome to the Bunker</h4>
                    <p class="text-secondary">Sistem transmisi online. Ruang ini aman.</p>
                    
                    </div>

            </div>
            <div class="col-sm-2"></div>
        </div>
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    </body>
</html>