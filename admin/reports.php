<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
include('../includes/db.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reports — Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body{font-family:"Segoe UI",sans-serif;background:#f3f4f6;margin:0;padding:30px}
    .card{background:#fff;border-radius:12px;padding:18px;max-width:900px;margin:0 auto;box-shadow:0 8px 24px rgba(0,0,0,0.06)}
    h2{margin:0 0 12px 0}
    .row{display:flex;gap:12px;align-items:center;margin-bottom:12px}
    input[type=date]{padding:8px;border-radius:8px;border:1px solid #e6e6e6}
    .btn{background:#0a2d7a;color:#fff;padding:10px 14px;border-radius:8px;border:none;cursor:pointer}
    .btn.ghost{background:transparent;border:1px solid #ddd;color:#333}
</style>
</head>
<body>

<div class="card">
    <h2>Revenue Reports</h2>
    <p style="color:#6b7280">Filter revenue by date range and export the results.</p>

    <form action="export_revenue.php" method="POST" id="reportForm">
        <div class="row">
            <div>
                <label>Start date</label><br>
                <input type="date" name="start_date" required>
            </div>
            <div>
                <label>End date</label><br>
                <input type="date" name="end_date" required>
            </div>
            <div style="align-self:flex-end">
                <button type="submit" class="btn">Export (Excel)</button>
            </div>
        </div>
    </form>

    <hr>

    <h3>Quick Stats</h3>
    <p style="color:#6b7280">You can also view other saved reports here (future expansion).</p>
</div>

</body>
</html>
