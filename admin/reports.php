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

/* 1. USER STATS */
$userStats = $conn->query("SELECT role, COUNT(*) AS total FROM user GROUP BY role");
$usersCount = ["STUDENT"=>0,"STAFF"=>0,"ADMIN"=>0];
while ($u = $userStats->fetch_assoc()) { $usersCount[$u['role']] = $u['total']; }

/* 2. COMPANY RANKING */
$companyRanking = $conn->query("SELECT company_name, average_rating FROM company ORDER BY average_rating DESC");
$companyLabels = [];
$companyRatings = [];
while($c = $companyRanking->fetch_assoc()) {
    $companyLabels[] = $c['company_name'];
    $companyRatings[] = $c['average_rating'] ?? 0;
}

/* 3. FEEDBACK SUMMARY */
$feedbackStats = $conn->query("SELECT COUNT(*) AS total_feedback, AVG(rating) AS avg_rating FROM feedback")->fetch_assoc();

/* 4. CRS DISTRIBUTION */
$crsData = $conn->query("SELECT score FROM career_readiness_score");
$range1=$range2=$range3=$range4=0;
while ($c = $crsData->fetch_assoc()) {
    $score = $c['score'];
    if ($score <=50) $range1++;
    else if ($score<=70) $range2++;
    else if ($score<=85) $range3++;
    else $range4++;
}

