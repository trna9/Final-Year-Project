<?php
session_start();
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STUDENT') {
    header("Location: login.html");
    exit;
}

require_once 'db.php';

$student_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Student';

// Fetch student info
$query = $conn->prepare("
    SELECT 
        s.student_id,
        u.name,
        u.email,
        s.contact_number,
        s.ic_passport_no,
        s.program_code,
        s.profile_picture
    FROM student s
    INNER JOIN user u ON s.student_id = u.user_id
    WHERE s.student_id = ?
");
$query->bind_param("s", $student_id);
$query->execute();
$result = $query->get_result();
$student = $result->fetch_assoc();
$query->close();

$profile_pic = $student['profile_picture'] ?? 'images/default_profile.png';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_number = trim($_POST['contact_number']);
    $ic_passport_no = trim($_POST['ic_passport_no']);
    $company_ids = $_POST['company_id'] ?? [];

    // Update student info
    $update = $conn->prepare("UPDATE student SET contact_number = ?, ic_passport_no = ? WHERE student_id = ?");
    $update->bind_param("sss", $contact_number, $ic_passport_no, $student_id);
    $update->execute();
    $update->close();

    // Insert BLI-01 entry
    $stmt = $conn->prepare("INSERT INTO bli01_form (student_id, submitted_on) VALUES (?, NOW())");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $submission_id = $conn->insert_id;
    $stmt->close();

    // Insert selected companies
    $insert = $conn->prepare("INSERT INTO selected_company (submission_id, company_id, approval_status) VALUES (?, ?, 'PENDING')");
    foreach ($company_ids as $cid) {
        if (!empty($cid)) {
            $insert->bind_param("ii", $submission_id, $cid);
            $insert->execute();
        }
    }
    $insert->close();

    echo "<script>alert('BLI-01 Form submitted successfully!'); window.location.href='whitelist.php';</script>";
    exit;
}

