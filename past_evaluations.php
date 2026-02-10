<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$role = strtoupper($_SESSION['role']);

$company_id = $_GET['id'] ?? null;
if (!$company_id) {
    echo "No company selected.";
    exit;
}

// Fetch user profile
if ($role === 'STUDENT') {
    $query = $conn->prepare("SELECT profile_picture FROM student WHERE student_id = ?");
} else {
    $query = $conn->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
}
$query->bind_param("s", $user_id);
$query->execute();
$profile_data = $query->get_result()->fetch_assoc();
$profile_pic = $profile_data['profile_picture'] ?? 'images/default_profile.png';
$query->close();

// Fetch company
$stmt = $conn->prepare("SELECT company_name, logo_url FROM company WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$company) {
    echo "Company not found.";
    exit;
}

// Fetch evaluations with comments
$stmt = $conn->prepare("
    SELECT e.*, u.name AS evaluator_name
    FROM company_evaluation e
    JOIN staff s ON e.evaluator_id = s.staff_id
    JOIN user u ON s.staff_id = u.user_id
    WHERE e.company_id = ?
    ORDER BY e.evaluated_on DESC
");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$evaluations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($company['company_name']); ?> | Past Evaluations</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body {
    margin:0;
    font-family:'Nunito',sans-serif;
    background-color:#F5F5F8;
}

header {
    background-color:#9F5EB7;
    color:white;
    padding:16px 24px;
    display:flex;
    align-items:center;
    justify-content:space-between;
}

header h1 {
    margin:0;
    font-size:22px;
    font-weight:800;
    position:absolute;
    left:50%;
    transform:translateX(-50%);
}

.profile-pic {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    aspect-ratio: 1 / 1;
    flex-shrink: 0;
    display: block;
    border: 2px solid white;
}

.welcome {
    display:flex;
    align-items:center;
    gap:8px;
}

.hamburger {
    font-size:24px;
    cursor:pointer;
    background:none;
    border:none;
    color:white;
}

/* Sidebar */
.sidebar {
    height:100%;
    width:0;
    position:fixed;
    top:0;
    left:0;
    background:linear-gradient(180deg,#2e2040,#1b1524);
    overflow-x:hidden;
    transition:0.4s;
    padding-top:80px;
    border-top-right-radius:20px;
    border-bottom-right-radius:20px;
    box-shadow:4px 0 16px rgba(0,0,0,0.4);
    z-index:1000;
}

.sidebar a {
    padding:12px 20px;
    margin:8px 16px;
    text-decoration:none;
    font-size:16px;
    color:#f2f2f2;
    display:flex;
    align-items:center;
    gap:10px;
    border-radius:30px;
    transition:0.3s;
}

.sidebar a:hover {
    background:rgba(159,94,183,0.2);
    color:#d8b4f8;
    transform:translateX(5px);
}

.sidebar .closebtn {
    position:absolute;
    top:10px;
    right:10px;
    font-size:24px;
    color:white;
    cursor:pointer;
}

/* Content */
.content {
    max-width:1200px;
    margin:20px auto;
    padding:0 20px;
}

/* Company Header */
.company-header {
    display:flex;
    align-items:center;
    gap:16px;
    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.1);
}

.company-header img {
    width:80px;
    height:80px;
    border-radius:10px;
    object-fit:cover;
}

.company-header h2 {
    margin:0;
    color:#333;
}

/* Back Button */
.back-button {
    margin: 15px 0 0 20px;
}

.back-button button {
    padding:8px 16px;
    background:#9F5EB7;
    color:white;
    border:none;
    border-radius:6px;
    font-weight:700;
    cursor:pointer;
    font-family:'Nunito',sans-serif;
    font-size:14px;
    transition:0.3s;
}

.back-button button:hover {
    background:#E09D46;
    transform:scale(1.05);
}

/* Evaluations List */
.evaluations-container {
    background:white;
    padding:20px;
    border-radius:12px;
    margin-top:20px;
}

.evaluation-item {
    padding:12px 0;
    border-bottom:1px solid #ddd;
}

.evaluation-item:last-child {
    border-bottom:none;
}

.evaluation-item h4 {
    margin:0 0 6px;
    color:#9F5EB7;
    font-size:16px;
}

.evaluation-item p {
    margin:4px 0;
    font-size:14px;
    color:#333;
}

.evaluation-item small {
    color:#666;
}
</style>
</head>
<body>

<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="document.getElementById('mySidebar').style.width='0'">&times;</a>
  <?php if($role==='STUDENT'): ?>
    <a href="student_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
    <a href="companies.php"><i class="fas fa-magnifying-glass"></i> Find Companies</a>
    <a href="company_ranking.php"><i class="fas fa-ranking-star"></i> Company Ranking</a>
    <a href="career_readiness.php"><i class="fas fa-seedling"></i> Career Readiness</a>
    <a href="whitelist.php"><i class="fas fa-list"></i> Whitelist</a> 
  <?php else: ?>
    <a href="staff_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
    <a href="my_students.php"><i class="fas fa-users"></i> My Students</a>
    <a href="companies.php"><i class="fas fa-building"></i> Companies</a>
    <a href="company_ranking.php"><i class="fas fa-ranking-star"></i> Company Ranking</a>
    <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
  <?php endif; ?>
  <a href="login.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
</div>

<header>
  <button class="hamburger" onclick="openSidebar()"><i class="fas fa-bars"></i></button>
  <h1>Past Evaluations</h1>
  <div class="welcome">
    Hi, <?php echo htmlspecialchars($name); ?>
    <a href="<?php echo $role==='STUDENT'?'student_profile.php':'staff_profile.php'; ?>">
      <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-pic">
    </a>
  </div>
</header>

<div class="back-button">
    <button onclick="window.location.href='company_details.php?id=<?php echo $company_id; ?>'">
      <i class="fas fa-arrow-left"></i> Back
    </button>
</div>

<div class="content">

  <div class="company-header">
    <img src="<?php echo htmlspecialchars($company['logo_url']); ?>" alt="Logo">
    <h2><?php echo htmlspecialchars($company['company_name']); ?></h2>
  </div>

  <?php if(count($evaluations) > 0): ?>
  <div class="evaluations-container">
    <?php foreach($evaluations as $eval): ?>
      <div class="evaluation-item">
        <h4>Evaluator: <?php echo htmlspecialchars($eval['evaluator_name']); ?></h4>
        <p><strong>Score:</strong> <?php echo htmlspecialchars($eval['score']); ?></p>
        <p><strong>Remarks:</strong> <?php echo nl2br(htmlspecialchars($eval['remarks'])); ?></p>
        <?php if(!empty($eval['comment'])): ?>
          <p><strong>Comment:</strong> <?php echo nl2br(htmlspecialchars($eval['comment'])); ?></p>
        <?php endif; ?>
        <small>Evaluated on: <?php echo htmlspecialchars($eval['evaluated_on']); ?></small>
      </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
    <p style="text-align:center; color:#666; margin-top:40px;">No evaluations yet.</p>
  <?php endif; ?>

</div>

<script>
function openSidebar(){ document.getElementById("mySidebar").style.width="250px"; }
</script>

</body>
</html>
