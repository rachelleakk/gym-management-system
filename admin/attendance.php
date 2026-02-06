<?php
include('../includes/db.php');

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = trim($_POST['phone']);

    // Find member
    $query = "SELECT * FROM members WHERE phone = '$phone'";
    $result = mysqli_query($conn, $query);


    if (mysqli_num_rows($result) > 0) {
    $member = mysqli_fetch_assoc($result);
    $member_id = $member['id'];

    // Check today's attendance (USE check_in_time ONLY)
    $check = mysqli_query(
        $conn,
        "SELECT * FROM attendance 
         WHERE member_id = '$member_id' 
         AND DATE(check_in_time) = CURDATE()"
    );

    if (mysqli_num_rows($check) == 0) {

        mysqli_query(
            $conn,
            "INSERT INTO attendance (member_id, check_in_time) 
             VALUES ('$member_id', NOW())"
        );

        $message = "<div class='alert success'>✅ Welcome, <b>{$member['name']}</b>! Attendance recorded.</div>";

    } else {
        $message = "<div class='alert info'>ℹ️ Attendance already recorded for today.</div>";
    }
} else {
    $message = "<div class='alert danger'>❌ No member found with that phone number.</div>";
}
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Member Attendance</title>

<!-- ✅ SAME MODERN CSS FROM LOGIN.PHP -->
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
        text-align: center;
    }

    .login-title {
        font-size: 28px;
        font-weight: 600;
        margin-bottom: 25px;
        color: #111827;
    }

    .wrap-input100 {
        position: relative;
        margin-bottom: 22px;
    }

    .input100 {
        width: 100%;
        padding: 14px 18px 14px 48px;
        border-radius: 50px;
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

    .symbol-input100 {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 18px;
        transition: 0.3s;
    }

    .input100:focus ~ .symbol-input100,
    .wrap-input100:hover .symbol-input100 {
        color: #10b981;
    }

    .login-btn {
        margin-top: 10px;
        width: 100%;
        padding: 14px;
        background: #10b981;
        border: none;
        color: white;
        font-size: 17px;
        border-radius: 50px;
        cursor: pointer;
        font-weight: 500;
        transition: 0.2s;
    }

    .login-btn:hover {
        background: #000;
    }

    /* ✅ Alert styles matching your dashboard */
    .alert {
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 15px;
        font-size: 14px;
        font-weight: 500;
        animation: fadeIn 0.4s ease;
    }
    .success { background: #dcfce7; color: #166534; border-left: 5px solid #22c55e; }
    .info { background: #dbeafe; color: #1e3a8a; border-left: 5px solid #3b82f6; }
    .danger { background: #fee2e2; color: #991b1b; border-left: 5px solid #ef4444; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

</head>
<body>

<div class="login-box">

    <h2 class="login-title">Member Attendance</h2>

    <?= $message ?>

    <form method="POST">
        <div class="wrap-input100">
            <input class="input100" type="text" name="phone" placeholder="Enter phone number" required>
            <span class="symbol-input100">
                <i class="fa fa-phone"></i>
            </span>
        </div>

        <button type="submit" class="login-btn">Check In</button>
    </form>
</div>

<!-- ✅ Auto-hide alerts after 3 seconds -->
<script>
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(el => {
            el.style.transition = "0.5s";
            el.style.opacity = "0";
            setTimeout(() => el.remove(), 500);
        });
    }, 3000);
</script>

<!-- ✅ Load FontAwesome Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</body>
</html>