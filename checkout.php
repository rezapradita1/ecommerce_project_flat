<?php
// FILE: checkout.php

// Pastikan sesi dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sertakan koneksi database (diperlukan untuk semua logika yang berinteraksi dengan DB)
include 'includes/db_connect.php';

// Inisialisasi variabel untuk pesan notifikasi
$message = '';
$message_type = ''; // 'success' or 'error'

// --- LOGIKA AWAL: REDIRECT JIKA KERANJANG KOSONG (HARUS SEBELUM OUTPUT HTML) ---
if (empty($_SESSION['cart'])) {
    header('Location: cart.php?message=error&text=' . urlencode('Keranjang belanja Anda kosong. Silakan tambahkan produk terlebih dahulu.'));
    exit();
}

// --- LOGIKA PENGAMBILAN DETAIL ITEM KERANJANG DARI DATABASE (DI ATAS HEADER) ---
// Ini perlu dilakukan sebelum header.php jika ada validasi stok yang bisa menyebabkan redirect
$cart_items = [];
$total_belanja = 0;

if (!empty($_SESSION['cart'])) {
    $product_ids_in_cart = array_keys($_SESSION['cart']);

    if (!empty($product_ids_in_cart)) {
        $placeholders = implode(',', array_fill(0, count($product_ids_in_cart), '?'));
        $sql_cart_details = "SELECT id, name, price, image_url, stock FROM products WHERE id IN ($placeholders)";

        $stmt_cart_details = $conn->prepare($sql_cart_details);
        if ($stmt_cart_details === false) {
            $message = "Error preparing statement for cart details: " . $conn->error;
            $message_type = 'error';
        } else {
            $types = str_repeat('i', count($product_ids_in_cart));
            $stmt_cart_details->bind_param($types, ...$product_ids_in_cart);

            if ($stmt_cart_details->execute()) {
                $result_cart_details = $stmt_cart_details->get_result();

                while ($product = $result_cart_details->fetch_assoc()) {
                    $product_id = $product['id'];
                    $quantity = $_SESSION['cart'][$product_id];

                    // Validasi stok saat checkout
                    if ($quantity > $product['stock']) {
                        $message = "Stok untuk '" . htmlspecialchars($product['name']) . "' tidak mencukupi. Hanya " . htmlspecialchars($product['stock']) . " tersedia. Kuantitas di keranjang disesuaikan.";
                        $message_type = 'error';
                        $_SESSION['cart'][$product_id] = $product['stock']; // Sesuaikan kuantitas di keranjang
                        $quantity = $product['stock']; // Gunakan kuantitas yang disesuaikan
                        if ($quantity == 0) { // Jika stok 0, hapus dari keranjang
                            unset($_SESSION['cart'][$product_id]);
                            continue; // Lewati produk ini
                        }
                    }

                    $subtotal = $product['price'] * $quantity;

                    $cart_items[] = [
                        'id' => $product_id,
                        'nama' => $product['name'],
                        'gambar' => $product['image_url'],
                        'harga' => $product['price'],
                        'kuantitas' => $quantity,
                        'subtotal' => $subtotal
                    ];
                    $total_belanja += $subtotal;
                }
                $result_cart_details->free();
            } else {
                $message = "Error fetching product details for cart: " . $stmt_cart_details->error;
                $message_type = 'error';
            }
            $stmt_cart_details->close();
        }
    }
}

// Jika setelah validasi stok keranjang menjadi kosong, redirect lagi (HARUS SEBELUM OUTPUT HTML)
if (empty($cart_items)) {
    header('Location: cart.php?message=error&text=' . urlencode('Keranjang belanja Anda kosong setelah penyesuaian stok.'));
    exit();
}

// --- LOGIKA PENGAMBILAN DATA PENGGUNA UNTUK PRE-FILL FORM (DI ATAS HEADER) ---
$user_id = $_SESSION['user_id'] ?? null; // Asumsi 'user_id' disimpan di sesi saat login
$shipping_address = '';
$full_name = '';
$email = '';
$phone_number = '';

