<?php 
require_once '../includes/db.php';

// ----------------------------------------
// Helpers
// ----------------------------------------
function clean_text($s, $max) {
    $s = trim(preg_replace('/\s+/u', ' ', (string)$s));
    if (function_exists('mb_substr')) {
        $s = mb_substr($s, 0, $max);
    } else {
        $s = substr($s, 0, $max);
    }
    return $s;
}
function sanitize_email_header($email) {
    return str_replace(["\r","\n","%0a","%0d"], '', $email);
}

// ----------------------------------------
// Fetch plans for select box
// ----------------------------------------
$plans = $conn->query("SELECT * FROM plans ORDER BY id ASC");

// Feedback containers
$error = "";
$expiredMatch = null; // holds array: ['id'=>..., 'name'=>..., 'status'=>...]
$activeMatch  = null;

// ----------------------------------------
// Handle POST
// ----------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize + length caps
    $name      = clean_text($_POST['name'] ?? '', 60);
    $email     = sanitize_email_header(clean_text($_POST['email'] ?? '', 80));
    $phone     = clean_text($_POST['phone'] ?? '', 15);
    $gender    = ($_POST['gender'] ?? '');
    $address   = clean_text($_POST['address'] ?? '', 120);
    $plan      = clean_text($_POST['membership_type'] ?? '', 40);
    $start_date= trim($_POST['start_date'] ?? '');

    // ------------------------------
    // Server-side validation
    // ------------------------------
    // Name: letters, spaces, apostrophes, hyphens (no digits/symbols)
    if (!preg_match("/^[A-Za-zÀ-ÖØ-öø-ÿ' -]{2,60}$/u", $name)) {
        $error = "Please enter a valid full name (letters, spaces, apostrophes, hyphens only).";
    }

    // Email
    if (!$error && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }

    // Phone (Ghana-focused): 0XXXXXXXXX or +233XXXXXXXXX
    if (
        !$error &&
        !preg_match('/^(0\d{9}|\+233\d{9})$/', $phone)
    ) {
        $error = "Phone must be Ghana format: 0XXXXXXXXX or +233XXXXXXXXX.";
    }

    // Gender
    if (!$error && !in_array(strtolower($gender), ['male','female'], true)) {
        $error = "Please choose a valid gender.";
    }

    // Dates
    if (!$error && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
        $error = "Please choose a valid start date (YYYY-MM-DD).";
    }

    // Plan must exist
    if (!$error) {
        $stmtPlan = $conn->prepare("SELECT * FROM plans WHERE plan_key = ? LIMIT 1");
        $stmtPlan->bind_param("s", $plan);
        $stmtPlan->execute();
        $planRes = $stmtPlan->get_result();
        $planData = $planRes->fetch_assoc();
        $stmtPlan->close();

        if (!$planData) {
            $error = "Selected membership plan is invalid.";
        }
    }

    // ------------------------------
    // Duplicate checks (phone/email)
    // If ACTIVE → show “Member already exists”
    // If EXPIRED → show Renew option (button)
    // ------------------------------
    if (!$error) {
        $stmtDup = $conn->prepare("SELECT id, name, status FROM members WHERE phone = ? OR email = ? ORDER BY id DESC LIMIT 1");
        $stmtDup->bind_param("ss", $phone, $email);
        $stmtDup->execute();
        $dupRes = $stmtDup->get_result();
        if ($dupRes->num_rows > 0) {
            $rowDup = $dupRes->fetch_assoc();
            if (strtolower($rowDup['status']) === 'active') {
                $activeMatch = $rowDup; // Use to display a nicer message
                $error = "Member already exists and is ACTIVE.";
            } else {
                $expiredMatch = $rowDup; // Offer renew
                $error = ""; // we will not insert; we will render Renew choice UI
            }
        }
        $stmtDup->close();
    }

    // If no error and not an expired member (i.e., proceed to create)
    if (!$error && !$expiredMatch) {
        // Calculate pricing, dates, status
        $duration = (int)$planData['duration_days'];
        $amount   = (float)$planData['price'];

        $end_date = date('Y-m-d', strtotime($start_date . " +{$duration} days"));

        // Registration fee logic
        $walkKeys = ['walkin','walk-in','daily'];
        $registration_fee = in_array(strtolower($plan), $walkKeys, true) ? 0.00 : 100.00;

        $total_paid = $amount + $registration_fee;

        // Status by comparing end_date to today
        date_default_timezone_set('Africa/Accra');
        $today = date('Y-m-d');
        $status = ($end_date < $today) ? 'expired' : 'active';

        // Insert member (prepared)
        $stmt = $conn->prepare("INSERT INTO members 
            (name,email,phone,gender,address,membership_type,start_date,end_date,status,total_paid)
            VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param(
            "sssssssssd",
            $name,$email,$phone,$gender,$address,$plan,$start_date,$end_date,$status,$total_paid
        );
        $stmt->execute();
        $member_id = $stmt->insert_id;
        $stmt->close();

        // Insert payment
        $stmt2 = $conn->prepare("INSERT INTO payments (member_id, plan, amount, registration_fee) VALUES (?,?,?,?)");
        $stmt2->bind_param("isdd", $member_id, $plan, $amount, $registration_fee);
        $stmt2->execute();
        $stmt2->close();

        header("Location: manage_members.php?added=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Member</title>

<!-- UI styles -->
<style>
    body {
        font-family: "Segoe UI", sans-serif;
        background: #f3f4f6;
        display: flex;
        justify-content: center;
        padding-top: 40px;
        margin: 0;
    }

    .form-box {
        background: white;
        width: 470px;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 4px 25px rgba(0,0,0,0.1);
        animation: fadeIn 0.5s ease;
    }

    h2 {
        text-align: center;
        font-size: 26px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 18px;
    }

    .error-box, .info-box, .warn-box {
        padding: 12px 14px;
        border-radius: 10px;
        margin-bottom: 14px;
        font-weight: 500;
        animation: fadeIn 0.3s ease;
    }
    .error-box {
        background: #fee2e2;
        color: #b91c1c;
        border-left: 5px solid #ef4444;
        text-align: center;
    }
    .info-box {
        background: #e0f2fe;
        color: #075985;
        border-left: 5px solid #38bdf8;
    }
    .warn-box {
        background: #fff7ed;
        color: #9a3412;
        border-left: 5px solid #f59e0b;
    }

    .wrap-input100 {
        position: relative;
        margin-bottom: 18px;
    }

    .input100, select {
        width: 100%;
        padding: 14px 18px 14px 48px; /* space for icon on left */
        border-radius: 50px;
        background: #f1f1f1;
        border: 2px solid transparent;
        font-size: 15px;
        outline: none;
        transition: 0.3s;
        box-sizing: border-box;
    }
    .input100:focus, select:focus {
        border-color: #10b981;
        background: white;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.25);
    }
    select {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
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
    select:focus ~ .symbol-input100,
    .wrap-input100:hover .symbol-input100 {
        color: #10b981;
    }

    label {
        font-weight: 600;
        color: #374151;
        display: block;
        margin-bottom: 6px;
        margin-left: 5px;
    }

    .submit-btn, .renew-btn, .cancel-btn {
        width: 100%;
        padding: 14px;
        border: none;
        font-size: 16px;
        border-radius: 50px;
        cursor: pointer;
        transition: 0.2s;
    }
    .submit-btn {
        background: #10b981;
        color: #fff;
        margin-top: 6px;
    }
    .submit-btn:hover { background: #0a7f60; }

    .renew-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 10px;
    }
    .renew-btn {
        background: #0ea5e9;
        color: #fff;
    }
    .renew-btn:hover { background: #0284c7; }
    .cancel-btn {
        background: #e5e7eb; 
        color: #111827;
    }
    .cancel-btn:hover { background: #d1d5db; }

    .preview-box {
        background: #f9fafb;
        padding: 16px;
        border-radius: 12px;
        margin: 10px 0 4px 0;
        border-left: 4px solid #0ea5e9;
        font-size: 15px;
        display: none;
    }

    small.hint { color: #6b7280; display:block; margin: -6px 0 10px 6px; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="form-box">
    <h2>Add New Member</h2>

    <?php if ($error): ?>
        <div class="error-box">
            <?php if ($activeMatch): ?>
                This member (<?= htmlspecialchars($activeMatch['name']) ?>) already exists and is <b>ACTIVE</b>.
            <?php elseif ($expiredMatch): ?>
                This member (<?= htmlspecialchars($expiredMatch['name']) ?>) already exists and is <b>EXPIRED</b>.
                <br><br>
                <a class="renew-btn" style="display:block; margin-top:10px; text-align:center;" 
                   href="renew.php?id=<?= (int)$expiredMatch['id'] ?>">
                   Renew Member
                </a>
            <?php else: ?>
                <?= htmlspecialchars($error) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php 
    // If expired member found → stop rendering form
    if ($expiredMatch) exit; 
    ?>

    <form id="addMemberForm" method="POST" novalidate>
        <!-- Name -->
        <div class="wrap-input100">
            <input class="input100" type="text" name="name" placeholder="Full Name"
                   maxlength="60" required
                   pattern="[A-Za-zÀ-ÖØ-öø-ÿ' -]{2,60}"
                   title="Letters, spaces, apostrophes and hyphens only (2–60 characters)">
            <span class="symbol-input100"><i class="fa fa-user"></i></span>
        </div>

        <!-- Email -->
        <div class="wrap-input100">
            <input class="input100" type="email" name="email" placeholder="Email"
                   maxlength="80" required>
            <span class="symbol-input100"><i class="fa fa-envelope"></i></span>
        </div>

        <!-- Phone -->
        <div class="wrap-input100">
            <input class="input100" type="text" name="phone" placeholder="Phone Number"
                   maxlength="15" required
                   pattern="(0\d{9}|\+233\d{9})"
                   title="Use 0XXXXXXXXX or +233XXXXXXXXX">
            <span class="symbol-input100"><i class="fa fa-phone"></i></span>
        </div>

        <!-- Gender -->
        <label>Gender</label>
        <div class="wrap-input100">
            <select name="gender" required>
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
            </select>
            <span class="symbol-input100"><i class="fa fa-venus-mars"></i></span>
        </div>

        <!-- Address -->
        <div class="wrap-input100">
            <input class="input100" type="text" name="address" placeholder="Address"
                   maxlength="120" required>
            <span class="symbol-input100"><i class="fa fa-location-dot"></i></span>
        </div>

        <!-- Plan -->
        <label>Membership Plan</label>
        <div class="wrap-input100">
            <select name="membership_type" id="planSelect" required>
                <option value="">Select Plan</option>
                <?php 
                    // Reload plans for data attributes used in preview
                    $plans2 = $conn->query("SELECT * FROM plans ORDER BY id ASC");
                    while($p = $plans2->fetch_assoc()):
                ?>
                    <option value="<?= htmlspecialchars($p['plan_key']) ?>"
                            data-price="<?= htmlspecialchars($p['price']) ?>"
                            data-duration="<?= (int)$p['duration_days'] ?>">
                        <?= htmlspecialchars($p['title']) ?> - ₵<?= number_format($p['price'],2) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <span class="symbol-input100"><i class="fa fa-list"></i></span>
        </div>

        <!-- Start Date -->
        <label>Start Date</label>
        <div class="wrap-input100">
            <input class="input100" type="date" name="start_date" id="startDate" required>
            <span class="symbol-input100"><i class="fa fa-calendar"></i></span>
        </div>

        <!-- Live Preview -->
        <div id="previewBox" class="preview-box"></div>

        <button id="submitBtn" type="submit" class="submit-btn">
            Add Member
        </button>
    </form>
</div>

<script>
// ------------------------------
// Live preview of membership summary
// ------------------------------
const planEl = document.getElementById('planSelect');
const startEl = document.getElementById('startDate');
const preview = document.getElementById('previewBox');

function updatePreview() {
    if (!planEl.value || !startEl.value) {
        preview.style.display = 'none';
        preview.innerHTML = '';
        return;
    }
    const opt = planEl.selectedOptions[0];
    const amount = parseFloat(opt.dataset.price || '0');
    const duration = parseInt(opt.dataset.duration || '0', 10);

    const start = new Date(startEl.value);
    if (isNaN(start.getTime())) {
        preview.style.display = 'none';
        preview.innerHTML = '';
        return;
    }
    const end = new Date(start);
    end.setDate(start.getDate() + duration);
    const end_fmt = end.toISOString().split('T')[0];

    const v = planEl.value.toLowerCase();
    const regFee = (v === 'walkin' || v === 'walk-in' || v === 'daily') ? 0 : 100;
    const total = amount + regFee;

    preview.style.display = 'block';
    preview.innerHTML = `
        <b>Preview</b><br>
        Plan Price: ₵${amount.toFixed(2)}<br>
        Duration: ${duration} days<br>
        End Date: ${end_fmt}<br>
        Registration Fee: ₵${regFee.toFixed(2)}<br>
        <b>Total Payment: ₵${total.toFixed(2)}</b>
    `;
}
planEl.addEventListener('change', updatePreview);
startEl.addEventListener('change', updatePreview);

// ------------------------------
// Prevent duplicate submissions
// Disable button after first click
// ------------------------------
const form = document.getElementById('addMemberForm');
const submitBtn = document.getElementById('submitBtn');
let submitting = false;

form.addEventListener('submit', function (e) {
    // client-side validity check to avoid locking button on invalid form
    if (!form.checkValidity()) {
        // Let browser show built-in validation messages
        return;
    }
    if (submitting) {
        e.preventDefault();
        return false;
    }
    submitting = true;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
});
</script>

</body>
</html>
