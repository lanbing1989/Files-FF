<?php
require_once 'inc/auth.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login($_POST['username'], $_POST['password'])) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "用户名或密码错误";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>文件分发管理登录</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card p-4 shadow">
                <h4 class="mb-3">登录</h4>
                <form method="post">
                    <div class="mb-3">
                        <label>用户名</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>密码</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">登录</button>
                    <?php if(isset($error)) echo "<p class='text-danger mt-3'>$error</p>"; ?>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>