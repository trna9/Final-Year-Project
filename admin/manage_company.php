<?php
session_start();
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location:../login.html");
    exit;
}

require_once '../db.php';

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Admin';
$profile_pic = '../img/admin_avatar.jpg';

// Fetch approved companies
$approved_sql = "SELECT * FROM company WHERE status='approved' ORDER BY company_name ASC";
$approved_result = $conn->query($approved_sql);
$approved_companies = $approved_result->fetch_all(MYSQLI_ASSOC);
$total_approved = count($approved_companies);

// Fetch pending companies
$pending_sql = "SELECT * FROM company WHERE status='pending' ORDER BY company_name ASC";
$pending_result = $conn->query($pending_sql);
$pending_companies = $pending_result->fetch_all(MYSQLI_ASSOC);
$total_pending = count($pending_companies);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Companies | FYP System</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Keep your existing CSS exactly as it is */
body { margin:0; font-family:'Nunito',sans-serif; background-color:#F5F5F8; }

header {
  background-color:#9F5EB7; color:white;
  padding:16px 24px; display:flex; align-items:center;
  justify-content:space-between; position:relative;
}
header h1 { margin:0; font-size:22px; font-weight:800; position:absolute; left:50%; transform:translateX(-50%); }
.welcome { margin-left:auto; display:flex; align-items:center; gap:10px; }
.hamburger { font-size:24px; cursor:pointer; background:none; border:none; color:white; }
.profile-pic { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid white; }

.sidebar {
  height: 100%; width: 0; position: fixed; top: 0; left: 0;
  background: linear-gradient(180deg, #2e2040, #1b1524);
  overflow-x: hidden; transition: 0.4s; padding-top: 80px;
  border-top-right-radius: 20px; border-bottom-right-radius: 20px;
  box-shadow: 4px 0 16px rgba(0, 0, 0, 0.4); z-index: 1000;
}
.sidebar a { padding: 12px 20px; margin: 8px 16px; text-decoration: none; font-size: 16px; color: #f2f2f2; display: flex; align-items: center; gap: 10px; border-radius: 30px; transition: 0.3s, transform 0.2s, color 0.3s; }
.sidebar a:hover { background: rgba(159, 94, 183, 0.2); color: #d8b4f8; transform: translateX(5px); }
.sidebar a i { font-size: 20px; width: 28px; text-align: center; background: linear-gradient(135deg, #9F5EB7, #6A3A8D); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(1px 1px 2px rgba(0,0,0,0.3)); transition: transform 0.2s ease; }
.sidebar a:hover i { transform: scale(1.2) rotate(-5deg); }
.sidebar .closebtn { position: absolute; top: 10px; right: 10px; font-size: 24px; color: white; cursor: pointer; }

.table-container { max-width:1100px; margin:40px auto 40px; background:white; padding:20px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
.table-top-row { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
.badge { display:inline-block; background:#E8E0F0; color:#3a2e4d; font-weight:700; padding:8px 15px; border-radius:12px; font-size:16px; }
.add-btn { display:inline-block; background: #9F5EB7; color:white; padding:10px 20px; border-radius:10px; font-weight:700; text-decoration:none; }
.add-btn:hover { background:#813c99; }
table { width:100%; border-collapse:collapse; margin-top:10px;}
th, td { padding:12px; border-bottom:1px solid #ddd; text-align:left; }
th { background:#E8E0F0; font-weight:800; color:#3a2e4d; }
.action-btn { display:inline-block; border:none; padding:6px 12px; border-radius:8px; font-size:13px; cursor:pointer; font-weight:700; font-family:'Nunito',sans-serif; text-decoration:none; transition:0.3s; margin-right:4px; }
.edit { background: #2196f3; color:white; }
.delete { background:#f44336; color:white; }
.approve { background: #4CAF50; color:white; }
.reject { background: #f44336; color:white; }
.action-btn:hover { transform: scale(1.05); }
.no-data { text-align:center; padding:20px; font-size:16px; color:#555; }
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
  <h1>Manage Companies</h1>
  <div class="welcome">
    Hi, <?php echo htmlspecialchars($name); ?>
    <a href="staff_profile.php"><img src="<?php echo htmlspecialchars($profile_pic); ?>" class="profile-pic"></a>
  </div>
</header>

<!-- Approved Companies Table -->
<div class="table-container">
  <div class="table-top-row">
    <a href="add_company.php" class="add-btn"><i class="fas fa-plus"></i> Add New Company</a>
    <span class="badge">Total Companies: <?php echo $total_approved; ?></span>
  </div>

  <?php if($total_approved == 0): ?>
      <p class="no-data">No approved companies found.</p>
  <?php else: ?>
  <table>
    <tr>
      <th>Company Name</th>
      <th>City</th>
      <th>Nature</th>
      <th>Focus Area</th>
      <th>Actions</th>
    </tr>
    <?php foreach($approved_companies as $c): ?>
    <tr>
      <td><?php echo htmlspecialchars($c['company_name']); ?></td>
      <td><?php echo htmlspecialchars($c['city']); ?></td>
      <td><?php echo htmlspecialchars($c['nature']); ?></td>
      <td><?php echo htmlspecialchars($c['focus_area']); ?></td>
      <td>
        <a href="edit_company.php?id=<?php echo $c['company_id']; ?>" class="action-btn edit">Edit</a>
        <a href="delete_company.php?id=<?php echo $c['company_id']; ?>" class="action-btn delete" onclick="return confirm(' Are you sure you want to delete this company?');">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<!-- Pending Companies Table -->
<div class="table-container">
  <div class="table-top-row">
    <span class="badge">Companies Pending Approval: <?php echo $total_pending; ?></span>
  </div>

  <?php if($total_pending == 0): ?>
      <p class="no-data">No pending companies.</p>
  <?php else: ?>
  <table>
    <tr>
      <th>Company Name</th>
      <th>City</th>
      <th>Nature</th>
      <th>Focus Area</th>
      <th>Actions</th>
    </tr>
    <?php foreach($pending_companies as $c): ?>
    <tr>
      <td><?php echo htmlspecialchars($c['company_name']); ?></td>
      <td><?php echo htmlspecialchars($c['city']); ?></td>
      <td><?php echo htmlspecialchars($c['nature']); ?></td>
      <td><?php echo htmlspecialchars($c['focus_area']); ?></td>
      <td>
        <a href="approve_company.php?id=<?php echo $c['company_id']; ?>" class="action-btn approve">Approve</a>
        <a href="delete_company.php?id=<?php echo $c['company_id']; ?>" class="action-btn reject" onclick="return confirm('Are you sure you want to reject this company?');">Reject</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</div>

<script>
function openSidebar(){document.getElementById("mySidebar").style.width="260px";}
function closeSidebar(){document.getElementById("mySidebar").style.width="0";}
</script>
</body>
</html>
