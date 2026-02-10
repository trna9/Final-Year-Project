<?php
session_start();
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STUDENT') {
    header("Location: login.html");
    exit;
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Student';

$programList = [];
$programQuery = $conn->query("SELECT program_code, program_name FROM program ORDER BY program_code ASC");
while ($row = $programQuery->fetch_assoc()) {
    $programList[] = $row;
}

// Fetch all skills from skill_master
$allSkills = [];
$skillsQuery = $conn->query("SELECT skill_id, skill_name FROM skill_master ORDER BY skill_name ASC");
while ($row = $skillsQuery->fetch_assoc()) {
    $allSkills[] = $row;
}

// Fetch student's selected skills
$studentSkills = [];
$studentSkillQuery = $conn->prepare("SELECT skill_id FROM skill WHERE student_id = ?");
$studentSkillQuery->bind_param("s", $user_id);
$studentSkillQuery->execute();
$resultSkills = $studentSkillQuery->get_result();
while ($row = $resultSkills->fetch_assoc()) {
    $studentSkills[] = $row['skill_id'];
}
$studentSkillQuery->close();


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $program_code = !empty($_POST['program_code']) ? $_POST['program_code'] : null;
    $cgpa = !empty($_POST['cgpa']) ? $_POST['cgpa'] : null;
    $graduation_year = !empty($_POST['graduation_year']) ? $_POST['graduation_year'] : null;
    $internship_status = $_POST['internship_status'] ?? 'LOOKING';
    $portfolio_link = !empty($_POST['portfolio_link']) ? $_POST['portfolio_link'] : null;
    $about = !empty($_POST['about']) ? $_POST['about'] : null;

    $query = $conn->prepare("SELECT profile_picture FROM student WHERE student_id = ?");
    $query->bind_param("s", $user_id);
    $query->execute();
    $result = $query->get_result();
    $student_data = $result->fetch_assoc();
    $old_picture_path = $student_data['profile_picture'] ?? 'img/default_profile.png';
    $query->close();

    $profile_picture_path = $old_picture_path;

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "img/";
        $fileName = basename($_FILES["profile_picture"]["name"]);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $validExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileExt, $validExts)) {
            $targetFilePath = $targetDir . time() . "_" . uniqid() . "." . $fileExt;
            if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFilePath)) {
                if ($old_picture_path !== 'img/default_profile.png' && file_exists($old_picture_path)) {
                    unlink($old_picture_path);
                }
                $profile_picture_path = $targetFilePath;
            }
        }
    }

    $update = $conn->prepare("
    UPDATE student 
    SET program_code = ?, cgpa = ?, graduation_year = ?, internship_status = ?, portfolio_link = ?, about = ?, profile_picture = ?
    WHERE student_id = ?
    ");
    
    $update->bind_param("idisssss", $program_code, $cgpa, $graduation_year, $internship_status, $portfolio_link, $about, $profile_picture_path, $user_id);
    $update->execute();
    $update->close();

    // Delete old skills
    $deleteStmt = $conn->prepare("DELETE FROM skill WHERE student_id = ?");
    $deleteStmt->bind_param("s", $user_id);
    $deleteStmt->execute();
    $deleteStmt->close();

    // Insert new selected skills (decoded from JSON)
    if (!empty($_POST['skills'])) {
        $skills = json_decode($_POST['skills'][0], true); // decode the first JSON string
        if (is_array($skills)) {
            $insertStmt = $conn->prepare("INSERT INTO skill (student_id, skill_id) VALUES (?, ?)");
            foreach ($skills as $skill_id) {
                $insertStmt->bind_param("ss", $user_id, $skill_id);
                $insertStmt->execute();
            }
            $insertStmt->close();
        }
    }

    // --- Certifications ---
    $conn->query("DELETE FROM certification WHERE student_id='$user_id'");
    if(!empty($_POST['cert_name'])) {
        $stmt = $conn->prepare("INSERT INTO certification (student_id, cert_name, issuer, cert_url, date_obtained) VALUES (?, ?, ?, ?, ?)");
        for($i=0; $i<count($_POST['cert_name']); $i++){
            $stmt->bind_param("sssss", $user_id, $_POST['cert_name'][$i], $_POST['issuer'][$i], $_POST['cert_url'][$i], $_POST['date_obtained'][$i]);
            $stmt->execute();
        }
        $stmt->close();
    }

    // --- Projects ---
    $conn->query("DELETE FROM project WHERE student_id='$user_id'");
    if(!empty($_POST['project_title'])) {
        $stmt = $conn->prepare("INSERT INTO project (student_id, project_title, project_link, description) VALUES (?, ?, ?, ?)");
        for($i=0; $i<count($_POST['project_title']); $i++){
            $stmt->bind_param("ssss", $user_id, $_POST['project_title'][$i], $_POST['project_link'][$i], $_POST['project_desc'][$i]);
            $stmt->execute();
        }
        $stmt->close();
    }

    // --- Extracurriculars ---
    $conn->query("DELETE FROM extracurricular WHERE student_id='$user_id'");
    if(!empty($_POST['activity'])) {
        $stmt = $conn->prepare("INSERT INTO extracurricular (student_id, activity, description) VALUES (?, ?, ?)");
        for($i=0; $i<count($_POST['activity']); $i++){
            $stmt->bind_param("sss", $user_id, $_POST['activity'][$i], $_POST['activity_desc'][$i]);
            $stmt->execute();
        }
        $stmt->close();
    }

    // --- Leadership Roles ---
    $conn->query("DELETE FROM leadership_role WHERE student_id='$user_id'");
    if(!empty($_POST['role_title'])) {
        $stmt = $conn->prepare("INSERT INTO leadership_role (student_id, role_title, organization, year) VALUES (?, ?, ?, ?)");
        for($i=0; $i<count($_POST['role_title']); $i++){
            $year = $_POST['role_year'][$i] ?: null;
            $stmt->bind_param("sssi", $user_id, $_POST['role_title'][$i], $_POST['organization'][$i], $year);
            $stmt->execute();
        }
        $stmt->close();
    }

    header("Location: student_profile.php?saved=1");
    exit;

}
$query = $conn->prepare("
    SELECT s.*, p.program_name 
    FROM student s
    LEFT JOIN program p ON s.program_code = p.program_code
    WHERE s.student_id = ?
");
$query->bind_param("s", $user_id);
$query->execute();
$result = $query->get_result();
$student = $result->fetch_assoc();
$query->close();
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
  <button class="hamburger" onclick="window.location.href='student_dashboard.php'">
    <i class="fas fa-arrow-left"></i>
  </button>
  <h1>Student Profile</h1>
  <div class="welcome">
    Hi, <?php echo htmlspecialchars($name); ?>
    <img src="<?php echo htmlspecialchars($student['profile_picture'] ?? 'img/default_profile.png'); ?>" alt="Profile">
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
        <h2><?php echo htmlspecialchars($name); ?></h2>
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
      <button class="btn-edit" onclick="openModal()"><i class="fas fa-pen"></i> Edit</button>
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
      <?php 
        foreach ($allSkills as $skill) {
            if (in_array($skill['skill_id'], $studentSkills)) {
                echo '<div class="details-item">' . htmlspecialchars($skill['skill_name']) . '</div>';
            }
        }
      ?>
    <?php else: ?>
      <p>No skills added yet.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Certifications Section -->
<div class="section">
  <h3>Certifications</h3>
  <button type="button" class="dynamic-add-btn" onclick="openCertModal()">
    <i class="fas fa-plus"></i> Add Certification
  </button>
  <div id="certificationsContainer" class="details-grid">
  <?php
  $certQuery = $conn->prepare("SELECT * FROM certification WHERE student_id=? ORDER BY date_obtained DESC");
  $certQuery->bind_param("s", $user_id);
  $certQuery->execute();
  $resultCerts = $certQuery->get_result();
  while ($cert = $resultCerts->fetch_assoc()) {
      echo '<div class="details-item" data-cert-id="'.$cert['cert_id'].'">';
      echo '<strong>' . htmlspecialchars($cert['cert_name']) . '</strong><br>';
      echo '<span>' . htmlspecialchars($cert['issuer']) . '</span><br>';
      if (!empty($cert['cert_url'])) {
          echo '<a href="' . htmlspecialchars($cert['cert_url']) . '" target="_blank">Link</a><br>';
      }
      if (!empty($cert['date_obtained'])) {
          echo '<small>' . htmlspecialchars($cert['date_obtained']) . '</small><br>';
      }

      echo '<button type="button" onclick="editCertification(' . $cert['cert_id'] . ')" class="dynamic-btn-edit">Edit</button>';
      echo '<button type="button" onclick="deleteCertification(' . $cert['cert_id'] . ', this)" class="dynamic-btn-delete">Delete</button>';


      echo '</div>';
  }
  $certQuery->close();
  ?>
  </div>
</div>


<!-- Certification Modal -->
<div id="certModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" onclick="closeCertModal()">&times;</span>
    <h2>Add Certification</h2>
    <form id="certForm">
      <input type="hidden" name="student_id" value="<?php echo $_SESSION['user_id']; ?>">
      
      <div class="form-field">
        <label for="cert_name">Certification Name</label>
        <input type="text" name="cert_name" id="cert_name" required>
      </div>

      <div class="form-field">
      <label for="issuer">Issuer</label>
      <input type="text" name="issuer" id="issuer">
      </div>

      <div class="form-field">
      <label for="cert_url">Certification URL</label>
      <input type="url" name="cert_url" id="cert_url">
      </div>

      <div class="form-field">
      <label for="date_obtained">Date Obtained</label>
      <input type="date" name="date_obtained" id="date_obtained">
      </div>

      <button type="button" onclick="saveCertification()">Save</button>
    </form>
  </div>
</div>

<!-- Projects Section -->
<div class="section">
  <h3>Projects</h3>

  <!-- Add Project Button -->
  <button type="button" class="dynamic-add-btn" onclick="openProjectModal()">
    <i class="fas fa-plus"></i> Add Project
  </button>

  <div id="projectsContainer" class="details-grid">
    <?php
    $projQuery = $conn->prepare("SELECT * FROM project WHERE student_id=? ORDER BY project_id DESC");
    $projQuery->bind_param("s", $user_id);
    $projQuery->execute();
    $resultProj = $projQuery->get_result();

    while ($proj = $resultProj->fetch_assoc()) {
        echo '<div class="details-item" data-project-id="'.$proj['project_id'].'">';
        echo '<strong>' . htmlspecialchars($proj['project_title']) . '</strong><br>';

        if (!empty($proj['project_link'])) {
            echo '<a href="' . htmlspecialchars($proj['project_link']) . '" target="_blank">Project Link</a><br>';
        }

        if (!empty($proj['description'])) {
            echo '<small>' . nl2br(htmlspecialchars($proj['description'])) . '</small><br>';
        }

        echo '<button type="button" onclick="editProject(' . $proj['project_id'] . ')" class="dynamic-btn-edit">Edit</button>';
        echo '<button type="button" onclick="deleteProject(' . $proj['project_id'] . ', this)" class="dynamic-btn-delete">Delete</button>';

        echo '</div>';
    }

    $projQuery->close();
    ?>
  </div>
</div>

<!-- Project Modal -->
<div id="projectModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" onclick="closeProjectModal()">&times;</span>
    <h2 id="projectModalTitle">Add Project</h2>

    <form id="projectForm">
      <input type="hidden" name="project_id" id="project_id">
      <input type="hidden" name="student_id" value="<?php echo $_SESSION['user_id']; ?>">

      <div class="form-field">
        <label for="project_title">Project Title</label>
        <input type="text" name="project_title" id="project_title" required>
      </div>

      <div class="form-field">
        <label for="project_link">Project Link</label>
        <input type="url" name="project_link" id="project_link">
      </div>

      <div class="form-field">
        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4"></textarea>
      </div>

      <button type="button" onclick="saveProject()">Save</button>
    </form>
  </div>
</div>

<div class="section">
  <h3>Extracurricular</h3>

  <button type="button" class="dynamic-add-btn" onclick="openExtracModal()">
    <i class="fas fa-plus"></i> Add Activity
  </button>

  <div id="extracContainer" class="details-grid">
    <?php
    $extracQuery = $conn->prepare("SELECT * FROM extracurricular WHERE student_id=? ORDER BY id DESC");
    $extracQuery->bind_param("s", $user_id);
    $extracQuery->execute();
    $resultExtrac = $extracQuery->get_result();

    while($e = $resultExtrac->fetch_assoc()) {
        echo '<div class="details-item" data-extrac-id="'.$e['id'].'">';
        echo '<strong>'.htmlspecialchars($e['activity']).'</strong><br>';
        if (!empty($e['description'])) {
            echo '<small>'.nl2br(htmlspecialchars($e['description'])).'</small><br>';
        }
        echo '<button type="button" onclick="editExtracurricular('.$e['id'].')" class="dynamic-btn-edit">Edit</button>';
        echo '<button type="button" onclick="deleteExtracurricular('.$e['id'].', this)" class="dynamic-btn-delete">Delete</button>';
        echo '</div>';
    }
    $extracQuery->close();
    ?>
  </div>
</div>

<!-- Extracurricular Modal -->
<div id="extracModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" onclick="closeExtracModal()">&times;</span>
    <h2>Add Activity</h2>
    <form id="extracForm">
      <input type="hidden" name="id" id="extrac_id">
      <input type="hidden" name="student_id" value="<?php echo $_SESSION['user_id']; ?>">

      <div class="form-field">
        <label for="activity">Activity</label>
        <input type="text" name="activity" id="activity" required>
      </div>

      <div class="form-field">
        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4"></textarea>
      </div>

      <button type="button" onclick="saveExtracurricular()">Save</button>
    </form>
  </div>
</div>

<div class="section">
  <h3>Leadership Roles</h3>

  <button type="button" class="dynamic-add-btn" onclick="openLeadModal()">
    <i class="fas fa-plus"></i> Add Role
  </button>

  <div id="leadContainer" class="details-grid">
    <?php
    $leadQuery = $conn->prepare("SELECT * FROM leadership_role WHERE student_id=? ORDER BY id DESC");
    $leadQuery->bind_param("s", $user_id);
    $leadQuery->execute();
    $resultLead = $leadQuery->get_result();

    while($l = $resultLead->fetch_assoc()) {
        echo '<div class="details-item" data-lead-id="'.$l['id'].'">';
        echo '<strong>'.htmlspecialchars($l['role_title']).'</strong> at <em>'.htmlspecialchars($l['organization']).'</em><br>';
        if (!empty($l['year'])) echo '<small> '.htmlspecialchars($l['year']).'</small><br>';
        echo '<button type="button" onclick="editLeadership('.$l['id'].')" class="dynamic-btn-edit">Edit</button>';
        echo '<button type="button" onclick="deleteLeadership('.$l['id'].', this)" class="dynamic-btn-delete">Delete</button>';
        echo '</div>';
    }
    $leadQuery->close();
    ?>
  </div>
</div>

<!-- Leadership Modal -->
<div id="leadModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" onclick="closeLeadModal()">&times;</span>
    <h2>Add Leadership Role</h2>
    <form id="leadForm">
      <input type="hidden" name="id" id="lead_id">
      <input type="hidden" name="student_id" value="<?php echo $_SESSION['user_id']; ?>">

      <div class="form-field">
        <label for="role_title">Role Title</label>
        <input type="text" name="role_title" id="role_title" required>
      </div>

      <div class="form-field">
        <label for="organization">Organization</label>
        <input type="text" name="organization" id="organization" required>
      </div>

      <div class="form-field">
        <label for="year">Year</label>
        <input type="number" name="year" id="year" min="1900" max="2100">
      </div>

      <button type="button" onclick="saveLeadership()">Save</button>
    </form>
  </div>
</div>

<!-- Internship Section -->
<div class="section">
  <h3>Internship Experience</h3>

  <!-- Add Internship Button -->
  <button type="button" class="dynamic-add-btn" onclick="openInternshipModal()">
    <i class="fas fa-plus"></i> Add Internship
  </button>

  <div id="internshipContainer" class="details-grid">
  <?php
  $query = $conn->prepare("
    SELECT i.*, c.company_name
    FROM internship_experience i
    LEFT JOIN company c ON i.company_id = c.company_id
    WHERE i.student_id = ?
    ORDER BY i.experience_id DESC
  ");
  $query->bind_param("s", $user_id);
  $query->execute();
  $result = $query->get_result();

  while ($exp = $result->fetch_assoc()) {
      echo '<div class="details-item" data-experience-id="'.$exp['experience_id'].'" data-company-id="'.$exp['company_id'].'">';
      echo '<strong>'.htmlspecialchars($exp['position']).'</strong><br>';
      echo '<em class="company_name">'.htmlspecialchars($exp['company_name']).'</em><br>';
      echo '<small class="dates">'.$exp['start_date'].' to '.$exp['end_date'].'</small><br><br>';
      if(!empty($exp['reflection'])) echo '<small class="reflection">'.nl2br(htmlspecialchars($exp['reflection'])).'</small><br>';
      if(!empty($exp['internship_cert_url'])) echo '<a href="'.htmlspecialchars($exp['internship_cert_url']).'" target="_blank">Certificate</a><br>';
      echo '<button type="button" onclick="editInternship('.$exp['experience_id'].')" class="dynamic-btn-edit">Edit</button>';
      echo '<button type="button" onclick="deleteInternship('.$exp['experience_id'].', this)" class="dynamic-btn-delete">Delete</button>';
      echo '</div>';
  }
  $query->close();
  ?>
  </div>

</div>

<!-- Internship Modal -->
<div id="internshipModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" onclick="closeInternshipModal()">&times;</span>
    <h2>Add Internship</h2>

    <form id="internshipForm">
      <input type="hidden" name="experience_id" id="experience_id">
      <input type="hidden" name="student_id" value="<?php echo $_SESSION['user_id']; ?>">

      <div class="form-field">
        <label for="company_id">Company</label>
        <select name="company_id" id="company_id" required>
          <option value="">Select a company</option>
          <?php
          $companies = $conn->query("SELECT company_id, company_name FROM company ORDER BY company_name ASC");
          while ($c = $companies->fetch_assoc()) {
              echo '<option value="'.$c['company_id'].'">'.htmlspecialchars($c['company_name']).'</option>';
          }
          ?>
        </select>
      </div>

      <div class="form-field">
        <label for="position">Position</label>
        <input type="text" name="position" id="position" required>
      </div>

      <div class="form-field">
        <label for="start_date">Start Date</label>
        <input type="date" name="start_date" id="start_date" required>
      </div>

      <div class="form-field">
        <label for="end_date">End Date</label>
        <input type="date" name="end_date" id="end_date" required>
      </div>

      <div class="form-field">
        <label for="reflection">Reflection</label>
        <textarea name="reflection" id="reflection" rows="4"></textarea>
      </div>

      <div class="form-field">
        <label for="internship_cert_url">Certificate URL</label>
        <input type="url" name="internship_cert_url" id="internship_cert_url">
      </div>

      <button type="button" onclick="saveInternship()">Save</button>
    </form>
  </div>
</div>

<!-- Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h2>Edit Profile</h2>
    <form method="POST" enctype="multipart/form-data">

      <div class="form-field">
        <label>Program</label>
        <select name="program_code" required>
          <option value="">-- Select Program --</option>
          <?php foreach ($programList as $p): ?>
            <option value="<?php echo $p['program_code']; ?>" 
              <?php if (($student['program_code'] ?? '') == $p['program_code']) echo 'selected'; ?>>
              <?php echo htmlspecialchars($p['program_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-field">
        <label>CGPA</label>
        <input type="text" name="cgpa" value="<?php echo htmlspecialchars($student['cgpa'] ?? ''); ?>">
      </div>

      <div class="form-field">
        <label>Graduation Year</label>
        <input type="number" name="graduation_year" value="<?php echo htmlspecialchars($student['graduation_year'] ?? ''); ?>">
      </div>

      <div class="form-field">
        <label>Internship Status</label>
        <select name="internship_status">
          <option value="LOOKING" <?php if(($student['internship_status'] ?? '')=='LOOKING') echo 'selected'; ?>>Looking</option>
          <option value="ONGOING" <?php if(($student['internship_status'] ?? '')=='ONGOING') echo 'selected'; ?>>Ongoing</option>
          <option value="COMPLETED" <?php if(($student['internship_status'] ?? '')=='COMPLETED') echo 'selected'; ?>>Completed</option>
        </select>
      </div>

      <div class="form-field">
        <label>Portfolio Link</label>
        <input type="url" name="portfolio_link" value="<?php echo htmlspecialchars($student['portfolio_link'] ?? ''); ?>">
      </div>

      <div class="form-field">
        <label>Profile Picture</label>
        <input type="file" name="profile_picture" accept="image/*">
      </div>

      <div class="form-field">
        <label>About You</label>
        <textarea name="about"><?php echo htmlspecialchars($student['about'] ?? ''); ?></textarea>
      </div>

      <div class="form-field">
        <label>Skills</label>
        <div class="skill-picker">
          <input type="text" id="skillInputModal" placeholder="Type to search skills...">
          <div id="skillSuggestionsModal" class="suggestions"></div>
          <div id="selectedSkillsModal" class="selected-skills"></div>
        </div>
        <input type="hidden" name="skills[]" id="selectedSkillValuesModal">
      </div>


      <button type="submit">Save Changes</button>
    </form>
  </div>
</div>

<script>
function openModal() { document.getElementById("editModal").style.display = "block"; }
function closeModal() { document.getElementById("editModal").style.display = "none"; }
window.onclick = function(event) {
  const modal = document.getElementById("editModal");
  if (event.target == modal) closeModal();
}
</script>

<script>
const allSkills = <?php echo json_encode($allSkills); ?>;
const preselected = <?php echo json_encode($studentSkills ?? []); ?>;

const skillInputModal = document.getElementById('skillInputModal');
const suggestionsModal = document.getElementById('skillSuggestionsModal');
const selectedSkillsDivModal = document.getElementById('selectedSkillsModal');
const selectedSkillValuesModal = document.getElementById('selectedSkillValuesModal');

let selectedSkillsModalArray = [...preselected]; // copy preselected skills

function renderSelectedSkillsModal() {
  selectedSkillsDivModal.innerHTML = '';
  selectedSkillsModalArray.forEach(id => {
    const skill = allSkills.find(s => s.skill_id == id);
    if (skill) {
      const tag = document.createElement('div');
      tag.className = 'skill-tag';
      tag.innerHTML = `${skill.skill_name} <span data-id="${skill.skill_id}">&times;</span>`;
      selectedSkillsDivModal.appendChild(tag);
    }
  });
  selectedSkillValuesModal.value = JSON.stringify(selectedSkillsModalArray);
}

function showSuggestionsModal(text) {
  const filtered = allSkills.filter(s => 
    s.skill_name.toLowerCase().includes(text.toLowerCase()) &&
    !selectedSkillsModalArray.includes(s.skill_id)
  );
  suggestionsModal.innerHTML = '';
  if (filtered.length) {
    filtered.forEach(s => {
      const div = document.createElement('div');
      div.textContent = s.skill_name;
      div.onclick = () => {
        selectedSkillsModalArray.push(s.skill_id);
        renderSelectedSkillsModal();
        suggestionsModal.style.display = 'none';
        skillInputModal.value = '';
      };
      suggestionsModal.appendChild(div);
    });
    suggestionsModal.style.display = 'block';
  } else {
    suggestionsModal.style.display = 'none';
  }
}

// Event listeners
skillInputModal.addEventListener('input', () => {
  const val = skillInputModal.value.trim();
  if (val.length > 0) showSuggestionsModal(val);
  else suggestionsModal.style.display = 'none';
});

selectedSkillsDivModal.addEventListener('click', (e) => {
  if (e.target.closest('span')) {
    const id = parseInt(e.target.getAttribute('data-id'));
    selectedSkillsModalArray = selectedSkillsModalArray.filter(s => s !== id);
    renderSelectedSkillsModal();
  }
});

// When opening modal
function openModal() {
  document.getElementById('editModal').style.display = 'block';
  renderSelectedSkillsModal(); // populate the modal with current skills
}

// When saving modal
function saveModalToForm() {
  document.getElementById('selectedSkillValues').value = JSON.stringify(selectedSkillsModalArray); 
  closeModal();
}

function closeModal() {
  document.getElementById('editModal').style.display = 'none';
}
</script>

<script>
//CERTIFICATION MODAL 
function openCertModal() {
    document.getElementById('certModal').style.display = 'block';
}

function closeCertModal() {
    document.getElementById('certModal').style.display = 'none';
}

function saveCertification() {
    const form = document.getElementById('certForm');
    const formData = new FormData(form);

    // Check if editing
    const editId = form.dataset.editId;
    if(editId) formData.append('cert_id', editId);

    fetch('save_cert.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            if(editId) {
                const oldDiv = document.querySelector(`[data-cert-id='${editId}']`);
                const newDiv = addCertificationToDOM(data.cert);
                oldDiv.replaceWith(newDiv);
            } else {
                const container = document.getElementById('certificationsContainer');
                container.prepend(addCertificationToDOM(data.cert));
            }
            closeCertModal();
            form.reset();
            delete form.dataset.editId;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => console.error(err));
}


function addCertificationToDOM(cert) {
    const div = document.createElement('div');
    div.className = 'details-item';
    div.dataset.certId = cert.cert_id;

    div.innerHTML = `
        <strong>${cert.cert_name}</strong><br>
        <span>${cert.issuer}</span><br>
        ${cert.cert_url ? `<a href="${cert.cert_url}" target="_blank">Link</a><br>` : ''}
        ${cert.date_obtained ? `<small>${cert.date_obtained}</small><br>` : ''}
        <button type="button" onclick="editCertification(${cert.cert_id})" class="dynamic-btn-edit">Edit</button>
        <button type="button" onclick="deleteCertification(${cert.cert_id}, this)" class="dynamic-btn-delete">Delete</button>
    `;

    return div; 
}

function editCertification(certId) {
    // Fetch existing data (or pass it via data-* attributes)
    const div = document.querySelector(`[data-cert-id='${certId}']`);
    const name = div.querySelector('strong').innerText;
    const issuer = div.querySelector('span').innerText;
    const url = div.querySelector('a') ? div.querySelector('a').href : '';
    const date = div.querySelector('small') ? div.querySelector('small').innerText : '';

    // Fill modal form
    const form = document.getElementById('certForm');
    form.cert_name.value = name;
    form.issuer.value = issuer;
    form.cert_url.value = url;
    form.date_obtained.value = date;

    form.dataset.editId = certId; // mark as editing

    openCertModal();
}

function deleteCertification(certId, btn) {
    if (!confirm('Are you sure you want to delete this certification?')) return;

    fetch('delete_cert.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `cert_id=${certId}`
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            btn.parentElement.remove(); // remove from DOM
        } else {
            alert('Failed to delete: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => console.error(err));
}
</script>

<script>
// PROJECT MODAL CONTROL
function openProjectModal() {
    document.getElementById('projectModal').style.display = 'block';
}

function closeProjectModal() {
    document.getElementById('projectModal').style.display = 'none';
}

function saveProject() {
    const form = document.getElementById('projectForm');
    const formData = new FormData(form);

    // Check if editing
    const editId = form.dataset.editId;
    if (editId) formData.append('project_id', editId);

    fetch('save_project.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            
            if (editId) {
                // Replace old project DOM with updated version
                const oldDiv = document.querySelector(`[data-project-id='${editId}']`);
                const newDiv = addProjectToDOM(data.project);
                oldDiv.replaceWith(newDiv);
            } else {
                // Add new project to top of list
                const container = document.getElementById('projectsContainer');
                container.prepend(addProjectToDOM(data.project));
            }

            closeProjectModal();
            form.reset();
            delete form.dataset.editId;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => console.error(err));
}

function addProjectToDOM(proj) {
    const div = document.createElement('div');
    div.className = 'details-item';
    div.dataset.projectId = proj.project_id;

    div.innerHTML = `
        <strong>${proj.project_title}</strong><br>
        ${proj.project_link ? `<a href="${proj.project_link}" target="_blank">Project Link</a><br>` : ''}
        ${proj.description ? `<small>${proj.description.replace(/\n/g, '<br>')}</small><br>` : ''}

        <button type="button" onclick="editProject(${proj.project_id})" class="dynamic-btn-edit">Edit</button>
        <button type="button" onclick="deleteProject(${proj.project_id}, this)" class="dynamic-btn-delete">Delete</button>
    `;

    return div;
}

function editProject(projectId) {
    const div = document.querySelector(`[data-project-id='${projectId}']`);
    
    const title = div.querySelector('strong').innerText;
    const link = div.querySelector('a') ? div.querySelector('a').href : '';
    const desc = div.querySelector('small') ? div.querySelector('small').innerHTML.replace(/<br>/g, '\n') : '';

    // Fill modal
    const form = document.getElementById('projectForm');
    form.project_title.value = title;
    form.project_link.value = link;
    form.description.value = desc;

    form.dataset.editId = projectId;

    openProjectModal();
}

function deleteProject(projectId, btn) {
    if (!confirm('Delete this project?')) return;

    fetch('delete_project.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `project_id=${projectId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            btn.parentElement.remove();
        } else {
            alert('Failed to delete: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => console.error(err));
}
</script>
<script>
// Extracurricular Modal Control
function openExtracModal() {
    document.getElementById('extracModal').style.display = 'block';
}

function closeExtracModal() {
    document.getElementById('extracModal').style.display = 'none';
}

function saveExtracurricular() {
    const form = document.getElementById('extracForm');
    const formData = new FormData(form);

    const editId = form.dataset.editId;
    if (editId) formData.append('id', editId);

    fetch('save_extracurricular.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (editId) {
                const oldDiv = document.querySelector(`[data-extrac-id='${editId}']`);
                const newDiv = addExtracToDOM(data.extrac);
                oldDiv.replaceWith(newDiv);
            } else {
                const container = document.getElementById('extracContainer');
                container.prepend(addExtracToDOM(data.extrac));
            }
            closeExtracModal();
            form.reset();
            delete form.dataset.editId;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => console.error(err));
}

function addExtracToDOM(extrac) {
    const div = document.createElement('div');
    div.className = 'details-item';
    div.dataset.extracId = extrac.id;

    div.innerHTML = `
        <strong>${extrac.activity}</strong><br>
        ${extrac.description ? `<small>${extrac.description.replace(/\n/g,'<br>')}</small><br>` : ''}
        <button type="button" onclick="editExtracurricular(${extrac.id})" class="dynamic-btn-edit">Edit</button>
        <button type="button" onclick="deleteExtracurricular(${extrac.id}, this)" class="dynamic-btn-delete">Delete</button>
    `;
    return div;
}

function editExtracurricular(id) {
    const div = document.querySelector(`[data-extrac-id='${id}']`);
    const activity = div.querySelector('strong').innerText;
    const desc = div.querySelector('small') ? div.querySelector('small').innerHTML.replace(/<br>/g, '\n') : '';

    const form = document.getElementById('extracForm');
    form.activity.value = activity;
    form.description.value = desc;

    form.dataset.editId = id;

    openExtracModal();
}

function deleteExtracurricular(id, btn) {
    if (!confirm('Delete this activity?')) return;

    fetch('delete_extracurricular.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `id=${id}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            btn.parentElement.remove();
        } else {
            alert('Delete failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => console.error(err));
}
</script>
<script>
// Leadership Modal Control
function openLeadModal() {
    document.getElementById('leadModal').style.display = 'block';
}

function closeLeadModal() {
    document.getElementById('leadModal').style.display = 'none';
}

function saveLeadership() {
    const form = document.getElementById('leadForm');
    const formData = new FormData(form);

    const editId = form.dataset.editId;
    if (editId) formData.append('id', editId);

    fetch('save_leadership.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (editId) {
                const oldDiv = document.querySelector(`[data-lead-id='${editId}']`);
                const newDiv = addLeadershipToDOM(data.leadership);
                oldDiv.replaceWith(newDiv);
            } else {
                const container = document.getElementById('leadContainer');
                container.prepend(addLeadershipToDOM(data.leadership));
            }
            closeLeadModal();
            form.reset();
            delete form.dataset.editId;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => console.error(err));
}

function addLeadershipToDOM(lead) {
    const div = document.createElement('div');
    div.className = 'details-item';
    div.dataset.leadId = lead.id;

    div.innerHTML = `
        <strong>${lead.role_title}</strong> at <em>${lead.organization}</em><br>
        ${lead.year ? `<small>Year: ${lead.year}</small><br>` : ''}
        <button type="button" onclick="editLeadership(${lead.id})" class="dynamic-btn-edit">Edit</button>
        <button type="button" onclick="deleteLeadership(${lead.id}, this)" class="dynamic-btn-delete">Delete</button>
    `;
    return div;
}

function editLeadership(id) {
    const div = document.querySelector(`[data-lead-id='${id}']`);
    const role = div.querySelector('strong').innerText;
    const org = div.querySelector('em').innerText;
    const year = div.querySelector('small') ? div.querySelector('small').innerText.replace('Year: ','') : '';

    const form = document.getElementById('leadForm');
    form.role_title.value = role;
    form.organization.value = org;
    form.year.value = year;

    form.dataset.editId = id;

    openLeadModal();
}

function deleteLeadership(id, btn) {
    if (!confirm('Delete this role?')) return;

    fetch('delete_leadership.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `id=${id}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            btn.parentElement.remove();
        } else {
            alert('Delete failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => console.error(err));
}
</script>
<script>
// Internship Modal Control
function openInternshipModal() { document.getElementById('internshipModal').style.display = 'block'; }
function closeInternshipModal() { document.getElementById('internshipModal').style.display = 'none'; }

function saveInternship() {
    const form = document.getElementById('internshipForm');
    const formData = new FormData(form);

    const editId = form.dataset.editId;
    if(editId) formData.append('experience_id', editId);

    fetch('save_internship.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            if(editId) {
                const oldDiv = document.querySelector(`[data-experience-id='${editId}']`);
                const newDiv = addInternshipToDOM(data.internship);
                oldDiv.replaceWith(newDiv);
            } else {
                const container = document.getElementById('internshipContainer');
                container.prepend(addInternshipToDOM(data.internship));
            }
            closeInternshipModal();
            form.reset();
            delete form.dataset.editId;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => console.error(err));
}

function addInternshipToDOM(i) {
    const div = document.createElement('div');
    div.className = 'details-item';
    div.dataset.experienceId = i.experience_id;

    div.innerHTML = `
        <strong>${i.position}</strong><br>
        <em>${i.company_name}</em><br>
        <small>${i.start_date} to ${i.end_date}</small><br><br>
        ${i.reflection ? `<small>${i.reflection.replace(/\n/g,'<br>')}</small><br>` : ''}
        ${i.internship_cert_url ? `<a href="${i.internship_cert_url}" target="_blank">Certificate</a><br>` : ''}
        <button type="button" onclick="editInternship(${i.experience_id})" class="dynamic-btn-edit">Edit</button>
        <button type="button" onclick="deleteInternship(${i.experience_id}, this)" class="dynamic-btn-delete">Delete</button>
    `;
    return div;
}

function editInternship(id) {
    const div = document.querySelector(`[data-experience-id='${id}']`);
    const position = div.querySelector('strong').innerText;
    const companyId = div.dataset.companyId;
    const dates = div.querySelector('.dates').innerText.split(' to ');
    const reflection = div.querySelector('.reflection')?.innerText || '';
    const cert = div.querySelector('a')?.href || '';

    const form = document.getElementById('internshipForm');
    form.position.value = position;
    form.start_date.value = dates[0];
    form.end_date.value = dates[1];
    form.reflection.value = reflection;
    form.internship_cert_url.value = cert;

    // Select the correct company
    const select = form.company_id;
    for (let opt of select.options) {
        opt.selected = opt.value === companyId;
    }

    form.dataset.editId = id; // optional: store edit ID
    openInternshipModal();
}


function deleteInternship(id, btn) {
    if(!confirm('Delete this internship?')) return;
    fetch('delete_internship.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`experience_id=${id}`
    })
    .then(res => res.json())
    .then(data => {
        if(data.status==='success') btn.parentElement.remove();
        else alert('Delete failed: '+(data.message||'Unknown'));
    })
    .catch(err=>console.error(err));
}
</script>
</body>
</html>
