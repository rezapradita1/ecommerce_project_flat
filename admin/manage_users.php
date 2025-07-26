<?php
// FILE: admin/manage_users.php

// Pastikan sesi dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sertakan file autentikasi dan cek status login admin
include '../includes/auth.php';
check_admin_login(); // Fungsi ini akan mengarahkan jika belum login

// Sertakan koneksi database
include '../includes/db_connect.php';

// Inisialisasi variabel untuk pesan notifikasi
$message = '';
$message_type = ''; // 'success' atau 'error'

// --- LOGIKA PENANGANAN AKSI (HAPUS PENGGUNA) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $user_id_to_delete = intval($_GET['id']); // ID pengguna yang akan dihapus

    // Pastikan admin tidak menghapus dirinya sendiri!
    // Asumsikan $_SESSION['admin_id'] menyimpan ID pengguna admin yang sedang login
    if (isset($_SESSION['admin_id']) && $user_id_to_delete == $_SESSION['admin_id']) {
        $message = "Anda tidak bisa menghapus akun Anda sendiri.";
        $message_type = 'error';
    } else {
        // Query untuk menghapus pengguna
        $sql_delete = "DELETE FROM users WHERE id = ?";
        $stmt_delete = $conn->prepare($sql_delete);

        if ($stmt_delete) {
            $stmt_delete->bind_param("i", $user_id_to_delete); // 'i' untuk integer
            if ($stmt_delete->execute()) {
                $message = "Pengguna berhasil dihapus.";
                $message_type = 'success';
            } else {
                $message = "Gagal menghapus pengguna: " . $stmt_delete->error;
                $message_type = 'error';
            }
            $stmt_delete->close();
        } else {
            $message = "Error menyiapkan query hapus: " . $conn->error;
            $message_type = 'error';
        }
    }
    // Redirect untuk mencegah pengiriman ulang form saat refresh
    header("Location: manage_users.php?message=" . $message_type . "&text=" . urlencode($message));
    exit();
}

// --- LOGIKA PENGAMBILAN DATA PENGGUNA ---
$users = [];
// Menggunakan prepared statement untuk query SELECT
$sql_users = "SELECT id, username, email, full_name, phone_number, address, created_at FROM users ORDER BY created_at DESC";
$stmt_users = $conn->prepare($sql_users);

if ($stmt_users) {
    if ($stmt_users->execute()) {
        $result_users = $stmt_users->get_result();
        while ($row = $result_users->fetch_assoc()) {
            $users[] = $row;
        }
        $result_users->free(); // Bebaskan hasil query
    } else {
        $message = "Gagal mengambil data pengguna: " . $stmt_users->error;
        $message_type = 'error';
    }
    $stmt_users->close();
} else {
    $message = "Error menyiapkan query pengguna: " . $conn->error;
    $message_type = 'error';
}

// Menangani pesan dari redirect (setelah hapus)
if (isset($_GET['message']) && isset($_GET['text'])) {
    $message = htmlspecialchars(urldecode($_GET['text']));
    $message_type = htmlspecialchars($_GET['message']);
}

?>

<?php include '../includes/admin_header.php'; // Sertakan header admin ?>

<main class="container admin-content-area">
  <h2>Kelola Pengguna</h2>

  <?php if (!empty($message)): ?>
  <div class="message-<?php echo $message_type; ?>">
    <?php echo $message; ?>
  </div>
  <?php endif; ?>

  <?php if (empty($users)): ?>
  <p class="text-center">Belum ada pengguna terdaftar.</p>
  <?php else: ?>
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>Nama Lengkap</th>
        <th>Telepon</th>
        <th>Tanggal Daftar</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $user): ?>
      <tr>
        <td><?php echo htmlspecialchars($user['id']); ?></td>
        <td><?php echo htmlspecialchars($user['username']); ?></td>
        <td><?php echo htmlspecialchars($user['email']); ?></td>
        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
        <td><?php echo htmlspecialchars($user['phone_number']); ?></td>
        <td><?php echo htmlspecialchars(date('d M Y H:i', strtotime($user['created_at']))); ?></td>
        <td class="actions">
          <a href="manage_users.php?action=delete&id=<?php echo htmlspecialchars($user['id']); ?>"
            onclick="return confirm('Anda yakin ingin menghapus pengguna ini?');" class="delete" title="Hapus Pengguna"
            style="color: #dc3545;">
            <i class="fas fa-trash-alt"></i> Hapus
          </a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</main>

<?php
// Tutup koneksi database
if (isset($conn) && $conn) {
    $conn->close(); // Menggunakan close() untuk konsistensi OO
}

// Sertakan footer admin
include '../includes/admin_footer.php';
?>