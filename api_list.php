<?php
require_once 'inc/db.php';
header('Content-Type: application/json');
$db = get_db();
$files = $db->query("SELECT filename, uploaded_at FROM files")->fetchAll(PDO::FETCH_ASSOC);

$files_dir = __DIR__ . '/files/';
foreach ($files as &$f) {
    $file_path = $files_dir . $f['filename'];
    if (is_file($file_path)) {
        $f['md5'] = md5_file($file_path);
    } else {
        $f['md5'] = null;
    }
}
unset($f);

echo json_encode($files);
?>