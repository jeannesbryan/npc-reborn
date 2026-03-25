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
    die("Database arsip belum diinisialisasi. Jalankan init_blog.php terlebih dahulu.");
}

// Proses Hapus Artikel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->execute([(int)$_POST['id']]);
        header("Location: blog_manager.php");
        exit;
    }
}

// Proses Simpan Artikel
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
        
        // Setelah sukses menyimpan ke database, beritahu halaman untuk menghapus auto-save di LocalStorage
        echo "<script>localStorage.removeItem('bunker_blog_draft_content'); localStorage.removeItem('bunker_blog_draft_title'); window.location.href='blog_manager.php';</script>";
        exit;
    }
}

// Endpoint AJAX untuk Upload Media
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

// Ambil data untuk diedit (jika ada)
$edit_data = ['id' => '', 'title' => '', 'slug' => '', 'content' => '', 'status' => 'DRAFT'];
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($res) $edit_data = $res;
}

$articles = $pdo->query("SELECT id, title, slug, status, created_at FROM articles ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blog Manager - Bunker</title>
    <meta name="theme-color" content="#121212">    
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
                <div class="text-muted fs-small">Write your manifesto. (Markdown Supported)</div>
            </div>
            <a href="dashboard.php" class="btn btn-outline-danger">KEMBALI KE DASBOR</a>
        </div>

        <div class="d-flex gap-3 flex-wrap align-items-start">
            <div class="card p-3 mb-4" style="flex: 2; min-width: 300px;">
                <form method="POST" id="editor-form">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="csrf_token" id="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                    
                    <div class="form-group">
                        <label class="form-label d-flex justify-content-between">
                            <span>Judul</span>
                            <span id="autosave-status">Tersimpan otomatis di browser</span>
                        </label>
                        <input type="text" name="title" id="editor-title" class="form-control" value="<?= htmlspecialchars($edit_data['title']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Slug (URL)</label>
                        <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($edit_data['slug']) ?>" placeholder="contoh: artikel-pertama-saya" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Konten (Markdown)</label>
                        <textarea name="content" id="editor-content" class="form-control" style="min-height: 400px;" required><?= htmlspecialchars($edit_data['content']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="DRAFT" <?= $edit_data['status'] === 'DRAFT' ? 'selected' : '' ?>>DRAFT</option>
                            <option value="PUBLISHED" <?= $edit_data['status'] === 'PUBLISHED' ? 'selected' : '' ?>>PUBLISHED</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-light w-100" id="btn-submit-post">SIMPAN TRANSMISI</button>
                </form>
            </div>

            <div style="flex: 1; min-width: 250px;">
                <div class="card p-3 mb-4">
                    <h4 class="mb-2">Media Uploader</h4>
                    <p class="text-muted fs-small mb-2">Pilih beberapa file sekaligus. Proses berjalan di belakang layar.</p>
                    
                    <input type="file" id="media-input" class="form-control text-muted fs-small mb-2" multiple accept="image/*, video/*">
                    <button type="button" id="btn-upload" class="btn btn-dark w-100 border-secondary">Unggah & Dapatkan URL</button>
                    
                    <div id="upload-results" class="mt-2"></div>
                </div>

                <div class="card p-3">
                    <h4 class="mb-3">Daftar Arsip</h4>
                    <?php foreach ($articles as $art): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <div class="fw-bold fs-small"><?= htmlspecialchars($art['title']) ?></div>
                            <div class="text-muted fs-small mb-1">
                                [<?= $art['status'] ?>] - <?= date('d M Y', strtotime($art['created_at'])) ?>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="?edit=<?= $art['id'] ?>" class="text-success fs-small">Edit</a>
                                <form method="POST" onsubmit="return confirm('Hapus permanen?');" style="margin:0;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $art['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <button type="submit" class="btn-danger-text fs-small p-0">Delete</button>
                                </form>
                                <?php if ($art['status'] === 'PUBLISHED'): ?>
                                    <?php 
                                        // Membangun URL dasar secara dinamis
                                        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                                        $host = $_SERVER['HTTP_HOST'];
                                        // Asumsi folder root proyek Anda. Sesuaikan jika berbeda (misal: '/npc-reborn')
                                        $base_dir = dirname(dirname($_SERVER['PHP_SELF'])); 
                                        // Jika base_dir hanya "/", kita kosongkan agar tidak double slash
                                        if ($base_dir === '/' || $base_dir === '\\') { $base_dir = ''; }
                                        
                                        $article_url = $protocol . "://" . $host . $base_dir . "/blog/" . htmlspecialchars($art['slug']);
                                    ?>
                                    <a href="<?= $article_url ?>" target="_blank" class="text-muted fs-small">Lihat</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // === 1. Logika AJAX Upload ===
        const btnUpload = document.getElementById('btn-upload');
        const mediaInput = document.getElementById('media-input');
        const resultsDiv = document.getElementById('upload-results');
        const csrfToken = document.getElementById('csrf_token').value;

        btnUpload.addEventListener('click', (e) => {
            e.preventDefault(); // Mengunci tombol agar tidak me-refresh halaman apapun yang terjadi

            if (mediaInput.files.length === 0) {
                alert('Pilih file terlebih dahulu.');
                return;
            }

            const originalText = btnUpload.innerText;
            btnUpload.innerText = 'Mengunggah...';
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
                    alert('Gagal mengunggah file. Pastikan format didukung.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btnUpload.innerText = originalText;
                btnUpload.disabled = false;
                alert('Terjadi kesalahan jaringan.');
            });
        });

        // === 2. Logika Penyelamat Tulisan (Auto-Save LocalStorage) ===
        const titleInput = document.getElementById('editor-title');
        const contentInput = document.getElementById('editor-content');
        const statusIndicator = document.getElementById('autosave-status');
        
        // Cek apakah kita sedang di mode "Buat Baru" (tidak ada ?edit= di URL)
        const urlParams = new URLSearchParams(window.location.search);
        const isEditing = urlParams.has('edit');

        if (!isEditing) {
            // Pulihkan teks jika ada yang tersimpan di browser
            if (localStorage.getItem('bunker_blog_draft_title')) {
                titleInput.value = localStorage.getItem('bunker_blog_draft_title');
            }
            if (localStorage.getItem('bunker_blog_draft_content')) {
                contentInput.value = localStorage.getItem('bunker_blog_draft_content');
            }

            // Simpan setiap kali ada ketikan
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