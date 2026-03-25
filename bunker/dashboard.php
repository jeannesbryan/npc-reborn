<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
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
    <meta name="theme-color" content="#121212">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="apple-touch-icon" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mt-4 mb-4">
            <div>
                <h2 class="mb-0">Echo</h2>
                <div class="text-muted fs-small">Thoughts transmitted into the void</div>
            </div>
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn-outline-danger">Disconnect</button>
            </form>
        </div>

        <div class="card p-3">
            <h3>Welcome to the Bunker</h3>
            <p class="text-muted mb-0">Sistem transmisi online. Ruang ini aman.</p>
        </div>
    </div>
</body>
</html>