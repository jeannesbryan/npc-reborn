<?php
$db_file = __DIR__ . '/blog_data.sqlite';
$slug = $_GET['slug'] ?? '';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE slug = ? AND status = 'PUBLISHED' LIMIT 1");
    $stmt->execute([$slug]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        header("HTTP/1.0 404 Not Found");
        die("<body style='background:#121212;color:#f8f9fa;font-family:monospace;text-align:center;padding:50px;'><h1>404 - Transmisi Tidak Ditemukan</h1><a href='index.php' style='color:#ff6b6b;'>Kembali ke /blog</a></body>");
    }
} catch (PDOException $e) {
    die("Database error.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> - Jeannes Bryan</title>
    <meta name="theme-color" content="#121212">    
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <style>
        .post-header { margin-bottom: 2rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; }
        /* Styling khusus untuk Markdown rendered content */
        .markdown-body { line-height: 1.7; font-size: 0.95rem; }
        .markdown-body img { max-width: 100%; height: auto; border-radius: 4px; border: 1px solid var(--border-color); margin: 15px 0; }
        .markdown-body h2, .markdown-body h3 { margin-top: 1.5em; margin-bottom: 0.5em; border-bottom: 1px solid var(--border-color); padding-bottom: 5px; }
        .markdown-body blockquote { border-left: 4px solid var(--border-color); color: var(--text-muted); margin: 0; padding-left: 15px; background: var(--bg-card); padding: 10px 15px; }
        .markdown-body pre { background-color: var(--bg-card); padding: 15px; border-radius: 6px; overflow-x: auto; border: 1px solid var(--border-color); }
        .markdown-body code { font-family: 'JetBrains Mono', monospace; background-color: rgba(255,255,255,0.1); padding: 2px 4px; border-radius: 3px; }
        .markdown-body pre code { background-color: transparent; padding: 0; }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <div class="mb-4">
            <a href="index.php" class="text-muted fs-small text-decoration-none">&larr; Back to /blog</a>
        </div>
        
        <div class="post-header">
            <h1 class="mb-1"><?= htmlspecialchars($post['title']) ?></h1>
            <div class="text-muted fs-small">
                Transmit Date: <?= date('d F Y - H:i', strtotime($post['created_at'])) ?> WIB
            </div>
        </div>

        <textarea id="raw-markdown" style="display:none;"><?= htmlspecialchars($post['content']) ?></textarea>
        
        <div id="content" class="markdown-body">
            <span class="text-muted">Decrypting transmission...</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const rawMarkdown = document.getElementById('raw-markdown').value;
            // Konfigurasi Marked untuk mendukung Highlight.js
            marked.setOptions({
                highlight: function(code, lang) {
                    const language = hljs.getLanguage(lang) ? lang : 'plaintext';
                    return hljs.highlight(code, { language }).value;
                }
            });
            // Render Markdown ke HTML
            document.getElementById('content').innerHTML = marked.parse(rawMarkdown);
        });
    </script>
</body>
</html>