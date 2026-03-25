<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Proses Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    $_SESSION = array();
    session_destroy();
    header("Location: index.php");
    exit;
}

// Proses Eksekusi Backup (Mengompres Root Folder)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'backup') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Validasi CSRF gagal.");
    }

    $rootPath = realpath(__DIR__ . '/..');
    $zipName = 'bunker_backup_' . date('Y-m-d_H-i-s') . '.zip';
    $zipPath = sys_get_temp_dir() . '/' . $zipName;

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                // Jangan masukkan file zip itu sendiri jika tersimpan di folder yang sama
                if ($relativePath !== $zipName) {
                    $zip->addFile($filePath, $relativePath);
                }
            }
        }
        $zip->close();

        // Download File
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename='.$zipName);
        header('Content-Length: ' . filesize($zipPath));
        readfile($zipPath);
        unlink($zipPath); // Hapus file temporari setelah diunduh
        exit;
    } else {
        $backup_error = "Sistem gagal membuat kompresi arsip.";
    }
}

// Ambil Access Log
$db_file = __DIR__ . '/bunker_data.sqlite';
try {
    $pdo = new PDO("sqlite:" . $db_file);
    $stmt = $pdo->query("SELECT * FROM access_logs ORDER BY created_at DESC LIMIT 10");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $logs = [];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Jeannes Bryan | Bunker</title>
    <meta name="theme-color" content="#121212">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="apple-touch-icon" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        /* Memastikan card sejajar tinggi dan rapi */
        .dashboard-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .dashboard-card {
            flex: 1 1 30%; /* Berbagi ruang seimbang 3 kolom */
            min-width: 250px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mt-4 mb-4">
            <div>
                <h2 class="mb-0">Bunker</h2>
                <div class="text-muted fs-small">Sistem kendali operasi pusat</div>
            </div>
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn-outline-danger">Disconnect</button>
            </form>
        </div>

        <?php if (isset($backup_error)): ?>
            <div class="alert-bunker p-2 mb-3 text-center"><?= $backup_error ?></div>
        <?php endif; ?>

        <div class="dashboard-cards">
            
            <div class="card p-3 dashboard-card">
                <div>
                    <h3 class="mb-1">Echo Timeline</h3>
                    <p class="text-muted fs-small mb-3">Akses jalur transmisi hampa (Micro-blog).</p>
                </div>
                <a href="../echo/index.php" class="btn btn-light btn-block">Enter Echo &rarr;</a>
            </div>

            <div class="card p-3 dashboard-card">
                <div>
                    <h3 class="mb-1">Blog Manager</h3>
                    <p class="text-muted fs-small mb-3">Tulis dan atur arsip manifesto publik Anda.</p>
                </div>
                <a href="blog_manager.php" class="btn btn-dark border-secondary btn-block text-center">Open Editor</a>
            </div>

            <div class="card p-3 dashboard-card" style="border-color: var(--danger);">
                <div>
                    <h3 class="mb-1 text-danger">Evacuation Protocol</h3>
                    <p class="text-muted fs-small mb-3">Kompres dan unduh kode sumber & database situs.</p>
                </div>
                <form method="POST" onsubmit="return confirm('Memulai pengarsipan file server. Lanjutkan?');" style="margin: 0;">
                    <input type="hidden" name="action" value="backup">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="btn btn-outline-danger btn-block w-100">Download Full Backup</button>
                </form>
            </div>

        </div>

        <div class="card p-3 mb-5">
            <h3 class="mb-3">Security Access Logs</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Waktu (WIB)</th>
                            <th>Status</th>
                            <th>IP Address</th>
                            <th>Browser Info</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="text-muted"><?= htmlspecialchars($log['created_at']) ?></td>
                                    <td class="fw-bold <?= $log['status'] === 'SUCCESS' ? 'text-success' : 'text-danger' ?>">
                                        <?= htmlspecialchars($log['status']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                    <td class="text-muted fs-small" title="<?= htmlspecialchars($log['user_agent']) ?>">
                                        <?= substr(htmlspecialchars($log['user_agent']), 0, 25) ?>...
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted">Belum ada log terekam.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>