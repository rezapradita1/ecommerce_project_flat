<?php
// FILE: generate_hash.php
$password_plain = 'admin123'; // Password yang ingin Anda hash
$password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);
echo "Hash baru untuk 'admin123': <br>";
echo "<strong>" . htmlspecialchars($password_hashed) . "</strong>";
?>
