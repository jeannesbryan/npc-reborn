<?php
require_once realpath(__DIR__ . '/../bunker/core.php');
require_login();

$db_file = __DIR__ . '/index_data.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("> SYS_ERR: FAILED TO CONNECT TO INDEX CLUSTER. HAVE YOU RUN THE INIT SCRIPT?");
}

// 1. Handler: Tambah Bookmark
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_bookmark') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("> SYS_ERR: AUTHENTICATION FAILED.");
    
    $title = trim($_POST['title'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $category = strtoupper(trim($_POST['category'] ?? 'UNCLASSIFIED'));
    if (empty($category)) $category = 'UNCLASSIFIED';

    if (!empty($title) && !empty($url)) {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }
        $stmt = $pdo->prepare("INSERT INTO bookmarks (title, url, category) VALUES (?, ?, ?)");
        $stmt->execute([htmlspecialchars($title), htmlspecialchars($url), htmlspecialchars($category)]);
    }
    header("Location: index.php");
    exit;
}

// 2. Handler: Edit Bookmark
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_bookmark') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("> SYS_ERR: AUTHENTICATION FAILED.");
    
    $id = (int)$_POST['id'];
    $title = trim($_POST['title'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $category = strtoupper(trim($_POST['category'] ?? 'UNCLASSIFIED'));
    if (empty($category)) $category = 'UNCLASSIFIED';

    if (!empty($title) && !empty($url)) {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }
        $stmt = $pdo->prepare("UPDATE bookmarks SET title = ?, url = ?, category = ? WHERE id = ?");
        $stmt->execute([htmlspecialchars($title), htmlspecialchars($url), htmlspecialchars($category), $id]);
    }
    header("Location: index.php");
    exit;
}

// 3. Handler: Hapus 1 Bookmark
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_bookmark') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("> SYS_ERR: AUTHENTICATION FAILED.");
    
    $id = (int)$_POST['id'];
    $pdo->prepare("DELETE FROM bookmarks WHERE id = ?")->execute([$id]);
    header("Location: index.php");
    exit;
}

// 4. NEW Handler: Hapus 1 Kategori Penuh (Mass Purge)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_category') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("> SYS_ERR: AUTHENTICATION FAILED.");
    
    $category = trim($_POST['category'] ?? '');
    if (!empty($category)) {
        $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE category = ?");
        $stmt->execute([$category]);
    }
    header("Location: index.php");
    exit;
}

