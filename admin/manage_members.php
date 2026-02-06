<?php
require_once '../includes/db.php';

// Auto-update expired members before showing
date_default_timezone_set('Africa/Accra'); // Ensures local Ghana time
$today = date('Y-m-d');
$conn->query("UPDATE members SET status='expired' WHERE end_date < '$today' AND status = 'active'");

// Fetch all members
$sql = "SELECT * FROM members ORDER BY id DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Manage Members</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    body {
      font-family: "Segoe UI", Arial, sans-serif;
      background: #f3f4f6;
      margin: 0;
      padding: 0;
    }

    header {
  background: #ffffff; /* White background */
  color: #111827; /* Dark text */
  padding: 15px 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05); /* Optional: light shadow for depth */
}

header h2 {
  margin: 0;
  font-weight: 600;
  letter-spacing: 0.5px;
  color: #111827; /* Make heading text black */
}


    .home-btn {
  color: #111827; /* Black text */
  text-decoration: none;
  font-weight: 1000;
  transition: 0.2s;
  opacity: 0.8;
  font-size: 25px;
}
.home-btn:hover {
  opacity: 1;
}
/* Icon buttons */
/* Minimal icon-only buttons */
.icon-btn {
    font-size: 20px;
    margin-right: 10px;
    cursor: pointer;
    text-decoration: none;
    transition: 0.2s ease;
}

/* Colors for each icon */
.icon-renew { color: #16a34a; }   /* green */
.icon-edit { color: #0a2d7a; }    /* blue */
.icon-delete { color: #ef4444; }  /* red */

/* Hover animations */
.icon-btn:hover {
    transform: scale(1.25);
    opacity: 0.8;
}


    main {
      padding: 30px;
    }

    .alert {
      padding: 12px 18px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 15px;
      max-width: 600px;
      box-shadow: 0 3px 8px rgba(0,0,0,0.08);
      animation: fadeIn 0.4s ease-in;
      opacity: 1;
      transition: opacity 0.5s ease;
    }
    .alert.success {
      background: #dcfce7;
      color: #166534;
      border-left: 5px solid #22c55e;
    }
    .alert.info {
      background: #dbeafe;
      color: #1e3a8a;
      border-left: 5px solid #3b82f6;
    }
    .alert.danger {
      background: #fee2e2;
      color: #991b1b;
      border-left: 5px solid #ef4444;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-5px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .top-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .add-btn {
      background: #020001;
    }

    .btn {
      padding: 8px 14px;
      border-radius: 6px;
      color: white;
      text-decoration: none;
      font-weight: 500;
      transition: 0.2s ease;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    .edit-btn { background: #0a2d7aff;; }
    .del-btn { background: #ef4444; }
    .renew-btn { background: #16a34a; }
    .renew-btn:hover { background: #15803d; }

    table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    th {
      background: #0a2d7aff;
      color: white;
      text-align: left;
      padding: 14px;
      font-size: 15px;
      letter-spacing: 0.3px;
    }

    td {
      padding: 12px 14px;
      border-bottom: 1px solid #f1f1f1;
      font-size: 17px;
      color: #374151;
      font-weight: 500;
    }

    tr:hover {
      background: #f9fafb;
    }

    .status-active {
      color: #10b981;
      font-weight: bold;
    }
    .status-expired {
      color: #ef4444;
      font-weight: bold;
    }
  </style>
</head>
<body>

<header>
  <h2>Manage Members</h2>
  <a href="dashboard.php" class="home-btn"> Home</a>
</header>

<main>

<?php
// Flash success messages
if (isset($_GET['added'])) {
    echo "<div class='alert success'>✅ Member added successfully!</div>";
}
if (isset($_GET['updated'])) {
    echo "<div class='alert info'>🔄 Member updated successfully!</div>";
}
if (isset($_GET['deleted'])) {
    echo "<div class='alert danger'>❌ Member deleted successfully!</div>";
}
if (isset($_GET['renewed'])) {
    echo "<div class='alert success'>♻️ Member renewed successfully!</div>";
}
?>

<div class="top-bar">
  <a href="add_member.php" class="btn add-btn">+ Add Member</a>
</div>

<table>
  <tr>
    <th>ID</th>
    <th>Name</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Plan</th>
    <th>Start Date</th>
    <th>End Date</th>
    <th>Status</th>
    <th>Total Paid (₵)</th>
    <th>Actions</th>
  </tr>

<?php while($row = $result->fetch_assoc()): ?>
  <tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td><?= htmlspecialchars($row['phone']) ?></td>
    <td><?= ucfirst($row['membership_type']) ?></td>
    <td><?= $row['start_date'] ?></td>
    <td><?= $row['end_date'] ?></td>
    <td class="status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></td>
    <td>₵<?= number_format($row['total_paid'], 2) ?></td>
    <td>
    <a href="renew.php?id=<?= $row['id'] ?>" class="icon-btn icon-renew" title="Renew">
        <i class="fa-solid fa-rotate-right"></i>
    </a>

    <a href="edit_member.php?id=<?= $row['id'] ?>" class="icon-btn icon-edit" title="Edit">
        <i class="fa-solid fa-pen-to-square"></i>
    </a>

    <a href="delete_member.php?id=<?= $row['id'] ?>" 
       class="icon-btn icon-delete" 
       title="Delete"
       onclick="return confirm('Delete this member?')">
        <i class="fa-solid fa-trash"></i>
    </a>
</td>

  </tr>
<?php endwhile; ?>
</table>

</main>

<script>
// Fade + slide-up removal after 4 seconds
setTimeout(() => {
  document.querySelectorAll('.alert').forEach(alert => {
    alert.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    alert.style.opacity = '0';
    alert.style.transform = 'translateY(-20px)';
    setTimeout(() => alert.remove(), 600);
  });
}, 4000);
</script>
</body>
</html>
