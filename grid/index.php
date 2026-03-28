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
    $pdo->query("SELECT topic_id FROM tasks LIMIT 1");
} catch (Exception $e) {
    die("<div style='background:#050505; color:red; font-family:monospace; padding:20px; height:100vh;'>> SYS_ERR: STRUKTUR DATABASE USANG.<br>> Silakan jalankan <b>migrate.php</b> terlebih dahulu.</div>");
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
            $stmt = $pdo->prepare("INSERT INTO tasks (content, status, topic_id) VALUES (?, 'PENDING', ?)");
            $stmt->execute([$content, $topic_id]);
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
            $stmt = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $item_id]);
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

$stmt = $pdo->prepare("SELECT * FROM tasks WHERE topic_id = ? ORDER BY id DESC");
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
    <title>OP: GRID - Logistics Matrix</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        body { padding-bottom: 50px; }
        
        /* TOPIC TABS */
        .topic-bar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-bottom: 25px; border-bottom: 1px dashed var(--border-color); padding-bottom: 15px; }
        .topic-tab { padding: 8px 15px; border: 1px solid var(--border-color); color: var(--text-muted); text-decoration: none; font-size: 0.85rem; letter-spacing: 1px; transition: 0.3s; background: rgba(0,255,65,0.02); }
        .topic-tab:hover { border-color: var(--text-main); color: var(--text-main); }
        .topic-tab.active { background: var(--text-main); color: var(--bg-dark); border-color: var(--text-main); font-weight: bold; box-shadow: 0 0 10px rgba(0,255,65,0.3); }

        /* KANBAN GRID */
        .grid-board { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; align-items: start; }
        .grid-column { background: rgba(0,255,65,0.02); border: 1px dashed var(--border-color); padding: 15px; min-height: 500px; display: flex; flex-direction: column; gap: 12px;}
        .col-header { font-weight: bold; border-bottom: 1px solid var(--text-main); padding-bottom: 10px; margin-bottom: 10px; text-align: center; letter-spacing: 1px; }
        
        /* TASK CARD */
        .task-card { background: var(--bg-dark); border: 1px solid var(--border-color); padding: 12px; position: relative; word-wrap: break-word; font-size: 0.95rem; line-height: 1.4; border-left: 3px solid var(--text-main); }
        .task-card:hover { border-color: var(--text-main); }
        .task-card.transit { border-left-color: var(--light); }
        .task-card.secured { border-left-color: var(--text-muted); opacity: 0.7; text-decoration: line-through; color: var(--text-muted); }
        
        .task-actions { display: flex; justify-content: space-between; align-items: center; border-top: 1px dotted var(--border-color); padding-top: 8px; margin-top: 10px; }
        
        /* FORM INPUT */
        .task-input { background: transparent; border: 1px dashed var(--border-color); color: var(--text-main); width: 100%; padding: 10px; font-family: 'JetBrains Mono', monospace; resize: vertical; outline: none; margin-bottom: 10px; }
        .task-input:focus { border-color: var(--text-main); box-shadow: inset 0 0 5px rgba(0,255,65,0.1); }
    </style>
