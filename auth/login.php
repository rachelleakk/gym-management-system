<?php
session_start();
include('../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Fetch admin user
    $query = "SELECT * FROM users WHERE email = ? AND role = 'admin' LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['name'] = $row['name'];

            header("Location: ../admin/dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "Admin not found!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Gym System</title>

    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
    body {
        margin: 0;
        padding: 0;
        font-family: "Segoe UI", sans-serif;
        background: #f3f4f6;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .login-box {
        background: white;
        width: 380px;
        padding: 40px 35px;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        animation: fadeIn 0.5s ease;
    }

    .login-title {
        text-align: center;
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 25px;
        color: #111827;
    }

    .wrap-input100 {
        position: relative;
        margin-bottom: 22px;
    }

    /* ✅ Rounded inputs - icons moved left */
    .input100 {
        width: 100%;
        padding: 14px 18px 14px 48px; /* extra space on LEFT for icon */
        border-radius: 50px; /* fully rounded */
        background: #f1f1f1;
        border: 2px solid transparent;
        font-size: 15px;
        outline: none;
        transition: 0.3s;
        box-sizing: border-box;
    }

    .input100:focus {
        border-color: #10b981;
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.2);
    }

    /* ✅ Icon positioned on the LEFT now */
    .symbol-input100 {
        position: absolute;
        left: 16px;  /* moved from right → left */
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 18px;
        transition: 0.3s;
    }

    /* ✅ Icon turns green on focus */
    .input100:focus ~ .symbol-input100,
    .wrap-input100:hover .symbol-input100 {
        color: #10b981;
    }

    /* ✅ Fully rounded login button */
    .login-btn {
        margin-top: 10px;
        width: 100%;
        padding: 14px;
        background: #10b981;
        border: none;
        color: white;
        font-size: 17px;
        border-radius: 50px; /* fully rounded button */
        cursor: pointer;
        font-weight: 500;
        transition: 0.2s;
    }

    .login-btn:hover {
        background: #000;
    }

    .error {
        background: #fee2e2;
        color: #b91c1c;
        padding: 10px;
        text-align: center;
        border-radius: 6px;
        margin-bottom: 15px;
        border-left: 4px solid #dc2626;
        font-size: 14px;
    }

    @keyframes fadeIn {
        from {opacity: 0; transform: translateY(10px);}
        to {opacity: 1; transform: translateY(0);}
    }
</style>

</head>
<body>

<div class="login-box">

    <h2 class="login-title">Admin Login</h2>

    <?php if (!empty($error)) echo "<div class='error'>$error</div>"; ?>

    <form method="POST">

        <div class="wrap-input100">
            <input class="input100" type="email" name="email" placeholder="Email" required>
            <span class="focus-input100"></span>
            <span class="symbol-input100">
                <i class="fa fa-envelope"></i>
            </span>
        </div>

        <div class="wrap-input100">
            <input class="input100" type="password" name="password" placeholder="Password" required>
            <span class="focus-input100"></span>
            <span class="symbol-input100">
                <i class="fa fa-lock"></i>
            </span>
        </div>

        <button type="submit" class="login-btn">Login</button>

    </form>

</div>

</body>
</html>
