<?php
date_default_timezone_set('Asia/Jakarta');
$db_file = __DIR__ . '/blog_data.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $stmt = $pdo->query("SELECT title, slug, created_at FROM articles WHERE status = 'PUBLISHED' ORDER BY created_at DESC");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $articles = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - Jeannes Bryan | NPC</title>
    <meta name="theme-color" content="#121212">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="apple-touch-icon" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .blog-list { list-style: none; padding: 0; }
        .blog-item { padding: 15px 0; border-bottom: 1px dashed var(--border-color); }
        .blog-item:last-child { border-bottom: none; }
        .blog-date { color: var(--text-muted); font-size: 0.85rem; margin-bottom: 5px; }
        .blog-title { font-size: 1.2rem; font-weight: bold; color: var(--text-main); text-decoration: none; }
        .blog-title:hover { color: var(--danger); text-decoration: none; }
    </style>
</head>
<body>
    <div class="container mt-4">
        
        <div class="text-center border-bottom pb-2 mb-4">
            <h1 class="mb-0">/blog</h1>
            <div class="text-muted mt-1">Written transmissions.</div>
        </div>

        <ul class="blog-list mb-5">
            <?php if (count($articles) > 0): ?>
                <?php foreach ($articles as $art): ?>
                    <li class="blog-item">
                        <div class="blog-date">[ <?= date('Y.m.d', strtotime($art['created_at'])) ?> ]</div>
                        <a href="<?= htmlspecialchars($art['slug']) ?>" class="blog-title">
                            <?= htmlspecialchars($art['title']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="text-muted text-center mt-5">- No transmissions found -</li>
            <?php endif; ?>
        </ul>

        <div class="text-center mt-5 mb-5">
            <a href="../index.php" class="btn btn-dark border-secondary btn-sm">&larr; Return to Root</a>
        </div>
        
    </div>
</body>
</html>