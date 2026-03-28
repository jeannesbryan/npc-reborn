<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$db_file = __DIR__ . '/echo_data.sqlite';
$upload_dir = __DIR__ . '/uploads/';
$is_admin = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("PRAGMA foreign_keys = ON;"); 
} catch (PDOException $e) {
    die("> SYS_ERR: CONNECTION TO THE VOID LOST.");
}

// ==========================================
// [ AUTO-PURGE PROTOCOL ]
// ==========================================
try {
    $stmt = $pdo->query("SELECT id FROM posts WHERE expires_at IS NOT NULL AND expires_at <= datetime('now', 'localtime')");
    $expired_posts = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($expired_posts)) {
        $placeholders = str_repeat('?,', count($expired_posts) - 1) . '?';
        $media_stmt = $pdo->prepare("SELECT file_name FROM post_media WHERE post_id IN ($placeholders)");
        $media_stmt->execute($expired_posts);
        while ($media = $media_stmt->fetch(PDO::FETCH_ASSOC)) {
            if (file_exists($upload_dir . $media['file_name'])) unlink($upload_dir . $media['file_name']);
        }
        $pdo->prepare("DELETE FROM posts WHERE id IN ($placeholders)")->execute($expired_posts);
    }
} catch (PDOException $e) { }

// Mengambil Data Story Aktif (Volatile Signals)
$active_stories = [];
try {
    $stmt_stories = $pdo->query("SELECT id, created_at, expires_at FROM posts WHERE parent_id IS NULL AND expires_at IS NOT NULL AND expires_at > datetime('now', 'localtime') ORDER BY created_at DESC");
    $active_stories = $stmt_stories->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { }
// ==========================================

function format_indo_date($datetime) {
    $months = ['', 'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
    $timestamp = strtotime($datetime);
    return date('d', $timestamp) . ' ' . $months[(int)date('n', $timestamp)] . ' ' . date('Y', $timestamp) . ' // ' . date('H:i', $timestamp) . ' WIB';
}

function parse_echo_embeds($text) {
    $text = preg_replace('~(?:https?://)?(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]{11})(?:\S+)?~i', '<div class="ratio mt-2 mb-2"><iframe src="https://www.youtube.com/embed/$1" allowfullscreen></iframe></div>', $text);
    $text = preg_replace('~(?<!src=")(https?://[^\s]+(?:\.jpg|\.jpeg|\.png|\.gif|\.webp))~i', '<img src="$1" class="echo-media-single mt-2 mb-2 zoomable-image" title="[ CLICK TO ENLARGE ]" alt="Attached Data">', $text);
    $text = preg_replace('~(?<!src="|")\b(https?://[^\s<]+)\b~i', '<a href="$1" target="_blank">[LINK: $1]</a>', $text);
    return $text;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_post' && $is_admin) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("> SYS_ERR: AUTHENTICATION FAILED.");
    
    $post_id = (int)$_POST['post_id'];
    $stmt = $pdo->prepare("SELECT file_name FROM post_media WHERE post_id IN (SELECT id FROM posts WHERE id = ? OR parent_id = ?)");
    $stmt->execute([$post_id, $post_id]);
    while ($media = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (file_exists($upload_dir . $media['file_name'])) unlink($upload_dir . $media['file_name']);
    }
    $pdo->prepare("DELETE FROM posts WHERE id = ? OR parent_id = ?")->execute([$post_id, $post_id]);
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_post' && $is_admin) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("> SYS_ERR: AUTHENTICATION FAILED.");

    $content = trim($_POST['content'] ?? '');
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $files = $_FILES['media'] ?? null;
    $total_files = !empty($files['name'][0]) ? count($files['name']) : 0;
    $expires_at = isset($_POST['is_volatile']) ? date('Y-m-d H:i:s', strtotime('+24 hours')) : null;

    if ($total_files > 4) die("> SYS_ERR: OVERLOAD. MAX 4 PAYLOADS ALLOWED.");

    if ($content !== '' || $total_files > 0) {
        $stmt = $pdo->prepare("INSERT INTO posts (parent_id, content, created_at, expires_at) VALUES (:parent_id, :content, :created_at, :expires_at)");
        $stmt->execute([
            ':parent_id' => $parent_id, 
            ':content' => htmlspecialchars($content), 
            ':created_at' => date('Y-m-d H:i:s'),
            ':expires_at' => $expires_at
        ]); 
        $new_post_id = $pdo->lastInsertId();

        if ($total_files > 0) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'mp4', 'webm', 'ogg'];
            for ($i = 0; $i < $total_files; $i++) {
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                if ($files['error'][$i] === 0 && in_array($ext, $allowed_ext)) {
                    $new_filename = uniqid('echo_') . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($files['tmp_name'][$i], $upload_dir . $new_filename)) {
                        $pdo->prepare("INSERT INTO post_media (post_id, file_name) VALUES (?, ?)")->execute([$new_post_id, $new_filename]);
                    }
                }
            }
        }
    }
    header("Location: index.php");
    exit;
}

