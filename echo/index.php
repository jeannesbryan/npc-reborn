<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

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
        '<div class="ratio ratio-16x9 mt-2 mb-2"><iframe src="https://www.youtube.com/embed/$1" allowfullscreen class="rounded border border-secondary"></iframe></div>',
        $text
    );
    // B. Embed Direct Image (Dengan fitur ZOOM otomatis)
    $text = preg_replace(
        '~(?<!src=")(https?://[^\s]+(?:\.jpg|\.jpeg|\.png|\.gif|\.webp))~i',
        '<img src="$1" class="echo-media-single border border-secondary mt-2 mb-2 zoomable-image" title="Klik untuk memperbesar" alt="External Image">',
        $text
    );
    // C. Regular Links
    $text = preg_replace(
        '~(?<!src="|")\b(https?://[^\s<]+)\b~i',
        '<a href="$1" target="_blank" class="text-info text-decoration-none">$1</a>',
        $text
    );
    return $text;
}

// 4. Proses Hapus Postingan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_post' && $is_admin) {
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
    $content = trim($_POST['content'] ?? '');
    $files = $_FILES['media'] ?? null;
    $total_files = !empty($files['name'][0]) ? count($files['name']) : 0;

    if ($total_files > 4) {
        die("Transmisi ditolak. Maksimal 4 file media per echo.");
    }

    if ($content !== '' || $total_files > 0) {
        $stmt = $pdo->prepare("INSERT INTO posts (content) VALUES (:content)");
        $stmt->execute([':content' => htmlspecialchars($content)]); 
        $post_id = $pdo->lastInsertId();

        if ($total_files > 0) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'ogg'];
            
            for ($i = 0; $i < $total_files; $i++) {
                $tmp_name = $files['tmp_name'][$i];
                $name = $files['name'][$i];
                $error = $files['error'][$i];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if ($error === 0 && in_array($ext, $allowed_ext)) {
                    $new_filename = uniqid('echo_') . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($tmp_name, $upload_dir . $new_filename)) {
                        $stmt_media = $pdo->prepare("INSERT INTO post_media (post_id, file_name) VALUES (?, ?)");
                        $stmt_media->execute([$post_id, $new_filename]);
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
        <div class="card mb-3 border-secondary bg-dark">
            <ul class="list-group list-group-flush text-bg-dark">
                <li class="list-group-item bg-dark text-light border-secondary">
                    
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex">
                            <div class="p-2 ps-0">
                                <img src="../assets/jeannesbryan.webp" class="rounded-circle bg-secondary" width="50px" height="50px" alt="Avatar" style="object-fit:cover;">
                            </div>
                            <div class="p-2 lh-sm">
                                <div class="fw-bold text-light" style="font-size: 0.95rem;">
                                    Jeannes Bryan <span class="text-secondary fw-normal" style="font-size: 0.85rem;">&#x25CF; @jeannesbryan</span>
                                </div>
                                <div class="text-secondary mt-1" style="font-size: 0.75rem;">
                                    <?= format_indo_date($post['created_at']) ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($is_admin): ?>
                        <form method="POST" onsubmit="return confirm('Hapus jejak gema ini secara permanen?');" class="p-2 pe-0 m-0">
                            <input type="hidden" name="action" value="delete_post">
                            <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Delete Post">&times;</button>
                        </form>
                        <?php endif; ?>
                    </div>

                    <div class="p-2 pt-1 ps-0">
                        <?php if (!empty($post['content'])): ?>
                            <div class="mb-2"><?= parse_echo_embeds(nl2br($post['content'])) ?></div>
                        <?php endif; ?>

                        <?php if (count($medias) > 0): ?>
                            <div class="row g-2 mt-2">
                                <?php 
                                $col_class = count($medias) == 1 ? 'col-12' : 'col-6';
                                $media_class = count($medias) == 1 ? 'echo-media-single' : 'echo-media';
                                ?>
                                
                                <?php foreach ($medias as $media): 
                                    $file_ext = strtolower(pathinfo($media['file_name'], PATHINFO_EXTENSION));
                                ?>
                                    <div class="<?= $col_class ?>">
                                        <?php if (in_array($file_ext, $video_extensions)): ?>
                                            <video controls class="<?= $media_class ?> border border-secondary">
                                                <source src="uploads/<?= $media['file_name'] ?>" type="video/<?= $file_ext === 'mkv' ? 'webm' : $file_ext ?>">
                                            </video>
                                        <?php else: ?>
                                            <img src="uploads/<?= $media['file_name'] ?>" class="<?= $media_class ?> border border-secondary zoomable-image" title="Klik untuk memperbesar" alt="Attached Echo">
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                </li>
            </ul>
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
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
        <style>
            .echo-media {
                width: 100%;
                height: 250px;
                object-fit: cover;
                background-color: #000;
                border-radius: 0.375rem;
            }
            .echo-media-single {
                width: 100%;
                max-height: 400px;
                object-fit: contain;
                background-color: #000;
                border-radius: 0.375rem;
            }
            /* CSS Khusus untuk Interaksi Zoom */
            .zoomable-image {
                cursor: zoom-in;
                transition: opacity 0.2s ease-in-out;
            }
            .zoomable-image:hover {
                opacity: 0.8;
            }
            /* Menyempurnakan Tampilan Modal Lightbox */
            #imageZoomModal .modal-content {
                background-color: transparent;
                border: none;
            }
            #imageZoomModal .modal-header {
                border-bottom: none;
                padding: 1rem 1rem 0;
            }
            #modalImage {
                width: 100%;
                max-height: 85vh;
                object-fit: contain;
                border-radius: 0.375rem;
            }
        </style>
    </head>
    <body class="container font-monospace" data-bs-theme="dark">
        <div class="row">
            <div class="col-sm-2"></div>
            <div class="col-sm-8">
                
                <div class="my-3 text-bg-dark border-bottom border-light text-center pb-2">
                    <figure class="text-center mb-0">
                        <blockquote class="blockquote">
                            <h1 class="display-6 fw-bold">echo</h1>
                        </blockquote>
                        <figcaption class="blockquote-footer mt-1">
                            Thoughts transmitted into the void
                        </figcaption>
                    </figure>
                </div>

                <?php if ($is_admin): ?>
                <form method="POST" enctype="multipart/form-data" class="mb-4">
                    <input type="hidden" name="action" value="add_post">
                    <div class="mb-3">
                        <div class="form-floating">
                            <textarea class="form-control bg-dark text-light border-secondary" name="content" placeholder="Leave a comment here" id="floatingTextarea2" style="height: 100px"></textarea>
                            <label for="floatingTextarea2" class="text-secondary">Apa yang anda pikirkan?</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <input class="form-control bg-dark border-secondary text-secondary" type="file" name="media[]" id="formFileMultiple" multiple accept="image/*, video/*">
                        <small class="text-secondary">Maksimal 4 file (Gambar atau Video).</small>
                    </div>
                    <div class="d-flex justify-content-end mb-3">
                        <button type="submit" class="btn btn-sm btn-light">Transmit</button>
                    </div>
                </form>
                <hr class="border-secondary mb-4">
                <?php endif; ?>

                <div id="echo-container"></div>

                <div class="d-grid mt-2 mb-4" id="load-more-container">
                    <button class="btn btn-sm btn-dark border-secondary" type="button" id="btn-load-more" onclick="loadPosts()">- Load More -</button>
                </div>
                
                <div class="small text-center mt-3 text-secondary d-none mb-5" id="end-indicator">- Void has completely echoed -</div>

            </div>
            <div class="col-sm-2"></div>
        </div>

        <div class="modal fade" id="imageZoomModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center p-0">
                        <img id="modalImage" src="" alt="Zoomed Echo">
                    </div>
                </div>
            </div>
        </div>

     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
     
     <script>
        let currentOffset = 0;
        const container = document.getElementById('echo-container');
        const btnLoadMore = document.getElementById('btn-load-more');
        const endIndicator = document.getElementById('end-indicator');

        // Logika Load Data AJAX
        function loadPosts() {
            const originalText = btnLoadMore.innerHTML;
            btnLoadMore.innerHTML = 'Receiving...';
            btnLoadMore.disabled = true;

            fetch(`index.php?ajax=1&offset=${currentOffset}`)
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === "END") {
                        document.getElementById('load-more-container').classList.add('d-none');
                        endIndicator.classList.remove('d-none');
                    } else {
                        container.insertAdjacentHTML('beforeend', data);
                        currentOffset += 10; 
                        btnLoadMore.innerHTML = originalText;
                        btnLoadMore.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error fetching echos:', error);
                    btnLoadMore.innerHTML = originalText;
                    btnLoadMore.disabled = false;
                });
        }

        document.addEventListener('DOMContentLoaded', loadPosts);

        // LOGIKA EVENT DELEGATION UNTUK ZOOM GAMBAR
        // Kita pakai document.addEventListener karena gambar dimuat secara AJAX setelah halaman selesai me-load.
        document.addEventListener('click', function(e) {
            // Cek apakah elemen yang diklik memiliki class 'zoomable-image'
            if (e.target && e.target.classList.contains('zoomable-image')) {
                // Ambil URL gambar yang diklik
                const imgSrc = e.target.getAttribute('src');
                
                // Masukkan URL tersebut ke dalam tag <img> yang ada di dalam Modal
                document.getElementById('modalImage').setAttribute('src', imgSrc);
                
                // Panggil dan tampilkan Bootstrap Modal
                const myModal = new bootstrap.Modal(document.getElementById('imageZoomModal'));
                myModal.show();
            }
        });
     </script>
    </body>
</html>