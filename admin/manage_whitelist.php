<?php
session_start();
require_once '../db.php';

// Only allow admin access
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: ../login.html");
    exit;
}

$admin_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Admin';

// static profile pic for admin
$profile_pic = '../img/admin_avatar.jpg';

// Fetch whitelist submissions
$query = "
    SELECT 
        u.user_id,
        u.name AS student_name,
        c.company_name,
        sc.approval_status,
        b.submission_id,
        b.submitted_on
    FROM selected_company sc
    JOIN bli01_form b ON sc.submission_id = b.submission_id
    JOIN student s ON b.student_id = s.student_id
    JOIN user u ON u.user_id = s.student_id
    JOIN company c ON sc.company_id = c.company_id
    ORDER BY b.submitted_on DESC, u.name ASC
";

$result = $conn->query($query);
$whitelist = [];
while ($row = $result->fetch_assoc()) {
    $whitelist[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Whitelist | FYP System</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { margin:0; font-family:'Nunito',sans-serif; background:#f5f5f5; color:#333; }
a { text-decoration:none; color:inherit; }

header {
    background-color:#9F5EB7;
    color:white;
    padding:16px 24px;
    display:flex;
    align-items:center;
    position:relative;
    z-index:100;
}
header h1 {
    font-size:22px;
    font-weight:800;
    position:absolute;
    left:50%;
    transform:translateX(-50%);
    margin:0;
}
.welcome { display:flex; align-items:center; gap:10px; margin-left:auto; }
.welcome img {
    width:40px; height:40px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid white;
}

   .sidebar {
      height: 100%;
      width: 0;
      position: fixed;
      top: 0;
      left: 0;
      background: linear-gradient(180deg, #2e2040, #1b1524);
      overflow-x: hidden;
      transition: 0.4s;
      padding-top: 80px;
      border-top-right-radius: 20px;
      border-bottom-right-radius: 20px;
      box-shadow: 4px 0 16px rgba(0, 0, 0, 0.4);
      z-index: 1000;
    }
    .sidebar a {
      padding: 12px 20px;
      margin: 8px 16px;
      text-decoration: none;
      font-size: 16px;
      color: #f2f2f2;
      display: flex;
      align-items: center;
      gap: 10px;
      border-radius: 30px;
      transition: 0.3s, transform 0.2s, color 0.3s;
    }
    .sidebar a:hover {
      background: rgba(159, 94, 183, 0.2);
      color: #d8b4f8;
      transform: translateX(5px);
    }
    .sidebar a i {
      font-size: 20px;
      width: 28px;
      text-align: center;
      background: linear-gradient(135deg, #9F5EB7, #6A3A8D);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      filter: drop-shadow(1px 1px 2px rgba(0,0,0,0.3));
      transition: transform 0.2s ease;
    }
    .sidebar a:hover i {
      transform: scale(1.2) rotate(-5deg);
    }
    .sidebar .closebtn {
      position: absolute;
      top: 10px;
      right: 10px;
      font-size: 24px;
      color: white;
      cursor: pointer;
    }
.hamburger { font-size:24px; cursor:pointer; background:none; border:none; color:white; }

.container {
    max-width:1100px;
    margin:40px auto;
    padding:0 20px;
}

table {
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
th, td { padding:14px 16px; text-align:center; }
th {
    background:#9F5EB7;
    color:white;
    font-weight:700;
    text-transform:uppercase;
    font-size:14px;
}
tr:nth-child(even) { background:#faf7ff; }
tr:hover { background:#f0e5ff; }

.status {
    font-weight:700;
    text-transform:uppercase;
}
.status.PENDING { color:#E09D46; }
.status.APPROVED { color:green; }
.status.REJECTED { color:red; }

.empty-msg { text-align:center; font-size:16px; color:#555; margin-top:30px; }
</style>
</head>
<body>

<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="closeSidebar()">&times;</a>
  <a href="admin_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
  <a href="reports.php"><i class="fas fa-chart-column"></i> Reports</a>
  <a href="manage_users.php"><i class="fas fa-users-gear"></i> Manage Users</a>
  <a href="manage_company.php"><i class="fas fa-building"></i> Manage Companies</a>
  <a href="manage_whitelist.php"><i class="fas fa-list-check"></i> Manage Whitelist</a>
  <a href="manage_crs.php"><i class="fas fa-chart-line"></i> Manage CRS</a>
  <a href="manage_announcements.php"><i class="fas fa-bullhorn"></i> Manage Announcements</a>
  <a href="manage_feedback.php"><i class="fas fa-comment-dots"></i> Manage Feedback</a>
  <a href="../login.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
</div>

<header>
  <button class="hamburger" onclick="openSidebar()"><i class="fas fa-bars"></i></button>
  <h1>Manage Whitelist</h1>
  <div class="welcome">
    Hi, <?= htmlspecialchars($name) ?>
    <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile">
  </div>
</header>

<div class="container">
<?php if (empty($whitelist)): ?>
    <p class="empty-msg">No whitelist submissions found.</p>
<?php else: ?>
<table>
<thead>
<tr>
    <th>Student ID</th>
    <th>Student Name</th>
    <th>Company</th>
    <th>Status</th>
    <th>Submitted On</th>
</tr>
</thead>
<tbody>
<?php foreach ($whitelist as $row): ?>
<tr>
    <td><?= htmlspecialchars($row['user_id']) ?></td>
    <td><?= htmlspecialchars($row['student_name']) ?></td>
    <td><?= htmlspecialchars($row['company_name']) ?></td>
    <td class="status <?= htmlspecialchars($row['approval_status']) ?>">
        <?= htmlspecialchars($row['approval_status']) ?>
    </td>
    <td><?= htmlspecialchars($row['submitted_on']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</div>

<script>
function openSidebar(){document.getElementById("mySidebar").style.width="260px";}
function closeSidebar(){document.getElementById("mySidebar").style.width="0";}
</script>

</body>
</html>