// Fetch companies
$companies = [];
$result = $conn->query("SELECT company_id, company_name FROM company ORDER BY company_name ASC");
while ($row = $result->fetch_assoc()) {
    $companies[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BLI-01 Form | FYP System</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body { margin:0; font-family:'Nunito',sans-serif; background:#F5F5F8; }
header { background:#9F5EB7; color:white; padding:16px 24px; display:flex; align-items:center; justify-content:space-between; position:relative; }
header h1 { margin:0; font-size:22px; font-weight:800; position:absolute; left:50%; transform:translateX(-50%); }
.welcome { display:flex; align-items:center; gap:10px; }
.profile-pic {
    width: 40px;
    height: 40px;
    min-width: 40px;
    min-height: 40px;
    border-radius: 50%;
    object-fit: cover;
    display: block;
    border: 2px solid #fff;
}
.hamburger {
  font-size: 24px;
  cursor: pointer;
  background: none;
  border: none;
  color: white;
}

/* Main Container */
.main-content { max-width:1400px; margin: auto; background:white; padding:35px; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,0.1); }
h2 { color:#9F5EB7; text-align:center; font-weight:800; margin-bottom:25px; }

/* Sections */
.section-block { margin-top:40px; }
.section-header {
    font-size: 22px;
    font-weight: 700;
    color: #9F5EB7;
    padding: 14px 18px;
    margin: 60px 0 25px;
    background: #F4ECF9;
    border-radius: 8px;
    border: none;
}

/* Two Column Layout */
.grid-2 {
    display:grid;
    grid-template-columns: repeat(2, 1fr);
    gap:20px 40px;
}

label { font-weight:600; color:#333; margin-bottom:5px; display:block; }
input[type="text"], input[type="email"], select {
    padding:10px;
    width:100%;
    border:1px solid #ccc;
    border-radius:8px;
    font-size:15px;
    font-family:'Nunito',sans-serif;
}
input[readonly] { background:#efefef; }

/* Company List */
.company-row { display:flex; align-items:center; gap:10px; margin-bottom:10px; }
.company-row select { flex:1; }

/* Buttons */
.add-btn {
    background:#E09D46; color:white; border:none; padding:8px 14px; font-family:'Nunito',sans-serif;
    border-radius:8px; font-weight:700; cursor:pointer;
}
.remove-btn {
    background:#E74C3C; color:white; border:none; padding:8px 14px; font-family:'Nunito',sans-serif;
    border-radius:8px; font-weight:700; cursor:pointer;
}
.add-btn:hover { background:#d28219; }
.remove-btn:hover { background:#c0392b; }

.submit-btn {
    margin-top:30px; width:200px; padding:14px; border:none;
    background:#E09D46; color:white; font-size:16px; font-weight:800; font-family:'Nunito',sans-serif;
    border-radius:10px; cursor:pointer; transition:0.2s; display: block;
    margin-left: auto; box-shadow: 0 6px 12px rgba(0,0,0,0.1);
}
.submit-btn:hover { background:#d28219; transform:scale(1.02); }

.back-button { margin:20px 0 0 20px; }
.back-button button {
    background:#9F5EB7; color:white; padding:10px 18px; border:none;
    border-radius:8px; font-weight:700; cursor:pointer;
}
.back-button button:hover { background:#E09D46; }

/* Responsive */
@media(max-width:900px){
    .grid-2 { grid-template-columns:1fr; }
}
</style>
</head>

<body>

<header>
  <button class="hamburger" onclick="window.location.href='whitelist.php'">
    <i class="fas fa-arrow-left"></i>
  </button>
  <h1>BLI-01 Form</h1>
    <div class="welcome">
    Hi, <?= htmlspecialchars($name) ?>
      <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile" class="profile-pic">
  </div>
</header>

<div class="main-content">
  <h2>Industrial Training Placement Form (BLI-01)</h2>

  <form method="POST">

    <!-- Section A -->
    <div class="section-block">
        <div class="section-header">Section A – Student Information</div>

        <div class="grid-2">
            <div>
                <label>Student Name</label>
                <input type="text" value="<?= htmlspecialchars($student['name']) ?>" readonly>
            </div>

            <div>
                <label>Student ID</label>
                <input type="text" value="<?= htmlspecialchars($student['student_id']) ?>" readonly>
            </div>

            <div>
                <label>Programme</label>
                <input type="text" value="<?= htmlspecialchars($student['program_code']) ?>" readonly>
            </div>

            <div>
                <label>Email</label>
                <input type="email" value="<?= htmlspecialchars($student['email']) ?>" readonly>
            </div>

            <div>
                <label>Contact Number</label>
                <input type="text" name="contact_number"
                       value="<?= htmlspecialchars($student['contact_number'] ?? '') ?>"
                       placeholder="Enter contact number" required>
            </div>

            <div>
                <label>IC / Passport No.</label>
                <input type="text" name="ic_passport_no"
                       value="<?= htmlspecialchars($student['ic_passport_no'] ?? '') ?>"
                       placeholder="Enter IC/Passport number" required>
            </div>
        </div>
    </div>

    <!-- Section B -->
    <div class="section-block">
        <div class="section-header">Section B – Company Selections (Up to 20)</div>

        <div id="company-container" class="company-list">
            <div class="company-row">
                <select name="company_id[]" required>
                    <option value="">-- Select Company --</option>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?= $c['company_id'] ?>"><?= htmlspecialchars($c['company_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="add-btn" onclick="addCompany()">+ Add</button>
            </div>
        </div>
    </div>

    <!-- Section C -->
    <div class="section-block">
        <div class="section-header">Section C – Academic Supervisor's Approval</div>
        <p><em>To be completed by Academic Supervisor after student submission.</em></p>
    </div>

    <button type="submit" class="submit-btn">Submit Form</button>

  </form>
</div>

<script>
let maxCompanies = 20;

function addCompany() {
    const container = document.getElementById('company-container');
    const count = container.querySelectorAll('.company-row').length;

    if (count >= maxCompanies) {
        alert("Maximum 20 companies allowed.");
        return;
    }

    const newRow = document.createElement("div");
    newRow.classList.add("company-row");

    newRow.innerHTML = `
        <select name="company_id[]" required>
            <option value="">-- Select Company --</option>
            <?= implode('', array_map(fn($c) => "<option value='{$c['company_id']}'>" . htmlspecialchars($c['company_name']) . "</option>", $companies)) ?>
        </select>
        <button type="button" class="remove-btn" onclick="removeCompany(this)">− Remove</button>
    `;

    container.appendChild(newRow);
}

function removeCompany(button) {
    button.parentElement.remove();
}
</script>

</body>
</html>
