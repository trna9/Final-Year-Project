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
$profile_pic = '../img/admin_avatar.jpg';

// Get student ID from GET
$student_id = $_GET['user_id'] ?? null;
if (!$student_id) {
    echo "No student selected.";
    exit;
}

// Fetch student basic info by joining user and student
$stmt = $conn->prepare("
    SELECT u.user_id, u.name, u.email, u.role, s.program_code, s.contact_number, s.ic_passport_no
    FROM user u
    LEFT JOIN student s ON u.user_id = s.student_id
    WHERE u.user_id=?
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    echo "Student not found.";
    exit;
}

// Fetch skills
$skills = [];
$stmt = $conn->prepare("
    SELECT sm.skill_name 
    FROM skill s
    JOIN skill_master sm ON s.skill_id = sm.skill_id
    WHERE s.student_id = ?
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $skills[] = $row;
}
$stmt->close();

// Fetch certifications
$certifications = [];
$stmt = $conn->prepare("SELECT * FROM certification WHERE student_id=? ORDER BY date_obtained DESC");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $certifications[] = $row;
}
$stmt->close();

// Fetch projects
$projects = [];
$stmt = $conn->prepare("SELECT * FROM project WHERE student_id=? ORDER BY project_id DESC");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}
$stmt->close();

// Fetch extracurricular
$extracurriculars = [];
$stmt = $conn->prepare("SELECT * FROM extracurricular WHERE student_id=? ORDER BY id DESC");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $extracurriculars[] = $row;
}
$stmt->close();

// Fetch leadership roles
$roles = [];
$stmt = $conn->prepare("SELECT * FROM leadership_role WHERE student_id=? ORDER BY year DESC");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $roles[] = $row;
}
$stmt->close();

// Fetch internship experiences
$internships = [];
$stmt = $conn->prepare("
    SELECT ie.*, c.company_name 
    FROM internship_experience ie 
    LEFT JOIN company c ON ie.company_id=c.company_id 
    WHERE ie.student_id=? 
    ORDER BY start_date DESC
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $internships[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Student | FYP System</title>
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
.sidebar a:hover { background: rgba(159, 94, 183, 0.2); color: #d8b4f8; transform: translateX(5px); }
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
.sidebar a:hover i { transform: scale(1.2) rotate(-5deg); }
.sidebar .closebtn { position: absolute; top: 10px; right: 10px; font-size: 24px; color: white; cursor: pointer; }
.hamburger { font-size:24px; cursor:pointer; background:none; border:none; color:white; }

.container { max-width:1100px; margin:40px auto; padding:0 20px; }

table {
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 4px 10px rgba(0,0,0,0.1);
    margin-bottom:30px;
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

.empty-msg { text-align:center; font-size:16px; color:#555; margin-top:30px; }

.section-title {
    font-size: 22px;
    font-weight: 700;
    color: #9F5EB7;
    margin: 40px 0 10px 0;
    padding-bottom: 5px;
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
  <button class="hamburger" onclick="window.location.href='manage_users.php'">
    <i class="fas fa-arrow-left"></i>
  </button>
    <h1>Student Details</h1>
  <div class="welcome">
    Hi, <?= htmlspecialchars($name) ?>
    <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile">
  </div>
</header>

<div class="container">

<!-- Basic Info -->
<div class="section-title">Basic Information</div>
<table>
<thead>
<tr>
    <th>Field</th>
    <th>Value</th>
</tr>
</thead>
<tbody>
<tr><td>Student ID</td><td><?= htmlspecialchars($student['user_id']) ?></td></tr>
<tr><td>Name</td><td><?= htmlspecialchars($student['name']) ?></td></tr>
<tr><td>Email</td><td><?= htmlspecialchars($student['email']) ?></td></tr>
<tr><td>Program</td><td><?= htmlspecialchars($student['program_code']) ?></td></tr>
<tr><td>Contact Number</td><td><?= htmlspecialchars($student['contact_number']) ?></td></tr>
<tr><td>IC/Passport No</td><td><?= htmlspecialchars($student['ic_passport_no']) ?></td></tr>
</tbody>
</table>

<!-- Skills -->
<?php if(!empty($skills)): ?>
<div class="section-title">Skills</div>
<table>
<thead><tr><th>#</th><th>Skill Name</th></tr></thead>
<tbody>
<?php foreach($skills as $i => $s): ?>
<tr><td><?= $i+1 ?></td><td><?= htmlspecialchars($s['skill_name']) ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<!-- Certifications -->
<?php if(!empty($certifications)): ?>
<div class="section-title">Certifications</div>
<table>
<thead><tr><th>#</th><th>Certification</th><th>Issuer</th><th>Date Obtained</th><th>Link</th></tr></thead>
<tbody>
<?php foreach($certifications as $i => $c): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($c['cert_name']) ?></td>
<td><?= htmlspecialchars($c['issuer']) ?></td>
<td><?= htmlspecialchars($c['date_obtained']) ?></td>
<td><?php if($c['cert_url']): ?><a href="<?= htmlspecialchars($c['cert_url']) ?>" target="_blank">View</a><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<!-- Projects -->
<?php if(!empty($projects)): ?>
<div class="section-title">Projects</div>
<table>
<thead><tr><th>#</th><th>Project Title</th><th>Description</th><th>Link</th></tr></thead>
<tbody>
<?php foreach($projects as $i => $p): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($p['project_title']) ?></td>
<td><?= nl2br(htmlspecialchars($p['description'])) ?></td>
<td><?php if($p['project_link']): ?><a href="<?= htmlspecialchars($p['project_link']) ?>" target="_blank">Link</a><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<!-- Extracurricular -->
<?php if(!empty($extracurriculars)): ?>
<div class="section-title">Extracurricular</div>
<table>
<thead><tr><th>#</th><th>Activity</th><th>Description</th></tr></thead>
<tbody>
<?php foreach($extracurriculars as $i => $e): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($e['activity']) ?></td>
<td><?= htmlspecialchars($e['description']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<!-- Leadership Roles -->
<?php if(!empty($roles)): ?>
<div class="section-title">Leadership Roles</div>
<table>
<thead><tr><th>#</th><th>Role</th><th>Organization</th><th>Year</th></tr></thead>
<tbody>
<?php foreach($roles as $i => $r): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($r['role_title']) ?></td>
<td><?= htmlspecialchars($r['organization']) ?></td>
<td><?= htmlspecialchars($r['year']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<!-- Internships -->
<?php if(!empty($internships)): ?>
<div class="section-title">Internship Experience</div>
<table>
<thead><tr><th>#</th><th>Position</th><th>Company</th><th>Start - End</th><th>Reflection</th><th>Certificate</th></tr></thead>
<tbody>
<?php foreach($internships as $i => $ie): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($ie['position']) ?></td>
<td><?= htmlspecialchars($ie['company_name']) ?></td>
<td><?= htmlspecialchars($ie['start_date']) ?> - <?= htmlspecialchars($ie['end_date']) ?></td>
<td style="text-align:justify;"><?= nl2br(htmlspecialchars($ie['reflection'])) ?></td>
<td><?php if($ie['internship_cert_url']): ?><a href="<?= htmlspecialchars($ie['internship_cert_url']) ?>" target="_blank">View</a><?php endif; ?></td>
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
