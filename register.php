<?php
// FILE: register.php

// Pastikan sesi dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Jika pengguna sudah login, arahkan ke halaman utama atau dashboard pengguna
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header('Location: index.php'); // Atau ke 'my_account.php'
    exit();
}

// Sertakan koneksi database
include 'includes/db_connect.php';

// Inisialisasi variabel untuk pesan notifikasi
$message = '';
$message_type = ''; // 'success' or 'error'

// Inisialisasi variabel untuk mempertahankan input form
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$full_name = $_POST['full_name'] ?? '';
$phone_number = $_POST['phone_number'] ?? '';
$address = $_POST['address'] ?? '';

// Tangani proses registrasi saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);

    // Validasi input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
        $message = "Username, Email, Password, Konfirmasi Password, dan Nama Lengkap tidak boleh kosong.";
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid.";
        $message_type = 'error';
    } elseif ($password !== $confirm_password) {
        $message = "Konfirmasi password tidak cocok.";
        $message_type = 'error';
    } elseif (strlen($password) < 6) {
        $message = "Password harus minimal 6 karakter.";
        $message_type = 'error';
    } else {
        // Cek apakah username atau email sudah terdaftar
        $sql_check = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt_check = $conn->prepare($sql_check);
        if ($stmt_check) {
            $stmt_check->bind_param("ss", $username, $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $message = "Username atau Email sudah terdaftar.";
                $message_type = 'error';
            } else {
                // Hash password sebelum menyimpan ke database
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Masukkan data pengguna baru ke database
                $sql_insert = "INSERT INTO users (username, email, password, full_name, phone_number, address) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);

                if ($stmt_insert) {
                    $stmt_insert->bind_param("ssssss", $username, $email, $hashed_password, $full_name, $phone_number, $address);
                    if ($stmt_insert->execute()) {
                        $message = "Registrasi berhasil! Silakan login.";
                        $message_type = 'success';
                        // Redirect ke halaman login setelah registrasi berhasil
                        header('Location: login.php?message=success&text=' . urlencode($message));
                        exit();
                    } else {
                        $message = "Gagal mendaftar: " . $stmt_insert->error;
                        $message_type = 'error';
                    }
                    $stmt_insert->close();
                } else {
                    $message = "Error menyiapkan query pendaftaran: " . $conn->error;
                    $message_type = 'error';
                }
            }
            $stmt_check->close();
        } else {
            $message = "Error menyiapkan query cek username/email: " . $conn->error;
            $message_type = 'error';
        }
    }
}

// Sertakan header halaman pengguna
include 'includes/header.php';
?>

<main class="container my-5">
  <h2 class="text-center mb-4">User Registration</h2>

  <?php if (!empty($message)): ?>
  <div class="message message-<?php echo $message_type; ?>">
    <?php echo htmlspecialchars($message); ?>
  </div>
  <?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-md-7">
      <div class="card p-4 shadow-sm">
        <form action="register.php" method="post">
          <div class="mb-3">
            <label for="username" class="form-label">Username:</label>
            <input type="text" class="form-control" id="username" name="username"
              value="<?= htmlspecialchars($username) ?>" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>"
              required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password:</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password:</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
          </div>
          <div class="mb-3">
            <label for="full_name" class="form-label">Full Name:</label>
            <input type="text" class="form-control" id="full_name" name="full_name"
              value="<?= htmlspecialchars($full_name) ?>" required>
          </div>
          <div class="mb-3">
            <label for="phone_number" class="form-label">Phone Number:</label>
            <input type="text" class="form-control" id="phone_number" name="phone_number"
              value="<?= htmlspecialchars($phone_number) ?>">
          </div>
          <div class="mb-3">
            <label for="address" class="form-label">Address:</label>
            <textarea class="form-control" id="address" name="address"
              rows="3"><?= htmlspecialchars($address) ?></textarea>
          </div>
          <button type="submit" class="btn btn-success w-100">Register</button>
        </form>
        <p class="text-center mt-3">Sudah punya akun? <a href="login.php">Login di sini</a></p>
      </div>
    </div>
  </div>
</main>

<?php
// Tutup koneksi database
if (isset($conn) && $conn) {
    $conn->close();
}

// Sertakan footer halaman pengguna
include 'includes/footer.php';
?>