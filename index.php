<?php
// FILE: index.php

// Sertakan koneksi database
include 'includes/db_connect.php';

// Sertakan header halaman pengguna (ini akan membuka tag <html>, <head>, <body>, dan <main>)
include 'includes/header.php';

// --- Ambil beberapa produk terbaru/unggulan untuk ditampilkan ---
// Menggunakan prepared statement untuk keamanan dan konsistensi
$sql_products = "SELECT p.id, p.name, p.price, p.image_url, c.name AS category_name
                 FROM products p
                 JOIN categories c ON p.category_id = c.id
                 ORDER BY p.created_at DESC LIMIT 8";
$stmt_products = $conn->prepare($sql_products);

$result_products = false; // Inisialisasi
if ($stmt_products) {
    if ($stmt_products->execute()) {
        $result_products = $stmt_products->get_result();
    } else {
        // Log error, jangan tampilkan ke pengguna di produksi
        error_log("Error executing product query: " . $stmt_products->error);
    }
    $stmt_products->close();
} else {
    error_log("Error preparing product query: " . $conn->error);
}
?>

<section class="hero-section">
  <div class="hero-content">
    <h2>Trending Accessories</h2>
    <h3>MODERN SUNGLASSES</h3>
    <p>starting at $15.00</p>
    <!-- Link ke halaman produk dengan kategori ID 1 (Sunglasses) -->
    <a href="products.php?category_id=1" class="button">SHOP NOW</a>
  </div>
  <div class="hero-images">
    <!-- Pastikan path gambar benar relatif terhadap index.php -->
    <img src="images/products/sunglasses1.jpg" alt="Sunglasses 1"
      style="width: 200px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
    <img src="images/products/watch1.jpg" alt="Watch 1"
      style="width: 150px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
  </div>
</section>

<section class="container my-30">
  <h2 class="text-center">New Arrivals</h2>
  <div class="product-grid">
    <?php
            if ($result_products && $result_products->num_rows > 0) {
                while($row = $result_products->fetch_assoc()) {
                    echo "<div class='product-card'>";
                    echo "  <div class='product-card-image'>";
                    echo "      <a href='product_detail.php?id=" . htmlspecialchars($row["id"]) . "'>";
                    echo "          <img src='" . htmlspecialchars($row["image_url"]) . "' alt='" . htmlspecialchars($row["name"]) . "'>";
                    echo "      </a>";
                    echo "  </div>";
                    echo "  <div class='product-card-content'>";
                    echo "      <p class='category'>" . htmlspecialchars($row["category_name"]) . "</p>";
                    echo "      <h3><a href='product_detail.php?id=" . htmlspecialchars($row["id"]) . "'>" . htmlspecialchars($row["name"]) . "</a></h3>";
                    echo "      <p class='price'>$" . number_format($row["price"], 2) . "</p>";
                    echo "      <form action='cart.php' method='post'>";
                    echo "          <input type='hidden' name='action' value='add'>";
                    echo "          <input type='hidden' name='product_id' value='" . htmlspecialchars($row["id"]) . "'>";
                    echo "          <button type='submit' class='add-to-cart-btn'>Add to Cart</button>";
                    echo "      </form>";
                    echo "  </div>";
                    echo "</div>";
                }
                $result_products->free(); // Bebaskan hasil query
            } else {
                echo "<p class='text-center'>No products available.</p>";
            }
            ?>
  </div>
</section>

<section class="container my-30">
  <div style="display: flex; gap: 30px; flex-wrap: wrap; justify-content: center;">
    <div class="deal-of-day-card"
      style="flex: 1; min-width: 300px; max-width: 450px; background-color: #f0f8ff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); text-align: center;">
      <h3>Deals of the Day</h3>
      <p style="font-size: 1.1em; margin-bottom: 10px;">Shampoo & Conditioner 429ml</p>
      <p style="font-size: 1.8em; font-weight: bold; color: #dc3545; margin-bottom: 15px;">
        $25.00 <s style="font-size: 0.7em; color: #888;">$30.00</s>
      </p>
      <!-- Pastikan path gambar benar relatif terhadap index.php -->
      <img src="images/products/sunglasses1.jpg" alt="Shampoo"
        style="max-width: 120px; height: auto; display: block; margin: 15px auto; border-radius: 5px;">
      <p style="text-align: center; font-size: 0.9em; color: #555;">HURRY UP! OFFER ENDS IN:</p>
      <div
        style="display: flex; justify-content: center; gap: 15px; font-weight: bold; font-size: 1.3em; margin-top: 10px;">
        <span
          style="background-color: #fff; padding: 8px 12px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">360D</span>
        <span
          style="background-color: #fff; padding: 8px 12px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">24H</span>
        <span
          style="background-color: #fff; padding: 8px 12px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">59M</span>
        <span
          style="background-color: #fff; padding: 8px 12px; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">00S</span>
      </div>
    </div>
    <div class="our-services-card"
      style="flex: 2; min-width: 400px; max-width: 600px; background-color: #fcfcfc; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.05);">
      <h3>Our Services</h3>
      <ul style="list-style: none; padding: 0; margin-top: 20px;">
        <li style="margin-bottom: 10px; font-size: 1.1em;"><i class="fas fa-truck"
            style="color: #3498db; margin-right: 10px;"></i> Worldwide Delivery</li>
        <li style="margin-bottom: 10px; font-size: 1.1em;"><i class="fas fa-shipping-fast"
            style="color: #27ae60; margin-right: 10px;"></i> Next Day Delivery</li>
        <li style="margin-bottom: 10px; font-size: 1.1em;"><i class="fas fa-phone-alt"
            style="color: #e67e22; margin-right: 10px;"></i> Best Online Support</li>
        <li style="margin-bottom: 10px; font-size: 1.1em;"><i class="fas fa-undo"
            style="color: #9b59b6; margin-right: 10px;"></i> Return Policy</li>
        <li style="margin-bottom: 10px; font-size: 1.1em;"><i class="fas fa-money-bill-alt"
            style="color: #f1c40f; margin-right: 10px;"></i> Money Back Guarantee</li>
      </ul>
    </div>
  </div>
</section>

<?php
// Sertakan footer halaman pengguna (ini akan menutup tag <main>, <body>, dan <html>)
include 'includes/footer.php';
$conn->close(); // Tutup koneksi database
?>