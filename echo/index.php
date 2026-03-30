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

// Mengambil Data Story Aktif
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
    $text = preg_replace('~(?:https?://)?(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/)([a-zA-Z0-9_-]{11})(?:\S+)?~i', '<div class="mt-2 mb-2"><iframe width="100%" height="315" style="border:1px dashed var(--t-green-dim);" src="https://www.youtube.com/embed/$1" allowfullscreen></iframe></div>', $text);
    $text = preg_replace('~(?<!src=")(https?://[^\s]+(?:\.jpg|\.jpeg|\.png|\.gif|\.webp))~i', '<img src="$1" class="echo-media zoomable-image mt-2 mb-2" title="[ CLICK TO ENLARGE ]" alt="Attached Data">', $text);
    $text = preg_replace('~(?<!src="|")\b(https?://[^\s<]+)\b~i', '<a href="$1" target="_blank" style="color:var(--t-green); text-decoration:underline dashed;">[LINK: $1]</a>', $text);
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

// FUNGSI RENDER (Dirombak untuk Terminal UI)
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
    
    // Tentukan class parent (Card biasa atau bagian dari Thread Connector)
    $wrapper_class = $is_thread_view ? 't-thread-item' : 't-card mb-4';
    $volatile_class = !empty($post['expires_at']) ? 'danger' : '';
    ?>
    
    <div class="<?= $wrapper_class ?> <?= $volatile_class ?>" style="<?= $is_thread_view ? 'margin-bottom: 30px;' : '' ?>">
        
        <?php if (!empty($post['expires_at'])): ?>
            <div class="mb-3 pb-2 fs-small text-danger t-border-bottom t-flicker">
                > [ WARNING: VOLATILE SIGNAL. SELF-DESTRUCT INITIATED ]
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-start mb-3 t-border-bottom pb-2">
            <div class="d-flex gap-3 align-items-center">
                <img src="../assets/jeannesbryan.webp" width="45" height="45" alt="Avatar" style="border: 1px solid var(--t-green-dim); filter: grayscale(1) sepia(0.5) hue-rotate(80deg);">
                <div>
                    <div class="font-bold text-success">ENTITY_ID: JEANNES_BRYAN <span class="text-muted fw-normal fs-small">&#x25CF; [AUTHOR]</span></div>
                    <div class="text-muted fs-small">> LOG_DATE: <?= format_indo_date($post['created_at']) ?></div>
                </div>
            </div>
            
            <?php if ($is_admin): ?>
            <div class="d-flex gap-2">
                <?php if (!$is_thread_view && empty($post['expires_at'])): ?>
                    <button type="button" class="t-btn t-btn-sm" title="Chain / Reply" onclick="replyTo(<?= $post['id'] ?>)">[ ↳ CHAIN ]</button>
                <?php endif; ?>
                <form method="POST" onsubmit="return confirm('WARNING: Purge this signal and its entire chain?');" class="m-0">
                    <input type="hidden" name="action" value="delete_post">
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="t-btn danger t-btn-sm" title="Purge Signal">[ X PURGE ]</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($post['content'])): ?>
            <div class="mb-3" style="color: var(--t-green); font-size: 14px; word-wrap: break-word;">
                <?= parse_echo_embeds(nl2br($post['content'])) ?>
            </div>
        <?php endif; ?>

        <?php if (count($medias) > 0): ?>
            <div class="echo-media-grid mt-2">
                <?php 
                $is_single = count($medias) == 1;
                foreach ($medias as $media): 
                    $file_ext = strtolower(pathinfo($media['file_name'], PATHINFO_EXTENSION));
                ?>
                    <div class="media-item <?= $is_single ? 'full-width' : '' ?>">
                        <?php if (in_array($file_ext, $video_extensions)): ?>
                            <video controls class="echo-media w-100" style="border: 1px dashed var(--t-green-dim);">
                                <source src="uploads/<?= $media['file_name'] ?>" type="video/<?= $file_ext === 'mkv' ? 'webm' : $file_ext ?>">
                            </video>
                        <?php else: ?>
                            <img src="uploads/<?= $media['file_name'] ?>" class="echo-media zoomable-image w-100" title="[ ENLARGE_VISUAL ]" alt="Attached Data" style="cursor: pointer; border: 1px dashed var(--t-green-dim); filter: grayscale(0.5) sepia(0.5);">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($child_count > 0 && !$is_thread_view): ?>
            <button class="t-btn t-btn-sm w-100 mt-3" onclick="viewThread(<?= $post['id'] ?>)">
                &#x21B3; [ DECRYPT <?= $child_count ?> LINKED SIGNALS ]
            </button>
        <?php endif; ?>
    </div>
    <?php
}

