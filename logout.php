<?php
// FILE: logout.php

// Pastikan sesi dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hapus semua variabel sesi
session_unset();

// Hancurkan sesi
session_destroy();

// Redirect pengguna ke halaman login atau halaman utama
// Anda bisa menambahkan parameter pesan jika ingin menampilkan notifikasi logout
header('Location: login.php?message=success&text=' . urlencode('Anda telah berhasil logout.'));
exit();
?>