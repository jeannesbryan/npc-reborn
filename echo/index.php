<?php
session_start();
// Pastikan zona waktu sudah di set ke Jakarta
date_default_timezone_set('Asia/Jakarta');

// [BARU] Buat CSRF Token jika belum ada di sesi ini
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1. Konfigurasi Sistem
$db_file = __DIR__ . '/echo_data.sqlite';
$upload_dir = __DIR__ . '/uploads/';
$is_admin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// 2. Koneksi Database
try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA foreign_keys = ON;"); 
} catch (PDOException $e) {
    die("Kesalahan Koneksi The Void.");
}

// 3. Helper Functions
function format_indo_date($datetime) {
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $timestamp = strtotime($datetime);
    return date('j', $timestamp) . ' ' . $bulan[(int)date('n', $timestamp)] . ' ' . date('Y', $timestamp) . ' - ' . date('H:i', $timestamp) . ' WIB';
}

function parse_echo_embeds($text) {
    // A. Embed YouTube
    $text = preg_replace(
        '~(?:https?://)?(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]{11})(?:\S+)?~i',
        '<div class="ratio mt-2 mb-2"><iframe src="https://www.youtube.com/embed/$1" allowfullscreen></iframe></div>',
        $text
    );
    // B. Embed Direct Image
    $text = preg_replace(
        '~(?<!src=")(https?://[^\s]+(?:\.jpg|\.jpeg|\.png|\.gif|\.webp))~i',
        '<img src="$1" class="echo-media-single mt-2 mb-2 zoomable-image" title="Klik untuk memperbesar" alt="External Image">',
        $text
    );
    // C. Regular Links
    $text = preg_replace(
        '~(?<!src="|")\b(https?://[^\s<]+)\b~i',
        '<a href="$1" target="_blank">$1</a>',
        $text
    );
    return $text;
}

// 4. Proses Hapus Postingan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_post' && $is_admin) {
    // [BARU] Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Transmisi ditolak: Validasi integritas CSRF gagal.");
    }

    $post_id = (int)$_POST['post_id'];
    
    $stmt = $pdo->prepare("SELECT file_name FROM post_media WHERE post_id = ?");
    $stmt->execute([$post_id]);
    while ($media = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $file_path = $upload_dir . $media['file_name'];
        if (file_exists($file_path)) unlink($file_path);
    }

    $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$post_id]);
    header("Location: index.php");
    exit;
}

// 5. Proses Tambah Postingan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_post' && $is_admin) {
    // [BARU] Validasi CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Transmisi ditolak: Validasi integritas CSRF gagal.");
    }

    $content = trim($_POST['content'] ?? '');
    $files = $_FILES['media'] ?? null;
    $total_files = !empty($files['name'][0]) ? count($files['name']) : 0;

    if ($total_files > 4) {
        die("Transmisi ditolak. Maksimal 4 file media per echo.");
    }

    if ($content !== '' || $total_files > 0) {
        $stmt = $pdo->prepare("INSERT INTO posts (content, created_at) VALUES (:content, :created_at)");
        $stmt->execute([
            ':content' => htmlspecialchars($content),
            ':created_at' => date('Y-m-d H:i:s') 
        ]); 
        
        $post_id = $pdo->lastInsertId();

        if ($total_files > 0) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'ogg'];
            
            for ($i = 0; $i < $total_files; $i++) {
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                if ($files['error'][$i] === 0 && in_array($ext, $allowed_ext)) {
                    $new_filename = uniqid('echo_') . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($files['tmp_name'][$i], $upload_dir . $new_filename)) {
                        $pdo->prepare("INSERT INTO post_media (post_id, file_name) VALUES (?, ?)")->execute([$post_id, $new_filename]);
                    }
                }
            }
        }
    }
    header("Location: index.php");
    exit;
}

