<?php
session_start();
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role'] ?? '') !== 'STAFF') {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// ==========================
// STAFF INFO
// ==========================
$staff_id = $_SESSION['user_id'];
$staff_name = $_SESSION['name'] ?? 'Staff';

// Fetch staff profile picture
$profileQuery = $conn->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
$profileQuery->bind_param("s", $staff_id);
$profileQuery->execute();
$profileResult = $profileQuery->get_result();
if ($profileResult->num_rows > 0) {
    $profileRow = $profileResult->fetch_assoc();
    $staff_picture = !empty($profileRow['profile_picture']) ? $profileRow['profile_picture'] : 'uploads/default_profile.png';
} else {
    $staff_picture = 'uploads/default_profile.png';
}
$profileQuery->close();

// ==========================
// STUDENT INFO
// ==========================

// Get student ID from URL
$student_id = $_GET['id'] ?? null;
if (!$student_id) {
    echo "Student ID not provided.";
    exit;
}

// Fetch student basic info
$stmt = $conn->prepare("
    SELECT s.*, u.name
    FROM student s
    JOIN user u ON u.user_id = s.student_id
    WHERE s.student_id = ?
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    echo "Student not found.";
    exit;
}

// Student fields stored separately
$student_name = $student['name'];
$student_picture = !empty($student['profile_picture']) ? $student['profile_picture'] : "img/default_profile.png";

// ==========================
// PROGRAM NAME
// ==========================
$programStmt = $conn->prepare("SELECT program_name FROM program WHERE program_code = ?");
$programStmt->bind_param("s", $student['program_code']);
$programStmt->execute();
$programRow = $programStmt->get_result()->fetch_assoc();
$program_name = $programRow['program_name'] ?? '';
$programStmt->close();

// ==========================
// SKILLS
// ==========================
$studentSkills = [];
$skillQuery = $conn->prepare("
    SELECT sm.skill_name
    FROM skill s
    JOIN skill_master sm ON s.skill_id = sm.skill_id
    WHERE s.student_id = ?
");
$skillQuery->bind_param("s", $student_id);
$skillQuery->execute();
$result = $skillQuery->get_result();
while ($row = $result->fetch_assoc()) {
    $studentSkills[] = $row['skill_name'];
}
$skillQuery->close();

// ==========================
// CERTIFICATIONS
// ==========================
$certifications = [];
$certQuery = $conn->prepare("SELECT * FROM certification WHERE student_id = ?");
$certQuery->bind_param("s", $student_id);
$certQuery->execute();
$result = $certQuery->get_result();
while ($row = $result->fetch_assoc()) {
    $certifications[] = $row;
}
$certQuery->close();

// ==========================
// PROJECTS
// ==========================
$projects = [];
$projQuery = $conn->prepare("SELECT * FROM project WHERE student_id = ?");
$projQuery->bind_param("s", $student_id);
$projQuery->execute();
$result = $projQuery->get_result();
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}
$projQuery->close();

// ==========================
// EXTRACURRICULAR
// ==========================
$extracurriculars = [];
$extraQuery = $conn->prepare("SELECT * FROM extracurricular WHERE student_id = ?");
$extraQuery->bind_param("s", $student_id);
$extraQuery->execute();
$result = $extraQuery->get_result();
while ($row = $result->fetch_assoc()) {
    $extracurriculars[] = $row;
}
$extraQuery->close();

// ==========================
// LEADERSHIP ROLES
// ==========================
$leadershipRoles = [];
$leadQuery = $conn->prepare("SELECT * FROM leadership_role WHERE student_id = ?");
$leadQuery->bind_param("s", $student_id);
$leadQuery->execute();
$result = $leadQuery->get_result();
while ($row = $result->fetch_assoc()) {
    $leadershipRoles[] = $row;
}
$leadQuery->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Profile | FYP System</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ===== GENERAL ===== */
body {
    margin: 0;
    font-family: 'Nunito', sans-serif;
    background: white;
    color: #333;
}

a {
    text-decoration: none;
    color: inherit;
}