// Mengambil Data
$stmt = $pdo->query("SELECT * FROM bookmarks ORDER BY category ASC, id DESC");
$all_bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$clusters = [];
$unique_categories = [];
foreach ($all_bookmarks as $bm) {
    $cat = $bm['category'];
    if (!isset($clusters[$cat])) {
        $clusters[$cat] = [];
        $unique_categories[] = $cat;
    }
    $clusters[$cat][] = $bm;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title>NAV: INDEX - Bunker Storage</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/jeannesbryan/terminal/terminal.css">    
    <style>
        /* Styling mikro untuk link agar lebih retro */
        .bookmark-link::before { content: '[>] '; opacity: 0.5; color: var(--t-green-dim); }
        .bookmark-link:hover::before { opacity: 1; color: var(--t-green); }
    </style>
</head>
<body class="t-crt">
    
    <div id="splash-overlay" class="t-splash">
        <div class="font-bold text-success" id="splash-text" style="font-size: 1.1rem; letter-spacing: 2px; text-shadow: 0 0 8px currentColor;">
            > MOUNTING_INDEX_DIRECTORIES<span class="t-loading-dots"></span>
        </div>
    </div>

    <div class="t-container-fluid pt-0">
        
        <div class="d-flex justify-content-between align-items-center mb-4 t-border-bottom pb-3 mt-4 flex-wrap gap-3">
            <div>
                <h2 class="mb-0 text-success"><span class="t-led-dot t-led-green"></span> NAV: INDEX_DIRECTORIES</h2>
                <div class="text-muted fs-small mt-1">> STATUS: MEMORY BANK ACCESSED... <span class="t-blink">_</span></div>
            </div>
            <div>
                <a href="../bunker/dashboard.php" class="t-btn danger" title="Return to Dashboard">[ ➜ ] RETURN_OS</a>
            </div>
        </div>

        <div class="t-card mb-4">
            <h4 class="mb-3 fs-small text-success">> INJECT_NEW_COORDINATES</h4>
            <form method="POST" class="m-0">
                <input type="hidden" name="action" value="add_bookmark">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="d-flex flex-wrap gap-2">
                    <input type="text" class="t-input m-0" name="title" placeholder="> TITLE (e.g. GitHub)" required style="flex: 1; min-width: 200px;">
                    <input type="url" class="t-input m-0" name="url" placeholder="> URL_TARGET (https://...)" required style="flex: 1.5; min-width: 200px;">
                    <div class="d-flex gap-2" style="flex: 1; min-width: 250px;">
                        <input type="text" class="t-input m-0" name="category" list="category-list" placeholder="> DIRECTORY / CAT" required autocomplete="off" style="width: 100%;">
                        <datalist id="category-list">
                            <?php foreach($unique_categories as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <button type="submit" class="t-btn font-bold t-glow">[ INJECT ]</button>
                    </div>
                </div>
            </form>
        </div>

        <?php if (count($clusters) > 0): ?>
        <div class="t-card mb-4 p-3" style="background: rgba(0,255,65,0.02); border-style: dashed;">
            <div class="d-flex align-items-center gap-2">
                <span class="text-success font-bold">></span>
                <input type="text" id="live-search" class="t-input m-0 p-0 border-0" placeholder="QUERY_MEMORY_BANK... (Search by title, url, or directory)" style="box-shadow: none;">
            </div>
        </div>
        <?php endif; ?>

        <?php if (count($clusters) > 0): ?>
            <div class="t-masonry" id="cluster-container">
                <?php foreach ($clusters as $category => $bookmarks): ?>
                    
                    <div class="t-masonry-item t-card p-3 cluster-card mb-4" style="background: rgba(0,255,65,0.01);">
                        <div class="d-flex justify-content-between align-items-center t-border-bottom pb-2 mb-3">
                            <div class="font-bold text-success">> DIR: <span class="cat-name"><?= htmlspecialchars($category) ?></span></div>
                            
                            <form method="POST" onsubmit="return confirm('> CRITICAL WARNING!\n\nThis will purge directory [ <?= htmlspecialchars($category, ENT_QUOTES) ?> ] and ALL coordinates inside it permanently.\n\nPROCEED?');" class="m-0">
                                <input type="hidden" name="action" value="delete_category">
                                <input type="hidden" name="category" value="<?= htmlspecialchars($category) ?>">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <button type="submit" class="t-btn danger t-btn-sm" title="Purge Entire Directory">[ X PURGE_DIR ]</button>
                            </form>
                        </div>
                        
                        <?php foreach ($bookmarks as $bm): ?>
                            <div class="d-flex justify-content-between align-items-start mb-2 bookmark-item">
                                <a href="<?= htmlspecialchars($bm['url']) ?>" target="_blank" class="text-success bookmark-link" style="text-decoration: underline dashed; font-size: 13px; word-break: break-word; padding-right: 10px; line-height: 1.4;">
                                    <?= htmlspecialchars($bm['title']) ?>
                                </a>
                                
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <button type="button" class="t-btn t-btn-sm" 
                                        data-id="<?= $bm['id'] ?>" 
                                        data-title="<?= htmlspecialchars($bm['title'], ENT_QUOTES) ?>" 
                                        data-url="<?= htmlspecialchars($bm['url'], ENT_QUOTES) ?>" 
                                        data-category="<?= htmlspecialchars($bm['category'], ENT_QUOTES) ?>" 
                                        onclick="openEditModal(this)" title="Edit Link">[EDIT]</button>
                                    
                                    <form method="POST" onsubmit="return confirm('Purge coordinate [ <?= htmlspecialchars($bm['title'], ENT_QUOTES) ?> ]?');" class="m-0">
                                        <input type="hidden" name="action" value="delete_bookmark">
                                        <input type="hidden" name="id" value="<?= $bm['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="t-btn danger t-btn-sm" title="Purge Link">[X]</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>
                <?php endforeach; ?>
            </div>
            
            <div id="search-empty-state" class="text-center text-danger mt-4 mb-5 d-none t-flicker">
                > SYS_ERR: NO COORDINATES MATCH YOUR QUERY.
            </div>

        <?php else: ?>
            <div class="t-card text-center text-muted mt-5 mb-5 py-5" style="border-style: dashed;">
                > MEMORY BANK EMPTY. NO COORDINATES DETECTED.<br>
                <span class="fs-small">Awaiting initial injection...</span>
            </div>
        <?php endif; ?>

    </div>

    <div id="editModal" class="t-modal">
        <div class="t-modal-content">
            <div class="t-card-header d-flex justify-content-between align-items-center">
                <span class="text-success">> MODIFY_COORDINATES</span>
                <button class="t-btn danger t-btn-sm" onclick="Terminal.modal.close('editModal')">[ X ]</button>
            </div>
            
            <form method="POST" class="m-0">
                <input type="hidden" name="action" value="edit_bookmark">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id" id="edit-id">
                
                <label class="t-form-label">> TITLE</label>
                <input type="text" class="t-input" name="title" id="edit-title" required>
                
                <label class="t-form-label">> URL_TARGET</label>
                <input type="url" class="t-input" name="url" id="edit-url" required>
                
                <label class="t-form-label">> DIRECTORY / CLUSTER</label>
                <input type="text" class="t-input" name="category" id="edit-category" list="category-list-modal" required autocomplete="off">
                
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="t-btn danger" onclick="Terminal.modal.close('editModal')">[ ABORT ]</button>
                    <button type="submit" class="t-btn t-glow font-bold">[ EXECUTE_OVERRIDE ]</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/gh/jeannesbryan/terminal/terminal.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            if (typeof Terminal !== 'undefined' && Terminal.splash) {
                Terminal.splash.close(1000); // Tutup splash screen sedikit lebih cepat
            }
        });

        // Live Search Script
        const searchInput = document.getElementById('live-search');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                const clusters = document.querySelectorAll('.cluster-card');
                const emptyState = document.getElementById('search-empty-state');
                let totalMatches = 0;

                clusters.forEach(cluster => {
                    const items = cluster.querySelectorAll('.bookmark-item');
                    const categoryName = cluster.querySelector('.cat-name').innerText.toLowerCase();
                    let clusterHasMatch = false;

                    items.forEach(item => {
                        const title = item.querySelector('.bookmark-link').innerText.toLowerCase();
                        const url = item.querySelector('.bookmark-link').getAttribute('href').toLowerCase();

                        if (title.includes(query) || url.includes(query) || categoryName.includes(query)) {
                            item.style.display = 'flex';
                            clusterHasMatch = true;
                            totalMatches++;
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    // Karena menggunakan Masonry, kita menggunakan inline-block
                    if (clusterHasMatch) {
                        cluster.style.display = 'inline-block';
                    } else {
                        cluster.style.display = 'none';
                    }
                });

                if (totalMatches === 0 && query !== '') {
                    emptyState.classList.remove('d-none');
                } else {
                    emptyState.classList.add('d-none');
                }
            });
        }

        // Script Modal Terminal UI
        function openEditModal(button) {
            document.getElementById('edit-id').value = button.getAttribute('data-id');
            document.getElementById('edit-title').value = button.getAttribute('data-title');
            document.getElementById('edit-url').value = button.getAttribute('data-url');
            document.getElementById('edit-category').value = button.getAttribute('data-category');
            
            // Buka modal pakai script bawaan framework
            Terminal.modal.open('editModal');
        }
    </script>
</body>
</html>