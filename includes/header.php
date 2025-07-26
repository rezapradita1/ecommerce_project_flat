<?php
// FILE: includes/header.php
// Pastikan session_start() ada di BARIS PERTAMA setelah tag PHP pembuka
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sertakan file koneksi database.
// Asumsi db_connect.php berada di direktori yang sama dengan header.php (yaitu, 'includes/').
include 'db_connect.php';

// Hitung total item di keranjang.
// Asumsi $_SESSION['cart'] menyimpan array asosiatif [product_id => quantity].
$cart_item_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $cart_item_count += $quantity;
    }
}

// Ambil daftar kategori dari database untuk dropdown navigasi
$categories = [];
if (isset($conn)) { // Pastikan koneksi database ada
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
            // Log error, jangan tampilkan ke pengguna
            error_log("Error fetching categories in header: " . $stmt_categories->error);
        }
        $stmt_categories->close();
    } else {
        error_log("Error preparing categories query in header: " . $conn->error);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ReShop - That My Style</title>
  <!-- Link ke Font Awesome untuk ikon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Link ke Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Link ke stylesheet kustom Anda -->
  <link rel="stylesheet" href="css/style.css">

  <!-- CSS tambahan untuk fungsionalitas dropdown (jika tidak ada di style.css) -->
  <style>
  /* Pastikan elemen dropdown memiliki posisi relatif */
  .dropdown {
    position: relative;
    display: inline-block;
  }

  /* Sembunyikan konten dropdown secara default */
  .dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    /* Pastikan dropdown muncul di atas konten lain */
    border-radius: 5px;
    overflow: hidden;
    /* Untuk memastikan border-radius bekerja pada item */
    top: 100%;
    /* Posisikan tepat di bawah parent dropdown */
    left: 0;
    /* Sejajarkan ke kiri dengan parent */
  }

  /* Gaya untuk link di dalam dropdown content */
  .dropdown-content a {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    text-align: left;
  }

  /* Efek hover untuk item dropdown */
  .dropdown-content a:hover {
    background-color: #f1f1f1;
  }

  /* Tampilkan dropdown content saat parent .dropdown di-hover */
  .dropdown:hover .dropdown-content {
    display: block;
  }

  /* Style untuk ikon caret-down */
  .main-nav .dropdown>a .fa-caret-down {
    margin-left: 5px;
    transition: transform 0.3s ease;
  }

  /* Putar ikon caret-down saat dropdown di-hover */
  .main-nav .dropdown:hover>a .fa-caret-down {
    transform: rotate(180deg);
  }

  /* Pastikan link navigasi memiliki display yang sesuai untuk hover area */
  .main-nav ul li a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: #333;
    white-space: nowrap;
  }

  .main-nav ul li {
    list-style: none;
  }

  /* CSS untuk dropdown akun */
  .account-dropdown {
    position: relative;
    display: inline-block;
  }

  .account-dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    min-width: 160px;
    box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
    z-index: 1001;
    /* Z-index lebih tinggi dari dropdown kategori */
    border-radius: 5px;
    overflow: hidden;
    top: 100%;
    right: 0;
    /* Posisikan ke kanan untuk ikon akun */
    left: auto;
    /* Pastikan tidak ada konflik dengan left:0 */
  }

  .account-dropdown-content a {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
    text-align: left;
  }

  .account-dropdown-content a:hover {
    background-color: #f1f1f1;
  }

  .account-dropdown:hover .account-dropdown-content {
    display: block;
  }

  /* Gaya untuk ikon di header-icons */
  .header-icons .fa-user,
  .header-icons .fa-sign-in-alt {
    margin-right: 5px;
  }
  </style>
</head>

<body>

  <div class="top-bar">
    <div class="container">
      <div class="shipping-info">
        Free Shipping over $100
      </div>
      <div class="top-links">
        <span>USD <i class="fas fa-caret-down"></i></span>
        <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) : ?>
        <!-- Ini akan diganti oleh dropdown di header-icons -->
        <?php else : ?>
        <a href="login.php">Login</a>
        <a href="register.php">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <header class="main-header">
    <div class="container">
      <div class="logo">
        <a href="index.php">ReShop</a>
      </div>
      <nav class="main-nav">
        <ul>
          <li><a href="index.php">HOME</a></li>
          <li class="dropdown">
            <a href="#">CATEGORIES <i class="fas fa-caret-down"></i></a>
            <div class="dropdown-content">
              <?php if (!empty($categories)): ?>
              <?php foreach ($categories as $category): ?>
              <a href="products.php?category_id=<?php echo htmlspecialchars($category['id']); ?>">
                <?php echo htmlspecialchars($category['name']); ?>
              </a>
              <?php endforeach; ?>
              <?php else: ?>
              <a href="#">No Categories Available</a>
              <?php endif; ?>
            </div>
          </li>
          <li><a href="blog/index.php">BLOG</a></li>
          <li><a href="#">HOT OFFERS</a></li>
        </ul>
      </nav>
      <div class="header-icons">
        <a href="#"><i class="fas fa-search"></i></a>
        <a href="cart.php" class="cart-icon-container">
          <i class="fas fa-shopping-cart"></i>
          <span class="cart-count"><?= $cart_item_count ?></span>
        </a>
        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) : ?>
        <a href="admin/dashboard.php" class="admin-login-link" title="Admin Dashboard"><i
            class="fas fa-user-shield"></i> Admin</a>
        <?php elseif (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) : ?>
        <div class="account-dropdown">
          <a href="#" class="user-account-link" title="My Account">
            <i class="fas fa-user"></i> Account <i class="fas fa-caret-down"></i>
          </a>
          <div class="account-dropdown-content">
            <a href="my_account.php">Edit Profile</a>
            <a href="logout.php">Logout</a>
          </div>
        </div>
        <?php else : ?>
        <a href="login.php" class="user-login-link" title="Login / Register"><i class="fas fa-sign-in-alt"></i>
          Login</a>
        <?php endif; ?>
      </div>
    </div>
  </header>