// AJAX HANDLERS
if (isset($_GET['ajax_thread']) && $_GET['ajax_thread'] == '1') {
    $parent_id = (int)$_GET['parent_id'];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?"); $stmt->execute([$parent_id]);
    $parent_post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE parent_id = ? ORDER BY created_at ASC"); $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Menggunakan t-thread dari Terminal UI
    if ($parent_post) render_echo_card($parent_post, $pdo, $is_admin, false);
    
    echo "<div class='t-thread mt-4'>";
    foreach ($children as $child) { render_echo_card($child, $pdo, $is_admin, true); }
    echo "</div>"; exit;
}

if (isset($_GET['ajax_story']) && $_GET['ajax_story'] == '1') {
    $story_id = (int)$_GET['story_id'];
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?"); $stmt->execute([$story_id]);
    $story = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($story) { 
        render_echo_card($story, $pdo, $is_admin, false); 
    } else { 
        echo "<div class='text-danger text-center py-5'>> SYS_ERR: SIGNAL DECAYED OR PURGED.</div>"; 
    }
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE parent_id IS NULL AND expires_at IS NULL ORDER BY created_at DESC LIMIT 10 OFFSET :offset");
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT); $stmt->execute();
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
    <title>COMMS: ECHO - Bunker</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/terminal.css">
    
    <style>
        /* CSS Tambahan khusus Layouting Media (Tetap dipertahankan karena layouting grid gambar butuh spesifik) */
        .story-radar-container { display: flex; overflow-x: auto; gap: 12px; padding-bottom: 8px; scrollbar-width: thin; }
        .story-box { border: 1px solid var(--t-red); background: rgba(255,0,60,0.05); color: var(--t-red); padding: 10px 15px; cursor: pointer; transition: 0.2s; min-width: 140px; text-align: center; white-space: nowrap; flex-shrink: 0; }
        .story-box:hover { background: var(--t-red); color: var(--bg-surface); box-shadow: 0 0 10px var(--t-red); }
        
        .echo-media-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; }
        .media-item.full-width { grid-column: 1 / -1; }
        .media-item img { object-fit: cover; max-height: 400px; }
        
        /* Modifikasi Modal Image Zoom */
        #imageZoomModal .t-modal-content { max-width: 90vw; max-height: 90vh; padding: 5px; background: transparent; border: none; box-shadow: none; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        #modalImage { max-width: 100%; max-height: 85vh; border: 1px solid var(--t-green); box-shadow: 0 0 20px rgba(0,255,65,0.2); }
        
        /* Input File Styling */
        input[type="file"]::file-selector-button { background: transparent; color: var(--t-green); border: 1px solid var(--t-green-dim); padding: 4px 8px; font-family: inherit; font-size: 11px; cursor: pointer; margin-right: 10px; text-transform: uppercase; }
        input[type="file"]::file-selector-button:hover { background: rgba(0, 255, 65, 0.1); border-color: var(--t-green); }
    </style>