if ($user_id) {
    $sql_user_data = "SELECT full_name, email, phone_number, address FROM users WHERE id = ?";
    $stmt_user_data = $conn->prepare($sql_user_data);
    if ($stmt_user_data) {
        $stmt_user_data->bind_param("i", $user_id);
        if ($stmt_user_data->execute()) {
            $result_user_data = $stmt_user_data->get_result();
            if ($user_data = $result_user_data->fetch_assoc()) {
                $full_name = $user_data['full_name'];
                $email = $user_data['email'];
                $phone_number = $user_data['phone_number'];
                $shipping_address = $user_data['address'];
            }
            $result_user_data->free();
        } else {
            error_log("Error fetching user data for checkout: " . $stmt_user_data->error);
        }
        $stmt_user_data->close();
    } else {
        error_log("Error preparing user data query: " . $conn->error);
    }
}

// --- LOGIKA PROSES ORDER (SAAT FORM DI-SUBMIT) - HARUS SEBELUM OUTPUT HTML ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    // Ambil data dari form
    $input_full_name = trim($_POST['full_name']);
    $input_email = trim($_POST['email']);
    $input_phone_number = trim($_POST['phone_number']);
    $input_shipping_address = trim($_POST['shipping_address']);
    $payment_method = trim($_POST['payment_method']); // 'bank_transfer', 'credit_card', etc.

    // Validasi input
    if (empty($input_full_name) || empty($input_email) || empty($input_shipping_address) || empty($payment_method)) {
        $message = "Harap lengkapi semua bidang yang wajib diisi.";
        $message_type = 'error';
    } elseif (!filter_var($input_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Format email tidak valid.";
        $message_type = 'error';
    } else {
        // Mulai transaksi database
        $conn->begin_transaction();
        try {
            // 1. Masukkan data pesanan ke tabel 'orders'
            $order_status = 'pending'; // Status awal pesanan
            $current_date = date('Y-m-d H:i:s');

            $sql_insert_order = "INSERT INTO orders (user_id, order_date, total_amount, status, shipping_address, payment_method, customer_name, customer_email, customer_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert_order = $conn->prepare($sql_insert_order);
            if ($stmt_insert_order === false) {
                throw new Exception("Error preparing order insert statement: " . $conn->error);
            }
            // user_id bisa NULL jika pengguna tidak login
            $user_id_for_db = $user_id; // Gunakan ID pengguna jika login, atau NULL
            $stmt_insert_order->bind_param("isdssssss", $user_id_for_db, $current_date, $total_belanja, $order_status, $input_shipping_address, $payment_method, $input_full_name, $input_email, $input_phone_number);

            if (!$stmt_insert_order->execute()) {
                throw new Exception("Error executing order insert: " . $stmt_insert_order->error);
            }
            $order_id = $conn->insert_id; // Dapatkan ID pesanan yang baru saja dimasukkan
            $stmt_insert_order->close();

            // 2. Masukkan detail item pesanan ke tabel 'order_items'
            $sql_insert_order_item = "INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)";
            $stmt_insert_order_item = $conn->prepare($sql_insert_order_item);
            if ($stmt_insert_order_item === false) {
                throw new Exception("Error preparing order item insert statement: " . $conn->error);
            }

            foreach ($cart_items as $item) {
                $product_id = $item['id'];
                $quantity = $item['kuantitas'];
                $price_at_purchase = $item['harga']; // Simpan harga saat pembelian

                $stmt_insert_order_item->bind_param("iiid", $order_id, $product_id, $quantity, $price_at_purchase);
                if (!$stmt_insert_order_item->execute()) {
                    throw new Exception("Error inserting order item for product ID " . $product_id . ": " . $stmt_insert_order_item->error);
                }

                // 3. Kurangi stok produk
                $sql_update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $stmt_update_stock = $conn->prepare($sql_update_stock);
                if ($stmt_update_stock === false) {
                    throw new Exception("Error preparing stock update statement: " . $conn->error);
                }
                $stmt_update_stock->bind_param("ii", $quantity, $product_id);
                if (!$stmt_update_stock->execute()) {
                    throw new Exception("Error updating stock for product ID " . $product_id . ": " . $stmt_update_stock->error);
                }
                $stmt_update_stock->close();
            }
            $stmt_insert_order_item->close();

            // Commit transaksi jika semua berhasil
            $conn->commit();

            // Kosongkan keranjang setelah pesanan berhasil
            unset($_SESSION['cart']);

            $message = "Pesanan Anda berhasil ditempatkan! Nomor Pesanan: #" . $order_id;
            $message_type = 'success';
            // Redirect ke halaman konfirmasi pesanan
            header('Location: order_confirmation.php?order_id=' . $order_id);
            exit();

        } catch (Exception $e) {
            // Rollback transaksi jika terjadi kesalahan
            $conn->rollback();
            $message = "Terjadi kesalahan saat memproses pesanan Anda: " . $e->getMessage();
            $message_type = 'error';
            error_log("Checkout error: " . $e->getMessage()); // Log kesalahan untuk debugging
        }
    }
    // Setelah memproses POST, kita perlu menangkap pesan untuk ditampilkan
    // Ini penting jika ada validasi form yang gagal dan tidak ada redirect.
    // Jika ada redirect, kode di bawah ini tidak akan dieksekusi.
}