header {
    background-color: #9F5EB7;
    color: white;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    position: relative; /* needed for absolute positioning of title */
    z-index: 100;
}

header h1 {
    font-size: 22px;
    font-weight: 800;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    margin: 0;
    text-align: center;
}

.welcome {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-left: auto; /* pushes the profile info to the right */
}

.welcome img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid white;
}

/* ===== SIDEBAR ===== */
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
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    margin: 8px 16px;
    font-size: 16px;
    color: #f2f2f2;
    border-radius: 30px;
    transition: 0.3s, transform 0.2s, color 0.3s;
}

.sidebar a i {
    font-size: 20px;
    width: 28px;
    text-align: center;
    background: linear-gradient(135deg, #9F5EB7, #6A3A8D);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    filter: drop-shadow(1px 1px 2px rgba(0, 0, 0, 0.3));
    transition: transform 0.2s ease;
}

.sidebar a:hover {
    background: rgba(159, 94, 183, 0.2);
    color: #d8b4f8;
    transform: translateX(5px);
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

/* ===== HAMBURGER ===== */
.hamburger {
    font-size: 24px;
    cursor: pointer;
    background: none;
    border: none;
    color: white;
}

/* ===== CONTAINER ===== */
.container {
    width: 100%;
    margin: 0;
    padding: 0;
}

/* ===== PROFILE CARD ===== */
.profile-card {
    background: white;
    width: 100%;
    overflow: hidden;
}

/* ===== TOP ROW: Picture Left + Info Right + Edit ===== */
.top-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 20px;
    padding: 30px 24px;
}

.profile-picture-container {
    flex: 0 0 180px;
    display: flex;
    justify-content: center;
}

.profile-picture-left {
    width: 160px;
    height: 160px;
    object-fit: cover;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    transition: transform 0.3s;
}

.profile-picture-left:hover {
    transform: scale(1.05);
}

.profile-info {
    flex: 1 1 300px;
}

.profile-info h2 {
    font-weight: 800;
    margin-bottom: 8px;
    color: #4a2b68;
}

.profile-info p {
    margin: 4px 0;
    color: #555;
}

.status-badge {
    display: inline-block;
    margin-top: 8px;
    padding: 8px 18px;
    border-radius: 10px;
    font-weight: 700;
    font-size: 15px;
    letter-spacing: 0.3px;
    color: white;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.status-badge:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 14px rgba(0,0,0,0.3);
}

/* Status colors */
.status-looking {
    background: #e74c3c; 
}

.status-ongoing {
    background: #9F5EB7; 
}

.status-completed {
    background: #5cb85c; /* green */
}

/* ===== EDIT BUTTON ===== */
.btn-edit {
    display: flex;
    align-items: center;
    margin-left: auto;
    margin-top: 15px;
    height: 40px;
    padding: 0 20px;
    border-radius: 20px;
    background: #E09D46;
    color: white;
    border: none;
    font-weight: 700;
    font-family: 'Nunito', sans-serif;
    cursor: pointer;
    transition: 0.3s;
    align-self: flex-start;
}

.btn-edit i {
    margin-right: 8px;
}

.btn-edit:hover {
    background: #c47a34;
    transform: scale(1.05);
}

/* ===== SECTIONS ===== */
.section {
    padding: 24px 24px;
    border-bottom: 1.5px solid #e8e3e3ff;
}

.section:last-child {
    border-bottom: none;
}

.section h3 {
    color: #9F5EB7;
    font-weight: 800;
    margin-bottom: 12px;
}

.about-section p,
.details-section p,
.details-item span {
    color: #444;
    line-height: 1.6;
    font-size: 15px;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
    margin-top: 10px;
}

.details-item {
    background: #faf8fc;
    padding: 14px 16px;
    border-radius: 12px;
    text-align: center;
    transition: 0.3s;
}

.details-item:hover {
    background: #f0e6f8;
    transform: translateY(-3px);
}

.details-item strong {
    color: #4a2b68;
    display: block;
    margin-bottom: 6px;
}

.portfolio-link {
    color: #0E5FB4;
    font-weight: 600;
    text-decoration: none;
    transition: color 0.3s, transform 0.2s;
}

.portfolio-link:hover {
    color: #f9943b;
    transform: scale(1.05);
}

/* ===== MODAL ===== */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4);
    overflow-y: auto;
}