// 6. Endpoint AJAX (Merender Timeline)
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $stmt = $pdo->prepare("SELECT * FROM posts ORDER BY created_at DESC LIMIT 10 OFFSET :offset");
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($posts) === 0) {
        echo "END"; 
        exit;
    }

    $video_extensions = ['mp4', 'webm', 'ogg'];

    foreach ($posts as $post) {
        $media_stmt = $pdo->prepare("SELECT file_name FROM post_media WHERE post_id = ?");
        $media_stmt->execute([$post['id']]);
        $medias = $media_stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="card mb-3">
            <div class="p-3">
                
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="d-flex gap-2 align-items-center">
                        <img src="../assets/jeannesbryan.webp" class="avatar" width="45" height="45" alt="Avatar">
                        <div>
                            <div class="fw-bold">Jeannes Bryan <span class="text-muted fw-normal fs-small">&#x25CF; @jeannesbryan</span></div>
                            <div class="text-muted fs-small"><?= format_indo_date($post['created_at']) ?></div>
                        </div>
                    </div>
                    
                    <?php if ($is_admin): ?>
                    <form method="POST" onsubmit="return confirm('Hapus jejak gema ini secara permanen?');" style="margin:0;">
                        <input type="hidden" name="action" value="delete_post">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" class="btn-danger-text" title="Delete Post">&times;</button>
                    </form>
                    <?php endif; ?>
                </div>

                <?php if (!empty($post['content'])): ?>
                    <div class="mb-2"><?= parse_echo_embeds(nl2br($post['content'])) ?></div>
                <?php endif; ?>

                <?php if (count($medias) > 0): ?>
                    <div class="echo-media-grid mt-2">
                        <?php 
                        $is_single = count($medias) == 1;
                        foreach ($medias as $media): 
                            $file_ext = strtolower(pathinfo($media['file_name'], PATHINFO_EXTENSION));
                            $item_class = $is_single ? 'media-item full-width' : 'media-item';
                            $img_class = $is_single ? 'echo-media-single zoomable-image' : 'echo-media zoomable-image';
                        ?>
                            <div class="<?= $item_class ?>">
                                <?php if (in_array($file_ext, $video_extensions)): ?>
                                    <video controls class="<?= $is_single ? 'echo-media-single' : 'echo-media' ?>">
                                        <source src="uploads/<?= $media['file_name'] ?>" type="video/<?= $file_ext === 'mkv' ? 'webm' : $file_ext ?>">
                                    </video>
                                <?php else: ?>
                                    <img src="uploads/<?= $media['file_name'] ?>" class="<?= $img_class ?>" title="Klik untuk memperbesar" alt="Attached Echo">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    exit; 
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>echo - Jeannes Bryan | NPC</title>
    <meta name="theme-color" content="#121212">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="apple-touch-icon" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="container">
        
        <div class="text-center border-bottom pb-2 mb-4">
            <h1 class="mb-0">echo</h1>
            <div class="text-muted mt-1">Thoughts transmitted into the void</div>
        </div>

        <?php if ($is_admin): ?>
        <form method="POST" enctype="multipart/form-data" class="mb-4">
            <input type="hidden" name="action" value="add_post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label class="form-label">Apa yang anda pikirkan?</label>
                <textarea class="form-control" name="content" placeholder="Leave a comment here"></textarea>
            </div>
            <div class="form-group">
                <input class="form-control text-muted" type="file" name="media[]" multiple accept="image/*, video/*">
                <div class="text-muted fs-small mt-1">Maksimal 4 file (Gambar atau Video).</div>
            </div>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-light">Transmit</button>
            </div>
        </form>
        <hr>
        <?php endif; ?>

        <div id="echo-container"></div>

        <div class="mb-4 text-center d-none" id="load-more-container">
            <button class="btn btn-dark btn-block" type="button" id="btn-load-more" onclick="loadPosts()">- Load More -</button>
        </div>
        
        <div class="text-center mt-3 text-muted d-none mb-5" id="end-indicator">- Void has completely echoed -</div>

    </div>

    <div id="imageZoomModal" class="modal-overlay">
        <div class="modal-content">
            <button class="btn-close-modal" id="closeModalBtn">&times;</button>
            <img id="modalImage" src="" alt="Zoomed Echo">
        </div>
    </div>

    <script>
        let currentOffset = 0;
        const container = document.getElementById('echo-container');
        const btnLoadMore = document.getElementById('btn-load-more');
        const endIndicator = document.getElementById('end-indicator');

        function loadPosts() {
            const originalText = btnLoadMore.innerHTML;
            btnLoadMore.innerHTML = 'Receiving...';
            btnLoadMore.disabled = true;

            fetch(`index.php?ajax=1&offset=${currentOffset}`)
                .then(res => res.text())
                .then(data => {
                    if (data.trim() === "END") {
                        document.getElementById('load-more-container').classList.add('d-none');
                        endIndicator.classList.remove('d-none');
                    } else {
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = data;
                        const postCount = tempDiv.querySelectorAll('.card').length;

                        container.insertAdjacentHTML('beforeend', data);
                        currentOffset += 10; 
                        btnLoadMore.innerHTML = originalText;
                        btnLoadMore.disabled = false;

                        if (postCount < 10) {
                            document.getElementById('load-more-container').classList.add('d-none');
                            endIndicator.classList.remove('d-none');
                        } else {
                            document.getElementById('load-more-container').classList.remove('d-none');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching echos:', error);
                    btnLoadMore.innerHTML = originalText;
                    btnLoadMore.disabled = false;
                });
        }
        
        document.addEventListener('DOMContentLoaded', loadPosts);

        // Vanilla JS Modal Logic
        const modal = document.getElementById('imageZoomModal');
        const modalImg = document.getElementById('modalImage');
        const closeBtn = document.getElementById('closeModalBtn');

        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('zoomable-image')) {
                modalImg.src = e.target.getAttribute('src');
                modal.classList.add('active');
            }
        });

        closeBtn.addEventListener('click', () => modal.classList.remove('active'));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.remove('active');
        });
    </script>
</body>
</html>
