<?php
// auth/register.php
require_once __DIR__ . '/../includes/functions.php';

$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // basic validation
    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $message = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address.";
    } elseif ($password !== $confirm) {
        $message = "Passwords do not match.";
    } else {
        $created = create_user($name, $email, $password, 'member');
        if ($created) {
            header("Location: login.php?registered=1");
            exit();
        } else {
            $message = "Registration failed — email may already exist.";
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Register</title>
  <link rel="stylesheet" href="../public/style.css">
</head>
<body>
  <div class="auth-card">
    <h2>Register (Member)</h2>
    <?php if ($message): ?>
      <p class="error"><?= e($message) ?></p>
    <?php endif; ?>
    <form method="POST" novalidate>
      <label>Name</label>
      <input type="text" name="name" required>

      <label>Email</label>
      <input type="email" name="email" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <label>Confirm Password</label>
      <input type="password" name="confirm_password" required>

      <button type="submit">Register</button>
    </form>
    <div class="extra">Already have an account? <a href="login.php">Login</a></div>
  </div>
</body>
</html>
