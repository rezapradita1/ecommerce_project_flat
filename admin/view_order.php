<?php
// FILE: admin/view_order.php

// Pastikan sesi dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sertakan file autentikasi dan cek status login admin
include '../includes/auth.php';
check_admin_login(); // Fungsi ini akan mengarahkan jika belum login

// Sertakan koneksi database
include '../includes/db_connect.php';

// Inisialisasi variabel
$order_details = null;
$order_items = [];
$message = '';
$message_type = ''; // 'success' atau 'error'

// Ambil ID pesanan dari URL
if (isset($_GET['id'])) {
    $order_id = intval($_GET['id']);

    if ($order_id > 0) {
        // --- Ambil Detail Pesanan dari tabel 'orders' ---
        $sql_order_details = "SELECT o.id, o.order_date, o.total_amount, o.status, o.shipping_address,
                                     o.payment_method, o.customer_name, o.customer_email, o.customer_phone,
                                     u.username AS user_username
                              FROM orders o
                              LEFT JOIN users u ON o.user_id = u.id
                              WHERE o.id = ?";
        $stmt_order_details = $conn->prepare($sql_order_details);

        if ($stmt_order_details) {
            $stmt_order_details->bind_param("i", $order_id);
            if ($stmt_order_details->execute()) {
                $result_order_details = $stmt_order_details->get_result();
                $order_details = $result_order_details->fetch_assoc();
                $result_order_details->free();
            } else {
                error_log("Error executing order details query: " . $stmt_order_details->error);
                $message = "Gagal mengambil detail pesanan.";
                $message_type = 'error';
            }
            $stmt_order_details->close();
        } else {
            error_log("Error preparing order details query: " . $conn->error);
            $message = "Error menyiapkan query detail pesanan.";
            $message_type = 'error';
        }

        // --- Ambil Item Pesanan dari tabel 'order_items' ---
        if ($order_details) { // Hanya jika detail pesanan ditemukan
            $sql_order_items = "SELECT oi.quantity, oi.price_at_purchase, p.name AS product_name, p.image_url
                                FROM order_items oi
                                JOIN products p ON oi.product_id = p.id
                                WHERE oi.order_id = ?";
            $stmt_order_items = $conn->prepare($sql_order_items);

            if ($stmt_order_items) {
                $stmt_order_items->bind_param("i", $order_id);
                if ($stmt_order_items->execute()) {
                    $result_order_items = $stmt_order_items->get_result();
                    while ($row = $result_order_items->fetch_assoc()) {
                        $order_items[] = $row;
                    }
                    $result_order_items->free();
                } else {
                    error_log("Error executing order items query: " . $stmt_order_items->error);
                    $message = "Gagal mengambil item pesanan.";
                    $message_type = 'error';
                }
                $stmt_order_items->close();
            } else {
                error_log("Error preparing order items query: " . $conn->error);
                $message = "Error menyiapkan query item pesanan.";
                $message_type = 'error';
            }
        }
    } else {
        $message = "ID Pesanan tidak valid.";
        $message_type = 'error';
    }
} else {
    $message = "ID Pesanan tidak ditemukan.";
    $message_type = 'error';
}

// Menangani pesan dari redirect (jika ada)
if (isset($_GET['message']) && isset($_GET['text'])) {
    $message = htmlspecialchars(urldecode($_GET['text']));
    $message_type = htmlspecialchars($_GET['message']);
}

// Sertakan header admin
include '../includes/admin_header.php';
?>

<main class="container admin-content-area">
  <h2 class="text-center mb-4">Order Details - #<?php echo htmlspecialchars($order_id); ?></h2>

  <?php if (!empty($message)): ?>
  <div class="message message-<?php echo $message_type; ?>">
    <?php echo $message; ?>
  </div>
  <?php endif; ?>

  <?php if ($order_details): ?>
  <div class="card p-4 shadow-sm mb-4">
    <h3 class="mb-3">Order Information</h3>
    <div class="row">
      <div class="col-md-6">
        <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_details['id']); ?></p>
        <p><strong>Order Date:</strong>
          <?php echo htmlspecialchars(date('d M Y H:i', strtotime($order_details['order_date']))); ?></p>
        <p><strong>Status:</strong> <span
            class="badge bg-info text-dark"><?php echo htmlspecialchars(ucfirst($order_details['status'])); ?></span>
        </p>
        <p><strong>Total Amount:</strong> $<?php echo number_format($order_details['total_amount'], 2); ?></p>
        <p><strong>Payment Method:</strong>
          <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order_details['payment_method']))); ?></p>
      </div>
      <div class="col-md-6">
        <h4>Customer Information:</h4>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($order_details['customer_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($order_details['customer_email']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order_details['customer_phone']); ?></p>
        <p><strong>Shipping
            Address:</strong><br><?php echo nl2br(htmlspecialchars($order_details['shipping_address'])); ?></p>
        <?php if ($order_details['user_username']): ?>
        <p><strong>User Account:</strong> <?php echo htmlspecialchars($order_details['user_username']); ?></p>
        <?php else: ?>
        <p><strong>User Account:</strong> Guest Checkout</p>
        <?php endif; ?>
      </div>
    </div>
    <div class="mt-4 text-end">
      <a href="manage_orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Orders</a>
    </div>
  </div>

  <div class="card p-4 shadow-sm">
    <h3 class="mb-3">Ordered Items</h3>
    <?php if (!empty($order_items)): ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Product</th>
          <th>Quantity</th>
          <th>Price at Purchase</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($order_items as $item): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center">
              <?php if (!empty($item['image_url'])): ?>
              <img src="../<?php echo htmlspecialchars($item['image_url']); ?>"
                alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
              <?php endif; ?>
              <?php echo htmlspecialchars($item['product_name']); ?>
            </div>
          </td>
          <td><?php echo htmlspecialchars($item['quantity']); ?></td>
          <td>$<?php echo number_format($item['price_at_purchase'], 2); ?></td>
          <td>$<?php echo number_format($item['quantity'] * $item['price_at_purchase'], 2); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
    <p class="text-center">No items found for this order.</p>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <div class="alert alert-warning text-center" role="alert">
    <p>Order details could not be found or an error occurred.</p>
    <a href="manage_orders.php" class="btn btn-primary mt-3">Go to Manage Orders</a>
  </div>
  <?php endif; ?>
</main>

<?php
// Tutup koneksi database
if (isset($conn) && $conn) {
    $conn->close();
}

// Sertakan footer admin
include '../includes/admin_footer.php';
?>