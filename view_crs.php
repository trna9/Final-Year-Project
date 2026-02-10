<?php
session_start();
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STAFF') {
    header("Location: login.html");
    exit;
}

require_once 'db.php';

// Get student ID from the "My Students" table (GET parameter)
if (!isset($_GET['id'])) {
    die("Student ID not provided.");
}
$student_id = $_GET['id'];

// Logged-in staff info
$staff_id = $_SESSION['user_id'];
$staff_name = $_SESSION['name'] ?? 'Staff';

// Fetch logged-in staff profile picture
$stmt = $conn->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$staff = $stmt->get_result()->fetch_assoc();
$staff_profile_pic = $staff['profile_picture'] ?? 'images/default_profile.png';
$stmt->close();

// Fetch staff profile picture
$query = $conn->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
$query->bind_param("s", $staff_id);
$query->execute();
$result = $query->get_result();
$staff = $result->fetch_assoc();
$profile_pic = $staff['profile_picture'] ?? 'images/default_profile.png';
$query->close();

// Fetch student info
$stmt = $conn->prepare("
    SELECT u.name, s.profile_picture
    FROM student s
    INNER JOIN user u ON s.student_id = u.user_id
    WHERE s.student_id = ? AND u.role = 'STUDENT'
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student not found.");
}

$student_name = $student['name'];
$student_profile_pic = $student['profile_picture'] ?? 'images/default_profile.png';

// Fetch latest CRS
$stmt = $conn->prepare("
    SELECT score, generated_on
    FROM career_readiness_score
    WHERE student_id = ?
    ORDER BY generated_on DESC
    LIMIT 1
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$crs = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title> View CRS | FYP System</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { margin: 0; font-family: 'Nunito', sans-serif; background-color: #F5F5F8; }

/* HEADER */
header {
  background-color: #9F5EB7;
  color: white;
  padding: 16px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
  z-index: 10;
}
header h1 {
  margin: 0;
  font-size: 22px;
  font-weight: 800;
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}
.welcome {
  display: flex;
  align-items: center;
  gap: 8px;
}
.hamburger {
  font-size: 24px;
  cursor: pointer;
  background: none;
  border: none;
  color: white;
}
.profile-pic {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid white;
  display: block;
}

/* SIDEBAR */
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
  box-shadow: 4px 0 16px rgba(0,0,0,0.4);
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
.sidebar .closebtn {
  position: absolute;
  top: 10px;
  right: 10px;
  font-size: 24px;
  color: white;
  cursor: pointer;
}

/* MAIN CONTENT */
.main-content {
  padding: 40px 60px;
  background: #F5F5F8;
}

.badge-id {
  display: inline-block;
  background: #E09D46;
  color: white;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 16px;
}

.crs-title {
  font-weight: 800;
  margin-top: 10px;
  margin-bottom: 40px;
  font-size: 24px;
}

.result-container {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  gap: 70px;
  margin-top: 40px;
  flex-wrap: wrap;
}

.left-section {
  display: flex;
  justify-content: center;
  align-items: center;
  flex: 0 0 280px;
}

.right-section {
  flex: 1;
  min-width: 300px;
}

.score-card {
  width: 100%;
  border-radius: 16px;
  padding: 30px 20px;
  background: #f7f0ff;
  text-align: center;
  box-shadow: 0 8px 20px rgba(0,0,0,0.1);
  border: 2px solid #9F5EB7;
  display: flex;
  flex-direction: column;
  gap: 20px;
  transition: transform 0.2s ease;
}
.score-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 25px rgba(0,0,0,0.2);
}
.score-number {
  font-size: 60px;
  font-weight: 700;
  color: #9F5EB7;
  margin: 0;
}
.level-badge {
  display: inline-block;
  padding: 8px 20px;
  font-weight: 700;
  font-size: 18px;
  border-radius: 12px;
  background: #6b4d99;
  color: white;
  white-space: nowrap;
  text-align: center;
  margin: 0 auto;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.level-badge:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.25);
  cursor: pointer;
}

.progress-label {
  font-size: 14px;
  color: #6b4d99;
  margin: 0;
  font-weight: 600;
}
.progress-bar {
  width: 100%;
  height: 18px;
  background: #e0d8f0;
  border-radius: 10px;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  background: #5fc255ff;
  border-radius: 10px 0 0 10px;
  transition: width 1s ease-in-out;
}

