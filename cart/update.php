<?php
// FILE: cart/update.php
session_start();

if (isset($_POST['id']) && is_numeric($_POST['id']) && isset($_POST['quantity']) && is_numeric($_POST['quantity'])) {
    $product_id = (int)$_POST['id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity > 0) {
        $_SESSION['cart'][$product_id] = $quantity;
    } else {
        // Jika kuantitas 0 atau kurang, hapus item dari keranjang
        unset($_SESSION['cart'][$product_id]);
    }
}

header('Location: ../cart.php'); // Kembali ke halaman keranjang
exit();
?>