.modal-content {
    background: white;
    margin: 60px auto;
    padding: 20px 30px;
    border-radius: 16px;
    max-width: 700px;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.close {
    float: right;
    font-size: 22px;
    cursor: pointer;
    color: #9F5EB7;
    font-weight: bold;
}

label {
    display: block;
    margin-top: 8px;
    font-weight: bolder;
    color: #9F5EB7;
}

.form-field {
  margin-bottom: 15px;
}

.form-field label {
  display: block;
  font-weight: 700;
  margin-bottom: 5px;
}

.form-field input,
.form-field select,
.form-field textarea {
  width: 100%;
  padding: 8px;
  border-radius: 6px;
  border: 1px solid #ccc;
  font-family: 'Nunito', sans-serif;
}

.photo-preview {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.photo-tag {
  position: relative;
  display: inline-block;
}

.photo-tag img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #ccc;
}

.remove-photo {
  position: absolute;
  top: -5px;
  right: -5px;
  background: #d84352ff;
  color: white;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  text-align: center;
  line-height: 18px;
  cursor: pointer;
  font-weight: bold;
}

form button {
    display: block;       /* Make it block so margin works */
    margin: 10px auto 0;  /* Top 16px, auto left/right to center, 0 bottom */
    background: #E09D46;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 10px;
    font-family: 'Nunito', sans-serif;
    font-weight: 700;
    cursor: pointer;
}

form button:hover {
    background: #c47a34;
}

.skill-picker {
  position: relative;
  margin-top: 10px;
}

.skill-picker input {
  width: 100%;
  padding: 10px;
  border-radius: 12px;
  border: 1px solid #ccc;
}

.suggestions {
  position: absolute;
  background: white;
  border: 1px solid #ddd;
  border-radius: 8px;
  max-height: 150px;
  overflow-y: auto;
  width: 100%;
  z-index: 10;
  display: none;
}

.suggestions div {
  padding: 8px 12px;
  cursor: pointer;
}

.suggestions div:hover {
  background: #f0e6f8;
}

.selected-skills {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.skill-tag {
  background: #9F5EB7;
  color: white;
  padding: 6px 12px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 14px;
}

.skill-tag span {
  cursor: pointer;
  font-weight: bold;
}

/* Dynamic "Add" buttons for Certifications, Projects, etc. */
.dynamic-add-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #E09D46; 
    color: white;
    border: none;
    padding: 8px 16px;
    font-family: 'Nunito', sans-serif;
    font-weight: 700;
    border-radius: 12px;
    cursor: pointer;
    transition: 0.3s;
    margin-top: 8px;
    margin-bottom: 10px;
}

.dynamic-add-btn i {
    font-size: 16px;
}

.dynamic-add-btn:hover {
    background: #7B4DAE;
    transform: scale(1.05);
}

.dynamic-entry {
    border: 1px solid #ddd;
    padding: 12px 14px;
    margin-top: 12px;
    margin-bottom: 12px;
    border-radius: 12px;
    background: #fdfbff;
}

.dynamic-entry input,
.dynamic-entry textarea {
    margin-top: 8px;
    width: 98%;
    padding: 8px 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-family: 'Nunito', sans-serif;
}
/* Save button */
.dynamic-entry .save-btn {
    margin-top: 8px;
    background: #9F5EB7; /* purple */
    color: white;
    border: none;
    padding: 6px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-family: 'Nunito', sans-serif;
    font-weight: 700;
}

.dynamic-entry .save-btn:hover {
    background: #7B4DAE; /* darker purple on hover */
}

/* Remove button */
.dynamic-entry .remove-btn {
    margin-top: 8px;
    background: #e74e4eff; 
    color: white;
    border: none;
    padding: 6px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-family: 'Nunito', sans-serif;
    font-weight: 700;
}

.dynamic-entry .remove-btn:hover {
    background: #e02c2cff; /* darker red on hover */
}

.entry-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background-color: #faf8fc;
    border: 1px solid #ccc;   /* solid border for neatness */
    border-radius: 8px;       /* rounded corners */
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08); /* subtle shadow */
    margin-bottom: 10px;      /* spacing between rows */
    transition: transform 0.1s ease, box-shadow 0.1s ease;
}

