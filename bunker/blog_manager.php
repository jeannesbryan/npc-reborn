<?php
require_once 'core.php';
require_login();

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/jeannesbryan/terminal/terminal.css">
    <style>
        .snippet-box { 
            background: rgba(0, 255, 65, 0.05); 
            border: 1px dashed var(--t-green-dim); 
            padding: 10px; 
            margin-top: 10px; 
            word-break: break-all; 
        }
        .snippet-box code { 
            color: var(--t-green); 
            font-family: inherit; 
            font-size: 12px; 
        }
        #autosave-status { 
            font-size: 11px; 
            color: var(--t-green); 
            display: none; 
        }
        input[type="file"].t-input {
            padding: 5px 0;
            cursor: pointer;
        }
        input[type="file"]::file-selector-button {
            background: transparent;
            color: var(--t-green);
            border: 1px solid var(--t-green-dim);
            padding: 4px 8px;
            font-family: inherit;
            font-size: 11px;
            cursor: pointer;
            margin-right: 10px;
        }
        input[type="file"]::file-selector-button:hover {
            background: rgba(0, 255, 65, 0.1);
            border-color: var(--t-green);
        }
    </style>
</head>
<body class="t-crt">

    <div class="t-container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4" style="border-bottom: 1px dashed var(--t-green-dim); padding-bottom: 15px; margin-top: 20px;">
            <div>
                <h2 class="mb-0 text-success"><span class="t-led-dot t-led-green"></span> BLOG_MANAGER</h2>
                <div class="text-muted fs-small mt-1">> AUTHORIZATION ACCEPTED. DRAFTING_MANIFESTO.</div>
            </div>
            <div>
                <a href="dashboard.php" class="t-btn danger" title="Return to Dashboard">[ ➜ ] RETURN_OS</a>
            </div>
        </div>

        <div class="mb-4">
            <div class="text-muted fs-small">Sistem mendukung Markdown (.md) dan penyimpanan lokal otomatis.</div>
        </div>

        <div class="t-grid-layout">
            
            <div class="t-main-panel">
                <div class="t-card">
                    <div class="t-card-header">> COMPOSER_TERMINAL</div>
                    <form method="POST" id="editor-form" class="m-0">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="csrf_token" id="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                        
                        <div class="mb-3">
                            <label class="t-form-label d-flex justify-content-between">
                                <span>> TITLE</span>
                                <span id="autosave-status" class="t-blink">[Auto-saved locally]</span>
                            </label>
                            <input type="text" name="title" id="editor-title" class="t-input" value="<?= htmlspecialchars($edit_data['title']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="t-form-label">> SLUG (URL)</label>
                            <input type="text" name="slug" class="t-input" value="<?= htmlspecialchars($edit_data['slug']) ?>" placeholder="e.g: operation-bunker-alpha" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="t-form-label">> CONTENT_DATA (MARKDOWN)</label>
                            <textarea name="content" id="editor-content" class="t-textarea" style="min-height: 400px;" required><?= htmlspecialchars($edit_data['content']) ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="t-form-label">> SIGNAL_STATUS</label>
                            <select name="status" class="t-select">
                                <option value="DRAFT" <?= $edit_data['status'] === 'DRAFT' ? 'selected' : '' ?>>DRAFT (Local Only)</option>
                                <option value="PUBLISHED" <?= $edit_data['status'] === 'PUBLISHED' ? 'selected' : '' ?>>PUBLISHED (Broadcast)</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="t-btn t-btn-block" id="btn-submit-post">[ SAVE TRANSMISSION ]</button>
                    </form>
                </div>
            </div>

            <div class="t-side-panel">
                
                <div class="t-card mb-4">
                    <div class="t-card-header">> MEDIA_UPLOADER</div>
                    <p class="text-muted fs-small mb-3">Pilih file untuk diunggah ke server. Proses berjalan di latar belakang.</p>
                    
                    <input type="file" id="media-input" class="t-input text-muted fs-small mb-3" multiple accept="image/*, video/*">
                    <button type="button" id="btn-upload" class="t-btn w-100">[ UPLOAD & OBTAIN URL ]</button>
                    
                    <div id="upload-results" class="mt-2"></div>
                </div>

                <div class="t-card mb-4">
                    <div class="t-card-header">> ARCHIVE_REGISTRY</div>
                    
                    <div class="t-list-group m-0" style="border: none;">
                        <?php if (count($articles) > 0): ?>
                            <?php foreach ($articles as $art): ?>
                                <div class="py-2" style="border-bottom: 1px dashed var(--t-green-dim); margin-bottom: 10px; padding-bottom: 15px;">
                                    <div class="font-bold mb-1" style="color: var(--t-green);">
                                        <?= htmlspecialchars($art['title']) ?>
                                    </div>
                                    <div class="text-muted fs-small mb-2">
                                        <?php if ($art['status'] === 'PUBLISHED'): ?>
                                            <span class="t-badge text-success">PUBLISHED</span>
                                        <?php else: ?>
                                            <span class="t-badge warning">DRAFT</span>
                                        <?php endif; ?>
                                        <span class="ml-2">- <?= date('d M Y', strtotime($art['created_at'])) ?></span>
                                        <div class="mt-1 opacity-75">(&#x2299; <?= number_format($art['views'] ?? 0) ?> HITS)</div>
                                    </div>
                                    
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="?edit=<?= $art['id'] ?>" class="t-btn t-btn-sm">[ EDIT ]</a>
                                        
                                        <?php if ($art['status'] === 'PUBLISHED'): ?>
                                            <?php 
                                                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                                                $host = $_SERVER['HTTP_HOST'];
                                                $base_dir = dirname(dirname($_SERVER['PHP_SELF'])); 
                                                if ($base_dir === '/' || $base_dir === '\\') { $base_dir = ''; }
                                                $article_url = $protocol . "://" . $host . $base_dir . "/blog/" . htmlspecialchars($art['slug']);
                                            ?>
                                            <a href="<?= $article_url ?>" target="_blank" class="t-btn t-btn-sm" style="color: #a8ffb7; border-color: #a8ffb7;">[ VIEW ]</a>
                                        <?php endif; ?>
                                        
                                        <form method="POST" onsubmit="return confirm('WARNING: Purge this data permanently?');" class="m-0">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $art['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <button type="submit" class="t-btn danger t-btn-sm">[ PURGE ]</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-muted fs-small text-center py-3">NO ARCHIVES FOUND.</div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/gh/jeannesbryan/terminal/terminal.js"></script>
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

        // Autosave Logic
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