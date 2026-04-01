<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: index.php"); exit; }
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    $_SESSION = array(); session_destroy(); header("Location: index.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'backup') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) { die("> SYS_ERR: Integrity validation failed."); }
    $rootPath = realpath(__DIR__ . '/..');
    $zipName = 'bunker_backup_' . date('Y-m-d_H-i-s') . '.zip';
    $zipPath = sys_get_temp_dir() . '/' . $zipName;

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootPath), RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);
                if ($relativePath !== $zipName) { $zip->addFile($filePath, $relativePath); }
            }
        }
        $zip->close();
        header('Content-Type: application/zip'); header('Content-disposition: attachment; filename='.$zipName);
        header('Content-Length: ' . filesize($zipPath)); readfile($zipPath); unlink($zipPath); exit;
    } else { $backup_error = "> SYS_ERR: Failed to compress vessel data."; }
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB']; $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow)); return round($bytes, $precision) . ' ' . $units[$pow];
}

function getDirStats($dir) {
    $size = 0; $count = 0;
    if (is_dir($dir)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() !== 'htaccess') { $size += $file->getSize(); $count++; }
        }
    }
    return ['size' => $size, 'count' => $count];
}

$basePath = realpath(__DIR__ . '/..');
$bunker_db_size = file_exists(__DIR__ . '/bunker_data.sqlite') ? filesize(__DIR__ . '/bunker_data.sqlite') : 0;
$echo_db_size = file_exists($basePath . '/echo/echo_data.sqlite') ? filesize($basePath . '/echo/echo_data.sqlite') : 0;
$blog_db_size = file_exists($basePath . '/blog/blog_data.sqlite') ? filesize($basePath . '/blog/blog_data.sqlite') : 0;
$index_db_size = file_exists($basePath . '/index/index_data.sqlite') ? filesize($basePath . '/index/index_data.sqlite') : 0;
$grid_db_size = file_exists($basePath . '/grid/grid_data.sqlite') ? filesize($basePath . '/grid/grid_data.sqlite') : 0;
$total_db_size = $bunker_db_size + $echo_db_size + $blog_db_size + $index_db_size + $grid_db_size;

$echo_media = getDirStats($basePath . '/echo/uploads');
$blog_media = getDirStats($basePath . '/blog/uploads');
$total_media_size = $echo_media['size'] + $blog_media['size'];
$total_media_count = $echo_media['count'] + $blog_media['count'];

$db_file = __DIR__ . '/bunker_data.sqlite';
try {
    $pdo = new PDO("sqlite:" . $db_file);
    $stmt = $pdo->query("SELECT * FROM access_logs ORDER BY created_at DESC LIMIT 10");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $logs = []; }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title>MAIN OS - Bunker Control</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/terminal.css">
    
    <style>
        /* CSS Khusus Layout Dashboard */
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .telemetry-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .telemetry-val { font-size: 1.5rem; font-weight: bold; color: var(--t-green); margin-bottom: 5px; }
        
        /* Memperhalus header card telemetry */
        .telemetry-item { background: rgba(0, 255, 65, 0.03); border: 1px dashed var(--t-green-dim); padding: 15px; transition: 0.2s; }
        .telemetry-item:hover { border-color: var(--t-green); box-shadow: 0 0 10px rgba(0, 255, 65, 0.1); }
    </style>
