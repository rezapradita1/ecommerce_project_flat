<?php
// FILE: admin/manage_products.php

// Pastikan sesi dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sertakan file autentikasi dan cek status login admin
include '../includes/auth.php';
check_admin_login();

// Sertakan koneksi database
include '../includes/db_connect.php';

// --- LOGIKA PENANGANAN AKSI (HAPUS PRODUK) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']); // Amankan ID produk

    // Query untuk menghapus produk
    $sql_delete = "DELETE FROM products WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);

    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $product_id); // 'i' untuk integer
        if ($stmt_delete->execute()) {
            // Redirect kembali ke halaman manage_products.php dengan pesan sukses
            header("Location: manage_products.php?message=delete_success");
            exit();
        } else {
            // Tangani kesalahan eksekusi
            $error_message = "Error deleting product: " . $stmt_delete->error;
        }
        $stmt_delete->close();
    } else {
        // Tangani kesalahan persiapan statement
        $error_message = "Error preparing delete statement: " . $conn->error;
    }
}

// --- LOGIKA PENGAMBILAN DATA PRODUK ---
// Ambil semua produk dari database, gabungkan dengan tabel kategori untuk nama kategori
$sql_products = "SELECT p.id, p.name, c.name AS category_name, p.price, p.description, p.image_url, p.stock, p.created_at, p.updated_at
                 FROM products p
                 JOIN categories c ON p.category_id = c.id
                 ORDER BY p.created_at DESC"; // Urutkan berdasarkan tanggal pembuatan terbaru
$result_products = $conn->query($sql_products);

// Inisialisasi variabel untuk pesan notifikasi
$message = '';
$message_type = ''; // 'success' atau 'error'

if (isset($_GET['message'])) {
    if ($_GET['message'] == 'delete_success') {
        $message = "Produk berhasil dihapus!";
        $message_type = 'success';
    } elseif ($_GET['message'] == 'add_success') {
        $message = "Produk baru berhasil ditambahkan!";
        $message_type = 'success';
    } elseif ($_GET['message'] == 'edit_success') {
        $message = "Produk berhasil diperbarui!";
        $message_type = 'success';
    } elseif (isset($error_message)) {
        $message = $error_message;
        $message_type = 'error';
    }
}
?>

<?php include '../includes/admin_header.php'; // Sertakan header admin ?>

<main class="container admin-content-area">
  <h2>Kelola Produk</h2>

  <?php if (!empty($message)): ?>
  <div class="message-<?php echo $message_type; ?>">
    <?php echo htmlspecialchars($message); ?>
  </div>
  <?php endif; ?>

  <div style="text-align: right; margin-bottom: 20px;">
    <a href="add_product.php" class="button"
      style="background-color: #28a745; padding: 10px 20px; font-size: 1em; text-decoration: none; color: white; border-radius: 4px; display: inline-block;">
      <i class="fas fa-plus"></i> Tambah Produk Baru
    </a>
  </div>

  <?php if ($result_products->num_rows > 0): ?>
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Gambar</th>
        <th>Nama Produk</th>
        <th>Kategori</th>
        <th>Harga</th>
        <th>Stok</th>
        <th>Deskripsi</th>
        <th>Dibuat Pada</th>
        <th>Diperbarui Pada</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $result_products->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['id']); ?></td>
        <td>
          <?php if (!empty($row['image_url'])): ?>
          <img src="<?php echo htmlspecialchars($row['image_url']); ?>"
            alt="<?php echo htmlspecialchars($row['name']); ?>"
            style="width: 70px; height: 70px; object-fit: cover; border-radius: 5px;">
          <?php else: ?>
          Tidak Ada Gambar
          <?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($row['name']); ?></td>
        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
        <td>$<?php echo number_format($row['price'], 2); ?></td>
        <td><?php echo htmlspecialchars($row['stock']); ?></td>
        <td>
          <?php echo htmlspecialchars(substr($row['description'], 0, 100)) . (strlen($row['description']) > 100 ? '...' : ''); ?>
        </td>
        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
        <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
        <td class="actions">
          <a href="edit_product.php?id=<?php echo $row['id']; ?>" title="Edit Produk"><i class="fas fa-edit"></i>
            Edit</a> |
          <a href="manage_products.php?action=delete&id=<?php echo $row['id']; ?>"
            onclick="return confirm('Yakin ingin menghapus produk ini?');" title="Hapus Produk" style="color: #dc3545;">
            <i class="fas fa-trash-alt"></i> Hapus
          </a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <?php else: ?>
  <p class="text-center">Belum ada produk yang ditambahkan.</p>
  <?php endif; ?>

</main>

<?php
$result_products->close(); // Tutup hasil query
$conn->close(); // Tutup koneksi database
include '../includes/admin_footer.php'; // Sertakan footer admin
?>