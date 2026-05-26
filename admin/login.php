<?php
// admin/login.php - Admin login page
session_name('admin_session');
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_POST) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === 'admin' && $password === 'pass123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login_time'] = time();
        $_SESSION['admin_username'] = $username;
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Joy Eateries</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body {
            background: linear-gradient(135deg, #d4a373 0%, #b5835a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 450px;
            width: 90%;
            border-radius: 20px;
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #2c3e2f 0%, #1e2a22 100%);
            padding: 30px;
            text-align: center;
        }
        .login-header img {
            height: 80px;
            margin-bottom: 10px;
        }
        .login-header h4 {
            color: white;
            margin: 0;
        }
        .login-body {
            padding: 30px;
            background: white;
        }
        .btn-joy {
            background: linear-gradient(135deg, #d4a373 0%, #b5835a 100%);
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="card login-card">
        <div class="login-header">
            <img src="../img/logo.png" alt="Joy Eateries">
            <h4>Admin Panel</h4>
            <p style="color: #d4a373; margin: 0;">Joy Eateries Management</p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="card-panel red lighten-4 red-text">
                    <i class="material-icons left">error</i>
                    <?= $error ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="input-field">
                    <i class="material-icons prefix">person</i>
                    <input type="text" name="username" id="username" required>
                    <label for="username">Username</label>
                </div>
                <div class="input-field">
                    <i class="material-icons prefix">lock</i>
                    <input type="password" name="password" id="password" required>
                    <label for="password">Password</label>
                </div>
                <button type="submit" class="btn btn-joy waves-effect waves-light">
                    <i class="material-icons left">login</i>Login
                </button>
            </form>
            <div class="center-align" style="margin-top: 20px;">
                <a href="../customer/index.php" class="grey-text">← Back to Customer Site</a>
            </div>
        </div>
    </div>
</body>
</html>