<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

include('../includes/db.php');

// 1) Auto-update expired members BEFORE computing stats (fixes dashboard counts)
date_default_timezone_set('Africa/Accra');
$conn->query("UPDATE members SET status='expired' WHERE end_date < CURDATE() AND status != 'expired'");

// 2) Fetch Stats (after update)
$totalMembers   = (int)$conn->query("SELECT COUNT(*) AS total FROM members")->fetch_assoc()['total'];
$activeMembers  = (int)$conn->query("SELECT COUNT(*) AS active FROM members WHERE status='active'")->fetch_assoc()['active'];
$expiredMembers = (int)$conn->query("SELECT COUNT(*) AS expired FROM members WHERE status='expired'")->fetch_assoc()['expired'];
$totalRevenue   = $conn->query("SELECT SUM(total_paid) AS total FROM payments")->fetch_assoc()['total'] ?? 0;

// 3) attendance today (count of check-ins recorded today)
$attendanceToday = (int)$conn->query("SELECT COUNT(*) AS today FROM attendance WHERE DATE(check_in_time) = CURDATE()")->fetch_assoc()['today'];

// 4) Chart data (members per month)
$chartData = [];
$result = $conn->query("SELECT MONTH(start_date) AS month, COUNT(*) AS count FROM members GROUP BY MONTH(start_date) ORDER BY MONTH(start_date)");
while ($row = $result->fetch_assoc()) {
    $chartData[] = $row;
}

// 5) Search (keeps previous behavior) and fetch up to 8 recent members for the dashboard preview
$search = "";
if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    $members = $conn->query("SELECT * FROM members WHERE name LIKE '%" . $conn->real_escape_string($search) . "%' OR email LIKE '%" . $conn->real_escape_string($search) . "%' ORDER BY start_date DESC LIMIT 8");
} else {
    $members = $conn->query("SELECT * FROM members ORDER BY start_date DESC LIMIT 8");
}