.entry-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12); /* lift effect on hover */
}

.entry-item:last-child {
    border-bottom: none; /* remove line from last row */
}

.entry-item .entry-buttons {
    display: flex;
    gap: 8px;
}

.entry-item button {
  padding: 6px 14px;
  font-family: 'Nunito', sans-serif;
  font-weight: 700;
  border-radius: 8px;
  border: none;
  cursor: pointer;
}

.entry-item .edit-btn {
  background-color: #9F5EB7;
  color: white;
}

.entry-item .edit-btn:hover {
  background-color: #E09D46;
}

.entry-item .remove-btn {
  background-color: #e74e4eff;
  color: white;
}

.entry-item .remove-btn:hover {
  background-color: #e02c2cff;
}

/* Grid for certifications */
.certifications-section .details-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); /* smaller min width */
  gap: 12px;
  margin-top: 40px;
}

/* Individual certification card as a square */
.certifications-section .details-item {
  background-color: #ffffff;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  padding: 12px 10px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  aspect-ratio: 1 / 1;  /* makes it square */
  text-align: center;
  transition: transform 0.2s;
}

.certifications-section .details-item:hover {
  transform: translateY(-2px);
}

/* EDIT BUTTON */
.dynamic-btn-edit {
    background-color: #9F5EB7;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 3px 6px rgba(0,0,0,0.15);
    transition: 0.2s;
    font-family: 'Nunito', sans-serif;
    margin-top: 15px;
}

.dynamic-btn-edit:hover {
    background-color: #E09D46;
    box-shadow: 0 4px 10px rgba(0,0,0,0.25);
}

/* DELETE BUTTON */
.dynamic-btn-delete {
    background-color: #e74c3c;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    margin-left: 6px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.15);
    transition: 0.2s;
    font-family: 'Nunito', sans-serif;
    margin-top: 15px;
}

.dynamic-btn-delete:hover {
    background-color: #c0392b; /* darker red */
    box-shadow: 0 4px 10px rgba(0,0,0,0.25);
}

</style>
</head>
<body>

<!-- SIDEBAR -->
<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="document.getElementById('mySidebar').style.width='0'">&times;</a>
  <a href="student_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
  <a href="find_companies.php"><i class="fas fa-magnifying-glass"></i> Find Companies</a>
  <a href="company_ranking.php"><i class="fas fa-ranking-star"></i> Company Ranking</a>
  <a href="career_readiness.php"><i class="fas fa-seedling"></i> Career Readiness</a>
  <a href="whitelist.php"><i class="fas fa-list"></i> Whitelist</a> 
  <a href="login.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
</div>

<header>
  <button class="hamburger" onclick="window.location.href='my_students.php'">
    <i class="fas fa-arrow-left"></i>
  </button>
  <h1>Student Profile</h1>
    <div class="welcome">
        Hi, <?php echo htmlspecialchars($staff_name); ?>
        <img src="<?php echo htmlspecialchars($staff_picture); ?>" alt="Profile">
    </div>
</header>

