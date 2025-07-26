<?php
// FILE: includes/footer.php
// Ini adalah bagian footer untuk halaman pengguna.
// File ini akan menutup tag <main>, <body>, dan <html> yang dibuka di header.php.
?>
</main> <!-- Menutup tag <main> yang dibuka di header.php -->

<footer>
  <div class="container">
    <div class="footer-grid">
      <div class="footer-col">
        <h3>COMPANY</h3>
        <ul>
          <li><a href="#">About Us</a></li>
          <li><a href="#">Our Store</a></li>
          <li><a href="#">Contact Us</a></li>
          <li><a href="#">Privacy Policy</a></li>
          <li><a href="#">Terms & Conditions</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h3>OUR SERVICES</h3>
        <ul>
          <li><a href="#">Delivery Info</a></li>
          <li><a href="#">Returns Policy</a></li>
          <li><a href="#">Shipping Policy</a></li>
          <li><a href="#">Affiliate Program</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h3>CONTACT</h3>
        <p><i class="fas fa-map-marker-alt"></i> 123 Main Street, Bandung, Indonesia</p>
        <p><i class="fas fa-phone"></i> +123 242 542</p>
        <p><i class="fas fa-envelope"></i> rezaprdt1@gmail.com</p>
      </div>
      <div class="footer-col">
        <h3>NEWSLETTER</h3>
        <p>Subscribe to our newsletter for updates.</p>
        <form class="newsletter-form">
          <input type="email" placeholder="Your Email Address">
          <button type="submit"><i class="fas fa-paper-plane"></i></button>
        </form>
      </div>
    </div>
    <div class="bottom-footer">
      <p>&copy; <?php echo date("Y"); ?> Anon Store. All rights reserved.</p>
      <div class="payment-icons">
        <i class="fab fa-cc-visa"></i>
        <i class="fab fa-cc-mastercard"></i>
        <i class="fab fa-cc-paypal"></i>
        <i class="fab fa-cc-amex"></i> <!-- Menambahkan Amex sebagai contoh -->
      </div>
    </div>
  </div>
</footer>

<!-- Opsional: Sertakan script JavaScript di sini jika diperlukan -->
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
<!-- <script src="js/script.js"></script> -->

</body>

</html>