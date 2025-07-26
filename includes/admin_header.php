<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Anon E-commerce</title>
  <!-- Link ke stylesheet utama Anda (relatif dari folder admin/) -->
  <link rel="stylesheet" href="../css/style.css">
  <!-- Link ke Font Awesome untuk ikon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
  /* Admin specific styles */
  body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f0f2f5;
    /* Warna latar belakang body admin */
    color: #333;
  }

  .admin-header {
    background-color: #2c3e50;
    /* Warna latar belakang header */
    color: #ecf0f1;
    /* Warna teks header */
    padding: 15px 0;
    margin-bottom: 20px;
    border-bottom: 4px solid #3498db;
    /* Garis bawah header */
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    /* Bayangan header */
  }

  .admin-header .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 90%;
    /* Lebar konten header */
    margin: 0 auto;
    /* Pusatkan konten header */
  }

  .admin-header .logo a {
    font-size: 28px;
    font-weight: bold;
    color: #ecf0f1;
    text-decoration: none;
  }

  .admin-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    /* Gunakan flexbox untuk navigasi */
  }

  .admin-nav ul li {
    margin-left: 25px;
    /* Jarak antar item navigasi */
  }

  .admin-nav ul li a {
    color: #ecf0f1;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
  }

  .admin-nav ul li a:hover {
    color: #3498db;
    /* Warna hover untuk link navigasi */
  }

  /* Gaya untuk area konten utama admin */
  .admin-content-area {
    background-color: #ffffff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    margin-top: 20px;
    /* Jarak dari header */
  }

  /* Gaya umum untuk form */
  .form-group {
    margin-bottom: 20px;
  }

  .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #34495e;
  }

  .form-group input[type="text"],
  .form-group input[type="password"],
  .form-group input[type="number"],
  .form-group textarea,
  .form-group select,
  .form-group input[type="file"] {
    width: calc(100% - 22px);
    /* Sesuaikan lebar input */
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 1em;
  }

  .form-group textarea {
    resize: vertical;
    min-height: 100px;
  }

  .form-group button {
    background-color: #3498db;
    color: white;
    padding: 12px 25px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1.1em;
    transition: background-color 0.3s ease;
  }

  .form-group button:hover {
    background-color: #2980b9;
  }

  /* Gaya untuk pesan notifikasi */
  .message-success {
    color: #27ae60;
    background-color: #e6ffee;
    border: 1px solid #27ae60;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
    text-align: center;
  }

  .message-error {
    color: #c0392b;
    background-color: #ffe6e6;
    border: 1px solid #c0392b;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
    text-align: center;
  }

  /* Gaya untuk tabel admin */
  .admin-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
  }

  .admin-table th,
  .admin-table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
  }

  .admin-table th {
    background-color: #f2f2f2;
    font-weight: bold;
  }

  .admin-table td img {
    max-width: 80px;
    height: auto;
    border-radius: 4px;
  }

  .admin-table .actions a {
    margin-right: 10px;
    color: #3498db;
    text-decoration: none;
  }

  .admin-table .actions a:hover {
    text-decoration: underline;
  }
  </style>
</head>

<body>
  <header class="admin-header">
    <div class="container">
      <div class="logo">
        <!-- Link ke dashboard admin, relatif dari folder admin/ -->
        <a href="index.php">ADMIN PANEL</a>
      </div>
      <nav class="admin-nav">
        <ul>
          <!-- Navigasi admin, link relatif dari folder admin/ -->
          <li><a href="dashboard.php">Dashboard</a></li>
          <li><a href="manage_products.php">Produk</a></li>
          <li><a href="manage_categories.php">Kategori</a></li>
          <li><a href="manage_orders.php">Pesanan</a></li>
          <!-- Link logout, konsisten dengan dashboard.php -->
          <li><a href="dashboard.php?action=logout">Logout</a></li>
        </ul>
      </nav>
    </div>
  </header>
  <!-- Main content area starts here, will be closed in admin_footer.php -->
  <main class="container admin-content-area">