<div class="container">
  <div class="profile-card">
    <!-- Top row -->
    <div class="top-row">
      <div class="profile-picture-container">
        <img src="<?php echo htmlspecialchars($student['profile_picture'] ?? 'img/default_profile.png'); ?>" 
             class="profile-picture-left" alt="Profile Picture">
      </div>
      <div class="profile-info">
        <h2><?php echo htmlspecialchars($student_name); ?></h2>
        <p><strong><?php echo htmlspecialchars($student['student_id']); ?></strong></p>
        <p><?php echo htmlspecialchars($student['program_name'] ?? 'Program not set'); ?></p>
        <?php
        $status = $student['internship_status'] ?? 'LOOKING';
        $badgeClass = '';
        switch($status) {
            case 'LOOKING':
                $badgeClass = 'status-looking';
                break;
            case 'ONGOING':
                $badgeClass = 'status-ongoing';
                break;
            case 'COMPLETED':
                $badgeClass = 'status-completed';
                break;
        }
        ?>
        <div class="status-badge <?php echo $badgeClass; ?>">
            <?php 
            if ($status == 'LOOKING') echo 'Looking for Internship';
            elseif ($status == 'ONGOING') echo 'Internship In-Progress';
            else echo 'Internship Completed';
            ?>
        </div>
      </div>
    </div>

    <!-- About Me -->
    <div class="section about-section">
      <h3>About Me</h3>
      <p><?php echo nl2br(htmlspecialchars($student['about'] ?? 'No bio yet.')); ?></p>
    </div>

    <!-- Details -->
    <div class="section details-section">
      <h3>Details</h3>
      <div class="details-grid">
        <div class="details-item">
          <strong>CGPA</strong>
          <span><?php echo htmlspecialchars($student['cgpa'] ?? '-'); ?></span>
        </div>
        <div class="details-item">
          <strong>Graduation Year</strong>
          <span><?php echo htmlspecialchars($student['graduation_year'] ?? '-'); ?></span>
        </div>
        <div class="details-item">
          <strong>Portfolio</strong>
          <?php if (!empty($student['portfolio_link'])): ?>
            <a href="<?php echo htmlspecialchars($student['portfolio_link']); ?>" 
                target="_blank" class="portfolio-link">
                View Portfolio
            </a>
          <?php else: ?>
            <span>-</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Skills Section -->
<div class="section skills-section">
  <h3>Skills</h3>
  <div class="details-grid">

    <?php if (!empty($studentSkills)): ?>
        <?php foreach ($studentSkills as $skillName): ?>
            <div class="details-item">
                <?php echo htmlspecialchars($skillName); ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No skills added yet.</p>
    <?php endif; ?>

  </div>
</div>


