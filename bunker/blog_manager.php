<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$db_file = __DIR__ . '/../blog/blog_data.sqlite';
$upload_dir = __DIR__ . '/../blog/uploads/';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("> SYS_ERR: Archive database not initialized. Run init_updater.php first.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        header("Location: blog_manager.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $title = trim($_POST['title']);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['slug'])));
        $content = $_POST['content'];
        $status = $_POST['status'] === 'PUBLISHED' ? 'PUBLISHED' : 'DRAFT';
        $now = date('Y-m-d H:i:s');
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE articles SET title=?, slug=?, content=?, status=? WHERE id=?");
            $stmt->execute([$title, $slug, $content, $status, $id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO articles (title, slug, content, status, created_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $content, $status, $now]);
        }
        
        echo "<script>localStorage.removeItem('bunker_blog_draft_content'); localStorage.removeItem('bunker_blog_draft_title'); window.location.href='blog_manager.php';</script>";
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_media_ajax') {
    header('Content-Type: application/json');
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'CSRF Token Invalid.']);
        exit;
    }

    $results = [];
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm'];

    if (!empty($_FILES['media']['name'][0])) {
        $total = count($_FILES['media']['name']);
        for ($i = 0; $i < $total; $i++) {
            $ext = strtolower(pathinfo($_FILES['media']['name'][$i], PATHINFO_EXTENSION));
            if ($_FILES['media']['error'][$i] === 0 && in_array($ext, $allowed)) {
                $filename = 'media_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['media']['tmp_name'][$i], $upload_dir . $filename)) {
                    $is_video = in_array($ext, ['mp4', 'webm']);
                    $url = "/blog/uploads/" . $filename;
                    $markdown_code = $is_video ? "<video controls src=\"$url\" class=\"w-100 mt-2 mb-2\"></video>" : "![gambar]($url)";
                    $results[] = $markdown_code;
                }
            }
        }
    }
    
    echo json_encode(['success' => true, 'snippets' => $results]);
    exit;
}

$edit_data = ['id' => '', 'title' => '', 'slug' => '', 'content' => '', 'status' => 'DRAFT'];
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) $edit_data = $res;
}