</head>
<body>

    <div id="splash-overlay">
        <div class="splash-content text-main">
            > ALIGNING_GRID_MATRICES<span class="loading-dots"></span>
        </div>
    </div>

    <div class="container" style="max-width: 1200px; margin-top: 30px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
            <div>
                <h2 class="mb-0 text-main" style="letter-spacing: 2px;">[ OP: GRID ]</h2>
                <div class="text-muted fs-small mt-1">> LOGISTICS_MATRIX_V2 // <span style="color:var(--text-main);"><?= htmlspecialchars($active_topic_name) ?></span></div>
            </div>
            <a href="../bunker/dashboard.php" class="btn btn-danger btn-sm">[ RETURN_TO_BUNKER ]</a>
        </div>

        <div class="topic-bar">
            <?php foreach ($topics as $t): ?>
                <a href="?topic=<?= $t['id'] ?>" class="topic-tab <?= ($t['id'] == $active_topic_id) ? 'active' : '' ?>">
                    [ <?= htmlspecialchars($t['name']) ?> ]
                </a>
            <?php endforeach; ?>
            
            <button onclick="createNewTopic()" class="btn btn-main btn-sm">[ + NEW_SECTOR ]</button>
            
            <div style="margin-left: auto; display: flex; gap: 10px;">
                <button onclick="renameTopic(<?= $active_topic_id ?>, '<?= htmlspecialchars($active_topic_name, ENT_QUOTES) ?>')" class="btn btn-main btn-sm">[ EDIT_SECTOR ]</button>
                
                <?php if (count($topics) > 1): ?>
                    <button onclick="purgeTopic(<?= $active_topic_id ?>, '<?= htmlspecialchars($active_topic_name, ENT_QUOTES) ?>')" class="btn btn-danger btn-sm">[ PURGE_SECTOR ]</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid-board">
            
            <div class="grid-column">
                <div class="col-header">> PENDING [ <?= count($board['PENDING']) ?> ]</div>
                
                <form method="POST" style="margin-bottom: 5px;">
                    <input type="hidden" name="action" value="add_task">
                    <input type="hidden" name="topic_id" value="<?= $active_topic_id ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <textarea name="content" class="task-input" rows="2" placeholder="> Input new objective..." required></textarea>
                    <button type="submit" class="btn btn-main btn-block btn-sm mt-2">[ INJECT_TASK ]</button>
                </form>

                <?php foreach ($board['PENDING'] as $t): ?>
                    <div class="task-card">
                        <div style="color: var(--text-main);"><?= nl2br(htmlspecialchars(htmlspecialchars_decode($t['content']))) ?></div>
                        <div class="task-actions">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <form method="POST" style="margin:0;"><input type="hidden" name="action" value="delete_task"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="btn btn-danger btn-sm" title="Purge" onclick="return confirm('Erase task?');">[X] PURGE</button></form>
                                <button type="button" class="btn btn-main btn-sm" data-content="<?= htmlspecialchars(htmlspecialchars_decode($t['content']), ENT_QUOTES) ?>" onclick="editTask(<?= $t['id'] ?>, this)">[ EDIT ]</button>
                            </div>
                            <form method="POST" style="margin:0;"><input type="hidden" name="action" value="move_task"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="new_status" value="IN_TRANSIT"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="btn btn-main btn-sm">IN_TRANSIT ></button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="grid-column">
                <div class="col-header" style="color: var(--light); border-color: var(--light);">> IN_TRANSIT [ <?= count($board['IN_TRANSIT']) ?> ]</div>
                <?php foreach ($board['IN_TRANSIT'] as $t): ?>
                    <div class="task-card transit">
                        <div style="color: var(--light);"><?= nl2br(htmlspecialchars(htmlspecialchars_decode($t['content']))) ?></div>
                        <div class="task-actions">
                            <form method="POST" style="margin:0;"><input type="hidden" name="action" value="move_task"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="new_status" value="PENDING"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="btn btn-main btn-sm">< PENDING</button></form>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <button type="button" class="btn btn-main btn-sm" data-content="<?= htmlspecialchars(htmlspecialchars_decode($t['content']), ENT_QUOTES) ?>" onclick="editTask(<?= $t['id'] ?>, this)">[ EDIT ]</button>
                                <form method="POST" style="margin:0;"><input type="hidden" name="action" value="delete_task"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="btn btn-danger btn-sm" title="Purge">[X]</button></form>
                            </div>
                            <form method="POST" style="margin:0;"><input type="hidden" name="action" value="move_task"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="new_status" value="SECURED"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="btn btn-main btn-sm">SECURED ></button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="grid-column">
                <div class="col-header" style="color: var(--text-muted); border-color: var(--text-muted);">> SECURED [ <?= count($board['SECURED']) ?> ]</div>
                <?php foreach ($board['SECURED'] as $t): ?>
                    <div class="task-card secured">
                        <div><?= nl2br(htmlspecialchars(htmlspecialchars_decode($t['content']))) ?></div>
                        <div class="task-actions" style="justify-content: flex-start; gap: 15px;">
                            <form method="POST" style="margin:0;"><input type="hidden" name="action" value="move_task"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="new_status" value="IN_TRANSIT"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="btn btn-main btn-sm">< REVERT</button></form>
                            <button type="button" class="btn btn-main btn-sm" data-content="<?= htmlspecialchars(htmlspecialchars_decode($t['content']), ENT_QUOTES) ?>" onclick="editTask(<?= $t['id'] ?>, this)">[ EDIT ]</button>
                            <form method="POST" style="margin:0;"><input type="hidden" name="action" value="delete_task"><input type="hidden" name="item_id" value="<?= $t['id'] ?>"><input type="hidden" name="topic_id" value="<?= $active_topic_id ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" class="btn btn-danger btn-sm" title="Purge" onclick="return confirm('Erase permanently?');">[X]</button></form>
                        </div>
                    </div>
                <?php endforeach; ?>
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

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const splash = document.getElementById('splash-overlay');
            if (splash) setTimeout(() => { splash.classList.add('splash-hidden'); }, 1000);
        });

        // ==========================================
        // [ SCRIPT: SECTOR MANAGEMENT ]
        // ==========================================
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

        // ==========================================
        // [ SCRIPT: TASK MANAGEMENT ]
        // ==========================================
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