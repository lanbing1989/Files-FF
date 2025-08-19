<?php
require_once 'inc/auth.php';
check_login();
$db = get_db();
$id = intval($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM files WHERE id=?");
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    exit('文件不存在');
}
$filepath = __DIR__.'/files/'.$file['filename'];
if (file_exists($filepath)) {
    unlink($filepath);
}
$stmt = $db->prepare("DELETE FROM files WHERE id=?");
$stmt->execute([$id]);
header('Location: dashboard.php');
exit();
?>