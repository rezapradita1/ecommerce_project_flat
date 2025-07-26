<?php
// FILE: admin/manage_categories.php

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

// Inisialisasi variabel untuk form edit
$category_name_edit = '';
$category_id_edit = 0;

// --- LOGIKA PENANGANAN AKSI (TAMBAH/EDIT KATEGORI) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

    if (empty($name)) {
        $message = "Nama kategori tidak boleh kosong.";
        $message_type = 'error';
    } else {
        if ($category_id > 0) { // Mode Edit
            $sql_update = "UPDATE categories SET name = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            if ($stmt_update) {
                $stmt_update->bind_param("si", $name, $category_id);
                if ($stmt_update->execute()) {
                    $message = "Kategori berhasil diperbarui.";
                    $message_type = 'success';
                } else {
                    $message = "Gagal memperbarui kategori: " . $stmt_update->error;
                    $message_type = 'error';
                }
                $stmt_update->close();
            } else {
                $message = "Error menyiapkan query update: " . $conn->error;
                $message_type = 'error';
            }
        } else { // Mode Tambah
            $sql_insert = "INSERT INTO categories (name) VALUES (?)";
            $stmt_insert = $conn->prepare($sql_insert);
            if ($stmt_insert) {
                $stmt_insert->bind_param("s", $name);
                if ($stmt_insert->execute()) {
                    $message = "Kategori '{$name}' berhasil ditambahkan.";
                    $message_type = 'success';
                } else {
                    $message = "Gagal menambahkan kategori: " . $stmt_insert->error;
                    $message_type = 'error';
                }
                $stmt_insert->close();
            } else {
                $message = "Error menyiapkan query tambah: " . $conn->error;
                $message_type = 'error';
            }
        }
    }
    // Redirect setelah POST untuk mencegah pengiriman ulang form (PRG pattern)
    header("Location: manage_categories.php?message=" . $message_type . "&text=" . urlencode($message));
    exit();
}

// --- LOGIKA PENANGANAN AKSI (HAPUS KATEGORI) ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $category_id_to_delete = intval($_GET['id']);

    // Pertimbangkan apa yang terjadi pada produk jika kategori dihapus.
    // Asumsi: Database memiliki ON DELETE SET NULL atau ON DELETE CASCADE
    // Jika tidak, Anda perlu menangani produk terkait secara manual di sini.
    $sql_delete = "DELETE FROM categories WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $category_id_to_delete);
        if ($stmt_delete->execute()) {
            $message = "Kategori berhasil dihapus.";
            $message_type = 'success';
        } else {
            $message = "Gagal menghapus kategori: " . $stmt_delete->error;
            $message_type = 'error';
        }
        $stmt_delete->close();
    } else {
        $message = "Error menyiapkan query hapus: " . $conn->error;
        $message_type = 'error';
    }
    // Redirect setelah DELETE
    header("Location: manage_categories.php?message=" . $message_type . "&text=" . urlencode($message));
    exit();
}

// --- LOGIKA UNTUK MENGISI FORM SAAT MODE EDIT ---
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $category_id_edit = intval($_GET['id']);
    $sql_select_edit = "SELECT name FROM categories WHERE id = ?";
    $stmt_select_edit = $conn->prepare($sql_select_edit);
    if ($stmt_select_edit) {
        $stmt_select_edit->bind_param("i", $category_id_edit);
        $stmt_select_edit->execute();
        $result_select_edit = $stmt_select_edit->get_result();
        if ($row = $result_select_edit->fetch_assoc()) {
            $category_name_edit = $row['name'];
        } else {
            // Kategori tidak ditemukan, reset ke mode tambah
            $category_id_edit = 0;
            $category_name_edit = '';
            $message = "Kategori tidak ditemukan.";
            $message_type = 'error';
        }
        $stmt_select_edit->close();
    } else {
        $message = "Error menyiapkan query edit: " . $conn->error;
        $message_type = 'error';
    }
}