function render_echo_card($post, $pdo, $is_admin, $is_thread_view = false) {
    $video_extensions = ['mp4', 'webm', 'ogg'];
    $media_stmt = $pdo->prepare("SELECT file_name FROM post_media WHERE post_id = ?");
    $media_stmt->execute([$post['id']]);
    $medias = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

    $child_count = 0;
    if (!$is_thread_view) {
        $child_stmt = $pdo->prepare("SELECT COUNT(id) FROM posts WHERE parent_id = ?");
        $child_stmt->execute([$post['id']]);
        $child_count = $child_stmt->fetchColumn();
    }
    ?>
    <div class="card mb-3 <?= $is_thread_view ? 'thread-item' : '' ?>" style="<?= $is_thread_view ? 'border:none; border-left:2px dashed var(--border-color); border-radius:0; padding-left:15px; background:transparent;' : '' ?>">
        <div class="p-3">
            
            <?php if (!empty($post['expires_at'])): ?>
                <div class="mb-3 pb-1 fs-small" style="color: var(--danger); border-bottom: 1px dotted var(--danger);">
                    > [ WARNING: VOLATILE SIGNAL. SELF-DESTRUCT SEQUENCE INITIATED ]
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-start mb-2 border-bottom pb-2">
                <div class="d-flex gap-2 align-items-center">
                    <img src="../assets/jeannesbryan.webp" class="avatar" width="45" height="45" alt="Avatar">
                    <div>
                        <div class="text-main fw-bold">ENTITY_ID: JEANNES_BRYAN <span class="text-muted fw-normal fs-small">&#x25CF; [AUTHOR]</span></div>
                        <div class="text-muted fs-small">> LOG_DATE: <?= format_indo_date($post['created_at']) ?></div>
                    </div>
                </div>
                
                <?php if ($is_admin): ?>
                <div class="d-flex gap-2">
                    <?php if (!$is_thread_view && empty($post['expires_at'])): ?>
                        <button type="button" class="btn-icon-main" title="Chain / Reply" onclick="replyTo(<?= $post['id'] ?>)"> <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"> <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path> <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path> </svg> </button>
                    <?php endif; ?>
                    <form method="POST" onsubmit="return confirm('WARNING: Purge this signal and its entire chain?');" style="margin:0;">
                        <input type="hidden" name="action" value="delete_post">
                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" class="btn-icon-danger" title="Purge Signal" onclick="return confirm('WARNING: Erase this signal permanently?');"> <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"> <polyline points="3 6 5 6 21 6"></polyline> <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path> <line x1="10" y1="11" x2="10" y2="17"></line> <line x1="14" y1="11" x2="14" y2="17"></line> </svg> </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($post['content'])): ?>
                <div class="mb-2" style="color: var(--text-main);">
                    <?= parse_echo_embeds(nl2br($post['content'])) ?>
                </div>
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
                                <img src="uploads/<?= $media['file_name'] ?>" class="<?= $img_class ?>" title="[ ENLARGE_VISUAL ]" alt="Attached Data">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($child_count > 0 && !$is_thread_view): ?>
                <button class="btn btn-dark border-secondary btn-sm w-100 mt-3" onclick="viewThread(<?= $post['id'] ?>)">&#x21B3; [ DECRYPT <?= $child_count ?> LINKED SIGNALS ]</button>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

// Endpoint AJAX: Utas (Thread)
if (isset($_GET['ajax_thread']) && $_GET['ajax_thread'] == '1') {
    $parent_id = (int)$_GET['parent_id'];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent_post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE parent_id = ? ORDER BY created_at ASC");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<div class='thread-container p-2'>";
    if ($parent_post) render_echo_card($parent_post, $pdo, $is_admin, true);
    foreach ($children as $child) { render_echo_card($child, $pdo, $is_admin, true); }
    echo "</div>";
    exit;
}

// Endpoint AJAX: Membuka Story Modal
if (isset($_GET['ajax_story']) && $_GET['ajax_story'] == '1') {
    $story_id = (int)$_GET['story_id'];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$story_id]);
    $story = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<div class='thread-container p-2'>";
    if ($story) {
        render_echo_card($story, $pdo, $is_admin, true);
    } else {
        echo "<div class='text-danger text-center py-5'>> SYS_ERR: SIGNAL DECAYED OR PURGED.</div>";
    }
    echo "</div>";
    exit;
}

