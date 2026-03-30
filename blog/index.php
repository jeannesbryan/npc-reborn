<?php
date_default_timezone_set('Asia/Jakarta');
$db_file = __DIR__ . '/blog_data.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $stmt = $pdo->query("SELECT title, slug, created_at, views FROM articles WHERE status = 'PUBLISHED' ORDER BY created_at DESC");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $articles = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARCHIVE: MANIFESTO - Bunker OS</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/terminal.css">
    
    </head>
<body class="t-crt">
    
    <div class="t-container t-box-md mt-4">
        
        <div class="text-center t-border-bottom pb-3 mb-4 mt-3">
            <h1 class="mb-1 text-success">[ ARCHIVE: MANIFESTO ]</h1>
            <div class="text-muted fs-small">> LONG-TERM DATA STORAGE ACCESSED. <span class="t-blink">_</span></div>
        </div>

        <div class="t-card mb-5">
            <div class="t-list-group m-0" style="border: none;">
                <?php if (count($articles) > 0): ?>
                    <?php foreach ($articles as $art): ?>
                        <a href="<?= htmlspecialchars($art['slug']) ?>" class="t-list-item py-3" style="border-bottom: 1px dashed var(--t-green-dim);">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="font-bold text-success" style="font-size: 1.1rem; text-transform: uppercase;">
                                    <?= htmlspecialchars($art['title']) ?>
                                </span>
                            </div>
                            <div class="text-muted fs-small d-flex justify-content-between">
                                <span>> LOG_DATE: <?= date('Y-m-d', strtotime($art['created_at'])) ?></span>
                                <span title="Entities Reached">&#x2299; <?= number_format($art['views'] ?? 0) ?> HITS</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-danger text-center py-5">> ARCHIVE_EMPTY. NO DATA RECOVERED.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-5 mb-5">
            <a href="../index.php" class="t-btn danger t-btn-sm">[ <-- CLOSE_ARCHIVE ]</a>
        </div>
        
    </div>
</body>
</html>