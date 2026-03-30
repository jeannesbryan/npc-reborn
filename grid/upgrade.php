<?php
// upgrade.php - Versi 2 (Patch Data Lama)
date_default_timezone_set('Asia/Jakarta');
$db_file = __DIR__ . '/grid_data.sqlite';

try {
    $pdo = new PDO("sqlite:" . $db_file);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Buat tabel topics jika belum ada
    $pdo->exec("CREATE TABLE IF NOT EXISTS topics (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        created_at DATETIME
    )");

    // 2. Pastikan minimal ada 1 topik (Sektor) default
    $stmt = $pdo->query("SELECT COUNT(*) FROM topics");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO topics (name, created_at) VALUES ('MAIN_OBJECTIVE', datetime('now', 'localtime'))");
    }
    
    // Ambil ID topik default tersebut untuk menampung task lama
    $default_topic_id = $pdo->query("SELECT id FROM topics LIMIT 1")->fetchColumn();

    // 3. Cek apakah tabel tasks sudah ada
    $tableExists = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='tasks'")->fetchColumn();

    if (!$tableExists) {
        // Jika database benar-benar kosong, buat tabel baru
        $pdo->exec("CREATE TABLE tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            topic_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            status TEXT DEFAULT 'PENDING',
            position INTEGER DEFAULT 0,
            FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
        )");
    } else {
        // Jika tabel lama sudah ada, kita periksa struktur kolomnya
        $columns = $pdo->query("PRAGMA table_info(tasks)")->fetchAll(PDO::FETCH_ASSOC);
        $hasTopicId = false;
        $hasPosition = false;
        
        foreach ($columns as $col) {
            if ($col['name'] === 'topic_id') $hasTopicId = true;
            if ($col['name'] === 'position') $hasPosition = true;
        }

        // Suntikkan kolom topic_id ke tabel lama (hubungkan task lama ke Sektor Utama)
        if (!$hasTopicId) {
            $pdo->exec("ALTER TABLE tasks ADD COLUMN topic_id INTEGER NOT NULL DEFAULT $default_topic_id");
        }
        // Suntikkan kolom urutan (position)
        if (!$hasPosition) {
            $pdo->exec("ALTER TABLE tasks ADD COLUMN position INTEGER DEFAULT 0");
        }
    }

    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <title>GRID UPGRADE V2</title>
        <style>
            body { background: #030303; color: #00ff41; font-family: 'Courier New', monospace; padding: 40px; }
            a { color: #ff003c; text-decoration: none; border: 1px solid #ff003c; padding: 5px 10px; display: inline-block; margin-top: 20px; }
            a:hover { background: #ff003c; color: #fff; }
        </style>
    </head>
    <body>
        <h2>> DATABASE_UPGRADE_COMPLETE (V2).</h2>
        <p>> Struktur Grid Matrix lama berhasil di-patch dan disinkronisasi.</p>
        <p>> Data lama Anda berhasil diselamatkan dan dipindah ke 'MAIN_OBJECTIVE'.</p>
        <a href='index.php'>[ ➜ KEMBALI KE OP: GRID ]</a>
    </body>
    </html>
    ";

} catch (PDOException $e) {
    echo "<div style='color:red; font-family:monospace;'>> SYS_ERR: GAGAL MEM-PATCH MATRIX. <br>" . htmlspecialchars($e->getMessage()) . "</div>";
}
?>