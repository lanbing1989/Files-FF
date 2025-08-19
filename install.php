<?php
require_once __DIR__ . '/inc/db.php';
$msg = "数据库已自动初始化，管理员：admin 密码：admin123";
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>数据库初始化</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="alert alert-info"><?php echo htmlspecialchars($msg); ?></div>
    <a href="index.php" class="btn btn-primary">进入登录</a>
</div>
</body>
</html>