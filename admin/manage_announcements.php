<?php
session_start();
require_once '../db.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: ../login.html");
    exit;
}

$admin_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Admin';

// use static image for admin profile
$profile_pic = '../img/admin_avatar.jpg';

// Fetch all announcements
$stmt = $conn->prepare("
    SELECT a.announcement_id, a.title, a.content, a.posted_on, a.last_edited, a.attachment_url, 
           a.posted_by, a.visibility, u.name AS staff_name
    FROM announcements a
    JOIN user u ON a.posted_by = u.user_id
    ORDER BY a.posted_on DESC
");

$stmt->execute();
$result = $stmt->get_result();
$announcements = [];
while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Announcements | FYP System</title>
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
    max-width:1200px;
    margin:40px auto;
    padding:0 20px;
}

/* Table design */
table {
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
}
th, td {
    padding:14px 16px;
    text-align:center;
}
th {
    background:#9F5EB7;
    color:white;
    font-weight:700;
    text-transform:uppercase;
    font-size:14px;
}
tr:nth-child(even) { background:#faf7ff; }
tr:hover { background:#f0e5ff; }

.action-buttons {
    display:flex;
    justify-content:center;
    gap:8px;
}
.action-buttons a {
    padding:6px 10px;
    border-radius:8px;
    font-size:13px;
    font-weight:600;
    color:white;
    transition:0.3s;
}

.action-buttons a:hover {
    transform: scale(1.05);
}

.delete-btn { background:#f44336; }
.delete-btn:hover { background:#ff5e50; }

.empty-msg {
    text-align:center;
    font-size:16px;
    color:#555;
    margin-top:30px;
}
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
  <h1>Manage Announcements</h1>
  <div class="welcome">
    Hi, <?php echo htmlspecialchars($name); ?>
    <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile">
  </div>
</header>

<div class="container">
<?php if (empty($announcements)): ?>
    <p class="empty-msg">No announcements available.</p>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Content</th>
                <th>Posted By</th>
                <th>Posted On</th>
                <th>Last Edited</th>
                <th>Attachment</th>
                <th>Visibility</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($announcements as $a): ?>
            <tr>
                <td><?php echo htmlspecialchars($a['announcement_id']); ?></td>
                <td><?php echo htmlspecialchars($a['title']); ?></td>
                <td><?php echo htmlspecialchars($a['content']); ?></td>
                <td><?php echo htmlspecialchars($a['staff_name']); ?></td>
                <td><?php echo htmlspecialchars($a['posted_on']); ?></td>
                <td><?php echo htmlspecialchars($a['last_edited']); ?></td>
                <td>
                    <?php if($a['attachment_url']): ?>
                        <a href="<?php echo htmlspecialchars($a['attachment_url']); ?>" target="_blank">View</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($a['visibility']); ?></td>
                <td>
                    <div class="action-buttons">
                        <a href="delete_announcements.php?announcement_id=<?php echo urlencode($a['announcement_id']); ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this announcement?');">
                            Delete
                        </a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>

<script>
function openSidebar() { document.getElementById("mySidebar").style.width="260px"; }
function closeSidebar() { document.getElementById("mySidebar").style.width="0"; }
</script>

</body>
</html>