<!-- Certifications Section -->
<div class="section">
  <h3>Certifications</h3>

  <div class="details-grid">
    <?php
    $certifications = [];
    $certQuery = $conn->prepare("
        SELECT cert_name, issuer, cert_url, date_obtained 
        FROM certification 
        WHERE student_id = ?
        ORDER BY date_obtained DESC
    ");
    $certQuery->bind_param("s", $student_id);
    $certQuery->execute();
    $resultCerts = $certQuery->get_result();

    if ($resultCerts->num_rows > 0) {
        while ($cert = $resultCerts->fetch_assoc()) {
            echo '<div class="details-item">';
            echo '<strong>' . htmlspecialchars($cert['cert_name']) . '</strong><br>';
            echo '<span>' . htmlspecialchars($cert['issuer']) . '</span><br>';

            if (!empty($cert['cert_url'])) {
                echo '<a href="' . htmlspecialchars($cert['cert_url']) . '" target="_blank">View Certificate</a><br>';
            }

            if (!empty($cert['date_obtained'])) {
                echo '<small>' . htmlspecialchars($cert['date_obtained']) . '</small>';
            }

            echo '</div>';
        }
    } else {
        echo "<p>No certifications added yet.</p>";
    }

    $certQuery->close();
    ?>
  </div>
</div>

<!-- Projects Section -->
<div class="section">
  <h3>Projects</h3>

  <div class="details-grid">
    <?php
    $projQuery = $conn->prepare("
        SELECT project_title, project_link, description 
        FROM project 
        WHERE student_id = ?
        ORDER BY project_id DESC
    ");
    $projQuery->bind_param("s", $student_id);
    $projQuery->execute();
    $resultProj = $projQuery->get_result();

    if ($resultProj->num_rows > 0) {
        while ($proj = $resultProj->fetch_assoc()) {
            echo '<div class="details-item">';
            echo '<strong>' . htmlspecialchars($proj['project_title']) . '</strong><br>';

            if (!empty($proj['project_link'])) {
                echo '<a href="' . htmlspecialchars($proj['project_link']) . '" target="_blank">Project Link</a><br>';
            }

            if (!empty($proj['description'])) {
                echo '<small>' . nl2br(htmlspecialchars($proj['description'])) . '</small>';
            }

            echo '</div>';
        }
    } else {
        echo "<p>No projects added yet.</p>";
    }

    $projQuery->close();
    ?>
  </div>
</div>

<!-- Extracurricular Section -->
<div class="section">
  <h3>Extracurricular</h3>

  <div class="details-grid">
    <?php
    $extracQuery = $conn->prepare("
        SELECT activity, description 
        FROM extracurricular 
        WHERE student_id = ?
        ORDER BY id DESC
    ");
    $extracQuery->bind_param("s", $student_id);
    $extracQuery->execute();
    $resultExtrac = $extracQuery->get_result();

    if ($resultExtrac->num_rows > 0) {
        while ($e = $resultExtrac->fetch_assoc()) {
            echo '<div class="details-item">';
            echo '<strong>' . htmlspecialchars($e['activity']) . '</strong><br>';

            if (!empty($e['description'])) {
                echo '<small>' . nl2br(htmlspecialchars($e['description'])) . '</small>';
            }

            echo '</div>';
        }
    } else {
        echo "<p>No extracurricular activities added yet.</p>";
    }

    $extracQuery->close();
    ?>
  </div>
</div>


<!-- Leadership Roles Section -->
<div class="section">
  <h3>Leadership Roles</h3>

  <div class="details-grid">
    <?php
    $leadQuery = $conn->prepare("
        SELECT role_title, organization, year 
        FROM leadership_role 
        WHERE student_id = ?
        ORDER BY id DESC
    ");
    $leadQuery->bind_param("s", $student_id);
    $leadQuery->execute();
    $resultLead = $leadQuery->get_result();

    if ($resultLead->num_rows > 0) {
        while ($l = $resultLead->fetch_assoc()) {
            echo '<div class="details-item">';
            echo '<strong>' . htmlspecialchars($l['role_title']) . '</strong>';
            echo ' at <em>' . htmlspecialchars($l['organization']) . '</em><br>';

            if (!empty($l['year'])) {
                echo '<small>' . htmlspecialchars($l['year']) . '</small>';
            }

            echo '</div>';
        }
    } else {
        echo "<p>No leadership roles added yet.</p>";
    }

    $leadQuery->close();
    ?>
  </div>
</div>

<!-- Internship Experience Section -->
<div class="section">
  <h3>Internship Experience</h3>

  <div class="details-grid">
    <?php
    $query = $conn->prepare("
        SELECT i.position, i.start_date, i.end_date, i.reflection, i.internship_cert_url,
               c.company_name
        FROM internship_experience i
        LEFT JOIN company c ON i.company_id = c.company_id
        WHERE i.student_id = ?
        ORDER BY i.experience_id DESC
    ");
    $query->bind_param("s", $student_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        while ($exp = $result->fetch_assoc()) {
            echo '<div class="details-item">';
            echo '<strong>' . htmlspecialchars($exp['position']) . '</strong><br>';
            echo '<em>' . htmlspecialchars($exp['company_name']) . '</em><br>';
            echo '<small>' . htmlspecialchars($exp['start_date']) . ' to ' . htmlspecialchars($exp['end_date']) . '</small><br><br>';

            if (!empty($exp['reflection'])) {
                echo '<small>' . nl2br(htmlspecialchars($exp['reflection'])) . '</small><br>';
            }

            if (!empty($exp['internship_cert_url'])) {
                echo '<a href="' . htmlspecialchars($exp['internship_cert_url']) . '" target="_blank">Certificate</a><br>';
            }

            echo '</div>';
        }
    } else {
        echo "<p>No internship experience added yet.</p>";
    }

    $query->close();
    ?>
  </div>
</div>
</body>
</html>
