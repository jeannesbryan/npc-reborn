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
        die("<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>SYS_ERR</title><link rel='stylesheet' href='../assets/style.css'></head><body style='display:flex;align-items:center;justify-content:center;height:100vh;text-align:center;'><div class='card p-5 border-danger'><h2 class='text-danger mb-3'>SYS_ERR: 404</h2><p class='text-muted mb-4'>> ARCHIVE CORRUPTED OR NOT FOUND.</p><a href='index.php' class='btn btn-outline-danger'>[ <-- RETURN_TO_ARCHIVE ]</a></div></body></html>");
    }

    $pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?")->execute([$post['id']]);
    $post['views'] += 1; 
    
} catch (PDOException $e) {
    die("> SYS_ERR: KONEKSI KE DATA BANK TERPUTUS.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> - DATA ARCHIVE</title>
    <meta name="theme-color" content="#030303">    
    <link rel="icon" type="image/svg+xml" href="../assets/npc-icon.svg">
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <style>
        .post-header { margin-bottom: 2rem; border-bottom: 2px solid var(--text-main); padding-bottom: 1rem; }
        .markdown-body { line-height: 1.8; font-size: 0.95rem; color: #d4d4d4; }
        .markdown-body img { max-width: 100%; height: auto; border: 1px solid var(--border-color); margin: 15px 0; filter: sepia(30%) hue-rotate(70deg); }
        .markdown-body h2, .markdown-body h3 { margin-top: 1.5em; margin-bottom: 0.5em; border-bottom: 1px dashed var(--border-color); padding-bottom: 5px; color: var(--text-main); text-transform: uppercase; }
        .markdown-body blockquote { border-left: 4px solid var(--text-muted); color: var(--text-muted); margin: 0; padding-left: 15px; background: rgba(0, 255, 65, 0.05); padding: 10px 15px; font-style: italic; }
        .markdown-body pre { background-color: var(--bg-card); padding: 15px; overflow-x: auto; border: 1px solid var(--border-color); box-shadow: inset 0 0 10px rgba(0,0,0,0.8); }
        .markdown-body code { font-family: 'JetBrains Mono', monospace; background-color: rgba(0,255,65,0.1); color: var(--accent); padding: 2px 4px; }
        .markdown-body pre code { background-color: transparent; padding: 0; color: inherit; }
        .markdown-body a { text-decoration: underline dashed; }
    </style>
</head>
<body>
    <div class="container mt-4 mb-5">
        <div class="mb-4">
            <a href="index.php" class="text-muted fs-small text-decoration-none">[ <-- RETURN_TO_ARCHIVE ]</a>
        </div>
        
        <div class="post-header">
            <h1 class="mb-1 text-main"><?= htmlspecialchars($post['title']) ?></h1>
            <div class="text-muted fs-small d-flex justify-content-between align-items-center mt-2">
                <span>> LOG_DATE: <?= date('Y-m-d // H:i', strtotime($post['created_at'])) ?></span>
                <span class="text-accent">&#x2299; <?= number_format($post['views']) ?> HITS</span>
            </div>
        </div>

        <textarea id="raw-markdown" style="display:none;"><?= htmlspecialchars($post['content']) ?></textarea>
        
        <div id="content" class="markdown-body">
            <span class="text-main">> DECRYPTING_MANIFESTO_DATA... PLEASE WAIT. <span class="blinking-cursor"></span></span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const rawMarkdown = document.getElementById('raw-markdown').value;
            marked.setOptions({
                highlight: function(code, lang) {
                    const language = hljs.getLanguage(lang) ? lang : 'plaintext';
                    return hljs.highlight(code, { language }).value;
                }
            });
            
            // Memberikan sedikit efek delay "dekripsi" ala terminal
            setTimeout(() => {
                document.getElementById('content').innerHTML = marked.parse(rawMarkdown);
            }, 300);
        });
    </script>
</body>
</html>