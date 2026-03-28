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
    <title>MAIN OS - Bunker Control</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="manifest" href="../manifest.json">
    <style>
        .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .dashboard-card { display: flex; flex-direction: column; justify-content: space-between; }
        .telemetry-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .telemetry-item { background: rgba(0,255,65,0.05); border: 1px dashed var(--border-color); padding: 15px; }
        .telemetry-val { font-size: 1.5rem; font-weight: bold; color: var(--text-main); margin-bottom: 5px; }
    </style>
</head>
<body>

    <div id="splash-overlay">
        <div class="splash-content text-main">
            > PROCESSING_ENCRYPTED_SATELLITE_TRANSMISSION<span class="loading-dots"></span>
        </div>
    </div>

    <div class="container" style="max-width: 1200px;">
        
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mt-4 mb-4">
            <div>
                <h2 class="mb-0"><span class="status-indicator"></span> BUNKER MAIN O.S.</h2>
                <div class="text-muted fs-small mt-1">> AUTHORIZATION ACCEPTED. WELCOME, COMMANDER.</div>
            </div>
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn-danger btn-icon" title="Sever Connection">[ ➜ ]</button>
            </form>
        </div>

        <div id="pwa-install-banner">
            <div>
                <strong class="text-main">> SYS_OPTIMIZATION_AVAILABLE</strong><br>
                <span class="fs-small text-muted">Install UI to home screen for isolated access.</span>
            </div>
            <button id="btn-install-pwa" class="btn btn-main btn-sm">[ INSTALL_MODULE ]</button>
        </div>

        <?php if (isset($backup_error)): ?>
            <div class="alert-bunker p-2 mb-3 text-center" style="color: var(--danger); border: 1px solid var(--danger);"><?= $backup_error ?></div>
        <?php endif; ?>

        <div class="dashboard-cards">
            
            <div class="card p-3 dashboard-card">
                <div>
                    <h3 class="mb-1 text-main">COMMS: ECHO</h3>
                    <p class="text-muted fs-small mb-3">Shortwave transmission station.</p>
                </div>
                <a href="../echo/index.php" target="_blank" class="btn btn-main btn-block app-link">> INITIALIZE</a>
            </div>

            <div class="card p-3 dashboard-card">
                <div>
                    <h3 class="mb-1 text-main">ARCHIVE: BLOG</h3>
                    <p class="text-muted fs-small mb-3">Manifesto drafting & data storage.</p>
                </div>
                <a href="blog_manager.php" target="_blank" class="btn btn-main btn-block app-link">> INITIALIZE</a>
            </div>

            <div class="card p-3 dashboard-card">
                <div>
                    <h3 class="mb-1 text-main">NAV: INDEX</h3>
                    <p class="text-muted fs-small mb-3">Bookmark and directory cluster.</p>
                </div>
                <a href="../index/index.php" target="_blank" class="btn btn-main btn-block app-link">> INITIALIZE</a>
            </div>

            <div class="card p-3 dashboard-card">
                <div>
                    <h3 class="mb-1 text-main">OP: GRID</h3>
                    <p class="text-muted fs-small mb-3">Logistics and task operation matrix.</p>
                </div>
                <a href="../grid/index.php" target="_blank" class="btn btn-main btn-block app-link">> INITIALIZE</a>
            </div>

            <div class="card p-3 dashboard-card">
                <div>
                    <h3 class="mb-1 text-main">SECURE: VAULT</h3>
                    <p class="text-muted fs-small mb-3" style="opacity: 0.8;">Zero-knowledge encrypted payload. Requires master key.</p>
                </div>
                <a href="../vault/index.php" target="_blank" class="btn btn-main btn-block app-link">> INITIALIZE</a>
            </div>

            <div class="card p-3 dashboard-card">
                <div>
                    <h3 class="mb-1 text-main">PROTOCOL: EVAC</h3>
                    <p class="text-muted fs-small mb-3" style="opacity: 0.8;">Package and secure all server data into a zip payload.</p>
                </div>
                <form method="POST" onsubmit="return confirm('WARNING: Initiating full system data download. Proceed?');" style="margin: 0;">
                    <input type="hidden" name="action" value="backup">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="btn btn-main btn-block w-100">> INITIALIZE</button>
                </form>
            </div>

        </div>

        <div class="card p-3 mb-4">
            <h3 class="mb-3">> CORE_TELEMETRY: DATA BANK INTEGRITY</h3>
            <div class="telemetry-grid">
                
                <div class="telemetry-item">
                    <div class="text-muted fs-small">> TOTAL_LOCAL_DB_WEIGHT</div>
                    <div class="telemetry-val"><?= formatBytes($total_db_size) ?></div>
                    <div class="fs-small" style="color: var(--text-muted); line-height: 1.4;">
                        <span class="text-main">></span> BUNKER: <?= formatBytes($bunker_db_size) ?><br>
                        <span class="text-main">></span> ECHO: <?= formatBytes($echo_db_size) ?><br>
                        <span class="text-main">></span> BLOG: <?= formatBytes($blog_db_size) ?><br>
                        <span class="text-main">></span> INDEX: <?= formatBytes($index_db_size) ?><br>
                        <span class="text-main">></span> GRID: <?= formatBytes($grid_db_size) ?><br>
                        <span class="text-main">> VAULT: [ CLOUD_SATELLITE ]</span>
                    </div>
                </div>

                <div class="telemetry-item">
                    <div class="text-muted fs-small">> TOTAL_MEDIA_PAYLOAD</div>
                    <div class="telemetry-val"><?= formatBytes($total_media_size) ?></div>
                    <div class="fs-small" style="color: var(--text-muted); line-height: 1.4;">
                        ATTACHMENTS: <?= $total_media_count ?> FILES<br>
                        <span class="text-main">></span> ECHO_DIR: <?= formatBytes($echo_media['size']) ?><br>
                        <span class="text-main">></span> BLOG_DIR: <?= formatBytes($blog_media['size']) ?>
                    </div>
                </div>

                <div class="telemetry-item">
                    <div class="text-muted fs-small">> SYS_ENVIRONMENT</div>
                    <div class="telemetry-val" style="font-size: 1.2rem; padding-top: 5px;">STABLE</div>
                    <div class="fs-small mt-1" style="color: var(--text-muted); line-height: 1.4;">
                        PHP_VERSION: <?= phpversion() ?><br>
                        SQLITE_VERSION: <?= $pdo->query('select sqlite_version()')->fetchColumn() ?><br>
                        MAX_UPLOAD: <?= ini_get('upload_max_filesize') ?>
                    </div>
                </div>

            </div>
        </div>

        <div class="card p-3 mb-5">
            <h3 class="mb-3">> RADAR: ACCESS LOGS</h3>
            <div class="table-responsive">
                <table>
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
                                    <td class="fw-bold <?= ($log['status'] ?? '') === 'SUCCESS' ? 'text-success' : 'text-danger' ?>">
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

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const splash = document.getElementById('splash-overlay');
            const splashContent = splash.querySelector('.splash-content');

            setTimeout(() => { splash.classList.add('splash-hidden'); }, 3000);

            const appLinks = document.querySelectorAll('.app-link');
            appLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault(); 
                    const targetUrl = this.getAttribute('href');
                    
                    splashContent.className = `splash-content text-main`;
                    splash.classList.remove('splash-hidden');

                    setTimeout(() => { 
                        window.open(targetUrl, '_blank'); 
                        splash.classList.add('splash-hidden'); 
                    }, 3000);
                });
            });
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

        if ('serviceWorker' in navigator) { navigator.serviceWorker.register('../sw.js').catch(err => console.log('SW Reg Failed:', err)); }
    </script>
</body>
</html>