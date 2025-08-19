<?php
function get_db() {
    $dbfile = __DIR__ . '/../db.sqlite';
    $init = !file_exists($dbfile);
    $db = new PDO('sqlite:' . $dbfile);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if ($init) {
        $db->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL
        )");
        $db->exec("CREATE TABLE IF NOT EXISTS files (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        // 默认管理员
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username=?");
        $stmt->execute(['admin']);
        if ($stmt->fetchColumn() == 0) {
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (username, password) VALUES (?, ?)")->execute(['admin', $password]);
        }
    }
    return $db;
}
?>