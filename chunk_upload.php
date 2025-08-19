<?php
$targetDir = "files/";
$fileName = $_POST["name"];
$chunk = isset($_POST["chunk"]) ? (int)$_POST["chunk"] : 0;
$chunks = isset($_POST["chunks"]) ? (int)$_POST["chunks"] : 0;

if (!file_exists($targetDir)) @mkdir($targetDir);

$out = fopen("{$targetDir}/{$fileName}.part{$chunk}", "wb");
if ($out) {
    $in = fopen($_FILES["file"]["tmp_name"], "rb");
    if ($in) {
        while ($buff = fread($in, 4096)) fwrite($out, $buff);
    }
    fclose($in);
    fclose($out);
}

// 合并分片
if ($chunks > 0 && $chunk == $chunks - 1) {
    $out = fopen("{$targetDir}/{$fileName}", "wb");
    for ($i=0; $i<$chunks; $i++) {
        $in = fopen("{$targetDir}/{$fileName}.part{$i}", "rb");
        while ($buff = fread($in, 4096)) fwrite($out, $buff);
        fclose($in);
        @unlink("{$targetDir}/{$fileName}.part{$i}");
    }
    fclose($out);
}
echo 'success';
?>