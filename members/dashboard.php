<?php
require_once __DIR__ . '/../includes/auth_check.php';
// members can only see their dashboard; if want to block admin:
if ($_SESSION['role'] !== 'member') {
    header("Location: /gym-system/admin/dashboard.php");
    exit();
}
?>
<!doctype html>
<html><head><title>Member Dashboard</title><link rel="stylesheet" href="../public/style.css"></head>
<body>
<?php echo "<h1>Welcome " . e($_SESSION['name']) . "</h1>"; ?>
<p><a href="/gym-system/auth/logout.php">Logout</a></p>
</body></html>
