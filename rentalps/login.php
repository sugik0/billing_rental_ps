<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Rental PS</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-card { padding: 40px; width: 350px; text-align: center; }
        .login-card h1 { margin-bottom: 30px; }
        .login-card .form-sewa { margin-top: 0; }
        .error-message { background-color: #fde0e0; color: #c0392b; padding: 10px; border-radius: 6px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="konsol-card login-card">
        <h1>LOGIN KASIR</h1>
        <?php 
            if (isset($_GET['error'])) {
                echo '<div class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
            }
        ?>
        <form action="auth.php" method="POST" class="form-sewa">
            <input type="text" name="username" placeholder="Username" required style="text-align:left; margin-bottom: 10px;">
            <input type="password" name="password" placeholder="Password" required style="text-align:left;">
            <button type="submit" style="margin-top:20px;">LOGIN</button>
        </form>
    </div>
</body>
</html>