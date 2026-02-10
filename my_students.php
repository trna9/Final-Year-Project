<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STAFF') {
    header("Location: login.html");
    exit;
}

$staff_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Staff';

// Fetch staff profile picture
$profileQuery = $conn->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
$profileQuery->bind_param("s", $staff_id);
$profileQuery->execute();
$profileResult = $profileQuery->get_result();
if ($profileResult->num_rows > 0) {
    $profileRow = $profileResult->fetch_assoc();
    $profilePicture = !empty($profileRow['profile_picture']) ? $profileRow['profile_picture'] : 'uploads/default_profile.png';
} else {
    $profilePicture = 'uploads/default_profile.png';
}

// Fetch staff role
$roleQuery = $conn->prepare("SELECT role FROM staff_role WHERE staff_id = ?");
$roleQuery->bind_param("s", $staff_id);
$roleQuery->execute();
$roleResult = $roleQuery->get_result();
$staff_role = ($roleResult->num_rows > 0) ? $roleResult->fetch_assoc()['role'] : '';

$accessDenied = ($staff_role !== 'SUPERVISOR');

// Fetch students assigned to this supervisor
$assignedStudents = [];
if (!$accessDenied) {
    $stmt = $conn->prepare("
        SELECT st.student_id, u.name AS student_name, st.program_code
        FROM student st
        JOIN user u ON u.user_id = st.student_id
        WHERE st.supervisor_id = ?
        ORDER BY u.name ASC
    ");
    $stmt->bind_param("s", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $assignedStudents[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Students | FYP System</title>
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
header h1 { font-size:22px; font-weight:800; position:absolute; left:50%; transform:translateX(-50%); margin:0; }
.welcome { display:flex; align-items:center; gap:10px; margin-left:auto; }
.welcome img { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid white; }

.sidebar { height:100%; width:0; position:fixed; top:0; left:0; background:linear-gradient(180deg,#2e2040,#1b1524); overflow-x:hidden; transition:0.4s; padding-top:80px; border-top-right-radius:20px; border-bottom-right-radius:20px; box-shadow:4px 0 16px rgba(0,0,0,0.4); z-index:1000; }
.sidebar a { display:flex; align-items:center; gap:10px; padding:12px 20px; margin:8px 16px; font-size:16px; color:#f2f2f2; border-radius:30px; transition:0.3s, transform 0.2s, color 0.3s; }
.sidebar a i { font-size:20px; width:28px; text-align:center; background:linear-gradient(135deg,#9F5EB7,#6A3A8D); -webkit-background-clip:text; -webkit-text-fill-color:transparent; filter:drop-shadow(1px 1px 2px rgba(0,0,0,0.3)); }
.sidebar a:hover { background: rgba(159,94,183,0.2); color:#d8b4f8; transform:translateX(5px); }
.sidebar .closebtn { position:absolute; top:10px; right:10px; font-size:24px; color:white; cursor:pointer; }
.hamburger { font-size:24px; cursor:pointer; background:none; border:none; color:white; }

.container { max-width:1000px; margin:40px auto; padding:0 20px; }

table { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
th, td { padding:14px 16px; text-align:left; }
th { background:#9F5EB7; color:white; font-weight:700; text-transform:uppercase; font-size:14px; }
tr:nth-child(even) { background:#faf7ff; }
tr:hover { background:#f0e5ff; }

.return-btn {
    display:inline-block; background:#9F5EB7; color:white; padding:8px 12px; border-radius:6px; border:none;
    font-weight:600; cursor:pointer; transition:0.3s;
}
.return-btn:hover { background:#E09D46; transform:scale(1.05); }

.note-box { display:flex; align-items:center; gap:10px; background:#EDE0F8; border-left:6px solid #9F5EB7; padding:12px 16px; border-radius:6px; margin-bottom:20px; color:#9F5EB7; font-weight:600; }
.note-box i { font-size:20px; }

.empty-msg { text-align:center; font-size:16px; color:#555; margin-top:30px; }

.action-icon {
    display:inline-block;
    margin-right:10px;
    font-size:18px;
    color:#9F5EB7;
    transition:0.3s;
}
.action-icon:hover {
    color:#E09D46;
    transform:scale(1.2);
}
</style>
</head>
<body>

<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="closeSidebar()">&times;</a>
  <a href="staff_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
  <a href="my_students.php"><i class="fas fa-users"></i> My Students</a>
  <a href="companies.php"><i class="fas fa-building"></i> Companies</a>
  <a href="company_ranking.php"><i class="fas fa-star"></i> Company Ranking</a>
  <a href="login.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
</div>

<header>
  <button class="hamburger" onclick="window.location.href='staff_dashboard.php'">
    <i class="fas fa-arrow-left"></i>
  </button>
  <h1>My Students</h1>
  <div class="welcome">
    Hi, <?= htmlspecialchars($name) ?>
    <img src="<?= htmlspecialchars($profilePicture) ?>" alt="Profile">
  </div>
</header>

<div class="container">

<?php if ($accessDenied): ?>
    <div class="note-box" style="background:#FFE0E0; border-left:6px solid #E05B5B; color:#E05B5B;">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Oops! You need supervisor privileges to view students.</span>
    </div>
    <div style="text-align:center; margin-top:20px;">
        <a href="staff_dashboard.php" class="return-btn">Return to Dashboard</a>
    </div>
<?php else: ?>

    <div class="note-box">
        <i class="fas fa-info-circle"></i>
        <span>All students currently under your supervision are listed below.</span>
    </div>

    <?php if (empty($assignedStudents)): ?>
        <p class="empty-msg">No students are assigned to you yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Program Code</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignedStudents as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['student_id']) ?></td>
                    <td><?= htmlspecialchars($s['student_name']) ?></td>
                    <td><?= htmlspecialchars($s['program_code']) ?></td>
                    <td>
                        <a href="profile_view.php?id=<?= $s['student_id'] ?>" title="Go to Profile" class="action-icon"><i class="fas fa-user"></i></a>
                        <a href="view_crs.php?id=<?= $s['student_id'] ?>" title="View CRS" class="action-icon"><i class="fas fa-seedling"></i></a>
                        <a href="staff_whitelist.php?id=<?= $s['student_id'] ?>" title="View Whitelist" class="action-icon"><i class="fas fa-list"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php endif; ?>

</div>

<script>
function openSidebar() { document.getElementById("mySidebar").style.width="250px"; }
function closeSidebar() { document.getElementById("mySidebar").style.width="0"; }
</script>

</body>
</html>