.summary-box {
  flex: 1;
  background: #f7f2ec;
  border-radius: 10px;
  border: 2px solid #E09D46;
  padding: 25px 30px;
}
.summary-header {
  background: #E09D46;
  color: white;
  font-weight: 700;
  border-radius: 8px 8px 0 0;
  padding: 10px 20px;
  margin: -25px -30px 20px -30px;
}
.summary-content p { margin-bottom: 15px; line-height: 1.6; color: #333; }
.summary-content ul { margin: 0; padding-left: 20px; }
.summary-content li { margin-bottom: 10px; line-height: 1.5; }

.action-buttons { display: flex; justify-content: flex-end; margin-top: 25px; gap: 10px; }
.pdf-btn { background: #9F5EB7; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700; }
.pdf-btn:hover { background: #5d2472ff; }

.retake-btn {
  background: #E09D46;
  color: white;
  padding: 12px 24px;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 700;
  display: block;
  width: fit-content;
  margin: 30px auto;
  margin-top: 30px;
  margin-bottom: 5px;
}
.retake-btn:hover { background: #b37320; }

.footer-text { text-align: right; font-style: italic; color: #555; margin-top: 30px; }
</style>
</head>
<body>

<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="document.getElementById('mySidebar').style.width='0'">&times;</a>
  <a href="staff_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
  <a href="assign_students.php"><i class="fas fa-user-plus"></i> Assign Students</a>
  <a href="my_students.php"><i class="fas fa-users"></i> My Students</a>
  <a href="companies.php"><i class="fas fa-building"></i> Companies</a>
  <a href="company_ranking.php"><i class="fas fa-star"></i> Company Ranking</a>
  <a href="login.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
</div>

<!-- HEADER -->
<header>
  <button class="hamburger" onclick="window.location.href='my_students.php'">
    <i class="fas fa-arrow-left"></i>
  </button>
  <h1>Student Career Readiness Score</h1>
  <div class="welcome">
    Hi, <?= htmlspecialchars($staff_name) ?>
    <a href="staff_profile.php">
      <img src="<?= htmlspecialchars($staff_profile_pic) ?>" alt="Profile" class="profile-pic">
    </a>
  </div>
</header>

<div class="main-content">
<?php if (!$crs): ?>
  <div style="text-align:center;margin-top:80px;color:#555;">
    <h2><span><?= htmlspecialchars($student_name) ?></span> has not completed the Career Readiness Assessment yet.</h2>
    <p>Once the student completes it, their results will appear here.</p>
  </div>
<?php else: ?>
  <?php
  $score = $crs['score'];

    if ($score >= 81) {
        $level = 'Thriving 🌟';
        $summary = "This student is thriving in career readiness! They demonstrate strong preparation and confidence.";
        $recommendations = [
            "🤝 Encourage them to mentor peers or take on leadership roles.",
            "💼 Advise on refining their personal brand and long-term career strategy.",
            "🌐 Support networking opportunities with industry professionals."
        ];
    } elseif ($score >= 61) {
        $level = 'Achieving 💪';
        $summary = "This student is making good progress and developing strong employability skills.";
        $recommendations = [
            "📚 Encourage continued specialization and skill development.",
            "🎓 Suggest attending professional workshops and networking events.",
            "🧩 Advise them to start a professional portfolio or LinkedIn showcase."
        ];
    } elseif ($score >= 41) {
        $level = 'Aspiring 🚀';
        $summary = "This student is beginning to gain traction in their career readiness journey.";
        $recommendations = [
            "🧭 Encourage engagement in co-curricular activities or internships.",
            "🎯 Provide guidance on exploring specific career paths.",
            "🤝 Recommend mentorship or advisory sessions to boost clarity."
        ];
    } elseif ($score >= 21) {
        $level = 'Emerging 🌱';
        $summary = "This student is exploring career readiness and developing self-awareness.";
        $recommendations = [
            "🌟 Encourage small leadership roles or campus involvement.",
            "🛠️ Suggest participation in workshops or short-term projects.",
            "💬 Advise career counseling for clearer goal-setting."
        ];
    } else {
        $level = 'Growing 🌼';
        $summary = "This student is in early stages of career development.";
        $recommendations = [
            "🧑‍🏫 Provide mentorship and introduce basic career skills.",
            "📈 Encourage consistent participation in career activities.",
            "🗒️ Offer structured feedback and personal goal-setting exercises."
        ];
    }
    ?>

  <h2 class="crs-title">Career Readiness Score for <span><?= htmlspecialchars($student_name) ?></span></h2>
  <span class="badge-id">Student ID: <?= htmlspecialchars($student_id) ?></span>
  
  <div class="result-container">

    <!-- LEFT SECTION -->
    <div class="left-section">
      <div class="score-card">
        <h1 class="score-number"><?= number_format($score, 0) ?>%</h1>
        <span class="level-badge">You are <?= htmlspecialchars($level) ?></span>
        <p class="progress-label">
            <?php
            switch ($level) {
                case 'Thriving 🌟':
                    echo "Outstanding performance.";
                    break;
                case 'Achieving 💪':
                    echo "Strong progress.";
                    break;
                case 'Aspiring 🚀':
                    echo "Steady improvement.";
                    break;
                case 'Emerging 🌱':
                    echo "Showing potential.";
                    break;
                case 'Growing 🌼':
                default:
                    echo "Needs guidance";
                    break;
            }
            ?>
        </p>
        <div class="progress-bar">
          <div class="progress-fill" style="width: <?= $score ?>%;"></div>
        </div>
      </div>
    </div>

    <!-- RIGHT SECTION -->
    <div class="right-section">
      <div class="summary-box">
        <div class="summary-header">Result Summary</div>
        <div class="summary-content">
          <p><?= htmlspecialchars($summary) ?></p>
          <ul>
            <?php foreach ($recommendations as $rec): ?>
              <li><?= $rec ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>

  </div>

  <p class="footer-text">CRS generated on <?= date('d/m/Y', strtotime($crs['generated_on'])) ?>.</p>
<?php endif; ?>

</div>
</body>
</html>