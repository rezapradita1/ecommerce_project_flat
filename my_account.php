<?php
// FILE: my_account.php

// Pastikan sesi dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: login.php?message=error&text=' . urlencode('Anda perlu login untuk mengakses halaman akun saya.'));
    exit();
}

// Sertakan koneksi database
include 'includes/db_connect.php';

// Inisialisasi variabel untuk pesan notifikasi
$message = '';
$message_type = ''; // 'success' or 'error'

// Ambil ID pengguna dari sesi
$user_id = $_SESSION['user_id'];

// Inisialisasi data pengguna
$user_data = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'phone_number' => '',
    'address' => ''
];

// --- LOGIKA PENGAMBILAN DATA PENGGUNA ---
$sql_fetch_user = "SELECT username, email, full_name, phone_number, address FROM users WHERE id = ?";
$stmt_fetch_user = $conn->prepare($sql_fetch_user);

if ($stmt_fetch_user) {
    $stmt_fetch_user->bind_param("i", $user_id);
    if ($stmt_fetch_user->execute()) {
        $result_fetch_user = $stmt_fetch_user->get_result();
        if ($fetched_data = $result_fetch_user->fetch_assoc()) {
            $user_data = $fetched_data;
        } else {
            // Pengguna tidak ditemukan (seharusnya tidak terjadi jika login berhasil)
            $message = "Data pengguna tidak ditemukan. Silakan login ulang.";
            $message_type = 'error';
            // Opsional: Hapus sesi dan arahkan ke login
            session_unset();
            session_destroy();
            header('Location: login.php?message=error&text=' . urlencode($message));
            exit();
        }
        $result_fetch_user->free();
    } else {
        error_log("Error executing user data fetch: " . $stmt_fetch_user->error);
        $message = "Gagal mengambil data profil Anda.";
        $message_type = 'error';
    }
    $stmt_fetch_user->close();
} else {
    error_log("Error preparing user data fetch query: " . $conn->error);
    $message = "Error menyiapkan query data profil.";
    $message_type = 'error';
}

// --- LOGIKA UPDATE PROFIL PENGGUNA ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_full_name = trim($_POST['full_name']);
    $new_phone_number = trim($_POST['phone_number']);
    $new_address = trim($_POST['address']);

    // Validasi dasar
    if (empty($new_username) || empty($new_email) || empty($new_full_name)) {
        $message = "Username, Email, dan Nama Lengkap tidak boleh kosong.";
        $message_type = 'error';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid.";
        $message_type = 'error';
    } else {
        // Cek duplikasi username atau email (kecuali milik sendiri)
        $sql_check_duplicate = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $stmt_check_duplicate = $conn->prepare($sql_check_duplicate);
        if ($stmt_check_duplicate) {
            $stmt_check_duplicate->bind_param("ssi", $new_username, $new_email, $user_id);
            $stmt_check_duplicate->execute();
            $result_check_duplicate = $stmt_check_duplicate->get_result();

            if ($result_check_duplicate->num_rows > 0) {
                $message = "Username atau Email sudah digunakan oleh pengguna lain.";
                $message_type = 'error';
            } else {
                // Update data pengguna
                $sql_update_user = "UPDATE users SET username = ?, email = ?, full_name = ?, phone_number = ?, address = ? WHERE id = ?";
                $stmt_update_user = $conn->prepare($sql_update_user);

                if ($stmt_update_user) {
                    $stmt_update_user->bind_param("sssssi", $new_username, $new_email, $new_full_name, $new_phone_number, $new_address, $user_id);
                    if ($stmt_update_user->execute()) {
                        $message = "Profil berhasil diperbarui!";
                        $message_type = 'success';
                        // Perbarui sesi jika username atau nama lengkap berubah
                        $_SESSION['username'] = $new_username;
                        $_SESSION['full_name'] = $new_full_name;
                        // Ambil ulang data untuk menampilkan yang terbaru di form
                        $user_data['username'] = $new_username;
                        $user_data['email'] = $new_email;
                        $user_data['full_name'] = $new_full_name;
                        $user_data['phone_number'] = $new_phone_number;
                        $user_data['address'] = $new_address;
                    } else {
                        $message = "Gagal memperbarui profil: " . $stmt_update_user->error;
                        $message_type = 'error';
                    }
                    $stmt_update_user->close();
                } else {
                    $message = "Error menyiapkan query update profil: " . $conn->error;
                    $message_type = 'error';
                }
            }
            $stmt_check_duplicate->close();
        } else {
            $message = "Error menyiapkan query cek duplikasi.";
            $message_type = 'error';
        }
    }
}

