<?php
require_once realpath(__DIR__ . '/../bunker/core.php');
require_login();

$db_file = __DIR__ . '/grid_data.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->query("SELECT topic_id FROM tasks LIMIT 1");
} catch (Exception $e) {
    die("<div style='background:#050505; color:red; font-family:monospace; padding:20px; height:100vh;'>> SYS_ERR: STRUKTUR DATABASE USANG.<br>> Silakan jalankan <b>upgrade.php</b> terlebih dahulu.</div>");
}

// ==========================================
// [ HANDLER: POST REQUESTS ]
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("> SYS_ERR: AUTHENTICATION FAILED.");
    }
    
    $action = $_POST['action'];
    $time = date('Y-m-d H:i:s');

    if ($action === 'add_topic') {
        $name = trim($_POST['topic_name']);
        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO topics (name, created_at) VALUES (?, ?)");
            $stmt->execute([strtoupper($name), $time]);
            $new_topic_id = $pdo->lastInsertId();
            header("Location: index.php?topic=" . $new_topic_id);
            exit;
        }
    }
    elseif ($action === 'edit_topic') {
        $t_id = $_POST['topic_id'];
        $new_name = trim($_POST['topic_name']);
        if (!empty($new_name)) {
            $stmt = $pdo->prepare("UPDATE topics SET name = ? WHERE id = ?");
            $stmt->execute([strtoupper($new_name), $t_id]);
        }
        header("Location: index.php?topic=" . $t_id);
        exit;
    }
    elseif ($action === 'delete_topic') {
        $t_id = $_POST['topic_id'];
        $count = $pdo->query("SELECT COUNT(*) FROM topics")->fetchColumn();
        if ($count > 1) {
            $pdo->prepare("DELETE FROM topics WHERE id = ?")->execute([$t_id]);
            $pdo->prepare("DELETE FROM tasks WHERE topic_id = ?")->execute([$t_id]);
        }
        header("Location: index.php");
        exit;
    }
    elseif ($action === 'add_task') {
        $content = trim($_POST['content']);
        $topic_id = $_POST['topic_id'];
        if (!empty($content)) {
            $max_pos_stmt = $pdo->prepare("SELECT COALESCE(MAX(position), 0) FROM tasks WHERE topic_id = ? AND status = 'PENDING'");
            $max_pos_stmt->execute([$topic_id]);
            $new_pos = $max_pos_stmt->fetchColumn() + 1;

            $stmt = $pdo->prepare("INSERT INTO tasks (content, status, topic_id, position) VALUES (?, 'PENDING', ?, ?)");
            $stmt->execute([$content, $topic_id, $new_pos]);
        }
        header("Location: index.php?topic=" . $topic_id);
        exit;
    }
    elseif ($action === 'edit_task') {
        $item_id = $_POST['item_id'];
        $topic_id = $_POST['topic_id'];
        $new_content = trim($_POST['content']);
        if (!empty($new_content)) {
            $stmt = $pdo->prepare("UPDATE tasks SET content = ? WHERE id = ?");
            $stmt->execute([$new_content, $item_id]);
        }
        header("Location: index.php?topic=" . $topic_id);
        exit;
    }
    elseif ($action === 'move_task') {
        $item_id = $_POST['item_id'];
        $new_status = $_POST['new_status'];
        $topic_id = $_POST['topic_id'];
        $valid_statuses = ['PENDING', 'IN_TRANSIT', 'SECURED'];
        if (in_array($new_status, $valid_statuses)) {
            $max_pos_stmt = $pdo->prepare("SELECT COALESCE(MAX(position), 0) FROM tasks WHERE topic_id = ? AND status = ?");
            $max_pos_stmt->execute([$topic_id, $new_status]);
            $new_pos = $max_pos_stmt->fetchColumn() + 1;

            $stmt = $pdo->prepare("UPDATE tasks SET status = ?, position = ? WHERE id = ?");
            $stmt->execute([$new_status, $new_pos, $item_id]);
        }
        header("Location: index.php?topic=" . $topic_id);
        exit;
    }
    elseif ($action === 'delete_task') {
        $item_id = $_POST['item_id'];
        $topic_id = $_POST['topic_id'];
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
        $stmt->execute([$item_id]);
        header("Location: index.php?topic=" . $topic_id);
        exit;
    }
    elseif ($action === 'reorder_task') {
        $item_id = $_POST['item_id'];
        $direction = $_POST['direction'];
        $topic_id = $_POST['topic_id'];
        
        $stmt = $pdo->prepare("SELECT id, position, status FROM tasks WHERE id = ?");
        $stmt->execute([$item_id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($current) {
            if ($direction === 'up') {
                $stmt = $pdo->prepare("SELECT id, position FROM tasks WHERE topic_id = ? AND status = ? AND position > ? ORDER BY position ASC LIMIT 1");
            } else {
                $stmt = $pdo->prepare("SELECT id, position FROM tasks WHERE topic_id = ? AND status = ? AND position < ? ORDER BY position DESC LIMIT 1");
            }
            $stmt->execute([$topic_id, $current['status'], $current['position']]);
            $adjacent = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($adjacent) {
                $pdo->prepare("UPDATE tasks SET position = ? WHERE id = ?")->execute([$adjacent['position'], $current['id']]);
                $pdo->prepare("UPDATE tasks SET position = ? WHERE id = ?")->execute([$current['position'], $adjacent['id']]);
            }
        }
        header("Location: index.php?topic=" . $topic_id);
        exit;
    }
}

// ==========================================
// [ DATA FETCHING ]
// ==========================================
$topics = $pdo->query("SELECT * FROM topics ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$active_topic_id = $_GET['topic'] ?? ($topics[0]['id'] ?? 1);
$active_topic_name = "UNKNOWN_NODE";

foreach ($topics as $t) {
    if ($t['id'] == $active_topic_id) {
        $active_topic_name = $t['name'];
        break;
    }
}

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE topic_id = ? ORDER BY position DESC, id DESC");
$stmt->execute([$active_topic_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

$board = ['PENDING' => [], 'IN_TRANSIT' => [], 'SECURED' => []];
foreach ($tasks as $task) {
    if (isset($board[$task['status']])) {
        $board[$task['status']][] = $task;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <title>OP: GRID - Logistics Matrix</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/jeannesbryan/terminal/terminal.css">
    <style>
        /* Animasi dan Styling khusus interaksi Kartu Task */
        .task-actions { 
            display: none; 
            justify-content: space-between; 
            align-items: center; 
            border-top: 1px dashed var(--t-green-dim); 
            padding-top: 12px; 
            margin-top: 10px; 
            flex-wrap: wrap;
            gap: 10px;
        }
        .task-actions.active { 
            display: flex; 
            animation: slideDown 0.2s ease-in-out; 
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Varian status board-card tanpa merusak struktur .t-board-card bawaan */
        .card-transit { color: var(--t-yellow); border-color: var(--t-yellow-dim); }
        .card-secured { color: var(--t-green-dim); text-decoration: line-through; opacity: 0.7; }
    </style>
</head>
<body class="t-crt">

    <div id="splash-overlay" class="t-splash">
        <div class="font-bold text-success" id="splash-text" style="font-size: 1.1rem; letter-spacing: 2px; text-shadow: 0 0 8px currentColor;">
            > ALIGNING_GRID_MATRICES<span class="t-loading-dots"></span>
        </div>
    </div>

    <div class="t-container-fluid" style="margin-top: 20px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4 t-border-bottom pb-3">
            <div>
                <h2 class="mb-0 text-success"><span class="t-led-dot t-led-green"></span> OP: GRID_MATRIX</h2>
                <div class="text-muted fs-small mt-1">> LOGISTICS_MATRIX_V2 // <span style="color:var(--t-green);"><?= htmlspecialchars($active_topic_name) ?></span></div>
            </div>
            <div>
                <a href="../bunker/dashboard.php" class="t-btn danger" title="Return to Bunker">[ ➜ ] RETURN_OS</a>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 t-border-bottom pb-3 flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="d-flex gap-2 flex-wrap">
                    <?php foreach ($topics as $t): ?>
                        <a href="?topic=<?= $t['id'] ?>" class="t-btn <?= ($t['id'] == $active_topic_id) ? 'active font-bold t-glow' : '' ?>">
                            [ <?= htmlspecialchars($t['name']) ?> ]
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="text-muted" style="opacity: 0.5;">|</div>
                
                <div class="d-flex gap-2">
                    <button onclick="createNewTopic()" class="t-btn t-btn-sm" title="New Sector">[ + ] NEW</button>
                    <button onclick="renameTopic(<?= $active_topic_id ?>, '<?= htmlspecialchars($active_topic_name, ENT_QUOTES) ?>')" class="t-btn t-btn-sm" title="Edit Sector">[ EDIT ]</button>
                    <?php if (count($topics) > 1): ?>
                        <button onclick="purgeTopic(<?= $active_topic_id ?>, '<?= htmlspecialchars($active_topic_name, ENT_QUOTES) ?>')" class="t-btn danger t-btn-sm" title="Purge Sector">[ X PURGE ]</button>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <button onclick="toggleInjectForm()" class="t-btn font-bold t-glow">[ INJECT_TASK ]</button>
            </div>
        </div>

        <div id="inject-task-pane" class="t-card mb-4" style="display: none;">
            <div class="t-card-header">> PREPARING_NEW_PAYLOAD</div>
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="add_task">
                <input type="hidden" name="topic_id" value="<?= $active_topic_id ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <textarea name="content" class="t-textarea" rows="2" placeholder="> ENTER NEW TASK PAYLOAD..." required></textarea>
                
                <div class="d-flex justify-content-end gap-2 mt-2">
                    <button type="button" class="t-btn danger t-btn-sm" onclick="toggleInjectForm()">[ ABORT ]</button>
                    <button type="submit" class="t-btn t-btn-sm">[ SUBMIT_PAYLOAD ]</button>
                </div>
            </form>
        </div>

        <div class="t-board w-100">
            
            <div class="t-board-column" style="flex: 1; min-width: 300px;">
                <div class="t-board-header">> PENDING [ <?= count($board['PENDING']) ?> ]</div>
                <div class="t-board-body">
                    <?php 
                    $tot_pending = count($board['PENDING']);
                    foreach ($board['PENDING'] as $idx => $t): 
                    ?>
                        <div class="t-board-card" onclick="toggleAccordion(<?= $t['id'] ?>)">
                            <div class="text-success"><?= nl2br(htmlspecialchars(htmlspecialchars_decode($t['content']))) ?></div>
                            
                            <div class="task-actions" id="actions-<?= $t['id'] ?>" onclick="event.stopPropagation();">
                                <div class="d-flex gap-1 flex-wrap">
                                    <form method="POST" class="m-0"><input type="hidden" name="action" value="delete_task"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="t-btn danger t-btn-sm" title="Purge Task" onclick="return confirm('Erase task?');">[ X ]</button></form>
                                    <button type="button" class="t-btn t-btn-sm" title="Edit Task" data-content="<?= htmlspecialchars(htmlspecialchars_decode($t['content']), ENT_QUOTES) ?>" onclick="editTask(<?= $t['id'] ?>, this)">[ EDIT ]</button>
                                    
                                    <?php if ($idx > 0): ?>
                                    <form method="POST" class="m-0"><input type="hidden" name="action" value="reorder_task"><input type="hidden" name="direction" value="up"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="t-btn t-btn-sm" title="Move Up">[ ^ ]</button></form>
                                    <?php endif; ?>
                                    
                                    <?php if ($idx < $tot_pending - 1): ?>
                                    <form method="POST" class="m-0"><input type="hidden" name="action" value="reorder_task"><input type="hidden" name="direction" value="down"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="t-btn t-btn-sm" title="Move Down">[ v ]</button></form>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" class="m-0"><input type="hidden" name="action" value="move_task"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="new_status" value="IN_TRANSIT"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="t-btn t-btn-sm text-warning" style="border-color: var(--t-yellow);" title="Move to In Transit">[ -> ]</button></form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="t-board-column" style="flex: 1; min-width: 300px;">
                <div class="t-board-header text-warning" style="border-bottom-color: var(--t-yellow-dim);">> IN_TRANSIT [ <?= count($board['IN_TRANSIT']) ?> ]</div>
                <div class="t-board-body">
                    <?php 
                    $tot_transit = count($board['IN_TRANSIT']);
                    foreach ($board['IN_TRANSIT'] as $idx => $t): 
                    ?>
                        <div class="t-board-card card-transit" onclick="toggleAccordion(<?= $t['id'] ?>)">
                            <div><?= nl2br(htmlspecialchars(htmlspecialchars_decode($t['content']))) ?></div>
                            
                            <div class="task-actions" id="actions-<?= $t['id'] ?>" onclick="event.stopPropagation();">
                                <form method="POST" class="m-0"><input type="hidden" name="action" value="move_task"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="new_status" value="PENDING"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="t-btn t-btn-sm text-success" title="Move to Pending">[ <- ]</button></form>
                                
                                <div class="d-flex gap-1 flex-wrap">
                                    <button type="button" class="t-btn t-btn-sm" title="Edit Task" style="color:var(--t-yellow); border-color:var(--t-yellow);" data-content="<?= htmlspecialchars(htmlspecialchars_decode($t['content']), ENT_QUOTES) ?>" onclick="editTask(<?= $t['id'] ?>, this)">[ EDIT ]</button>
                                    
                                    <?php if ($idx > 0): ?>
                                    <form method="POST" class="m-0"><input type="hidden" name="action" value="reorder_task"><input type="hidden" name="direction" value="up"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="t-btn t-btn-sm" style="color:var(--t-yellow); border-color:var(--t-yellow);" title="Move Up">[ ^ ]</button></form>
                                    <?php endif; ?>
                                    
                                    <?php if ($idx < $tot_transit - 1): ?>
                                    <form method="POST" class="m-0"><input type="hidden" name="action" value="reorder_task"><input type="hidden" name="direction" value="down"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="t-btn t-btn-sm" style="color:var(--t-yellow); border-color:var(--t-yellow);" title="Move Down">[ v ]</button></form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="m-0"><input type="hidden" name="action" value="delete_task"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="t-btn danger t-btn-sm" title="Purge Task" onclick="return confirm('Erase task?');">[ X ]</button></form>
                                </div>
                                
                                <form method="POST" class="m-0"><input type="hidden" name="action" value="move_task"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="new_status" value="SECURED"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="t-btn t-btn-sm text-muted" title="Move to Secured">[ -> ]</button></form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="t-board-column" style="flex: 1; min-width: 300px;">
                <div class="t-board-header text-muted">> SECURED [ <?= count($board['SECURED']) ?> ]</div>
                <div class="t-board-body">
                    <?php 
                    $tot_secured = count($board['SECURED']);
                    foreach ($board['SECURED'] as $idx => $t): 
                    ?>
                        <div class="t-board-card card-secured" onclick="toggleAccordion(<?= $t['id'] ?>)">
                            <div><?= nl2br(htmlspecialchars(htmlspecialchars_decode($t['content']))) ?></div>
                            
                            <div class="task-actions" id="actions-<?= $t['id'] ?>" onclick="event.stopPropagation();">
                                <form method="POST" class="m-0"><input type="hidden" name="action" value="move_task"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="new_status" value="IN_TRANSIT"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="t-btn t-btn-sm text-warning" style="border-color: var(--t-yellow);" title="Revert to In Transit">[ <- ]</button></form>
                                
                                <div class="d-flex gap-1 flex-wrap">
                                    <button type="button" class="t-btn t-btn-sm text-muted" title="Edit Task" data-content="<?= htmlspecialchars(htmlspecialchars_decode($t['content']), ENT_QUOTES) ?>" onclick="editTask(<?= $t['id'] ?>, this)">[ EDIT ]</button>
                                    
                                    <?php if ($idx > 0): ?>
                                    <form method="POST" class="m-0"><input type="hidden" name="action" value="reorder_task"><input type="hidden" name="direction" value="up"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="t-btn t-btn-sm text-muted" title="Move Up">[ ^ ]</button></form>
                                    <?php endif; ?>
                                    
                                    <?php if ($idx < $tot_secured - 1): ?>
                                    <form method="POST" class="m-0"><input type="hidden" name="action" value="reorder_task"><input type="hidden" name="direction" value="down"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="t-btn t-btn-sm text-muted" title="Move Down">[ v ]</button></form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="m-0"><input type="hidden" name="action" value="delete_task"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="t-btn danger t-btn-sm" title="Purge Permanently" onclick="return confirm('Erase permanently?');">[ X ]</button></form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

    <form id="actionForm" method="POST" style="display:none;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" id="actionField">
        <input type="hidden" name="topic_name" id="topicNameField">
        <input type="hidden" name="topic_id" id="topicIdField">
        <input type="hidden" name="item_id" id="taskIdField">
        <input type="hidden" name="content" id="taskContentField">
    </form>

    <script src="https://cdn.jsdelivr.net/gh/jeannesbryan/terminal/terminal.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            if (typeof Terminal !== 'undefined' && Terminal.splash) {
                Terminal.splash.close();
            }
        });

        function toggleInjectForm() {
            const pane = document.getElementById('inject-task-pane');
            if (pane.style.display === 'none') {
                pane.style.display = 'block';
                pane.querySelector('textarea').focus();
            } else {
                pane.style.display = 'none';
            }
        }

        function toggleAccordion(id) {
            const actionsDiv = document.getElementById('actions-' + id);
            const isCurrentlyActive = actionsDiv.classList.contains('active');
            document.querySelectorAll('.task-actions').forEach(el => { el.classList.remove('active'); });
            if (!isCurrentlyActive) actionsDiv.classList.add('active');
        }

        function createNewTopic() {
            let name = prompt("> ENTER_NEW_SECTOR_NAME (e.g. WORK, HOBBY):");
            if (name && name.trim() !== "") {
                document.getElementById('actionField').value = 'add_topic';
                document.getElementById('topicNameField').value = name;
                document.getElementById('actionForm').submit();
            }
        }

        function renameTopic(id, oldName) {
            let newName = prompt("> RENAME_SECTOR:", oldName);
            if (newName && newName.trim() !== "") {
                document.getElementById('actionField').value = 'edit_topic';
                document.getElementById('topicIdField').value = id;
                document.getElementById('topicNameField').value = newName;
                document.getElementById('actionForm').submit();
            }
        }

        function purgeTopic(id, name) {
            let confirmPurge = confirm(`> WARNING! INITIATING PURGE ON SECTOR: [ ${name} ]\n> All tasks inside this sector will be permanently destroyed.\n\nPROCEED?`);
            if (confirmPurge) {
                document.getElementById('actionField').value = 'delete_topic';
                document.getElementById('topicIdField').value = id;
                document.getElementById('actionForm').submit();
            }
        }

        function editTask(id, btnElement) {
            let oldContent = btnElement.getAttribute('data-content');
            let newContent = prompt("> EDIT_OBJECTIVE:", oldContent);
            
            if (newContent !== null && newContent.trim() !== "") {
                document.getElementById('actionField').value = 'edit_task';
                document.getElementById('taskIdField').value = id;
                document.getElementById('taskContentField').value = newContent;
                document.getElementById('topicIdField').value = <?= $active_topic_id ?>;
                document.getElementById('actionForm').submit();
            }
        }
    </script>
</body>
</html>