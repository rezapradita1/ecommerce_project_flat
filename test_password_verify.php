<?php
// FILE: test_password_verify.php

$password_from_form = 'admin123'; // Ini adalah password plain text yang Anda ketik di form
$hashed_password_from_db = 'PASTE_THE_HASH_FROM_DB_HERE'; // SALIN PASTE HASH DARI DB ANDA DI SINI

echo "Password dari form (plain text): '" . htmlspecialchars($password_from_form) . "'<br>";
echo "Password hash dari database: '" . htmlspecialchars($hashed_password_from_db) . "'<br>";

if (password_verify($password_from_form, $hashed_password_from_db)) {
    echo "<h3>Hasil: PASSWORD MATCH!</h3>";
} else {
    echo "<h3>Hasil: PASSWORD TIDAK MATCH!</h3>";
}

// Tambahan debug: cek panjang string
echo "Panjang plain text: " . strlen($password_from_form) . "<br>";
echo "Panjang hash dari DB: " . strlen($hashed_password_from_db) . "<br>";
?>
