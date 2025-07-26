<?php
// FILE: products.php

// Pastikan sesi dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sertakan koneksi database
include 'includes/db_connect.php';

// Sertakan header halaman pengguna (ini akan membuka tag <html>, <head>, <body>, dan <main>)
include 'includes/header.php';

$page_title = "All Products"; // Default title
$sql_where = []; // Array untuk menyimpan klausa WHERE
$params = [];    // Array untuk menyimpan parameter prepared statement
$param_types = ''; // String untuk tipe parameter prepared statement

// --- Logika Filter Kategori ---
if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
    $category_id = (int)$_GET['category_id'];
    $sql_where[] = "p.category_id = ?";
    $params[] = $category_id;
    $param_types .= 'i'; // 'i' for integer

    // Ambil nama kategori untuk judul halaman
    $sql_cat_name = "SELECT name FROM categories WHERE id = ?";
    $stmt_cat_name = $conn->prepare($sql_cat_name);
    if ($stmt_cat_name) {
        $stmt_cat_name->bind_param("i", $category_id);
        if ($stmt_cat_name->execute()) {
            $result_cat_name = $stmt_cat_name->get_result();
            if ($row_cat_name = $result_cat_name->fetch_assoc()) {
                $page_title = htmlspecialchars($row_cat_name['name']) . " Products";
            }
            $result_cat_name->free();
        } else {
            error_log("Error executing category name query: " . $stmt_cat_name->error);
        }
        $stmt_cat_name->close();
    } else {
        error_log("Error preparing category name query: " . $conn->error);
    }
}

// --- Logika Filter Gender ---
// Ini mengasumsikan Anda memiliki kategori spesifik untuk "Pria" dan "Wanita"
// atau kolom 'gender' di tabel 'products'.
// Untuk saat ini, kita akan mencoba mencocokkan nama kategori.
// IDEALNYA: Tambah kolom 'gender' (ENUM('men', 'women', 'unisex')) ke tabel 'products'
// atau memiliki ID kategori spesifik untuk gender.
if (isset($_GET['gender'])) {
    $gender = strtolower(trim($_GET['gender']));
    $gender_clause = [];
    $gender_params = [];
    $gender_param_types = '';

    if ($gender === 'men') {
        $gender_clause[] = "c.name LIKE ?";
        $gender_params[] = '%Men%';
        $gender_param_types .= 's';
        $gender_clause[] = "c.name LIKE ?";
        $gender_params[] = '%Pria%';
        $gender_param_types .= 's';
        $page_title = "Men's Products";
    } elseif ($gender === 'women') {
        $gender_clause[] = "c.name LIKE ?";
        $gender_params[] = '%Women%';
        $gender_param_types .= 's';
        $gender_clause[] = "c.name LIKE ?";
        $gender_params[] = '%Wanita%';
        $gender_param_types .= 's';
        $page_title = "Women's Products";
    }

    if (!empty($gender_clause)) {
        $sql_where[] = "(" . implode(' OR ', $gender_clause) . ")";
        $params = array_merge($params, $gender_params);
        $param_types .= $gender_param_types;
    }
}

// --- Query utama untuk mendapatkan produk ---
$sql = "SELECT p.id, p.name, p.price, p.image_url, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id";

if (!empty($sql_where)) {
    $sql .= " WHERE " . implode(' AND ', $sql_where);
}

$sql .= " ORDER BY p.created_at DESC";

// Menggunakan Prepared Statement untuk keamanan
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo "<div class='container mt-5 alert alert-danger text-center'>Error preparing query: " . htmlspecialchars($conn->error) . "</div>";
    $result_products = false; // Pastikan result_products tidak terdefinisi jika ada error
} else {
    // Jika ada parameter untuk prepared statement (untuk category_id atau gender)
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    if ($stmt->execute()) {
        $result_products = $stmt->get_result();
    } else {
        echo "<div class='container mt-5 alert alert-danger text-center'>Error executing query: " . htmlspecialchars($stmt->error) . "</div>";
        $result_products = false;
    }
    $stmt->close();
}
?>

<main class="container my-5">
  <h2 class="text-center mb-4"><?= $page_title ?></h2>

  <div class="product-grid">
    <?php
        // Pastikan $result_products sudah didefinisikan dan ada baris data
        if ($result_products && $result_products->num_rows > 0) {
            while($row = $result_products->fetch_assoc()) {
        ?>
    <div class="product-card">
      <a href="product_detail.php?id=<?= htmlspecialchars($row['id']) ?>">
        <div class="product-card-image">
          <!-- Pastikan path gambar benar relatif terhadap products.php -->
          <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="<?= htmlspecialchars($row['name']) ?>">
        </div>
        <div class="product-card-content">
          <p class="category"><?= htmlspecialchars($row['category_name']) ?></p>
          <h3><?= htmlspecialchars($row['name']) ?></h3>
          <p class="price">$<?= number_format($row['price'], 2) ?></p>
        </div>
      </a>
      <form action="cart.php" method="post">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= htmlspecialchars($row['id']) ?>">
        <button type="submit" class="add-to-cart-btn">Add to Cart</button>
      </form>
    </div>
    <?php
            }
            $result_products->free(); // Bebaskan hasil query
        } else {
            // Pesan jika tidak ada produk
            echo "<div class='col-12'><p class='alert alert-info text-center'>No products available with this filter.</p></div>";
        }
        ?>
  </div>
</main>

<?php
// Sertakan footer halaman pengguna (ini akan menutup tag <main>, <body>, dan <html>)
include 'includes/footer.php';

// Tutup koneksi database
if (isset($conn) && $conn) {
    $conn->close();
}
?>