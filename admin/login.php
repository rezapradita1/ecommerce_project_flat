<?php
// FILE: admin/login.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../includes/db_connect.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // --- DEBUGGING OUTPUT START ---
    echo "DEBUG: Username yang dimasukkan: '" . htmlspecialchars($username) . "'<br>";
    echo "DEBUG: Password plain text yang dimasukkan: '" . htmlspecialchars($password) . "'<br>";
    // --- DEBUGGING OUTPUT END ---

    if (empty($username) || empty($password)) {
        $message = "<span class='message-error'>Username dan password harus diisi.</span>";
    } else {
        $sql = "SELECT id, username, password FROM admin WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt === false) {
            $message = "<span class='message-error'>Terjadi kesalahan sistem. Silakan coba lagi nanti. (" . mysqli_error($conn) . ")</span>";
            // --- DEBUGGING OUTPUT START ---
            echo "DEBUG: mysqli_prepare gagal. Error: " . mysqli_error($conn) . "<br>";
            // --- DEBUGGING OUTPUT END ---
        } else {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if ($user) {
                // --- DEBUGGING OUTPUT START ---
                echo "DEBUG: User ditemukan. Username: '" . htmlspecialchars($user['username']) . "'<br>";
                echo "DEBUG: Password hash dari DB: '" . htmlspecialchars($user['password']) . "'<br>";
                $is_password_correct = password_verify($password, $user['password']);
                echo "DEBUG: Hasil password_verify(): " . ($is_password_correct ? 'TRUE' : 'FALSE') . "<br>";
                // --- DEBUGGING OUTPUT END ---

                if ($is_password_correct) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_id'] = $user['id'];
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $message = "<span class='message-error'>Password salah.</span>";
                }
            } else {
                $message = "<span class='message-error'>Username tidak ditemukan.</span>";
                // --- DEBUGGING OUTPUT START ---
                echo "DEBUG: User tidak ditemukan di database.<br>";
                // --- DEBUGGING OUTPUT END ---
            }
        }
    }
} elseif (isset($_GET['action']) && $_GET['action'] == 'logout') {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    session_unset();
    session_destroy();
    $message = "<span class='message-success'>Anda telah berhasil logout.</span>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { background-color: #f0f2f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; font-family: Arial, sans-serif; }
        .login-container {
            width: 400px;
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            text-align: center;
        }
        .login-container h2 {
            margin-bottom: 30px;
            color: #333;
            font-size: 2em;
        }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: calc(100% - 22px);
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .form-group button {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        .form-group button:hover {
            background-color: #0056b3;
        }
        .message-success {
            color: #27ae60;
            background-color: #e6ffee;
            border: 1px solid #27ae60;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            display: block;
        }
        .message-error {
            color: #c0392b;
            background-color: #ffe6e6;
            border: 1px solid #c0392b;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php if ($message): ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <button type="submit">Login</button>
            </div>
        </form>
    </div>
</body>
</html>
<?php
if (isset($conn) && $conn) {
    mysqli_close($conn);
}
?>