$articles = $pdo->query("SELECT id, title, slug, status, created_at, views FROM articles ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title>Blog Manager - Bunker</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .snippet-box { background: rgba(46, 160, 67, 0.1); border: 1px solid var(--success); padding: 8px; border-radius: 4px; margin-top: 8px; word-break: break-all; }
        .snippet-box code { color: var(--success); font-family: monospace; font-size: 0.85rem; }
        #autosave-status { font-size: 0.75rem; color: var(--success); font-style: italic; display: none; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-4">
            <div>
                <h2 class="mb-0">Blog Manager</h2>
                <div class="text-muted fs-small">> DRAFTING_MANIFESTO. (MARKDOWN SUPPORTED)</div>
            </div>
            <a href="dashboard.php" class="btn btn-danger">[ <-- RETURN TO CONTROL ]</a>
        </div>

        <div class="d-flex gap-3 flex-wrap align-items-start">
            <div class="card p-3 mb-4" style="flex: 2; min-width: 300px;">
                <form method="POST" id="editor-form">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="csrf_token" id="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                    
                    <div class="form-group">
                        <label class="form-label d-flex justify-content-between">
                            <span>> TITLE</span>
                            <span id="autosave-status">Auto-saved locally</span>
                        </label>
                        <input type="text" name="title" id="editor-title" class="form-control" value="<?= htmlspecialchars($edit_data['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">> SLUG (URL)</label>
                        <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($edit_data['slug']) ?>" placeholder="e.g: my-first-article" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">> CONTENT_DATA (MARKDOWN)</label>
                        <textarea name="content" id="editor-content" class="form-control" style="min-height: 400px;" required><?= htmlspecialchars($edit_data['content']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">> SIGNAL_STATUS</label>
                        <select name="status" class="form-control">
                            <option value="DRAFT" <?= $edit_data['status'] === 'DRAFT' ? 'selected' : '' ?>>DRAFT</option>
                            <option value="PUBLISHED" <?= $edit_data['status'] === 'PUBLISHED' ? 'selected' : '' ?>>PUBLISHED</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-main btn-block" id="btn-submit-post">[ SAVE TRANSMISSION ]</button>
                </form>
            </div>

            <div style="flex: 1; min-width: 250px;">
                <div class="card p-3 mb-4">
                    <h4 class="mb-2">Media Uploader</h4>
                    <p class="text-muted fs-small mb-2">Select multiple files. Upload process runs in the background.</p>
                    
                    <input type="file" id="media-input" class="form-control text-muted fs-small mb-2" multiple accept="image/*, video/*">
                    <button type="button" id="btn-upload" class="btn btn-main btn-block">[ UPLOAD & OBTAIN URL ]</button>
                    
                    <div id="upload-results" class="mt-2"></div>
                </div>

                <div class="card p-3">
                    <h4 class="mb-3">Archive Registry</h4>
                    <?php foreach ($articles as $art): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="fw-bold fs-small mb-1"><?= htmlspecialchars($art['title']) ?></div>
                            <div class="text-muted fs-small mb-2">
                                [<?= $art['status'] ?>] - <?= date('d M Y', strtotime($art['created_at'])) ?>
                                <span class="text-success ml-1">(&#x2299; <?= number_format($art['views'] ?? 0) ?> HITS)</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="?edit=<?= $art['id'] ?>" class="btn btn-main btn-sm">[ EDIT ]</a>
                                <?php if ($art['status'] === 'PUBLISHED'): ?>
                                    <?php 
                                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                                        $host = $_SERVER['HTTP_HOST'];
                                        $base_dir = dirname(dirname($_SERVER['PHP_SELF'])); 
                                        if ($base_dir === '/' || $base_dir === '\\') { $base_dir = ''; }
                                        $article_url = $protocol . "://" . $host . $base_dir . "/blog/" . htmlspecialchars($art['slug']);
                                    ?>
                                    <a href="<?= $article_url ?>" target="_blank" class="btn btn-main btn-sm">[ VIEW ]</a>
                                <?php endif; ?>
                                <form method="POST" onsubmit="return confirm('WARNING: Purge this data permanently?');" style="margin:0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $art['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">[ PURGE ]</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const btnUpload = document.getElementById('btn-upload');
        const mediaInput = document.getElementById('media-input');
        const resultsDiv = document.getElementById('upload-results');
        const csrfToken = document.getElementById('csrf_token').value;

        btnUpload.addEventListener('click', (e) => {
            e.preventDefault(); 

            if (mediaInput.files.length === 0) {
                alert('> SYS_ERR: PLEASE SELECT A FILE FIRST.');
                return;
            }

            const originalText = btnUpload.innerText;
            btnUpload.innerText = '[ UPLOADING... ]';
            btnUpload.disabled = true;

            const formData = new FormData();
            formData.append('action', 'upload_media_ajax');
            formData.append('csrf_token', csrfToken);
            
            for (let i = 0; i < mediaInput.files.length; i++) {
                formData.append('media[]', mediaInput.files[i]);
            }

            fetch('blog_manager.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                btnUpload.innerText = originalText;
                btnUpload.disabled = false;
                mediaInput.value = ''; 

                if (data.success && data.snippets.length > 0) {
                    data.snippets.forEach(snippet => {
                        const box = document.createElement('div');
                        box.className = 'snippet-box';
                        box.innerHTML = `<code>${snippet.replace(/</g, '&lt;').replace(/>/g, '&gt;')}</code>`;
                        resultsDiv.appendChild(box);
                    });
                } else {
                    alert('> SYS_ERR: UPLOAD FAILED. ENSURE SUPPORTED FORMAT.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btnUpload.innerText = originalText;
                btnUpload.disabled = false;
                alert('> SYS_ERR: NETWORK ERROR OCCURRED.');
            });
        });

        const titleInput = document.getElementById('editor-title');
        const contentInput = document.getElementById('editor-content');
        const statusIndicator = document.getElementById('autosave-status');
        
        const urlParams = new URLSearchParams(window.location.search);
        const isEditing = urlParams.has('edit');

        if (!isEditing) {
            if (localStorage.getItem('bunker_blog_draft_title')) {
                titleInput.value = localStorage.getItem('bunker_blog_draft_title');
            }
            if (localStorage.getItem('bunker_blog_draft_content')) {
                contentInput.value = localStorage.getItem('bunker_blog_draft_content');
            }

            const saveDraft = () => {
                localStorage.setItem('bunker_blog_draft_title', titleInput.value);
                localStorage.setItem('bunker_blog_draft_content', contentInput.value);
                statusIndicator.style.display = 'inline';
                setTimeout(() => { statusIndicator.style.display = 'none'; }, 2000);
            };

            titleInput.addEventListener('input', saveDraft);
            contentInput.addEventListener('input', saveDraft);
        }
    </script>
</body>
</html>