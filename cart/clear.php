<?php
// FILE: cart/clear.php
session_start();

// Kosongkan seluruh keranjang
unset($_SESSION['cart']);

header('Location: ../cart.php'); // Kembali ke halaman keranjang
exit();
?>