/* 5. MOST SELECTED COMPANIES (BLI01) */
$mostSelected = $conn->query("
    SELECT c.company_name, COUNT(sc.id) AS total_selected
    FROM selected_company sc
    JOIN company c ON c.company_id = sc.id
    JOIN bli01_form b ON sc.id = b.submission_id
    GROUP BY sc.id
    ORDER BY total_selected DESC
    LIMIT 10
");

$prefLabels = [];
$prefCounts = [];
while($row = $mostSelected->fetch_assoc()) {
    $prefLabels[] = $row['company_name'];
    $prefCounts[] = $row['total_selected'];
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Reports | FYP System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { margin:0; font-family:'Nunito',sans-serif; background:#f5f5f5; }
a { text-decoration:none; color:inherit; }
header { background-color:#9F5EB7; color:white; padding:16px 24px; display:flex; align-items:center; position:relative; z-index:100; }
header h1 { font-size:22px; font-weight:800; position:absolute; left:50%; transform:translateX(-50%); }
.welcome { margin-left:auto; display:flex; align-items:center; gap:10px; }
.welcome img { width:40px; height:40px; border-radius:50%; border:2px solid white; }
.hamburger { font-size:24px; cursor:pointer; background:none; border:none; color:white; }
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
    .sidebar a {
    white-space: nowrap; /* keep the text on one line */
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
.container { max-width:1100px; margin:40px auto; padding:0 20px; display:grid; grid-template-columns: repeat(auto-fit, minmax(300px,1fr)); gap:35px; }
.card { background:white; padding:20px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
.card h2 { margin:0 0 15px 0; font-size:20px; font-weight:800; }
canvas { max-width:100%; }
</style>
</head>
<body>

<!-- SIDEBAR -->
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
  <h1>Reports</h1>
  <div class="welcome">
      Hi, <?= htmlspecialchars($name) ?>
      <img src="<?= $profile_pic ?>">
  </div>
</header>

<div class="container">

<!-- User Stats -->
<div class="card">
<h2>User Statistics</h2>
<canvas id="userPieChart" height="200"></canvas>
</div>

<!-- Company Ranking Chart -->
<div class="card" style="grid-column: span 2;">
<h2>Company Ranking</h2>
<canvas id="companyChart"></canvas>
</div>

<!-- CRS Distribution Chart -->
<div class="card">
<h2>CRS Score Distribution</h2>
<canvas id="crsChart" height="200"></canvas>
</div>

<!-- Most Selected Companies -->
<div class="card">
  <h2>Most Selected Companies (BLI01)</h2>
  <canvas id="prefChart"></canvas>
</div>

<?php
// Count anonymous vs non-anonymous
$anonCount = $conn->query("SELECT COUNT(*) FROM feedback WHERE is_anonymous=1")->fetch_row()[0];
$nonAnonCount = $conn->query("SELECT COUNT(*) FROM feedback WHERE is_anonymous=0")->fetch_row()[0];
$totalFeedback = max($anonCount + $nonAnonCount, 1); // avoid division by 0
$anonPercent = round(($anonCount/$totalFeedback)*100);
$nonAnonPercent = round(($nonAnonCount/$totalFeedback)*100);
?>

<div class="card" style="padding:15px;">
  <h2>Feedback Summary</h2>

  <div style="display:flex; gap:12px; flex-wrap:wrap; justify-content:start;">
    <!-- Total Feedback -->
    <div style="flex:1 1 120px; min-width:120px; background:#6A3A8D; color:white; padding:12px; border-radius:10px; text-align:center; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
      <i class="fas fa-comments" style="font-size:18px; margin-bottom:5px; display:block;"></i>
      <div style="font-size:20px; font-weight:bold;"><?= $totalFeedback ?></div>
      <div style="font-size:14px;">Total Feedback</div>
    </div>

    <!-- Average Rating -->
    <div style="flex:1 1 120px; min-width:120px; background:#D8D262; color:#333; padding:12px; border-radius:10px; text-align:center; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
      <i class="fas fa-star" style="font-size:18px; margin-bottom:5px; display:block;"></i>
      <div style="font-size:20px; font-weight:bold;"><?= number_format($feedbackStats['avg_rating'],2) ?></div>
      <div style="font-size:14px;">Average Rating</div>
    </div>
  </div>

  <!-- Anonymous vs Non-Anonymous Bar -->
  <div style="margin-top:20px;">
    <div style="display:flex; justify-content:space-between; font-size:14px; margin-bottom:5px; font-weight:bold;">
      <span>Anonymous</span>
      <span>Non-Anonymous</span>
    </div>
    <div style="background:#eee; border-radius:12px; overflow:hidden; height:35px; display:flex;">
      <div style="width:<?= $anonPercent ?>%; background:#6A3A8D; display:flex; align-items:center; justify-content:center; color:white; font-size:14px; font-weight:bold;">
        <?= $anonPercent ?>%
      </div>
      <div style="width:<?= $nonAnonPercent ?>%; background:#D8D262; display:flex; align-items:center; justify-content:center; color:#333; font-size:14px; font-weight:bold;">
        <?= $nonAnonPercent ?>%
      </div>
    </div>
  </div>
</div>



</div>

<script>
function openSidebar(){ document.getElementById("mySidebar").style.width="260px"; }
function closeSidebar(){ document.getElementById("mySidebar").style.width="0"; }

// User Statistics Pie Chart
const ctxU = document.getElementById('userPieChart').getContext('2d');
new Chart(ctxU, {
    type: 'pie',
    data: {
        labels: ['Students', 'Staff', 'Admins'],
        datasets: [{
            data: [<?= $usersCount['STUDENT'] ?>, <?= $usersCount['STAFF'] ?>, <?= $usersCount['ADMIN'] ?>],
            backgroundColor: [
                'rgba(159,94,183,0.7)',
                'rgba(158,142,212,0.7)',
                'rgba(200,150,220,0.7)'
            ],
            borderColor: [
                'rgba(159,94,183,1)',
                'rgba(158,142,212,1)',
                'rgba(200,150,220,1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

// Company Chart
const ctxC = document.getElementById('companyChart').getContext('2d');
new Chart(ctxC, {
    type: 'bar',
    data: {
        labels: <?= json_encode($companyLabels) ?>,
        datasets: [{
            label: 'Average Rating',
            data: <?= json_encode($companyRatings) ?>,
            backgroundColor: 'rgba(159,94,183,0.7)',
            borderColor: 'rgba(159,94,183,1)',
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: { beginAtZero: true, max:5 }
        }
    }
});

// CRS Chart
const ctxR = document.getElementById('crsChart').getContext('2d');
new Chart(ctxR, {
    type: 'bar',
    data: {
        labels: ['0-50','51-70','71-85','86-100'],
        datasets: [{
            label: 'Number of Students',
            data: [<?= $range1 ?>, <?= $range2 ?>, <?= $range3 ?>, <?= $range4 ?>],
            backgroundColor: 'rgba(158,142,212,0.7)',
            borderColor: 'rgba(158,142,212,1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

// Most Selected Companies Chart (BLI01)
const ctxP = document.getElementById('prefChart').getContext('2d');
new Chart(ctxP, {
    type: 'bar',
    data: {
        labels: <?= json_encode($prefLabels) ?>,
        datasets: [{
            label: 'Number of Students',
            data: <?= json_encode($prefCounts) ?>,
            backgroundColor: 'rgba(200,150,220,0.7)',
            borderColor: 'rgba(200,150,220,1)',
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            x: { beginAtZero: true },
            y: { ticks: { autoSkip: false } }
        }
    }
});

</script>
</body>
</html>
