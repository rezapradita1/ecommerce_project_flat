<?php
// FILE: product_detail.php

// Pastikan sesi dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sertakan koneksi database
include 'includes/db_connect.php';

// Inisialisasi variabel
$product = null;
$message = '';
$message_type = ''; // 'success' atau 'error'

// --- LOGIKA PENGAMBILAN DETAIL PRODUK ---
if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']); // Amankan ID produk

    // Gunakan prepared statement untuk mengambil detail produk
    $sql_product_detail = "SELECT p.*, c.name AS category_name
                           FROM products p
                           LEFT JOIN categories c ON p.category_id = c.id
                           WHERE p.id = ?";
    $stmt_product_detail = $conn->prepare($sql_product_detail);

    if ($stmt_product_detail) {
        $stmt_product_detail->bind_param("i", $product_id); // 'i' untuk integer
        if ($stmt_product_detail->execute()) {
            $result_product_detail = $stmt_product_detail->get_result();
            $product = $result_product_detail->fetch_assoc();
            $result_product_detail->free(); // Bebaskan hasil query
        } else {
            error_log("Error executing product detail query: " . $stmt_product_detail->error);
            $message = "Gagal mengambil detail produk.";
            $message_type = 'error';
        }
        $stmt_product_detail->close();

        if (!$product) {
            $message = "Produk tidak ditemukan.";
            $message_type = 'error';
        }
    } else {
        error_log("Error preparing product detail query: " . $conn->error);
        $message = "Error menyiapkan query produk.";
        $message_type = 'error';
    }
} else {
    $message = "ID Produk tidak ditemukan.";
    $message_type = 'error';
}

// --- LOGIKA TAMBAH KE KERANJANG ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    $added_product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    // Validasi dasar
    if ($added_product_id > 0 && $quantity > 0 && $product && $quantity <= $product['stock']) {
        // Inisialisasi keranjang jika belum ada
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Tambahkan atau update kuantitas produk di keranjang
        if (isset($_SESSION['cart'][$added_product_id])) {
            $_SESSION['cart'][$added_product_id] += $quantity;
        } else {
            $_SESSION['cart'][$added_product_id] = $quantity;
        }

        $message = "Produk berhasil ditambahkan ke keranjang!";
        $message_type = 'success';
        // Redirect untuk menghindari resubmission form (PRG pattern)
        header('Location: product_detail.php?id=' . htmlspecialchars($product_id) . '&message=' . $message_type . '&text=' . urlencode($message));
        exit();
    } else {
        $message = "Gagal menambahkan produk ke keranjang. Kuantitas atau ID tidak valid, atau stok tidak mencukupi.";
        $message_type = 'error';
    }
}

// Menangani pesan dari redirect (setelah POST action)
if (isset($_GET['message']) && isset($_GET['text'])) {
    $message = htmlspecialchars(urldecode($_GET['text']));
    $message_type = htmlspecialchars($_GET['message']);
}

// Sertakan header halaman pengguna (ini akan membuka tag <html>, <head>, <body>, dan <main>)
include 'includes/header.php';
?>

<main class="container">
  <?php if (!empty($message)): ?>
  <div class="message message-<?php echo $message_type; ?>">
    <?php echo $message; ?>
  </div>
  <?php endif; ?>

  <?php if ($product): ?>
  <div class="product-detail-container"
    style="display: flex; padding: 40px; max-width: 1000px; margin: 50px auto; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <div class="product-image" style="flex: 1; padding-right: 30px;">
      <!-- Pastikan path gambar benar relatif terhadap product_detail.php -->
      <img src="<?php echo htmlspecialchars($product['image_url']); ?>"
        alt="<?php echo htmlspecialchars($product['name']); ?>"
        style="max-width: 100%; height: auto; border-radius: 8px;">
    </div>
    <div class="product-info" style="flex: 2;">
      <h2 style="font-size: 2.5em; margin-top: 0; margin-bottom: 10px; color: #333;">
        <?php echo htmlspecialchars($product['name']); ?></h2>
      <p class="category" style="font-size: 1.1em; color: #777; margin-bottom: 15px;">Kategori:
        <?php echo htmlspecialchars($product['category_name'] ? $product['category_name'] : 'Tidak Berkategori'); ?></p>
      <p class="price" style="font-size: 2em; color: #007bff; font-weight: bold; margin-bottom: 20px;">
        $<?php echo number_format($product['price'], 2); ?></p>
      <p class="description" style="line-height: 1.8; color: #555; margin-bottom: 25px;">
        <?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
      <p>Stok Tersedia: <strong><?php echo htmlspecialchars($product['stock']); ?></strong></p>

      <?php if ($product['stock'] > 0): ?>
      <form action="product_detail.php?id=<?php echo htmlspecialchars($product['id']); ?>" method="post"
        class="add-to-cart-form" style="display: flex; align-items: center; gap: 15px;">
        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
        <input type="number" name="quantity" value="1" min="1" max="<?php echo htmlspecialchars($product['stock']); ?>"
          style="width: 70px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; text-align: center; font-size: 1.1em;">
        <button type="submit" name="add_to_cart" class="button" style="background-color: #28a745;">
          <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
        </button>
      </form>
      <?php else: ?>
      <p style="color: red; font-weight: bold;">Stok Habis</p>
      <?php endif; ?>
    </div>
  </div>
  <?php else: ?>
  <div class="container" style="text-align: center; padding: 50px;">
    <p><?php echo htmlspecialchars($message); ?></p>
    <a href="index.php"
      style="display: inline-block; margin-top: 20px; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">Kembali
      ke Beranda</a>
  </div>
  <?php endif; ?>
</main>

<?php
// Sertakan footer halaman pengguna (ini akan menutup tag <main>, <body>, dan <html>)
include 'includes/footer.php';

// Tutup koneksi database
if (isset($conn) && $conn) {
    $conn->close();
}
?>