// Menangani pesan dari redirect (setelah POST/GET action)
// Ini harus tetap di sini agar pesan dari redirect sebelumnya bisa ditangkap
// (misal, dari cart.php jika keranjang kosong setelah penyesuaian stok).
if (isset($_GET['message']) && isset($_GET['text'])) {
    $message = htmlspecialchars(urldecode($_GET['text']));
    $message_type = htmlspecialchars($_GET['message']);
}

// Sertakan header halaman pengguna (ini akan membuka tag <html>, <head>, <body>, dan <main>)
// Semua logika yang berpotensi melakukan redirect harus berada di atas baris ini.
include 'includes/header.php';
?>

<main class="container my-5">
  <h2 class="text-center mb-4">Checkout</h2>

  <?php if (!empty($message)): ?>
  <div class="message message-<?php echo $message_type; ?>">
    <?php echo $message; ?>
  </div>
  <?php endif; ?>

  <div class="row">
    <!-- Ringkasan Pesanan -->
    <div class="col-md-6 mb-4">
      <div class="card p-4 shadow-sm">
        <h3 class="mb-3">Order Summary</h3>
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Product</th>
              <th>Qty</th>
              <th>Price</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cart_items as $item) : ?>
            <tr>
              <td><?= htmlspecialchars($item['nama']) ?></td>
              <td><?= htmlspecialchars($item['kuantitas']) ?></td>
              <td>$<?= number_format($item['harga'], 2) ?></td>
              <td>$<?= number_format($item['subtotal'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="3" class="text-end">Total:</th>
              <th>$<?= number_format($total_belanja, 2) ?></th>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Detail Pengiriman dan Pembayaran -->
    <div class="col-md-6 mb-4">
      <div class="card p-4 shadow-sm">
        <h3 class="mb-3">Shipping & Payment</h3>
        <form action="checkout.php" method="post">
          <div class="mb-3">
            <label for="full_name" class="form-label">Full Name:</label>
            <input type="text" class="form-control" id="full_name" name="full_name"
              value="<?= htmlspecialchars($full_name) ?>" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>"
              required>
          </div>
          <div class="mb-3">
            <label for="phone_number" class="form-label">Phone Number:</label>
            <input type="text" class="form-control" id="phone_number" name="phone_number"
              value="<?= htmlspecialchars($phone_number) ?>">
          </div>
          <div class="mb-3">
            <label for="shipping_address" class="form-label">Shipping Address:</label>
            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3"
              required><?= htmlspecialchars($shipping_address) ?></textarea>
          </div>

          <div class="mb-3">
            <label for="payment_method" class="form-label">Payment Method:</label>
            <select class="form-select" id="payment_method" name="payment_method" required>
              <option value="">Select Payment Method</option>
              <option value="bank_transfer">Bank Transfer</option>
              <option value="credit_card">Credit Card</option>
              <option value="cash_on_delivery">Cash on Delivery (COD)</option>
            </select>
          </div>

          <button type="submit" name="place_order" class="btn btn-primary w-100">
            <i class="fas fa-money-check-alt"></i> Place Order
          </button>
        </form>
      </div>
    </div>
  </div>
</main>

<?php
// Sertakan footer halaman pengguna
include 'includes/footer.php';

// Tutup koneksi database
if (isset($conn) && $conn) {
    $conn->close();
}
?>