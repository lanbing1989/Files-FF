<?php
require_once 'inc/auth.php';
check_login();
$db = get_db();
$files = $db->query("SELECT * FROM files ORDER BY uploaded_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// 游离文件查找
$files_dir = __DIR__.'/files/';
$actual_files = array_diff(scandir($files_dir), array('.', '..'));
$db_files = array_column($files, 'filename');
$orphans = array_diff($actual_files, $db_files);

// 游离文件认领处理
if (isset($_GET['adopt']) && $_GET['adopt']) {
    $filename = basename($_GET['adopt']);
    if (in_array($filename, $orphans)) {
        $stmt = $db->prepare("INSERT INTO files(filename) VALUES(?)");
        $stmt->execute([$filename]);
        header('Location: dashboard.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>文件分发管理平台</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css">
</head>
<body>
<nav class="navbar navbar-dark bg-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">文件分发管理平台</a>
    <span class="navbar-text">欢迎, <?php echo $_SESSION['username']; ?></span>
    <a href="logout.php" class="btn btn-outline-light ms-2">退出登录</a>
  </div>
</nav>
<div class="container">
    <h3>文件列表</h3>
    <a href="upload.php" class="btn btn-primary mb-3">上传新文件</a>
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>文件名</th>
                <th>上传时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($files as $f): ?>
            <tr>
                <td><?php echo htmlspecialchars($f['filename']); ?></td>
                <td><?php echo $f['uploaded_at']; ?></td>
                <td>
                    <a href="files/<?php echo urlencode($f['filename']); ?>" class="btn btn-success btn-sm" target="_blank">下载</a>
                    <a href="edit.php?id=<?php echo $f['id']; ?>" class="btn btn-warning btn-sm">重命名</a>
                    <a href="delete.php?id=<?php echo $f['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('确认删除？');">删除</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($orphans): ?>
    <h3 class="mt-5 text-danger">游离文件（未登记）</h3>
    <table class="table table-bordered table-hover">
        <thead>
            <tr>
                <th>文件名</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($orphans as $o): ?>
            <tr>
                <td><?php echo htmlspecialchars($o); ?></td>
                <td>
                    <a href="?adopt=<?php echo urlencode($o); ?>" class="btn btn-info btn-sm"
                       onclick="return confirm('确认登记此文件？');">认领并登记</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>