// 6) Fetch up to 8 recent attendance rows for the dashboard preview
$attendance = $conn->query("
    SELECT m.name,
           DATE(a.check_in_time) AS attendance_date,
           TIME(a.check_in_time) AS check_in_time
    FROM attendance a
    JOIN members m ON a.member_id = m.id
    ORDER BY a.check_in_time DESC
    LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Admin • Dashboard</title>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  :root{
    --bg: #f9fafb;
    --card: #ffffff;
    --muted: #6b7280;
    --text: #111827;
    --accent: #0a2d7a;
    --accent-2: #10b981;
    --glass: rgba(255,255,255,0.6);
    --radius: 12px;
  }
  .dark {
    --bg: #0b1220;
    --card: #0f1724;
    --muted: #9aa4b2;
    --text: #e6eef7;
    --accent: #6c9ef8;
    --accent-2: #22c55e;
    --glass: rgba(255,255,255,0.03);
  }

  * { box-sizing: border-box; }
  body {
    margin: 0;
    font-family: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial;
    background: var(--bg);
    color: var(--text);
    overflow-x: hidden; /* prevent horizontal scrolling */
  }

  .app {
  display: flex;
  height: 100vh;            /* lock to viewport */
  overflow: hidden;         /* prevent whole-page scroll */
  }


  /* Sidebar */
  .sidebar {
    width: 260px;
    background: linear-gradient(180deg, var(--card), var(--glass));
    border-right: 1px solid rgba(0,0,0,0.04);
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 18px;
    transition: width .28s ease, transform .28s ease;
    box-shadow: 0 6px 18px rgba(16,24,40,0.04);
    height: 100vh;            /* full viewport height */
    overflow: hidden;         /* sidebar never scrolls */
    position: sticky;
    top: 0;
    
     
  
  }
  .sidebar.collapsed { width: 72px; }
  .brand { display:flex; align-items:center; gap:12px; }
  .brand .logo { height:44px; width:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; background: linear-gradient(135deg,var(--accent),var(--accent-2)); color:white; font-weight:700; box-shadow: 0 6px 18px rgba(10,45,122,0.12); }
  .brand h3 { margin:0; font-size:18px; letter-spacing:0.2px; }
  .nav { display:flex; flex-direction:column; gap:8px; margin-top:6px; }
  .nav a { display:flex; gap:12px; align-items:center; padding:10px 12px; border-radius:10px; color:var(--muted); text-decoration:none; transition: background .18s, color .18s, transform .12s; font-weight:600; }
  .nav a:hover { background: rgba(0,0,0,0.04); color:var(--text); transform:translateY(-2px); }
  .nav a i { min-width:22px; text-align:center; font-size:16px; color:var(--accent); }
  .sidebar.collapsed .nav a span { display:none; }
  .sidebar.collapsed .brand h3 { display:none; }
  .sidebar .toggle { margin-top:auto; display:flex; gap:8px; align-items:center; justify-content:center; }
  .toggle button { background: transparent; border:1px solid rgba(0,0,0,0.06); padding:8px; border-radius:10px; cursor:pointer; color:var(--muted); }
  .sidebar.collapsed .toggle button span { display:none; }

  /* Main */
  .main { flex:1; padding:28px; transition: margin-left .28s; overflow-x: hidden; overflow-y: auto; scroll-behavior: smooth; }
  .topbar { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:18px; }
  .top-left { display:flex; align-items:center; gap:12px; }
  .search { display:flex; gap:8px; align-items:center; }
  .search input { padding:10px 12px; border-radius:10px; border:1px solid rgba(0,0,0,0.06); background: var(--card); color:var(--text); min-width:200px; }

  .actions { display:flex; gap:10px; align-items:center; }
  .btn { background: var(--accent); color: white; padding:10px 14px; border-radius:10px; text-decoration:none; font-weight:600; display:inline-flex; gap:8px; align-items:center; }
  .btn.ghost { background: transparent; color:var(--text); border:1px solid rgba(0,0,0,0.06); }

  /* Cards */
  .cards { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:18px; margin-bottom:20px; }
  .card { background: var(--card); padding:16px; border-radius:12px; box-shadow: 0 6px 18px rgba(2,6,23,0.04); transition: transform .16s ease, box-shadow .16s ease; }
  .card:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(2,6,23,0.06); }
  .card h3 { margin:0; color:var(--muted); font-size:13px; }
  .card p { font-size:20px; margin:8px 0 0; font-weight:700; color:var(--text); }

  /* Panel & Chart sizing (reduced height + max width) */
  .panel { background: var(--card); padding:18px; border-radius:12px; margin-bottom:18px; box-shadow: 0 6px 18px rgba(2,6,23,0.03); }
  .panel h2 { margin:0 0 12px 0; font-size:18px; color:var(--text); }
  .chart-wrap { max-width: 100%; width:100%; display:block; }
  /* reduce chart vertical height and cap width so it doesn't stretch too far */
  #membershipChart { width:100% !important; max-width:900px; height:280px !important; display:block; margin:0 auto; }

  table { width:100%; border-collapse:collapse; font-size:14px; }
  th, td { padding:10px 8px; text-align:left; border-bottom:1px solid rgba(0,0,0,0.06); color:var(--muted); }
  th { background: rgba(201, 81, 81, 0.02); color: #c84545ff; font-weight:700; }
  td { color:var(--text); font-weight:600; }

  .small { font-size:13px;color:var(--muted); }
  .pill { padding:6px 10px; border-radius:999px; background: rgba(10,45,122,0.08); color:var(--accent); font-weight:700; }

  .logout { background:#ef4444; color:white; padding:8px 12px; border-radius:8px; text-decoration:none; font-weight:700; }

  /* small helpers */
  .muted { color: var(--muted); font-size:13px; }
  .icon-btn { background:transparent; border:none; cursor:pointer; color:var(--muted); padding:8px; border-radius:8px; }
  .icon-btn:hover { color:var(--text); transform:translateY(-3px); }

  /* "show more" link */
  .show-more { display:inline-flex; gap:8px; align-items:center; color:var(--accent); cursor:pointer; font-weight:700; margin-top:8px; }

  /* Responsive */
  @media (max-width: 900px) {
    .sidebar { position: fixed; left:0; top:0; bottom:0; z-index:50; transform: translateX(-120%); }
    .sidebar.open { transform: translateX(0); }
    .sidebar.collapsed { width: 260px; }
    #membershipChart { height:220px !important; }
  }
</style>
</head>
<body>
<div class="app" id="appRoot">
  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="logo">GM</div>
      <h3>Gym Manager</h3>
    </div>

    <nav class="nav">
      <a href="dashboard.php"><i class="fa fa-home"></i> <span>Dashboard</span></a>
      <a href="manage_members.php"><i class="fa fa-users"></i> <span>Members</span></a>
      <a href="attendance.php"><i class="fa fa-calendar-check"></i> <span>Attendance</span></a>
      <a href="reports.php" title="Export Revenue"><i class="fa fa-file-export"></i> <span>Export</span></a>
      <a href="#" onclick="alert('Future: Reports page')" title="Reports"><i class="fa fa-chart-line"></i> <span>Reports</span></a>
    </nav>

    <div class="toggle">
      <button id="collapseBtn" title="Collapse sidebar"><i class="fa fa-angle-double-left"></i> <span>Collapse</span></button>
      <button id="mobileOpenBtn" style="display:none;"><i class="fa fa-bars"></i></button>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <div class="topbar">
      <div class="top-left">
        <button id="sidebarToggle" class="icon-btn" title="Toggle sidebar"><i class="fa fa-bars"></i></button>
        <div>
          <h1 style="margin:0;font-size:20px;">Welcome, Admin</h1>
          <div class="muted">Dashboard overview</div>
        </div>
      </div>

      <div class="actions">
        <form method="GET" class="search" style="margin-right:8px;">
          <input name="search" type="text" placeholder="Search members..." value="<?= htmlspecialchars($search) ?>">
          <button class="btn ghost" type="submit"><i class="fa fa-search"></i></button>
        </form>

        <form action="export_revenue.php" method="POST" style="margin-right:8px;">
          <button class="btn" type="submit"><i class="fa fa-file-excel"></i> Export Excel</button>
        </form>

        <button id="themeToggle" class="btn ghost" title="Toggle dark mode"><i class="fa fa-moon"></i></button>

        <a href="../auth/logout.php" class="logout">Logout</a>
      </div>
    </div>

    <!-- Cards -->
    <section class="cards" aria-label="summary cards">
      <div class="card">
        <h3>Total Members</h3>
        <p><?= $totalMembers ?></p>
        <div class="muted small">All members in the system</div>
      </div>

      <div class="card">
        <h3>Active Members</h3>
        <p><?= $activeMembers ?></p>
        <div class="muted small">Currently active</div>
      </div>

      <div class="card">
        <h3>Expired Members</h3>
        <p><?= $expiredMembers ?></p>
        <div class="muted small">Require renewal</div>
      </div>

      <div class="card">
        <h3>Total Revenue</h3>
        <p>₵<?= number_format($totalRevenue,2) ?></p>
        <div class="muted small">All payments recorded</div>
      </div>

      <div class="card">
        <h3>Attendance Today</h3>
        <p><?= $attendanceToday ?></p>
        <div class="muted small">Today's check-ins</div>
      </div>
    </section>

    <!-- Chart -->
    <section class="panel">
      <h2>Membership Trend (by month)</h2>
      <div class="chart-wrap">
        <canvas id="membershipChart"></canvas>
      </div>
    </section>

    <!-- Tables -->
    <section class="panel" style="display:flex; gap:18px; flex-direction:column;">
      <div>
        <h2 style="margin-bottom:8px;">Recent Members</h2>
        <div style="overflow:auto;">
          <table id="membersTable">
            <thead>
              <tr><th>Name</th><th>Email</th><th>Status</th><th>Join Date</th><th>Type</th></tr>
            </thead>
            <tbody>
              <?php
              $mIndex = 0;
              while($row = $members->fetch_assoc()):
                  $mIndex++;
                  $hidden = ($mIndex > 3) ? 'data-hidden="1" style="display:none;"' : '';
              ?>
              <tr <?= $hidden ?>>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><span class="pill"><?= ucfirst($row['status']) ?></span></td>
                <td><?= htmlspecialchars($row['start_date']) ?></td>
                <td><?= htmlspecialchars($row['membership_type']) ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <?php if ($mIndex > 3): ?>
          <div class="show-more" id="membersShowMore"><i class="fa fa-angle-down"></i><span>Show more</span></div>
        <?php endif; ?>
      </div>

      <div>
        <h2 style="margin-bottom:8px;">Recent Attendance</h2>
        <div style="overflow:auto;">
          <table id="attendanceTable">
            <thead>
              <tr><th>Member</th><th>Date</th><th>Check-in Time</th></tr>
            </thead>
            <tbody>
              <?php
              $aIndex = 0;
              while($row = $attendance->fetch_assoc()):
                  $aIndex++;
                  $hiddenA = ($aIndex > 3) ? 'data-hidden="1" style="display:none;"' : '';
              ?>
              <tr <?= $hiddenA ?>>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['attendance_date']) ?></td>
                <td><?= htmlspecialchars($row['check_in_time']) ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <?php if ($aIndex > 3): ?>
          <div class="show-more" id="attendanceShowMore"><i class="fa fa-angle-down"></i><span>Show more</span></div>
        <?php endif; ?>
      </div>
    </section>

  </main>
</div>

<script>
  // CHART SETUP (reduced size)
  const chartData = <?= json_encode($chartData) ?>;
  const labels = chartData.length ? chartData.map(d => 'Month ' + d.month) : [];
  const data = chartData.length ? chartData.map(d => d.count) : [];

  const ctx = document.getElementById('membershipChart').getContext('2d');
  const membershipChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'New Members',
        data: data,
        borderColor: getComputedStyle(document.documentElement).getPropertyValue('--accent').trim() || '#6366f1',
        backgroundColor: 'transparent',
        tension: 0.3,
        pointRadius: 4,
        pointHoverRadius: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { maxRotation: 0, minRotation: 0 } },
        y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } }
      },
      layout: { padding: { left: 0, right: 0, top: 6, bottom: 0 } }
    }
  });

  // SIDEBAR collapse / toggles
  const sidebar = document.getElementById('sidebar');
  const collapseBtn = document.getElementById('collapseBtn');
  const sidebarToggle = document.getElementById('sidebarToggle');
  collapseBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    collapseBtn.querySelector('i').classList.toggle('fa-angle-double-left');
    collapseBtn.querySelector('i').classList.toggle('fa-angle-double-right');
  });
  sidebarToggle.addEventListener('click', () => {
    if (window.innerWidth <= 900) {
      sidebar.classList.toggle('open');
    } else {
      sidebar.classList.toggle('collapsed');
    }
  });

  // THEME toggle (keeps behavior)
  const themeToggle = document.getElementById('themeToggle');
  function applyTheme(isDark){ if(isDark) document.documentElement.classList.add('dark'); else document.documentElement.classList.remove('dark'); }
  const saved = localStorage.getItem('gd_theme'); applyTheme(saved === 'dark');
  themeToggle.addEventListener('click', () => {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('gd_theme', isDark ? 'dark' : 'light');
    themeToggle.innerHTML = isDark ? '<i class="fa fa-sun"></i>' : '<i class="fa fa-moon"></i>';
  });
  themeToggle.innerHTML = document.documentElement.classList.contains('dark') ? '<i class="fa fa-sun"></i>' : '<i class="fa fa-moon"></i>';

  // SHOW MORE logic for Members (3 -> up to 8)
  const membersShow = document.getElementById('membersShowMore');
  if (membersShow) {
    membersShow.addEventListener('click', () => {
      const rows = document.querySelectorAll('#membersTable tbody tr[data-hidden="1"]');
      const hidden = Array.from(rows).filter(r => r.style.display === 'none');
      if (hidden.length > 0) {
        // reveal them
        hidden.forEach(r => r.style.display = '');
        membersShow.innerHTML = '<i class="fa fa-angle-up"></i><span>Show less</span>';
      } else {
        // hide them
        rows.forEach(r => r.style.display = 'none');
        membersShow.innerHTML = '<i class="fa fa-angle-down"></i><span>Show more</span>';
      }
    });
  }

  // SHOW MORE logic for Attendance (3 -> up to 8)
  const attendShow = document.getElementById('attendanceShowMore');
  if (attendShow) {
    attendShow.addEventListener('click', () => {
      const rows = document.querySelectorAll('#attendanceTable tbody tr[data-hidden="1"]');
      const hidden = Array.from(rows).filter(r => r.style.display === 'none');
      if (hidden.length > 0) {
        hidden.forEach(r => r.style.display = '');
        attendShow.innerHTML = '<i class="fa fa-angle-up"></i><span>Show less</span>';
      } else {
        rows.forEach(r => r.style.display = 'none');
        attendShow.innerHTML = '<i class="fa fa-angle-down"></i><span>Show more</span>';
      }
    });
  }

  // Close sidebar on outside click for mobile
  document.addEventListener('click', (e) => {
    if (window.innerWidth <= 900 && !sidebar.contains(e.target) && !e.target.closest('#sidebarToggle')) {
      sidebar.classList.remove('open');
    }
  });
</script>
</body>
</html>
