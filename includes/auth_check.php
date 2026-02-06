<?php
// includes/auth_check.php
require_once __DIR__ . '/functions.php';

// Make sure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /gym-system/auth/login.php"); // adjust path if needed
    exit();
}

/**
 * require_role('admin') will block non-admins
 */
function require_role($role) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        // optional: redirect to their dashboard or show 403
        if ($_SESSION['role'] === 'admin') {
            header("Location: /gym-system/admin/dashboard.php");
        } else {
            header("Location: /gym-system/members/dashboard.php");
        }
        exit();
    }
}
