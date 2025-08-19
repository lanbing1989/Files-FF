<?php
session_start();
require_once __DIR__ . '/db.php';

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit();
    }
}

function login($username, $password) {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE username=?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>