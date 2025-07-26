<?php
// FILE: admin/manage_orders.php

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

// --- LOGIKA PENANGANAN AKSI (UPDATE STATUS PESANAN) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['new_status']);

    $valid_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        $message = "Status tidak valid.";
        $message_type = 'error';
    } else {
        // Gunakan prepared statement untuk update status
        $sql_update_status = "UPDATE orders SET status = ? WHERE id = ?";
        $stmt_update_status = $conn->prepare($sql_update_status);

        if ($stmt_update_status) {
            $stmt_update_status->bind_param("si", $new_status, $order_id); // 's' for string, 'i' for integer
            if ($stmt_update_status->execute()) {
                $message = "Status pesanan #{$order_id} berhasil diperbarui menjadi '{$new_status}'.";
                $message_type = 'success';
            } else {
                $message = "Gagal memperbarui status pesanan: " . $stmt_update_status->error;
                $message_type = 'error';
            }
            $stmt_update_status->close();
        } else {
            $message = "Error menyiapkan query update status: " . $conn->error;
            $message_type = 'error';
        }
    }
    // Redirect setelah POST untuk mencegah pengiriman ulang form (PRG pattern)
    header("Location: manage_orders.php?message=" . $message_type . "&text=" . urlencode($message));
    exit();
}

// --- LOGIKA PENGAMBILAN DATA PESANAN ---
$orders = [];
// Gunakan prepared statement untuk mengambil semua pesanan
$sql_orders = "SELECT o.id, u.username, o.order_date, o.total_amount, o.status, o.shipping_address
               FROM orders o
               LEFT JOIN users u ON o.user_id = u.id
               ORDER BY o.order_date DESC";

$stmt_orders = $conn->prepare($sql_orders);

if ($stmt_orders) {
    if ($stmt_orders->execute()) {
        $result_orders = $stmt_orders->get_result();
        while ($row = $result_orders->fetch_assoc()) {
            $orders[] = $row;
        }
        $result_orders->free(); // Bebaskan hasil query
    } else {
        $message = "Gagal mengambil data pesanan: " . $stmt_orders->error;
        $message_type = 'error';
    }
    $stmt_orders->close();
} else {
    $message = "Error menyiapkan query pesanan: " . $conn->error;
    $message_type = 'error';
}

// Menangani pesan dari redirect (setelah POST/GET action)
if (isset($_GET['message']) && isset($_GET['text'])) {
    $message = htmlspecialchars(urldecode($_GET['text']));
    $message_type = htmlspecialchars($_GET['message']);
}

?>

<?php include '../includes/admin_header.php'; // Sertakan header admin ?>

<main class="container admin-content-area">
  <h2>Kelola Pesanan</h2>

  <?php if (!empty($message)): ?>
  <div class="message-<?php echo $message_type; ?>">
    <?php echo $message; ?>
  </div>
  <?php endif; ?>

  <?php if (empty($orders)): ?>
  <p class="text-center">Belum ada pesanan yang masuk.</p>
  <?php else: ?>
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID Pesanan</th>
        <th>Pelanggan</th>
        <th>Tanggal Pesan</th>
        <th>Total</th>
        <th>Status</th>
        <th>Alamat Kirim</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($orders as $order): ?>
      <tr>
        <td><?php echo htmlspecialchars($order['id']); ?></td>
        <td><?php echo htmlspecialchars($order['username'] ? $order['username'] : 'Tamu'); ?></td>
        <td><?php echo htmlspecialchars(date('d M Y H:i', strtotime($order['order_date']))); ?></td>
        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
        <td>
          <form action="manage_orders.php" method="post" style="display: inline-flex; align-items: center; gap: 5px;">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['id']); ?>">
            <select name="new_status"
              class="status-dropdown <?php echo 'status-' . htmlspecialchars($order['status']); ?>">
              <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
              <option value="processing" <?php echo ($order['status'] == 'processing') ? 'selected' : ''; ?>>Processing
              </option>
              <option value="shipped" <?php echo ($order['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
              <option value="completed" <?php echo ($order['status'] == 'completed') ? 'selected' : ''; ?>>Completed
              </option>
              <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled
              </option>
            </select>
            <button type="submit" class="status-update-btn button" style="background-color: #007bff;">
              <i class="fas fa-sync-alt"></i> Update
            </button>
          </form>
        </td>
        <td><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></td>
        <td class="actions">
          <a href="view_order.php?id=<?php echo htmlspecialchars($order['id']); ?>" title="Lihat Detail Pesanan"><i
              class="fas fa-eye"></i> Detail</a>
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