<?php
// FILE: login.php

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
$message_type = ''; // 'success' atau 'error'

// Tangani proses login saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = trim($_POST['username_or_email']);
    $password = $_POST['password']; // Password mentah dari form

    if (empty($username_or_email) || empty($password)) {
        $message = "Username/Email dan password tidak boleh kosong.";
        $message_type = 'error';
    } else {
        // Gunakan prepared statement untuk mencegah SQL Injection
        // Cari pengguna berdasarkan username atau email
        $sql = "SELECT id, username, email, password, full_name FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ss", $username_or_email, $username_or_email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                // Verifikasi password yang di-hash
                if (password_verify($password, $user['password'])) {
                    // Login berhasil
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];

                    $message = "Login berhasil! Selamat datang, " . htmlspecialchars($user['full_name']) . ".";
                    $message_type = 'success';

                    // Redirect ke halaman sebelumnya atau halaman utama
                    header('Location: index.php'); // Atau ke 'my_account.php'
                    exit();
                } else {
                    $message = "Username/Email atau password salah.";
                    $message_type = 'error';
                }
            } else {
                $message = "Username/Email atau password salah.";
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $message = "Error menyiapkan query login: " . $conn->error;
            $message_type = 'error';
        }
    }
}

// Menangani pesan dari redirect (misal dari cart.php jika belum login)
if (isset($_GET['message']) && isset($_GET['text'])) {
    $message = htmlspecialchars(urldecode($_GET['text']));
    $message_type = htmlspecialchars($_GET['message']);
}

// Sertakan header halaman pengguna
include 'includes/header.php';
?>

<main class="container my-5">
  <h2 class="text-center mb-4">User Login</h2>

  <?php if (!empty($message)): ?>
  <div class="message message-<?php echo $message_type; ?>">
    <?php echo $message; ?>
  </div>
  <?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card p-4 shadow-sm">
        <form action="login.php" method="post">
          <div class="mb-3">
            <label for="username_or_email" class="form-label">Username or Email:</label>
            <input type="text" class="form-control" id="username_or_email" name="username_or_email" required
              value="<?php echo htmlspecialchars($_POST['username_or_email'] ?? ''); ?>">
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password:</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        <p class="text-center mt-3">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
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