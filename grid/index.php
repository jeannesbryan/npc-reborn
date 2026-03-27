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

$db_file = __DIR__ . '/grid_data.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("> SYS_ERR: FAILED TO CONNECT TO GRID. HAVE YOU RUN THE INIT SCRIPT?");
}

// Handler: Tambah Tugas Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_task') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("> SYS_ERR: AUTHENTICATION FAILED.");
    $content = trim($_POST['content'] ?? '');
    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO tasks (content, status) VALUES (?, 'PENDING')");
        $stmt->execute([htmlspecialchars($content)]);
    }
    header("Location: index.php");
    exit;
}

// Handler: Pindahkan Tugas (Update Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'move_task') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("> SYS_ERR: AUTHENTICATION FAILED.");
    $id = (int)$_POST['id'];
    $new_status = $_POST['new_status'];
    $valid_statuses = ['PENDING', 'IN_TRANSIT', 'SECURED'];
    if (in_array($new_status, $valid_statuses)) {
        $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?")->execute([$new_status, $id]);
    }
    header("Location: index.php");
    exit;
}

// Handler: Hapus Tugas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_task') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) die("> SYS_ERR: AUTHENTICATION FAILED.");
    $id = (int)$_POST['id'];
    $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$id]);
    header("Location: index.php");
    exit;
}

// Ambil semua tugas
$stmt = $pdo->query("SELECT * FROM tasks ORDER BY id DESC");
$all_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$board = ['PENDING' => [], 'IN_TRANSIT' => [], 'SECURED' => []];
foreach ($all_tasks as $task) {
    $board[$task['status']][] = $task;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OP: GRID - Bunker Control</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .grid-board { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; align-items: start; }
        .grid-column { background: rgba(0,255,65,0.02); border: 1px dashed var(--border-color); padding: 15px; min-height: 300px; }
        .col-header { font-weight: bold; border-bottom: 1px solid var(--text-main); padding-bottom: 10px; margin-bottom: 15px; text-align: center; letter-spacing: 1px; }
        
        .task-card { background: var(--bg-dark); border: 1px solid var(--border-color); padding: 12px; margin-bottom: 12px; border-left: 3px solid var(--text-main); }
        .task-card.transit { border-left-color: var(--light); }
        .task-card.secured { border-left-color: var(--text-muted); opacity: 0.7; }
        
        .task-content { color: var(--text-main); margin-bottom: 10px; font-size: 0.95rem; line-height: 1.4; word-break: break-word; }
        .task-actions { display: flex; justify-content: space-between; align-items: center; border-top: 1px dotted var(--border-color); padding-top: 8px; }
        .btn-move { background: none; border: 1px solid var(--border-color); color: var(--text-main); font-size: 0.75rem; padding: 2px 8px; cursor: pointer; transition: 0.2s; }
        .btn-move:hover { background: var(--text-main); color: var(--bg-dark); }
        .btn-del { background: none; border: none; color: var(--danger); font-size: 0.8rem; cursor: pointer; opacity: 0.6; }
        .btn-del:hover { opacity: 1; text-shadow: 0 0 5px var(--danger); }
    </style>
</head>
<body>
    <div class="container" style="max-width: 1200px;"> <div class="d-flex justify-content-between align-items-center pb-3 mt-4 mb-4 border-bottom">
            <div>
                <h2 class="mb-0 text-main">[ OP: LOGISTICS_GRID ]</h2>
                <div class="text-muted fs-small mt-1">> STATUS: TRACKING OPERATIONS... <span class="blinking-cursor"></span></div>
            </div>
            <a href="../bunker/dashboard.php" class="btn btn-dark border-secondary">[ RETURN_TO_BUNKER ]</a>
        </div>

        <div class="card p-3 mb-5">
            <h4 class="mb-3 fs-small">> INJECT_NEW_DIRECTIVE</h4>
            <form method="POST" style="margin: 0; display: flex; gap: 10px;">
                <input type="hidden" name="action" value="add_task">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="text" class="form-control" name="content" placeholder="Enter task or payload objective..." required style="flex: 1;">
                <button type="submit" class="btn btn-light">[ ADD_TASK ]</button>
            </form>
        </div>

        <div class="grid-board">
            
            <div class="grid-column">
                <div class="col-header">> PENDING [ <?= count($board['PENDING']) ?> ]</div>
                <?php foreach ($board['PENDING'] as $t): ?>
                    <div class="task-card">
                        <div class="task-content"><?= nl2br(htmlspecialchars($t['content'])) ?></div>
                        <div class="task-actions">
                            <form method="POST" style="margin:0;"><input type="hidden" name="action" value="delete_task"><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="btn-del" title="Purge">[X]</button></form>
                            <form method="POST" style="margin:0;"><input type="hidden" name="action" value="move_task"><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="new_status" value="IN_TRANSIT"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="btn-move">IN_TRANSIT ></button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="grid-column">
                <div class="col-header" style="color: var(--light); border-color: var(--light);">> IN_TRANSIT [ <?= count($board['IN_TRANSIT']) ?> ]</div>
                <?php foreach ($board['IN_TRANSIT'] as $t): ?>
                    <div class="task-card transit">
                        <div class="task-content" style="color: var(--light);"><?= nl2br(htmlspecialchars($t['content'])) ?></div>
                        <div class="task-actions">
                            <form method="POST" style="margin:0;"><input type="hidden" name="action" value="move_task"><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="new_status" value="PENDING"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="btn-move">< PENDING</button></form>
                            <form method="POST" style="margin:0;"><input type="hidden" name="action" value="delete_task"><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="btn-del" title="Purge">[X]</button></form>
                            <form method="POST" style="margin:0;"><input type="hidden" name="action" value="move_task"><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="new_status" value="SECURED"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="btn-move">SECURED ></button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="grid-column">
                <div class="col-header" style="color: var(--text-muted); border-color: var(--text-muted);">> SECURED [ <?= count($board['SECURED']) ?> ]</div>
                <?php foreach ($board['SECURED'] as $t): ?>
                    <div class="task-card secured">
                        <div class="task-content" style="color: var(--text-muted); text-decoration: line-through;"><?= nl2br(htmlspecialchars($t['content'])) ?></div>
                        <div class="task-actions">
                            <form method="POST" style="margin:0;"><input type="hidden" name="action" value="move_task"><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="new_status" value="IN_TRANSIT"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="btn-move">< REVERT</button></form>
                            <form method="POST" style="margin:0;"><input type="hidden" name="action" value="delete_task"><input type="hidden" name="id" value="<?= $t['id'] ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="btn-del" title="Purge">[X]</button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</body>
</html>