<?php
require_once '../includes/db.php';

// Get member ID
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: manage_members.php");
    exit();
}

// Fetch current member data
$member = $conn->query("SELECT * FROM members WHERE id='$id'")->fetch_assoc();
if (!$member) {
    header("Location: manage_members.php");
    exit();
}

// Fetch plans
$plans = $conn->query("SELECT * FROM plans");

// Current plan info
$currentPlan = $conn->query("SELECT * FROM plans WHERE plan_key='" . $member['membership_type'] . "'")->fetch_assoc();
$currentPlanName = $currentPlan['title'] ?? ucfirst($member['membership_type']);
$currentPlanPrice = $currentPlan['price'] ?? 0.00;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPlan = $_POST['membership_type'] ?: $member['membership_type'];
    $start_date = $_POST['start_date'];

    $planData = $conn->query("SELECT * FROM plans WHERE plan_key='$selectedPlan'")->fetch_assoc();
    $duration = $planData['duration_days'];
    $amount = $planData['price'];
    $end_date = date('Y-m-d', strtotime("$start_date +$duration days"));

    // Registration fee rule
    if (in_array(strtolower($selectedPlan), ['walkin','walk-in','daily'])) {
        $registration_fee = 0.00;
    } else {
        $registration_fee = 100.00;
    }

    $total_paid = $member['total_paid'] + $amount + $registration_fee;

    // Update member record
    $stmt = $conn->prepare("UPDATE members 
                            SET membership_type=?, start_date=?, end_date=?, status='active', total_paid=? 
                            WHERE id=?");
    $stmt->bind_param("sssdi", $selectedPlan, $start_date, $end_date, $total_paid, $id);
    $stmt->execute();
    $stmt->close();

    // Insert renewal payment
    $stmt2 = $conn->prepare("INSERT INTO payments (member_id, plan, amount, registration_fee) VALUES (?,?,?,?)");
    $stmt2->bind_param("isdd", $id, $selectedPlan, $amount, $registration_fee);
    $stmt2->execute();
    $stmt2->close();

    header("Location: manage_members.php?renewed=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Renew Membership</title>

<!-- ✅ Same modern UI used in other pages -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    body {
        font-family: "Segoe UI", sans-serif;
        background: #f3f4f6;
        margin: 0;
        height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding-top: 60px;
        padding-bottom: 60px;
    }

    .renew-box {
        background: white;
        width: 480px;
        padding: 35px;
        border-radius: 20px;
        box-shadow: 0 4px 22px rgba(0,0,0,0.12);
        animation: fadeIn 0.5s ease-in-out;
       
    }

    h2 {
        text-align: center;
        font-size: 26px;
        margin-bottom: 20px;
        color: #111827;
        font-weight: 600;
    }

    .info-box {
        background: #eef2ff;
        padding: 15px;
        border-left: 5px solid #10b981;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 14px;
        color: #1e1b4b;
    }

    .wrap-input100 {
        position: relative;
        margin-bottom: 22px;
    }

    .input100, select {
        width: 100%;
        padding: 14px 18px 14px 50px;
        border-radius: 50px;
        background: #f1f1f1;
        border: 2px solid transparent;
        font-size: 15px;
        outline: none;
        transition: .3s;
        box-sizing: border-box;
    }

    .input100:focus, select:focus {
        border-color: #10b981;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.25);
    }

    .symbol-input100 {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 18px;
        transition: .3s;
    }

    .input100:focus ~ .symbol-input100,
    select:focus ~ .symbol-input100 {
        color: #10b981;
    }

    label {
        margin-left: 5px;
        font-weight: 600;
        margin-bottom: 6px;
        color: #374151;
        display: block;
    }

    .submit-btn {
        width: 100%;
        padding: 14px;
        border-radius: 50px;
        border: none;
        background: #10b981;
        color: white;
        font-size: 17px;
        cursor: pointer;
        transition: .2s;
        font-weight: 500;
        margin-top: 5px;
    }

    .submit-btn:hover {
        background: #000;
    }

    @keyframes fadeIn {
        from {opacity: 0; transform: translateY(10px);}
        to {opacity: 1; transform: translateY(0);}
    }
</style>
</head>
<body>

<div class="renew-box">
    <h2>Renew Membership</h2>

    <div class="info-box">
        <p><strong>Member:</strong> <?= htmlspecialchars($member['name']) ?></p>
        <p><strong>Current Plan:</strong> <?= $currentPlanName ?></p>
        <p><strong>Price:</strong> ₵<?= number_format($currentPlanPrice, 2) ?></p>
        <p><strong>Start:</strong> <?= $member['start_date'] ?></p>
        <p><strong>End:</strong> <?= $member['end_date'] ?></p>
    </div>

    <form method="POST">

        <label>Renew Plan</label>
        <div class="wrap-input100">
            <select name="membership_type">
                <option value="">Keep Current (<?= ucfirst($member['membership_type']) ?>)</option>
                <?php while($p = $plans->fetch_assoc()): ?>
                    <option value="<?= $p['plan_key'] ?>">
                        <?= $p['title'] ?> - ₵<?= number_format($p['price'],2) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <span class="symbol-input100"><i class="fa fa-repeat"></i></span>
        </div>

        <label>New Start Date</label>
        <div class="wrap-input100">
            <input type="date" name="start_date" class="input100" required>
            <span class="symbol-input100"><i class="fa fa-calendar"></i></span>
        </div>

        <button class="submit-btn" type="submit">Renew Member</button>
    </form>
</div>

</body>
</html>
