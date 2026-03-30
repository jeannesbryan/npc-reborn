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
        // Update error page to use terminal.css inline
        die("<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'><title>SYS_ERR</title><link rel='stylesheet' href='../assets/terminal.css'></head><body class='t-crt t-center-screen'><div class='t-card danger text-center p-4'><h2 class='mb-3'>> SYS_ERR: 404</h2><p class='text-muted mb-4'>ARCHIVE CORRUPTED OR NOT FOUND.</p><a href='index.php' class='t-btn danger'>[ <-- RETURN_TO_ARCHIVE ]</a></div></body></html>");
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/terminal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
</head>
<body class="t-crt">
    <div class="t-container t-box-lg mt-4 mb-5">
        <div class="mb-4">
            <a href="index.php" class="t-btn danger t-btn-sm">[ <-- RETURN_TO_ARCHIVE ]</a>
        </div>
        
        <div class="t-border-bottom pb-3 mb-4">
            <h1 class="mb-1 text-success" style="font-size: 2rem; text-transform: uppercase;"><?= htmlspecialchars($post['title']) ?></h1>
            <div class="text-muted fs-small d-flex justify-content-between align-items-center mt-2">
                <span>> LOG_DATE: <?= date('Y-m-d // H:i', strtotime($post['created_at'])) ?></span>
                <span style="color: #a8ffb7;">&#x2299; <?= number_format($post['views']) ?> HITS</span>
            </div>
        </div>

        <textarea id="raw-markdown" style="display:none;"><?= htmlspecialchars($post['content']) ?></textarea>
        
        <div id="content" class="t-markdown">
            <span class="text-success">> DECRYPTING_MANIFESTO_DATA... PLEASE WAIT. <span class="t-blink">_</span></span>
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
            
            setTimeout(() => {
                document.getElementById('content').innerHTML = marked.parse(rawMarkdown);
            }, 300); // Efek jeda dekripsi
        });
    </script>
</body>
</html>