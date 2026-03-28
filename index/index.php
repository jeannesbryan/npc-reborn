<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../bunker/index.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$db_file = __DIR__ . '/index_data.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("> SYS_ERR: FAILED TO CONNECT TO INDEX CLUSTER. HAVE YOU RUN THE INIT SCRIPT?");
}

// Handler: Tambah Bookmark
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

// Handler: Edit Bookmark
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

// Handler: Hapus Bookmark
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_bookmark') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("> SYS_ERR: AUTHENTICATION FAILED.");
    
    $id = (int)$_POST['id'];
    $pdo->prepare("DELETE FROM bookmarks WHERE id = ?")->execute([$id]);
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
    <title>NAV: INDEX - Bunker Storage</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .cluster-grid { column-width: 300px; column-gap: 20px; }
        .cluster-card { background: rgba(0,255,65,0.02); border: 1px solid var(--border-color); padding: 15px; break-inside: avoid; margin-bottom: 20px; display: inline-block; width: 100%; }
        .cluster-header { color: var(--text-main); font-weight: bold; border-bottom: 1px dashed var(--border-color); padding-bottom: 8px; margin-bottom: 12px; }
        .bookmark-item { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .bookmark-link { color: var(--text-main); text-decoration: none; word-break: break-word; font-size: 0.95rem; padding-right: 10px; }
        .bookmark-link:hover { color: var(--light); text-shadow: 0 0 8px var(--text-main); }
        .bookmark-link::before { content: '[>] '; opacity: 0.7; }
        
        .action-btns { display: flex; gap: 8px; flex-shrink: 0; }

        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); display: flex; align-items: center; justify-content: center; z-index: 1000; opacity: 0; visibility: hidden; transition: 0.2s; backdrop-filter: blur(3px); }
        .modal-overlay.active { opacity: 1; visibility: visible; }
        .modal-dialog { width: 100%; max-width: 500px; background: var(--bg-card); border: 1px solid var(--text-main); box-shadow: 0 0 15px rgba(0,255,65,0.2); padding: 20px; }
        
        /* Styling Search Bar CLI */
        .cli-search { border: none; border-bottom: 1px solid var(--text-main); border-radius: 0; background: transparent; padding-left: 0; color: var(--text-main); box-shadow: none !important; }
        .cli-search:focus { background: transparent; color: var(--text-main); border-bottom: 1px solid var(--light); }
        .cli-search::placeholder { color: var(--text-muted); opacity: 0.5; }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="d-flex justify-content-between align-items-center pb-3 mt-4 mb-4 border-bottom">
            <div>
                <h2 class="mb-0 text-main">[ NAV: INDEX_DIRECTORIES ]</h2>
                <div class="text-muted fs-small mt-1">> STATUS: MEMORY BANK ACCESSED... <span class="blinking-cursor"></span></div>
            </div>
            <a href="../bunker/dashboard.php" class="btn btn-danger">[ RETURN_TO_BUNKER ]</a>
        </div>

        <div class="card p-3 mb-4">
            <h4 class="mb-3 fs-small">> INJECT_NEW_COORDINATES</h4>
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="add_bookmark">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <div class="row" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <input type="text" class="form-control" name="title" placeholder="Link Title (e.g. GitHub)" required>
                    </div>
                    <div style="flex: 1.5; min-width: 200px;">
                        <input type="url" class="form-control" name="url" placeholder="URL (e.g. https://github.com)" required>
                    </div>
                    <div style="flex: 1; min-width: 150px;">
                        <input type="text" class="form-control" name="category" list="category-list" placeholder="Dir / Category" required autocomplete="off">
                        <datalist id="category-list">
                            <?php foreach($unique_categories as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-main btn-block">[ INJECT ]</button>
                    </div>
                </div>
            </form>
        </div>

        <?php if (count($clusters) > 0): ?>
        <div class="card p-3 mb-5" style="border-style: dashed; background: rgba(0,255,65,0.02);">
            <div class="d-flex align-items-center gap-2">
                <span class="text-main">></span>
                <input type="text" id="live-search" class="form-control cli-search" placeholder="QUERY_MEMORY_BANK... (Search by title, url, or directory)">
            </div>
        </div>
        <?php endif; ?>

        <?php if (count($clusters) > 0): ?>
            <div class="cluster-grid" id="cluster-container">
                <?php foreach ($clusters as $category => $bookmarks): ?>
                    <div class="cluster-card">
                        <div class="cluster-header">> DIR: <span class="cat-name"><?= htmlspecialchars($category) ?></span></div>
                        
                        <?php foreach ($bookmarks as $bm): ?>
                            <div class="bookmark-item">
                                <a href="<?= htmlspecialchars($bm['url']) ?>" target="_blank" class="bookmark-link" title="<?= htmlspecialchars($bm['url']) ?>">
                                    <?= htmlspecialchars($bm['title']) ?>
                                </a>
                                <div class="action-btns">
                                    <button type="button" class="btn btn-main btn-sm" 
                                        data-id="<?= $bm['id'] ?>" 
                                        data-title="<?= htmlspecialchars($bm['title']) ?>" 
                                        data-url="<?= htmlspecialchars($bm['url']) ?>" 
                                        data-category="<?= htmlspecialchars($bm['category']) ?>" 
                                        onclick="openEditModal(this)" title="Edit Link">[EDIT]</button>
                                    
                                    <form method="POST" onsubmit="return confirm('WARNING: Delete [ <?= htmlspecialchars($bm['title']) ?> ] from database?');" style="margin: 0;">
                                        <input type="hidden" name="action" value="delete_bookmark">
                                        <input type="hidden" name="id" value="<?= $bm['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Purge Link">[X]</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>

                    </div>
                <?php endforeach; ?>
            </div>
            
            <div id="search-empty-state" class="text-center text-danger mt-4 d-none">
                > SYS_ERR: NO COORDINATES MATCH YOUR QUERY.
            </div>

        <?php else: ?>
            <div class="text-center text-muted mt-5 mb-5 py-5" style="border: 1px dashed var(--border-color);">
                > MEMORY BANK EMPTY. NO COORDINATES DETECTED.<br>
                <span class="fs-small">Awaiting initial injection...</span>
            </div>
        <?php endif; ?>

    </div>

    <div id="editModal" class="modal-overlay">
        <div class="modal-dialog">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h3 class="mb-0 text-main">> MODIFY_COORDINATES</h3>
            </div>
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="edit_bookmark">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id" id="edit-id">
                
                <div class="form-group mb-3">
                    <label class="text-muted fs-small mb-1">> TITLE</label>
                    <input type="text" class="form-control" name="title" id="edit-title" required>
                </div>
                <div class="form-group mb-3">
                    <label class="text-muted fs-small mb-1">> URL_TARGET</label>
                    <input type="url" class="form-control" name="url" id="edit-url" required>
                </div>
                <div class="form-group mb-4">
                    <label class="text-muted fs-small mb-1">> DIRECTORY / CLUSTER</label>
                    <input type="text" class="form-control" name="category" id="edit-category" list="category-list-modal" required autocomplete="off">
                    <datalist id="category-list-modal">
                        <?php foreach($unique_categories as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-danger" onclick="closeEditModal()">[ ABORT ]</button>
                    <button type="submit" class="btn btn-main">[ EXECUTE_OVERRIDE ]</button>
                </div>
            </form>
        </div>
    </div>

    <script>
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

        const modal = document.getElementById('editModal');

        function openEditModal(button) {
            document.getElementById('edit-id').value = button.getAttribute('data-id');
            document.getElementById('edit-title').value = button.getAttribute('data-title');
            document.getElementById('edit-url').value = button.getAttribute('data-url');
            document.getElementById('edit-category').value = button.getAttribute('data-category');
            modal.classList.add('active');
        }

        function closeEditModal() {
            modal.classList.remove('active');
        }

        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>