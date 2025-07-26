<?php
// FILE: cart/remove.php
session_start();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
}

header('Location: ../cart.php'); // Kembali ke halaman keranjang
exit();
?>