// --- LOGIKA UPDATE PASSWORD PENGGUNA ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Ambil password hash yang tersimpan untuk verifikasi
    $sql_get_hash = "SELECT password FROM users WHERE id = ?";
    $stmt_get_hash = $conn->prepare($sql_get_hash);
    $hashed_password_from_db = '';

    if ($stmt_get_hash) {
        $stmt_get_hash->bind_param("i", $user_id);
        $stmt_get_hash->execute();
        $result_get_hash = $stmt_get_hash->get_result();
        if ($row = $result_get_hash->fetch_assoc()) {
            $hashed_password_from_db = $row['password'];
        }
        $stmt_get_hash->close();
    }

    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $message = "Semua bidang password harus diisi.";
        $message_type = 'error';
    } elseif (!password_verify($current_password, $hashed_password_from_db)) {
        $message = "Password saat ini salah.";
        $message_type = 'error';
    } elseif (strlen($new_password) < 6) {
        $message = "Password baru harus minimal 6 karakter.";
        $message_type = 'error';
    } elseif ($new_password !== $confirm_new_password) {
        $message = "Konfirmasi password baru tidak cocok.";
        $message_type = 'error';
    } else {
        // Hash password baru
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password di database
        $sql_update_password = "UPDATE users SET password = ? WHERE id = ?";
        $stmt_update_password = $conn->prepare($sql_update_password);
        if ($stmt_update_password) {
            $stmt_update_password->bind_param("si", $new_hashed_password, $user_id);
            if ($stmt_update_password->execute()) {
                $message = "Password berhasil diperbarui!";
                $message_type = 'success';
            } else {
                $message = "Gagal memperbarui password: " . $stmt_update_password->error;
                $message_type = 'error';
            }
            $stmt_update_password->close();
        } else {
            $message = "Error menyiapkan query update password: " . $conn->error;
            $message_type = 'error';
        }
    }
}

// Menangani pesan dari redirect (jika ada)
if (isset($_GET['message']) && isset($_GET['text'])) {
    $message = htmlspecialchars(urldecode($_GET['text']));
    $message_type = htmlspecialchars($_GET['message']);
}

// Sertakan header halaman pengguna
include 'includes/header.php';
?>

<main class="container my-5">
  <h2 class="text-center mb-4">My Account</h2>

  <?php if (!empty($message)): ?>
  <div class="message message-<?php echo $message_type; ?>">
    <?php echo $message; ?>
  </div>
  <?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card p-4 shadow-sm mb-4">
        <h3 class="mb-3">Edit Profile Information</h3>
        <form action="my_account.php" method="post">
          <div class="mb-3">
            <label for="username" class="form-label">Username:</label>
            <input type="text" class="form-control" id="username" name="username"
              value="<?= htmlspecialchars($user_data['username']) ?>" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" class="form-control" id="email" name="email"
              value="<?= htmlspecialchars($user_data['email']) ?>" required>
          </div>
          <div class="mb-3">
            <label for="full_name" class="form-label">Full Name:</label>
            <input type="text" class="form-control" id="full_name" name="full_name"
              value="<?= htmlspecialchars($user_data['full_name']) ?>" required>
          </div>
          <div class="mb-3">
            <label for="phone_number" class="form-label">Phone Number:</label>
            <input type="text" class="form-control" id="phone_number" name="phone_number"
              value="<?= htmlspecialchars($user_data['phone_number']) ?>">
          </div>
          <div class="mb-3">
            <label for="address" class="form-label">Address:</label>
            <textarea class="form-control" id="address" name="address"
              rows="3"><?= htmlspecialchars($user_data['address']) ?></textarea>
          </div>
          <button type="submit" name="update_profile" class="btn btn-primary w-100">
            <i class="fas fa-save"></i> Update Profile
          </button>
        </form>
      </div>

      <div class="card p-4 shadow-sm">
        <h3 class="mb-3">Change Password</h3>
        <form action="my_account.php" method="post">
          <div class="mb-3">
            <label for="current_password" class="form-label">Current Password:</label>
            <input type="password" class="form-control" id="current_password" name="current_password" required>
          </div>
          <div class="mb-3">
            <label for="new_password" class="form-label">New Password:</label>
            <input type="password" class="form-control" id="new_password" name="new_password" required>
          </div>
          <div class="mb-3">
            <label for="confirm_new_password" class="form-label">Confirm New Password:</label>
            <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
          </div>
          <button type="submit" name="update_password" class="btn btn-warning w-100">
            <i class="fas fa-key"></i> Change Password
          </button>
        </form>
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