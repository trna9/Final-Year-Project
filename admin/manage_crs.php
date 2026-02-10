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
$profile_pic = '../img/admin_avatar.jpg';

// Fetch CRS records
$query = "
    SELECT crs_id, student_id, score, generated_on, last_updated
    FROM career_readiness_score
    ORDER BY generated_on DESC
";
$result = $conn->query($query);
$crs_records = [];
while ($row = $result->fetch_assoc()) {
    $crs_records[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage CRS | FYP System</title>
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
.welcome img { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid white; }

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
.action-buttons a {
    display:inline-flex;
    align-items:center;
    gap:4px;
    margin:2px;
    padding:6px 12px;
    font-size:14px;
    font-weight:600;
    border-radius:6px;
    color:white;
    transition:0.2s;
    text-decoration:none;
}

.action-buttons a:hover {
    transform: scale(1.05);
}

.action-buttons a.download { background:#2196f3; }

.action-buttons a.delete { background:#f44336; }

.action-buttons i { font-size:14px; }


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
  <h1>Manage CRS</h1>
  <div class="welcome">
    Hi, <?= htmlspecialchars($name) ?>
    <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile">
  </div>
</header>

<div class="container">
<?php if (empty($crs_records)): ?>
    <p class="empty-msg">No CRS records found.</p>
<?php else: ?>
<table>
<thead>
<tr>
    <th>ID</th>
    <th>Student ID</th>
    <th>Score</th>
    <th>Generated On</th>
    <th>Last Updated</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($crs_records as $row): ?>
<tr>
    <td><?= htmlspecialchars($row['crs_id']) ?></td>
    <td><?= htmlspecialchars($row['student_id']) ?></td>
    <td><?= htmlspecialchars($row['score']) ?></td>
    <td><?= htmlspecialchars($row['generated_on']) ?></td>
    <td><?= htmlspecialchars($row['last_updated']) ?></td>
    <td class="action-buttons">
        <a href="download_crs.php?id=<?= $row['crs_id'] ?>" class="download">
            Download
        </a>
        <a href="delete_crs.php?id=<?= $row['crs_id'] ?>" class="delete" onclick="return confirm('Are you sure you want to delete this record?')">
            Delete
        </a>
    </td>
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