// Endpoint AJAX: Linimasa Utama
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE parent_id IS NULL AND expires_at IS NULL ORDER BY created_at DESC LIMIT 10 OFFSET :offset");
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($posts) === 0) { echo "END"; exit; }
    foreach ($posts as $post) { render_echo_card($post, $pdo, $is_admin, false); }
    exit; 
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>COMMS: ECHO - Jeannes Bryan | NPC</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .thread-modal-dialog { width: 100%; max-width: 600px; background: var(--bg-card); border: 1px solid var(--text-main); box-shadow: 0 0 15px rgba(0,255,65,0.2); padding: 20px; overflow-y: auto; max-height: 90vh; }
        .thread-item { position: relative; }
        .thread-item::before { content: ''; position: absolute; left: -2px; top: 0; bottom: 0; width: 2px; background: var(--border-color); }
        .thread-item:last-child::before { bottom: 50%; } 
        
        .story-radar-container { display: flex; overflow-x: auto; gap: 12px; padding-bottom: 4px; scrollbar-width: thin; }
        .story-box { border: 1px solid var(--danger); background: rgba(255,0,60,0.05); color: var(--danger); padding: 10px 15px; cursor: pointer; transition: 0.2s; min-width: 140px; text-align: center; white-space: nowrap; flex-shrink: 0; }
        .story-box:hover { background: var(--danger); color: var(--bg-dark); box-shadow: 0 0 10px var(--danger); }
        
        .story-modal-dialog { border-color: var(--danger) !important; box-shadow: 0 0 15px rgba(255,0,60,0.2) !important; }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="text-center mb-3 mt-3">
            <h1 class="mb-1 text-main">[ COMMS: ECHO_BROADCAST ]</h1>
            <div class="text-muted">> STATUS: TRANSMITTING SIGNALS INTO THE VOID... <span class="blinking-cursor"></span></div>
        </div>

        <?php if (count($active_stories) > 0): ?>
        <div class="mt-2 mb-3 pt-3 pb-3" style="border-top: 1px dashed var(--border-color); border-bottom: 1px dashed var(--border-color);">
            <div class="text-danger fs-small mb-2">> RADAR: ACTIVE VOLATILE SIGNALS DETECTED</div>
            <div class="story-radar-container">
                <?php foreach($active_stories as $st): ?>
                    <div class="story-box" onclick="viewStory(<?= $st['id'] ?>)" title="Decrypt this payload">
                        <div class="fw-bold fs-small">[ ECHO_LOG ]</div>
                        <div class="fs-small mt-1" style="opacity: 0.8;">> DECAYING...</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <hr class="mt-2 mb-3">
        <?php endif; ?>
        <?php if ($is_admin): ?>
        <form method="POST" enctype="multipart/form-data" class="mb-4 card p-3" id="echo-form">
            <input type="hidden" name="action" value="add_post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="parent_id" id="input-parent-id" value="">
            
            <div id="chain-indicator" class="alert-bunker p-2 mb-3 d-none d-flex justify-content-between align-items-center" style="border-color: var(--text-main); color: var(--text-main); background: rgba(0,255,65,0.05);">
                <span class="fs-small">> ESTABLISHING SIGNAL CHAIN...</span>
                <button type="button" class="btn-danger-text p-0 m-0" onclick="cancelReply()">[ ABORT ]</button>
            </div>

            <div class="form-group">
                <div id="payload-error" class="text-danger d-none fs-small mb-2">> SYS_ERR: CANNOT TRANSMIT EMPTY PAYLOAD.</div>
                <textarea class="form-control" name="content" id="echo-textarea" placeholder="> ENTER_TRANSMISSION_DATA..."></textarea>
            </div>
            
            <div class="form-group">
                <input class="form-control text-muted mb-2" type="file" name="media[]" multiple accept="image/*, video/*">
                
                <label style="cursor: pointer; display: flex; align-items: center; gap: 8px; color: var(--danger); font-size: 0.85rem; margin-top: 10px;">
                    <input type="checkbox" name="is_volatile" value="1" style="accent-color: var(--danger);">
                    > [ WARNING ] MAKE SIGNAL VOLATILE (SELF-DESTRUCT IN 24H)
                </label>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-light" id="btn-transmit">[ EXECUTE_BROADCAST ]</button>
            </div>
        </form>
        <hr class="mb-4">
        <?php endif; ?>

        <div id="echo-container"></div>

        <div class="mb-4 text-center d-none" id="load-more-container">
            <button class="btn btn-dark btn-block border-secondary" type="button" id="btn-load-more" onclick="loadPosts()">[ SCAN_FOR_OLDER_SIGNALS ]</button>
        </div>
        
        <div class="text-center mt-4 text-muted d-none mb-5" id="end-indicator" style="font-size: 0.85rem; letter-spacing: 1px;">
            > END_OF_LOG. NO FURTHER SIGNALS DETECTED.
        </div>
        
    </div>

    <div id="imageZoomModal" class="modal-overlay">
        <div class="modal-content">
            <button class="btn-close-modal" id="closeModalBtn" title="Close">&times;</button>
            <img id="modalImage" src="" alt="Zoomed Echo">
        </div>
    </div>

    <div id="threadModal" class="modal-overlay" style="align-items: flex-start; padding-top: 5vh;">
        <div class="thread-modal-dialog" id="threadModalBox">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2" id="threadModalHeader">
                <h3 class="mb-0 text-main" id="threadModalTitle">> SIGNAL_CHAIN</h3>
                <button class="btn-danger-text" style="font-size: 2rem; line-height: 1;" id="closeThreadBtn" title="Close">&times;</button>
            </div>
            <div id="threadModalContent"></div>
        </div>
    </div>

    <script>
        let currentOffset = 0;
        const container = document.getElementById('echo-container');
        const btnLoadMore = document.getElementById('btn-load-more');
        const endIndicator = document.getElementById('end-indicator');

        const echoForm = document.getElementById('echo-form');
        if (echoForm) {
            echoForm.addEventListener('submit', function(e) {
                const textarea = document.getElementById('echo-textarea');
                const fileInput = document.querySelector('input[type="file"]');
                const errDiv = document.getElementById('payload-error');
                
                if (textarea.value.trim() === '' && fileInput.files.length === 0) {
                    e.preventDefault(); 
                    errDiv.classList.remove('d-none');
                    textarea.focus();
                    setTimeout(() => errDiv.classList.add('d-none'), 3000);
                }
            });
        }

        function loadPosts() {
            const originalText = btnLoadMore.innerHTML;
            btnLoadMore.innerHTML = '[ SCANNING_THE_VOID... ]';
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
                });
        }
        document.addEventListener('DOMContentLoaded', loadPosts);

        function replyTo(id) {
            document.getElementById('input-parent-id').value = id;
            document.getElementById('chain-indicator').classList.remove('d-none');
            document.getElementById('echo-form').scrollIntoView({ behavior: 'smooth' });
            document.getElementById('echo-textarea').focus();
        }
        function cancelReply() {
            document.getElementById('input-parent-id').value = '';
            document.getElementById('chain-indicator').classList.add('d-none');
        }

        const threadModal = document.getElementById('threadModal');
        const threadModalBox = document.getElementById('threadModalBox');
        const threadModalHeader = document.getElementById('threadModalHeader');
        const threadModalTitle = document.getElementById('threadModalTitle');
        const threadContent = document.getElementById('threadModalContent');
        const closeThreadBtn = document.getElementById('closeThreadBtn');

        function viewThread(parentId) {
            threadModalBox.classList.remove('story-modal-dialog');
            threadModalHeader.style.borderColor = 'var(--border-color)';
            threadModalTitle.className = 'mb-0 text-main';
            threadModalTitle.innerText = '> SIGNAL_CHAIN';
            
            threadContent.innerHTML = '<div class="text-center text-muted py-5">> DECRYPTING_SIGNAL_CHAIN... <span class="blinking-cursor"></span></div>';
            threadModal.classList.add('active');
            
            fetch(`index.php?ajax_thread=1&parent_id=${parentId}`)
                .then(res => res.text())
                .then(html => { threadContent.innerHTML = html; });
        }

        function viewStory(id) {
            threadModalBox.classList.add('story-modal-dialog');
            threadModalHeader.style.borderColor = 'var(--danger)';
            threadModalTitle.className = 'mb-0 text-danger';
            threadModalTitle.innerText = '> VOLATILE_PAYLOAD_DECRYPTED';
            
            threadContent.innerHTML = '<div class="text-center text-danger py-5">> DECRYPTING_VOLATILE_DATA... <span class="blinking-cursor"></span></div>';
            threadModal.classList.add('active');
            
            fetch(`index.php?ajax_story=1&story_id=${id}`)
                .then(res => res.text())
                .then(html => { threadContent.innerHTML = html; });
        }
        
        closeThreadBtn.addEventListener('click', () => threadModal.classList.remove('active'));
        threadModal.addEventListener('click', (e) => {
            if (e.target === threadModal) threadModal.classList.remove('active');
        });

        const imageModal = document.getElementById('imageZoomModal');
        const modalImg = document.getElementById('modalImage');
        const closeImageBtn = document.getElementById('closeModalBtn');

        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('zoomable-image')) {
                modalImg.src = e.target.getAttribute('src');
                imageModal.classList.add('active');
            }
        });
        closeImageBtn.addEventListener('click', () => imageModal.classList.remove('active'));
    </script>
</body>
</html>