<?php
// FILE: includes/auth.php (PATH Redirect BERUBAH)

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function check_admin_login() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header("Location: admin/login.php"); // PERUBAHAN: dari /admin/login.php jadi admin/login.php
        exit();
    }
}

function logout_admin() {
    session_start();
    session_unset();
    session_destroy();
    header("Location: admin/login.php?action=logout"); // PERUBAHAN: dari /admin/login.php jadi admin/login.php
    exit();
}
?>
