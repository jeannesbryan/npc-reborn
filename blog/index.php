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
    <title>ARCHIVE: MANIFESTO - Jeannes Bryan | NPC</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .blog-list { list-style: none; padding: 0; }
        .blog-item { padding: 15px 10px; border-bottom: 1px dashed var(--border-color); transition: 0.2s; }
        .blog-item:hover { background: rgba(0, 255, 65, 0.03); border-left: 3px solid var(--text-main); padding-left: 15px; }
        .blog-item:last-child { border-bottom: none; }
        .blog-date { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 5px; }
        .blog-title { font-size: 1.2rem; font-weight: bold; color: var(--text-main); text-decoration: none; display: block; }
        .blog-title:hover { color: var(--accent); text-shadow: 0 0 5px var(--accent); }
    </style>
</head>
<body>
    <div class="container mt-4">
        
        <div class="text-center border-bottom pb-3 mb-4 mt-3">
            <h1 class="mb-1 text-main">[ ARCHIVE: MANIFESTO ]</h1>
            <div class="text-muted">> LONG-TERM DATA STORAGE ACCESSED. <span class="blinking-cursor"></span></div>
        </div>

        <div class="card p-2 mb-5">
            <ul class="blog-list">
                <?php if (count($articles) > 0): ?>
                    <?php foreach ($articles as $art): ?>
                        <li class="blog-item">
                            <div class="blog-date d-flex justify-content-between">
                                <span>> LOG_DATE: <?= date('Y-m-d', strtotime($art['created_at'])) ?></span>
                                <span class="text-muted" title="Entities Reached">&#x2299; <?= number_format($art['views'] ?? 0) ?> HITS</span>
                            </div>
                            <a href="<?= htmlspecialchars($art['slug']) ?>" class="blog-title">
                                <?= htmlspecialchars($art['title']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="text-danger text-center py-5">> ARCHIVE_EMPTY. NO DATA RECOVERED.</li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="text-center mt-5 mb-5">
            <a href="../index.php" class="btn btn-danger btn-sm">[ <-- CLOSE_ARCHIVE ]</a>
        </div>
        
    </div>
</body>
</html>