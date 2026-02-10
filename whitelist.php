<?php
session_start();
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STUDENT') {
    header("Location: login.html");
    exit;
}

require_once 'db.php';

$student_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Student';

// Fetch profile picture
$query = $conn->prepare("SELECT profile_picture FROM student WHERE student_id = ?");
$query->bind_param("s", $student_id);
$query->execute();
$result = $query->get_result();
$student = $result->fetch_assoc();
$profile_pic = $student['profile_picture'] ?? 'images/default_profile.png';
$query->close();

// Check if student has submitted any BLI-01 form
$stmt = $conn->prepare("SELECT submission_id FROM bli01_form WHERE student_id = ? ORDER BY submitted_on DESC");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$submission_ids = [];
while ($row = $result->fetch_assoc()) {
    $submission_ids[] = $row['submission_id'];
}
$stmt->close();

$bli01_submitted = count($submission_ids) > 0;

$status_counts = ['PENDING'=>0, 'APPROVED'=>0, 'REJECTED'=>0];
$companies = [];

if ($bli01_submitted) {
    // Prepare placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($submission_ids), '?'));
    $types = str_repeat('i', count($submission_ids));

    // Fetch companies joined with company table
    $query = "
        SELECT c.company_name, sc.approval_status 
        FROM selected_company sc
        JOIN company c ON sc.company_id = c.company_id
        WHERE sc.submission_id IN ($placeholders)
        ORDER BY c.company_name ASC
    ";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$submission_ids);
        $stmt->execute();
        $result = $stmt->get_result();

        $i = 1;
        while ($row = $result->fetch_assoc()) {
            $companies[] = $row;

            // Count status
            $status = strtoupper($row['approval_status']);
            if (isset($status_counts[$status])) {
                $status_counts[$status]++;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Whitelist | FYP System</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { margin:0; font-family:'Nunito', sans-serif; background:#F5F5F8; }
header { background:#9F5EB7; color:white; padding:16px 24px; display:flex; justify-content:space-between; align-items:center; position:relative; }
header h1 { margin:0; font-size:22px; font-weight:800; position:absolute; left:50%; transform:translateX(-50%); }
.welcome { display:flex; align-items:center; gap:8px; }
.hamburger {
  font-size: 24px;
  cursor: pointer;
  background: none;
  border: none;
  color: white;
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
.main-content {
  padding: 40px 60px;
  background: #F5F5F8;
}
.fill-form-btn {
    display:inline-block;
    background:#9F5EB7;
    color:white;
    padding:12px 20px;
    border-radius:10px;
    border:none;
    font-size:16px;
    font-weight:700;
    font-family:'Nunito',sans-serif;
    cursor:pointer;
    margin-top:20px;
    text-decoration:none;
    transition:0.3s;
}
.fill-form-btn:hover { background:#E09D46; transform:scale(1.05); }
.whitelist-table {
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}
.whitelist-table th, .whitelist-table td {
    padding:12px;
    border:1px solid #ddd;
    text-align:left;
}
.whitelist-table th {
    background:#9F5EB7;
    color:white;
}

.note-box { display:flex; align-items:center; gap:10px; background:#EDE0F8; border-left:6px solid #9F5EB7; padding:12px 16px; border-radius:6px; margin-bottom:20px; color:#9F5EB7; font-weight:600; }
.note-box i { font-size:20px; }
.status {
    font-weight:700;
}
.status.PENDING { color:#E09D46; }
.status.APPROVED { color:green; }
.status.REJECTED { color:red; }
</style>
</head>
<body>

<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="document.getElementById('mySidebar').style.width='0'">&times;</a>
  <a href="student_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
  <a href="companies.php"><i class="fas fa-magnifying-glass"></i> Find Companies</a>
  <a href="company_ranking.php"><i class="fas fa-ranking-star"></i> Company Ranking</a>
  <a href="career_readiness.php"><i class="fas fa-seedling"></i> Career Readiness</a>
  <a href="whitelist.php"><i class="fas fa-list"></i> Whitelist</a> 
  <a href="login.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
</div>

<header>
  <button class="hamburger" onclick="window.location.href='student_dashboard.php'">
    <i class="fas fa-arrow-left"></i>
  </button>
  <h1>Whitelist</h1>
  <div class="welcome">
    Hi, <?= htmlspecialchars($name) ?>
    <a href="student_profile.php">
      <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile" class="profile-pic">
    </a>
  </div>
</header>

<div class="main-content">
<?php if ($bli01_submitted): ?>
    <div class="note-box">
        <i class="fas fa-info-circle"></i>
        <span>Here is the list of companies you selected in your BLI-01 form.</span>
    </div>

    <table class="whitelist-table">
        <tr>
            <th>No.</th>
            <th>Company Name</th>
            <th>Status</th>
        </tr>
        <?php if (!empty($companies)): ?>
            <?php $i = 1; foreach ($companies as $row): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['company_name']) ?></td>
                <td class="status <?= htmlspecialchars($row['approval_status']) ?>">
                    <?= htmlspecialchars($row['approval_status']) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="3">No whitelist data available.</td></tr>
        <?php endif; ?>
    </table>

    <hr style="border:1px solid #ccc; margin:45px 0;">

    <!-- Card container aligned with table width -->
    <div style="width:100%; max-width:100%; margin:30px auto; border-radius:0px; overflow:hidden; box-shadow:0 2px 4px rgba(0,0,0,0.1);">

        <!-- Card header -->
        <div style="background: #9F5EB7; color:white; padding:16px 0; text-align:center; font-size:20px; font-weight:700;">
            Whitelist Status Overview
        </div>

        <!-- Card body with chart -->
        <div style="background:white; padding:20px;">
            <canvas id="statusChart" style="width:100%; max-width:350px; height:300px; display:block; margin:0 auto; margin-top: 10px; margin-bottom: 10px; "></canvas>
        </div>
    </div>

    <script>
    const ctx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Pending', 'Approved', 'Rejected'],
            datasets: [{
                data: [<?= $status_counts['PENDING'] ?>, <?= $status_counts['APPROVED'] ?>, <?= $status_counts['REJECTED'] ?>],
                backgroundColor: ['#E09D46', 'green', 'red'],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { size: 14 } }
                }
            }
        }
    });
    </script>
<?php else: ?>
    <div style="text-align:center; margin-top:80px; color:#555;">
        <h2>You haven’t submitted your BLI-01 Form yet.</h2>
        <p>Submit the BLI-01 Form to access your whitelist.</p>
        <button class="fill-form-btn" onclick="window.location.href='bli01_form.php'">Fill BLI-01 Form</button>
    </div>
<?php endif; ?>
</div>

</body>
</html>