</head>
<body class="t-crt">

    <div id="splash-overlay" class="t-splash">
        <div class="font-bold text-success" id="splash-text" style="font-size: 1.1rem; letter-spacing: 2px; text-shadow: 0 0 8px currentColor;">
            > DECRYPTING_ECHO_SIGNALS<span class="t-loading-dots"></span>
        </div>
    </div>

    <div class="t-container t-box-lg">
        <div class="d-flex justify-content-between align-items-center mb-4 t-border-bottom pb-3 mt-4">
            <div>
                <h2 class="mb-0 text-success"><span class="t-led-dot t-led-green"></span> COMMS: ECHO_BROADCAST</h2>
                <div class="text-muted fs-small mt-1">> STATUS: TRANSMITTING SIGNALS INTO THE VOID...</div>
            </div>
            <div>
                <a href="../bunker/dashboard.php" class="t-btn danger" title="Return to Dashboard">[ ➜ ] RETURN_OS</a>
            </div>
        </div>

        <?php if (count($active_stories) > 0): ?>
        <div class="t-card warning mb-4">
            <div class="t-card-header text-warning">> RADAR: ACTIVE VOLATILE SIGNALS DETECTED</div>
            <div class="story-radar-container">
                <?php foreach($active_stories as $st): ?>
                    <div class="story-box" onclick="viewStory(<?= $st['id'] ?>)" title="Decrypt this payload">
                        <div class="font-bold fs-small">[ ECHO_LOG ]</div>
                        <div class="fs-small mt-1" style="opacity: 0.8;">> DECAYING...</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($is_admin): ?>
        <div class="t-card mb-5">
            <div class="t-card-header">> COMPOSER_TERMINAL</div>
            <form method="POST" enctype="multipart/form-data" id="echo-form" class="m-0">
                <input type="hidden" name="action" value="add_post">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="parent_id" id="input-parent-id" value="">
                
                <div id="chain-indicator" class="t-alert warning d-none d-flex justify-content-between align-items-center mb-3 p-2">
                    <span class="fs-small">> ESTABLISHING SIGNAL CHAIN...</span>
                    <button type="button" class="t-btn danger t-btn-sm" onclick="cancelReply()">[ ABORT ]</button>
                </div>

                <div id="payload-error" class="text-danger d-none fs-small mb-2 t-flicker">> SYS_ERR: CANNOT TRANSMIT EMPTY PAYLOAD.</div>
                
                <textarea class="t-textarea" name="content" id="echo-textarea" placeholder="> ENTER_TRANSMISSION_DATA..." rows="4"></textarea>
                
                <input class="t-input fs-small text-muted mb-2" type="file" name="media[]" multiple accept="image/*, video/*">
                
                <label class="t-checkbox-label mt-2" style="color: var(--t-red);">
                    <input type="checkbox" name="is_volatile" value="1">
                    <span class="t-checkmark"></span> > [ WARNING ] MAKE SIGNAL VOLATILE (SELF-DESTRUCT IN 24H)
                </label>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="t-btn" id="btn-transmit">[ EXECUTE_BROADCAST ]</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <div id="echo-container"></div>

        <div class="mb-4 text-center d-none" id="load-more-container">
            <button class="t-btn t-btn-block" type="button" id="btn-load-more" onclick="loadPosts()">[ SCAN_FOR_OLDER_SIGNALS ]</button>
        </div>
        
        <div class="text-center mt-4 text-muted d-none mb-5" id="end-indicator" style="font-size: 0.85rem; letter-spacing: 1px;">
            > END_OF_LOG. NO FURTHER SIGNALS DETECTED.
        </div>
        
    </div>

    <div id="imageZoomModal" class="t-modal">
        <div class="t-modal-content">
            <div class="text-right mb-2">
                <button class="t-btn danger t-btn-sm" onclick="Terminal.modal.close('imageZoomModal')">[ X CLOSE ]</button>
            </div>
            <img id="modalImage" src="" alt="Zoomed Echo">
        </div>
    </div>

    <div id="threadModal" class="t-modal">
        <div class="t-modal-content" id="threadModalBox" style="max-width: 650px;">
            <div class="t-card-header d-flex justify-content-between align-items-center" id="threadModalHeader">
                <span id="threadModalTitle">> SIGNAL_CHAIN</span>
                <button class="t-btn danger t-btn-sm" onclick="Terminal.modal.close('threadModal')">[ X CLOSE ]</button>
            </div>
            <div id="threadModalContent"></div>
        </div>
    </div>

    <script src="../assets/terminal.js"></script>

    <script>
        let currentOffset = 0;
        const container = document.getElementById('echo-container');
        const btnLoadMore = document.getElementById('btn-load-more');
        const endIndicator = document.getElementById('end-indicator');

        // Form Validation
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
                } else {
                    Terminal.splash.show('> EXECUTING_BROADCAST_PROTOCOL');
                }
            });
        }

        // Lazy Load Feed
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
                        const postCount = tempDiv.querySelectorAll('.t-card, .t-thread-item').length;

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

        // Reply / Chain Protocol
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

        // Modals Setup
        const threadModalTitle = document.getElementById('threadModalTitle');
        const threadModalHeader = document.getElementById('threadModalHeader');
        const threadContent = document.getElementById('threadModalContent');

        function viewThread(parentId) {
            threadModalHeader.className = "t-card-header d-flex justify-content-between align-items-center text-success";
            threadModalTitle.innerText = '> SIGNAL_CHAIN';
            
            threadContent.innerHTML = '<div class="text-center text-muted py-5">> DECRYPTING_SIGNAL_CHAIN... <span class="t-spinner"></span></div>';
            Terminal.modal.open('threadModal');
            
            fetch(`index.php?ajax_thread=1&parent_id=${parentId}`)
                .then(res => res.text())
                .then(html => { threadContent.innerHTML = html; });
        }

        function viewStory(id) {
            threadModalHeader.className = "t-card-header d-flex justify-content-between align-items-center text-danger";
            threadModalTitle.innerText = '> VOLATILE_PAYLOAD_DECRYPTED';
            
            threadContent.innerHTML = '<div class="text-center text-danger py-5 t-flicker">> DECRYPTING_VOLATILE_DATA... <span class="t-spinner"></span></div>';
            Terminal.modal.open('threadModal');
            
            fetch(`index.php?ajax_story=1&story_id=${id}`)
                .then(res => res.text())
                .then(html => { threadContent.innerHTML = html; });
        }

        // Image Zoom Protocol
        const modalImg = document.getElementById('modalImage');
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('zoomable-image')) {
                modalImg.src = e.target.getAttribute('src');
                Terminal.modal.open('imageZoomModal');
            }
        });
    </script>
</body>
</html>