</head>
<body class="t-crt"> 
    <div id="splash-overlay" class="t-splash">
        <div class="font-bold text-success" id="splash-text" style="font-size: 1.1rem; letter-spacing: 2px; text-shadow: 0 0 8px currentColor;">
            > DECRYPTING_DASHBOARD_TELEMETRY<span class="t-loading-dots"></span>
        </div>
    </div>

    <div class="t-container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4" style="border-bottom: 1px dashed var(--t-green-dim); padding-bottom: 15px; margin-top: 20px;">
            <div>
                <h2 class="mb-0 text-success"><span class="t-led-dot t-led-green"></span> BUNKER MAIN O.S.</h2>
                <div class="text-muted fs-small mt-1">> AUTHORIZATION ACCEPTED. WELCOME, COMMANDER.</div>
            </div>
            <form method="POST" class="m-0">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="t-btn danger" title="Sever Connection">[ ➜ ] LOGOUT</button>
            </form>
        </div>

        <?php if (isset($backup_error)): ?>
            <div class="t-alert danger mb-4">> <?= $backup_error ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">
            
            <div class="t-card mb-0 d-flex flex-column justify-content-between">
                <div>
                    <h3 class="mb-1 text-success">> COMMS: ECHO</h3>
                    <p class="text-muted fs-small mb-4">Shortwave transmission station.</p>
                </div>
                <a href="../echo/index.php" class="t-btn t-btn-block">INITIALIZE</a>
            </div>

            <div class="t-card mb-0 d-flex flex-column justify-content-between">
                <div>
                    <h3 class="mb-1 text-success">> ARCHIVE: BLOG</h3>
                    <p class="text-muted fs-small mb-4">Manifesto drafting & data storage.</p>
                </div>
                <a href="blog_manager.php" class="t-btn t-btn-block">INITIALIZE</a>
            </div>

            <div class="t-card mb-0 d-flex flex-column justify-content-between">
                <div>
                    <h3 class="mb-1 text-success">> NAV: INDEX</h3>
                    <p class="text-muted fs-small mb-4">Bookmark and directory cluster.</p>
                </div>
                <a href="../index/index.php" class="t-btn t-btn-block">INITIALIZE</a>
            </div>

            <div class="t-card mb-0 d-flex flex-column justify-content-between">
                <div>
                    <h3 class="mb-1 text-success">> OP: GRID</h3>
                    <p class="text-muted fs-small mb-4">Logistics and task operation matrix.</p>
                </div>
                <a href="../grid/index.php" class="t-btn t-btn-block">INITIALIZE</a>
            </div>

            <div class="t-card mb-0 d-flex flex-column justify-content-between">
                <div>
                    <h3 class="mb-1 text-success">> SECURE: VAULT</h3>
                    <p class="text-muted fs-small mb-4" style="opacity: 0.8;">Zero-knowledge encrypted payload. Requires master key.</p>
                </div>
                <a href="../vault/index.php" class="t-btn t-btn-block">INITIALIZE</a>
            </div>

            <div class="t-card mb-0 d-flex flex-column justify-content-between">
                <div>
                    <h3 class="mb-1 text-success">> TOKEN: AUTH</h3>
                    <p class="text-muted fs-small mb-4" style="opacity: 0.8;">Zero-knowledge 2FA generator. Requires master key.</p>
                </div>
                <a href="../auth/index.php" class="t-btn t-btn-block">INITIALIZE</a>
            </div>

            <div class="t-card danger mb-0 d-flex flex-column justify-content-between">
                <div>
                    <h3 class="mb-1 text-danger">> PROTOCOL: EVAC</h3>
                    <p class="text-danger fs-small mb-4" style="opacity: 0.8;">Package and secure all server data into a zip payload.</p>
                </div>
                <form method="POST" onsubmit="return confirm('WARNING: Initiating full system data download. Proceed?');" class="m-0">
                    <input type="hidden" name="action" value="backup">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="t-btn danger t-btn-block w-100">INITIALIZE EVAC</button>
                </form>
            </div>

        </div>

        <div class="t-card mb-4">
            <h3 class="mb-3 text-success">> CORE_TELEMETRY: DATA BANK INTEGRITY</h3>
            <div class="telemetry-grid">
                
                <div class="telemetry-item">
                    <div class="text-muted fs-small">> TOTAL_LOCAL_DB_WEIGHT</div>
                    <div class="telemetry-val"><?= formatBytes($total_db_size) ?></div>
                    <div class="fs-small text-muted" style="line-height: 1.6;">
                        <span class="text-success">></span> BUNKER: <?= formatBytes($bunker_db_size) ?><br>
                        <span class="text-success">></span> ECHO: <?= formatBytes($echo_db_size) ?><br>
                        <span class="text-success">></span> BLOG: <?= formatBytes($blog_db_size) ?><br>
                        <span class="text-success">></span> INDEX: <?= formatBytes($index_db_size) ?><br>
                        <span class="text-success">></span> GRID: <?= formatBytes($grid_db_size) ?><br>
                        <span class="text-success">> VAULT: [ CLOUD_SATELLITE ]</span><br>
                        <span class="text-success">> AUTH: [ CLOUD_SATELLITE ]</span>
                    </div>
                </div>

                <div class="telemetry-item">
                    <div class="text-muted fs-small">> TOTAL_MEDIA_PAYLOAD</div>
                    <div class="telemetry-val"><?= formatBytes($total_media_size) ?></div>
                    <div class="fs-small text-muted" style="line-height: 1.6;">
                        ATTACHMENTS: <?= $total_media_count ?> FILES<br>
                        <span class="text-success">></span> ECHO_DIR: <?= formatBytes($echo_media['size']) ?><br>
                        <span class="text-success">></span> BLOG_DIR: <?= formatBytes($blog_media['size']) ?>
                    </div>
                </div>

                <div class="telemetry-item">
                    <div class="text-muted fs-small">> SYS_ENVIRONMENT</div>
                    <div class="telemetry-val" style="font-size: 1.2rem; padding-top: 5px;">STABLE</div>
                    <div class="fs-small mt-2 text-muted" style="line-height: 1.6;">
                        PHP_VERSION: <?= phpversion() ?><br>
                        SQLITE_VERSION: <?= $pdo->query('select sqlite_version()')->fetchColumn() ?><br>
                        MAX_UPLOAD: <?= ini_get('upload_max_filesize') ?>
                    </div>
                </div>

            </div>
        </div>

        <div class="t-card mb-5">
            <h3 class="mb-3 text-success">> RADAR: ACCESS LOGS</h3>
            <div class="t-table-wrapper mb-0">
                <table class="t-table">
                    <thead>
                        <tr>
                            <th>TIMESTAMP (WIB)</th>
                            <th>STATUS</th>
                            <th>IP_ORIGIN</th>
                            <th>ENTITY_SIGNATURE (Browser)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="text-muted"><?= htmlspecialchars($log['created_at'] ?? '') ?></td>
                                    <td class="font-bold <?= ($log['status'] ?? '') === 'SUCCESS' ? 'text-success' : 'text-danger' ?>">
                                        [ <?= htmlspecialchars($log['status'] ?? 'UNKNOWN') ?> ]
                                    </td>
                                    <td><?= htmlspecialchars($log['ip_address'] ?? 'UNKNOWN_IP') ?></td>
                                    <td class="text-muted fs-small" title="<?= htmlspecialchars($log['user_agent'] ?? 'UNKNOWN_ENTITY') ?>">
                                        <?= substr(htmlspecialchars($log['user_agent'] ?? 'UNKNOWN_ENTITY'), 0, 30) ?>...
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted">RADAR CLEAR. NO ENTITIES DETECTED.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>

    <script src="../assets/terminal.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Tutup splash screen awal saat halaman dashboard pertama kali dimuat
            if (typeof Terminal !== 'undefined' && Terminal.splash) {
                Terminal.splash.close();
            }
        });
    </script>
</body>
</html>