<?php
// FILE: cart/add.php
session_start();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $products_id = (int)$_GET['id'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Periksa apakah produk sudah ada di keranjang
    if (array_key_exists($product_id, $_SESSION['cart'])) {
        // Jika sudah ada, tambahkan kuantitasnya
        $_SESSION['cart'][$product_id]++;
    } else {
        // Jika belum ada, tambahkan dengan kuantitas 1
        $_SESSION['cart'][$product_id] = 1;
    }

    // Redirect kembali ke halaman produk atau halaman sebelumnya
    // Gunakan HTTP_REFERER untuk kembali ke halaman sebelumnya
    if (isset($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: ../index.php'); // Fallback jika tidak ada HTTP_REFERER
    }
    exit();
} else {
    // Jika ID tidak valid, redirect ke halaman utama atau tampilkan error
    header('Location: ../index.php');
    exit();
}
?>
