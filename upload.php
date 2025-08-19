<?php
require_once 'inc/auth.php';
check_login();
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file = $_FILES['file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $filename = basename($file['name']);
        $target = __DIR__.'/files/'.$filename;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $stmt = $db->prepare("INSERT INTO files(filename) VALUES(?)");
            $stmt->execute([$filename]);
            header('Location: dashboard.php');
            exit();
        } else {
            $error = "文件保存失败";
        }
    } else {
        $error = "上传失败";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>上传文件</title>
    <link rel="stylesheet" href="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h3>上传新文件</h3>
    <form method="post" enctype="multipart/form-data" id="uploadForm">
        <div class="mb-3">
            <label>选择文件</label>
            <input type="file" name="file" class="form-control" required id="fileInput">
        </div>
        <button type="submit" class="btn btn-primary" id="uploadBtn">上传</button>
        <a href="dashboard.php" class="btn btn-secondary">返回</a>
        <div class="progress mt-3" style="height:25px; display:none;" id="progressWrap">
            <div class="progress-bar" id="progressBar" style="width:0%">0%</div>
        </div>
        <?php if(isset($error)) echo "<p class='text-danger mt-3'>$error</p>"; ?>
    </form>
</div>
<script src="https://cdn.bootcdn.net/ajax/libs/twitter-bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('uploadForm').onsubmit = function(e){
    e.preventDefault();
    var fileInput = document.getElementById('fileInput');
    var file = fileInput.files[0];
    if (!file) return;
    var formData = new FormData();
    formData.append('file', file);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'upload.php', true);
    document.getElementById('progressWrap').style.display = 'block';
    xhr.upload.onprogress = function(e){
        if (e.lengthComputable) {
            var percent = Math.round((e.loaded/e.total)*100);
            var bar = document.getElementById('progressBar');
            bar.style.width = percent + '%';
            bar.innerText = percent + '%';
        }
    };
    xhr.onload = function(){
        if (xhr.status === 200) {
            window.location = 'dashboard.php';
        } else {
            alert('上传失败');
        }
    };
    xhr.send(formData);
};
</script>
</body>
</html>