// --- LOGIKA PENGAMBILAN SEMUA KATEGORI UNTUK TABEL ---
$categories_list = []; // Menggunakan nama variabel berbeda agar tidak bentrok
$sql_all_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$stmt_all_categories = $conn->prepare($sql_all_categories);

if ($stmt_all_categories) {
    if ($stmt_all_categories->execute()) {
        $result_all_categories = $stmt_all_categories->get_result();
        while ($row = $result_all_categories->fetch_assoc()) {
            $categories_list[] = $row;
        }
        $result_all_categories->free(); // Bebaskan hasil query
    } else {
        // Ini adalah error saat mengambil daftar kategori untuk ditampilkan
        // Tidak perlu menghentikan eksekusi, cukup tampilkan pesan error
        $message_display_error = "Gagal mengambil data kategori: " . $stmt_all_categories->error;
        $message_type_display_error = 'error';
    }
    $stmt_all_categories->close();
} else {
    $message_display_error = "Error menyiapkan query daftar kategori: " . $conn->error;
    $message_type_display_error = 'error';
}

// Menangani pesan dari redirect (setelah POST/GET action)
if (isset($_GET['message']) && isset($_GET['text'])) {
    $message = htmlspecialchars(urldecode($_GET['text']));
    $message_type = htmlspecialchars($_GET['message']);
}

?>

<?php include '../includes/admin_header.php'; // Sertakan header admin ?>

<main class="container admin-content-area">
  <h2>Kelola Kategori</h2>

  <?php if (!empty($message)): ?>
  <div class="message-<?php echo $message_type; ?>">
    <?php echo $message; ?>
  </div>
  <?php endif; ?>

  <?php if (isset($message_display_error) && !empty($message_display_error)): ?>
  <div class="message-<?php echo $message_type_display_error; ?>">
    <?php echo $message_display_error; ?>
  </div>
  <?php endif; ?>

  <div class="form-category"
    style="margin-bottom: 30px; padding: 20px; border: 1px solid #eee; border-radius: 8px; background-color: #f9f9f9;">
    <h3><?php echo ($category_id_edit > 0) ? 'Edit Kategori' : 'Tambah Kategori Baru'; ?></h3>
    <form action="manage_categories.php" method="post">
      <div class="form-group">
        <label for="name">Nama Kategori:</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($category_name_edit); ?>" required>
        <input type="hidden" name="category_id" value="<?php echo htmlspecialchars($category_id_edit); ?>">
      </div>
      <div class="form-group">
        <button type="submit" class="button" style="background-color: #007bff;">
          <?php if ($category_id_edit > 0): ?>
          <i class="fas fa-save"></i> Update Kategori
          <?php else: ?>
          <i class="fas fa-plus"></i> Tambah Kategori
          <?php endif; ?>
        </button>
        <?php if ($category_id_edit > 0): ?>
        <a href="manage_categories.php" class="button" style="background-color: #6c757d; margin-left: 10px;">
          <i class="fas fa-times"></i> Batal Edit
        </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <h2>Daftar Kategori</h2>
  <?php if (empty($categories_list)): ?>
  <p class="text-center">Belum ada kategori. Silakan tambahkan kategori baru.</p>
  <?php else: ?>
  <table class="admin-table">
    <thead>
      <tr>
        <th>ID</th>
        <th>Nama Kategori</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($categories_list as $category): ?>
      <tr>
        <td><?php echo htmlspecialchars($category['id']); ?></td>
        <td><?php echo htmlspecialchars($category['name']); ?></td>
        <td class="actions">
          <a href="manage_categories.php?action=edit&id=<?php echo htmlspecialchars($category['id']); ?>"
            title="Edit Kategori"><i class="fas fa-edit"></i> Edit</a> |
          <a href="manage_categories.php?action=delete&id=<?php echo htmlspecialchars($category['id']); ?>"
            onclick="return confirm('Anda yakin ingin menghapus kategori ini? (Produk yang terkait mungkin akan kehilangan kategorinya)');"
            class="delete" title="Hapus Kategori" style="color: #dc3545;">
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