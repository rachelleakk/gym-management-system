<?php
session_start();

// ✅ Restrict access to admins only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

include('../includes/db.php');

// ✅ Query payments joined with member names
$query = "
    SELECT m.name, p.amount, p.payment_date
    FROM payments p
    JOIN members m ON p.member_id = m.id
    ORDER BY p.payment_date DESC
";
$result = $conn->query($query);

// ✅ Set headers to trigger Excel download
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=revenue_data.xls");

// ✅ Print column headers
echo "Member\tAmount (₵)\tPayment Date\n";

// ✅ Loop through query results and print rows
while ($row = $result->fetch_assoc()) {
    echo "{$row['name']}\t{$row['amount']}\t{$row['payment_date']}\n";
}

exit();
?>
