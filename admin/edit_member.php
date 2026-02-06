<?php
require_once '../includes/db.php';

// -------------------------
// Validate & Fetch Member
// -------------------------
if (!isset($_GET['id'])) {
    header("Location: manage_members.php");
    exit;
}

$id = intval($_GET['id']);
$member = $conn->query("SELECT * FROM members WHERE id = $id")->fetch_assoc();

if (!$member) {
    die("Member not found!");
}

// For expired warning
$isExpired = ($member['status'] === 'expired');

$error = "";

// -------------------------
// Handle Form Submission
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $phone   = trim($_POST['phone']);
    $gender  = $_POST['gender'];
    $address = trim($_POST['address']);

    // ✅ Validate phone format
    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $error = "Invalid phone number. Use 10 digits (e.g. 0551234567).";
    }

    // ✅ Validate name (no numbers, no symbols)
    if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $error = "Name must contain letters only (no numbers or symbols).";
    }

    // ✅ Email format security
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    }

    // ✅ Check email uniqueness
    $checkEmail = $conn->query("SELECT id FROM members WHERE email='$email' AND id != $id");
    if ($checkEmail->num_rows > 0) {
        $error = "This email is already used by another member.";
    }

    // ✅ Check phone uniqueness
    $checkPhone = $conn->query("SELECT id FROM members WHERE phone='$phone' AND id != $id");
    if ($checkPhone->num_rows > 0) {
        $error = "This phone number is already used by another member.";
    }

    // ✅ If no validation errors → Update only PERSONAL FIELDS
    if ($error === "") {
        $stmt = $conn->prepare("
            UPDATE members 
            SET name=?, email=?, phone=?, gender=?, address=?
            WHERE id=?
        ");
        $stmt->bind_param("sssssi",
            $name, $email, $phone, $gender, $address, $id
        );
        $stmt->execute();
        $stmt->close();

        header("Location: manage_members.php?updated=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Member</title>

<!-- ✅ Modern UI Styling (Same as Add Member & Login) -->
<style>
    body {
        margin: 0;
        padding: 60px 0;
        font-family: "Segoe UI", sans-serif;
        background: #f3f4f6;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 100vh;
    }

    .edit-box {
        background: white;
        width: 450px;
        padding: 35px;
        border-radius: 20px;
        box-shadow: 0 4px 22px rgba(0,0,0,0.1);
        animation: fadeIn 0.4s ease;
    }

    h2 {
        text-align: center;
        color: #111827;
        font-size: 26px;
        margin-bottom: 25px;
    }

    .wrap-input100 {
        position: relative;
        margin-bottom: 20px;
    }

    .input100 {
        width: 100%;
        padding: 14px 18px 14px 50px;
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
        background: white;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.25);
    }

    .symbol-input100 {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 18px;
        transition: 0.3s;
    }

    .input100:focus ~ .symbol-input100 {
        color: #10b981;
    }

    label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 5px;
        display: block;
        margin-left: 5px;
    }

    .save-btn {
        width: 100%;
        padding: 14px;
        background: #10b981;
        border: none;
        color: white;
        font-size: 17px;
        border-radius: 50px;
        cursor: pointer;
        transition: 0.3s;
        margin-top: 10px;
    }

    .save-btn:hover {
        background: black;
    }

    .error-box {
        background: #fee2e2;
        color: #991b1b;
        padding: 12px;
        border-left: 5px solid #ef4444;
        border-radius: 10px;
        margin-bottom: 15px;
        text-align: center;
        font-weight: 600;
    }

    .expired-box {
        background: #fffbeb;
        color: #92400e;
        padding: 12px;
        border-left: 5px solid #fbbf24;
        border-radius: 10px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="edit-box">

    <h2>Edit Member</h2>

    <?php if ($error): ?>
        <div class="error-box"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($isExpired): ?>
        <div class="expired-box">
            ⚠ This member is currently <b>EXPIRED</b>.<br>
            If you want to change dates, use the <b>Renew</b> option instead.
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="wrap-input100">
            <input class="input100" type="text" name="name"
                   value="<?= htmlspecialchars($member['name']) ?>" required>
            <span class="symbol-input100"><i class="fa fa-user"></i></span>
        </div>

        <div class="wrap-input100">
            <input class="input100" type="email" name="email"
                   value="<?= htmlspecialchars($member['email']) ?>" required>
            <span class="symbol-input100"><i class="fa fa-envelope"></i></span>
        </div>

        <div class="wrap-input100">
            <input class="input100" type="text" name="phone"
                   value="<?= htmlspecialchars($member['phone']) ?>" required>
            <span class="symbol-input100"><i class="fa fa-phone"></i></span>
        </div>

        <label>Gender</label>
        <div class="wrap-input100">
            <select class="input100" name="gender" required>
                <option value="male" <?= $member['gender']=='male'?'selected':'' ?>>Male</option>
                <option value="female" <?= $member['gender']=='female'?'selected':'' ?>>Female</option>
            </select>
            <span class="symbol-input100"><i class="fa fa-venus-mars"></i></span>
        </div>

        <div class="wrap-input100">
            <input class="input100" type="text" name="address"
                   value="<?= htmlspecialchars($member['address']) ?>" required>
            <span class="symbol-input100"><i class="fa fa-map-marker"></i></span>
        </div>

        <!-- READ-ONLY FIELDS (Not editable anymore) -->
        <label>Membership Plan</label>
        <div class="wrap-input100">
            <input class="input100" type="text" value="<?= htmlspecialchars($member['membership_type']) ?>" disabled>
            <span class="symbol-input100"><i class="fa fa-id-card"></i></span>
        </div>

        <label>Start Date</label>
        <div class="wrap-input100">
            <input class="input100" type="date" value="<?= $member['start_date'] ?>" disabled>
            <span class="symbol-input100"><i class="fa fa-calendar"></i></span>
        </div>

        <label>End Date</label>
        <div class="wrap-input100">
            <input class="input100" type="date" value="<?= $member['end_date'] ?>" disabled>
            <span class="symbol-input100"><i class="fa fa-calendar-check"></i></span>
        </div>

        <button class="save-btn" type="submit">Save Changes</button>
    </form>

</div>

</body>
</html>
