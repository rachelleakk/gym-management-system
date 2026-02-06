<?php
require_once '../includes/db.php';
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM members WHERE id=$id");
}
header("Location: manage_members.php?deleted=1");
exit;
?>
