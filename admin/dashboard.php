<?php
// FILE: admin/dashboard.php

// Pastikan sesi dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sertakan file autentikasi dan cek status login admin
// Ini akan mengarahkan ke login.php jika admin belum login
include '../includes/auth.php';
check_admin_login();

// Sertakan koneksi database
include '../includes/db_connect.php';

// Cek apakah ada aksi logout yang diminta melalui GET request
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    session_unset();     // Hapus semua variabel sesi
    session_destroy();   // Hancurkan sesi
    header("Location: login.php?message=logout_success"); // Redirect ke login dengan pesan sukses
    exit();
}

// --- Ambil Data untuk Statistik Dashboard ---

// Ambil total produk
$total_products = 0;
$stmt_products = $conn->prepare("SELECT COUNT(*) AS total FROM products");
if ($stmt_products && $stmt_products->execute()) {
    $result_products = $stmt_products->get_result();
    $data_products = $result_products->fetch_assoc();
    $total_products = $data_products['total'];
    $stmt_products->close();
} else {
    // Handle error jika query gagal
    error_log("Error fetching total products: " . $conn->error);
}

// Ambil total kategori (jika ada tabel categories)
$total_categories = 0;
$stmt_categories = $conn->prepare("SELECT COUNT(*) AS total FROM categories");
if ($stmt_categories && $stmt_categories->execute()) {
    $result_categories = $stmt_categories->get_result();
    $data_categories = $result_categories->fetch_assoc();
    $total_categories = $data_categories['total'];
    $stmt_categories->close();
} else {
    error_log("Error fetching total categories: " . $conn->error);
}

// Ambil total pesanan (contoh, asumsikan ada tabel orders)
$total_orders = 0;
$stmt_orders = $conn->prepare("SELECT COUNT(*) AS total FROM orders");
if ($stmt_orders && $stmt_orders->execute()) {
    $result_orders = $stmt_orders->get_result();
    $data_orders = $result_orders->fetch_assoc();
    $total_orders = $data_orders['total'];
    $stmt_orders->close();
} else {
    error_log("Error fetching total orders: " . $conn->error);
}

// Ambil total pengguna (contoh, asumsikan ada tabel users)
$total_users = 0;
$stmt_users = $conn->prepare("SELECT COUNT(*) AS total FROM users");
if ($stmt_users && $stmt_users->execute()) {
    $result_users = $stmt_users->get_result();
    $data_users = $result_users->fetch_assoc();
    $total_users = $data_users['total'];
    $stmt_users->close();
} else {
    error_log("Error fetching total users: " . $conn->error);
}


// Sertakan header admin (ini diharapkan menyediakan tag <html>, <head>, <body>, dan navigasi utama)
include '../includes/admin_header.php';
?>

<main class="admin-dashboard-content">
  <div class="container">
    <h2 class="welcome-message">Selamat Datang di Admin Dashboard,
      <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</h2>
    <p>Gunakan navigasi di atas atau link di bawah ini untuk mengelola situs e-commerce Anda.</p>

    <div class="dashboard-stats"
      style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
      <div class="stat-card"
        style="background-color: #e8f5e9; border-left: 5px solid #4caf50; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0; color: #4caf50; font-size: 1.5em;">Total Produk</h3>
        <p style="font-size: 2.2em; font-weight: bold; color: #333; margin-bottom: 0;"><?php echo $total_products; ?>
        </p>
      </div>
      <div class="stat-card"
        style="background-color: #e3f2fd; border-left: 5px solid #2196f3; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0; color: #2196f3; font-size: 1.5em;">Total Kategori</h3>
        <p style="font-size: 2.2em; font-weight: bold; color: #333; margin-bottom: 0;"><?php echo $total_categories; ?>
        </p>
      </div>
      <div class="stat-card"
        style="background-color: #fff3e0; border-left: 5px solid #ff9800; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0; color: #ff9800; font-size: 1.5em;">Total Pesanan</h3>
        <p style="font-size: 2.2em; font-weight: bold; color: #333; margin-bottom: 0;"><?php echo $total_orders; ?></p>
      </div>
      <div class="stat-card"
        style="background-color: #ffebee; border-left: 5px solid #f44336; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0; color: #f44336; font-size: 1.5em;">Total Pengguna</h3>
        <p style="font-size: 2.2em; font-weight: bold; color: #333; margin-bottom: 0;"><?php echo $total_users; ?></p>
      </div>
    </div>

    <div class="dashboard-links">
      <ul
        style="list-style: none; padding: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <li
          style="background-color: #e9e9e9; padding: 20px; border-radius: 8px; text-align: center; transition: background-color 0.3s ease;">
          <a href="manage_products.php"
            style="text-decoration: none; color: #007bff; font-weight: bold; font-size: 1.1em; display: block;">Kelola
            Produk</a>
        </li>
        <li
          style="background-color: #e9e9e9; padding: 20px; border-radius: 8px; text-align: center; transition: background-color 0.3s ease;">
          <a href="manage_categories.php"
            style="text-decoration: none; color: #007bff; font-weight: bold; font-size: 1.1em; display: block;">Kelola
            Kategori</a>
        </li>
        <li
          style="background-color: #e9e9e9; padding: 20px; border-radius: 8px; text-align: center; transition: background-color 0.3s ease;">
          <a href="manage_orders.php"
            style="text-decoration: none; color: #007bff; font-weight: bold; font-size: 1.1em; display: block;">Lihat
            Pesanan</a>
        </li>
        <li
          style="background-color: #e9e9e9; padding: 20px; border-radius: 8px; text-align: center; transition: background-color 0.3s ease;">
          <a href="manage_users.php"
            style="text-decoration: none; color: #007bff; font-weight: bold; font-size: 1.1em; display: block;">Kelola
            Pengguna</a>
        </li>
      </ul>
    </div>

    <a href="dashboard.php?action=logout" class="logout-btn"
      style="display: inline-block; background-color: #dc3545; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; margin-top: 30px; transition: background-color 0.3s ease;">Logout</a>
  </div>
</main>

<?php
// Tutup koneksi database
if (isset($conn) && $conn) {
    $conn->close(); // Menggunakan close() untuk konsistensi OO
}

// Sertakan footer admin (ini diharapkan menyediakan tag </body> dan </html>)
include '../includes/admin_footer.php';
?>