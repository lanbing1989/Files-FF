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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newname = basename($_POST['filename']);
    $oldpath = __DIR__.'/files/'.$file['filename'];
    $newpath = __DIR__.'/files/'.$newname;
    if(rename($oldpath, $newpath)) {
        $stmt = $db->prepare("UPDATE files SET filename=? WHERE id=?");
        $stmt->execute([$newname, $id]);
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "重命名失败";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>编辑文件名</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h3>编辑文件名</h3>
    <form method="post">
        <div class="mb-3">
            <label>文件名</label>
            <input type="text" name="filename" value="<?php echo htmlspecialchars($file['filename']); ?>" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">保存</button>
        <a href="dashboard.php" class="btn btn-secondary">返回</a>
        <?php if(isset($error)) echo "<p class='text-danger mt-3'>$error</p>"; ?>
    </form>
</div>
<script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>