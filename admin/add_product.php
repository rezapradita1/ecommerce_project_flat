<?php
// FILE: admin/add_product.php

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

// Inisialisasi variabel untuk nilai form (untuk mempertahankan input setelah error)
$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? '';
$category_id = $_POST['category_id'] ?? '';
$description = $_POST['description'] ?? '';
$stock = $_POST['stock'] ?? '';

// --- LOGIKA PENGAMBILAN DATA KATEGORI ---
$categories = [];
// Menggunakan prepared statement untuk mengambil daftar kategori
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$stmt_categories = $conn->prepare($sql_categories);

if ($stmt_categories) {
    if ($stmt_categories->execute()) {
        $result_categories = $stmt_categories->get_result();
        while ($row = $result_categories->fetch_assoc()) {
            $categories[] = $row;
        }
        $result_categories->free(); // Bebaskan hasil query
    } else {
        $message = "Gagal mengambil kategori: " . $stmt_categories->error;
        $message_type = 'error';
    }
    $stmt_categories->close();
} else {
    $message = "Error menyiapkan query kategori: " . $conn->error;
    $message_type = 'error';
}

// --- LOGIKA PENANGANAN SUBMIT FORM (TAMBAH PRODUK) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan sanitasi input dari form
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $description = trim($_POST['description']);
    $stock = intval($_POST['stock']);
    $image_url = ''; // Default, akan diisi jika ada upload

    // Validasi input sederhana
    if (empty($name) || empty($price) || empty($stock)) {
        $message = "Nama, Harga, dan Stok tidak boleh kosong.";
        $message_type = 'error';
    } elseif ($price <= 0 || $stock < 0) {
        $message = "Harga harus lebih dari 0 dan Stok tidak boleh negatif.";
        $message_type = 'error';
    } else {
        // Logika Upload Gambar
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
            // Tentukan direktori target untuk upload gambar.
            // Asumsi folder 'uploads' berada di root proyek, sejajar dengan folder 'admin' dan 'includes'.
            $target_dir = "../uploads/";
            $file_extension = pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION);
            $new_file_name = uniqid('product_') . '.' . $file_extension;
            $target_file = $target_dir . $new_file_name;

            // Pastikan folder uploads ada dan writable
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true); // Buat folder jika belum ada
            }

            // Pindahkan file yang diunggah
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                // Simpan path relatif yang dapat diakses dari web (misal: http://yourdomain.com/uploads/...)
                $image_url = 'uploads/' . $new_file_name;
            } else {
                $message .= "Gagal mengunggah gambar.";
                $message_type = 'error';
            }
        }

        // Jika tidak ada error validasi atau upload gambar
        if (empty($message)) {
            // Masukkan data produk ke database menggunakan prepared statement
            $stmt_insert = $conn->prepare("INSERT INTO products (name, price, category_id, description, stock, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt_insert) {
                // 'sdisis' -> string, double, integer, string, integer, string
                $stmt_insert->bind_param("sdisis", $name, $price, $category_id, $description, $stock, $image_url);
                if ($stmt_insert->execute()) {
                    // Redirect ke manage_products.php dengan pesan sukses
                    header("Location: manage_products.php?message=add_success");
                    exit();
                } else {
                    $message = "Gagal menambahkan produk: " . $stmt_insert->error;
                    $message_type = 'error';
                }
                $stmt_insert->close();
            } else {
                $message = "Error menyiapkan query tambah produk: " . $conn->error;
                $message_type = 'error';
            }
        }
    }
}

// Menangani pesan dari redirect (jika ada)
if (isset($_GET['message']) && isset($_GET['text'])) {
    $message = htmlspecialchars(urldecode($_GET['text']));
    $message_type = htmlspecialchars($_GET['message']);
}

?>

<?php include '../includes/admin_header.php'; // Sertakan header admin ?>

<main class="container admin-content-area">
  <h2>Tambah Produk Baru</h2>

  <?php if (!empty($message)): ?>
  <div class="message-<?php echo $message_type; ?>">
    <?php echo $message; ?>
  </div>
  <?php endif; ?>

  <form action="add_product.php" method="post" enctype="multipart/form-data">
    <div class="form-group">
      <label for="name">Nama Produk:</label>
      <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
    </div>
    <div class="form-group">
      <label for="price">Harga:</label>
      <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($price); ?>" required>
    </div>
    <div class="form-group">
      <label for="category_id">Kategori:</label>
      <select id="category_id" name="category_id">
        <option value="">Pilih Kategori</option>
        <?php foreach ($categories as $category): ?>
        <option value="<?php echo htmlspecialchars($category['id']); ?>"
          <?php echo ($category_id == $category['id']) ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($category['name']); ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="description">Deskripsi:</label>
      <textarea id="description" name="description"><?php echo htmlspecialchars($description); ?></textarea>
    </div>
    <div class="form-group">
      <label for="stock">Stok:</label>
      <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($stock); ?>" required>
    </div>
    <div class="form-group">
      <label for="product_image">Gambar Produk:</label>
      <input type="file" id="product_image" name="product_image" accept="image/*">
    </div>
    <div class="form-group">
      <button type="submit" class="button" style="background-color: #28a745;">
        <i class="fas fa-plus"></i> Tambah Produk
      </button>
    </div>
  </form>
</main>

<?php
// Tutup koneksi database
if (isset($conn) && $conn) {
    $conn->close(); // Menggunakan close() untuk konsistensi OO
}

// Sertakan footer admin
include '../includes/admin_footer.php';
?>