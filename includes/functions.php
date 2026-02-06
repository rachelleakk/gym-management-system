<?php
// includes/functions.php
require_once __DIR__ . '/db.php';
session_start();

/**
 * Create a new user (member or admin) in `users` table
 * Returns inserted user id on success or false on failure
 */
function create_user($name, $email, $password, $role = 'member') {
    global $conn;
    // check if email exists
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        return false; // email exists
    }
    $stmt->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $hash, $role);
    $ok = $stmt->execute();
    if ($ok) {
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }
    $stmt->close();
    return false;
}

/**
 * Attempt login: returns user row array or false
 */
function attempt_login($email, $password) {
    global $conn;
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $user = $res->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            return $user;
        }
    }
    return false;
}

/**
 * Start a secure session for the user
 */
function login_user($user) {
    // regenerate session id to prevent fixation
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
}

/**
 * Log out user
 */
function logout_user() {
    // Unset all session variables and destroy session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Simple sanitiser for output (prevent XSS in outputs)
 */
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}
