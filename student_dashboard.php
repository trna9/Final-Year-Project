<?php
session_start();
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STUDENT') {
    header("Location: login.html");
    exit;
}

require_once 'db.php'; // your DB connection

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Student';

// Fetch student's profile picture
$query = $conn->prepare("SELECT profile_picture FROM student WHERE student_id = ?");
$query->bind_param("s", $user_id);
$query->execute();
$result = $query->get_result();
$student = $result->fetch_assoc();
$profile_pic = $student['profile_picture'] ?? 'images/default_profile.png';
$query->close();

// Fetch announcements from the database
$announcementQuery = $conn->prepare("
    SELECT a.title, a.content, a.posted_on, a.attachment_url, u.name AS posted_by
    FROM announcements a
    LEFT JOIN user u ON a.posted_by = u.user_id
    WHERE a.visibility = 'ALL' OR a.visibility = 'STUDENTS_ONLY'
    ORDER BY a.posted_on DESC
    LIMIT 10
");
$announcementQuery->execute();
$announcementResult = $announcementQuery->get_result();
$announcements = $announcementResult->fetch_all(MYSQLI_ASSOC);
$announcementQuery->close();

$company_stmt = $conn->prepare("
    SELECT 
        c.*,

        /* Average evaluation score (0–5) */
        AVG(e.score) AS avg_eval,

        /* Average feedback rating (0–5) */
        AVG(f.rating) AS avg_feedback,

        /* Final score logic */
        (
            CASE
                WHEN AVG(e.score) IS NOT NULL AND AVG(f.rating) IS NOT NULL
                    THEN AVG(e.score) * 0.6 + AVG(f.rating) * 0.4
                WHEN AVG(e.score) IS NOT NULL
                    THEN AVG(e.score)
                WHEN AVG(f.rating) IS NOT NULL
                    THEN AVG(f.rating)
                ELSE 0
            END
        ) AS final_score,

        /* Alumni who completed internship */
        COALESCE(SUM(
            CASE 
                WHEN s.internship_status = 'COMPLETED'
                     AND ie.company_id IS NOT NULL
                THEN 1 ELSE 0
            END
        ), 0) AS alumni_completed_count

    FROM company c
    LEFT JOIN company_evaluation e 
        ON c.company_id = e.company_id
    LEFT JOIN feedback f 
        ON c.company_id = f.company_id 
       AND f.is_visible = 1
    LEFT JOIN internship_experience ie 
        ON c.company_id = ie.company_id
    LEFT JOIN student s 
        ON ie.student_id = s.student_id

    GROUP BY c.company_id
    ORDER BY final_score DESC
");

$company_stmt->execute();
$company_result = $company_stmt->get_result();
$companies = $company_result->fetch_all(MYSQLI_ASSOC);
$company_stmt->close();

// Fetch latest CRS score for this student
$crs_stmt = $conn->prepare("
    SELECT score 
    FROM career_readiness_score 
    WHERE student_id = ? 
    ORDER BY last_updated DESC 
    LIMIT 1
");
$crs_stmt->bind_param("s", $user_id);
$crs_stmt->execute();
$crs_result = $crs_stmt->get_result();
$crs_data = $crs_result->fetch_assoc();
$crs_score = $crs_data['score'] ?? 0; // default to 0 if not found
$crs_stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Dashboard | FYP System</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      margin: 0;
      font-family: 'Nunito', sans-serif;
      background-color: #F5F5F8;
    }
    header {
      background-color: #9F5EB7;
      color: white;
      padding: 16px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      z-index: 1;     
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
    .main-layout {
      display: flex;
      gap: 20px;
      padding: 24px;
    }
    .left-section { flex: 2; }
    .right-section {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    .carousel {
      height: 220px;
      position: relative;
      overflow: hidden;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .slides {
      display: flex;
      transition: transform 0.5s ease-in-out;
      width: 100%;
    }
    .slide {
      min-width: 100%;
      height: 220px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: white;
      background-size: cover;
      background-position: center;
      position: relative;
      text-align: center;
    }
    .slide::before {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.4);
    }
    .slide h2, .slide p, .slide .btn { position: relative; z-index: 1; }
    .btn {
      background: #E09D46;
      border: none;
      color: white;
      font-weight: 700;
      padding: 8px 16px;
      border-radius: 20px;
      cursor: pointer;
      transition: 0.3s;
      font-family: 'Nunito', sans-serif;
    }
    .btn:hover { background: #c47a34; }

    /* ===== Liquid Progress Card ===== */
    .progress-card {
      background: white;
      border-radius: 12px;
      padding: 16px;
      text-align: center;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      position: relative;
    }
    .progress-card h2 {
      font-size: 16px;
      margin-bottom: 12px;
      color: #9F5EB7;
    }
    .circle-container {
      position: relative;
      width: 220px;
      height: 220px;
      border-radius: 50%;
      overflow: hidden;
      margin: 0 auto;
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
      background: #f0e9f5;
    }
    .wave {
      position: absolute;
      width: 200%;
      height: 200%;
      top: 100%;
      left: -50%;
      border-radius: 35%;
      background: #389096ff;
      opacity: 0.8;
      animation: wave-spin 6s linear infinite;
    }
    .wave2 {
      background: #80e3f0ff;
      opacity: 0.6;
      animation: wave-spin 8s linear infinite reverse;
    }
    @keyframes wave-spin {
      0% { transform: translateY(0) rotate(0deg); }
      100% { transform: translateY(0) rotate(360deg); }
    }
    .progress-text {
      position: absolute;
      font-size: 2.2rem;
      color: #333;
      font-weight: 700;
      z-index: 2;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      text-shadow: 0 2px 3px rgba(0,0,0,0.2);
    }
    #progress-label {
      font-size: 14px;
      color: #555;
      margin-top: 10px;
    }
    .announcement, .company-ranking {
      background: white;
      border-radius: 12px;
      padding: 18px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .announcement h2, .company-ranking h2 {
      font-size: 18px;
      margin-bottom: 12px;
      color: #9F5EB7;
    }
    .announcement-list {
      overflow-y: auto;
      max-height: 350px;
    }
    .announcement-list p {
      background: #f9f9f9;
      border-radius: 12px;
      padding: 12px;
      margin-bottom: 10px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.05);
      font-size: 14px;
    }
    .announcement-list small {
      color: #888;
      font-size: 12px;
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
  </style>
</head>
<body>

<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="document.getElementById('mySidebar').style.width='0'">&times;</a>
  <a href="student_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
  <a href="select_sv.php"><i class="fas fa-user-tie"></i> Supervisor</a>
  <a href="companies.php"><i class="fas fa-magnifying-glass"></i> Find Companies</a>
  <a href="company_ranking.php"><i class="fas fa-ranking-star"></i> Company Ranking</a>
  <a href="career_readiness.php"><i class="fas fa-seedling"></i> Career Readiness</a>
  <a href="whitelist.php"><i class="fas fa-list"></i> Whitelist</a>
  <a href="login.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
</div>


<header>
  <button class="hamburger" onclick="document.getElementById('mySidebar').style.width='250px'">
    <i class="fas fa-bars"></i>
  </button>
  <h1>Student Dashboard</h1>
  <div class="welcome">
    Hi, <?php echo htmlspecialchars($name); ?>
    <a href="student_profile.php">
      <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-pic">
    </a>
  </div>
</header>

<div class="main-layout">
  <div class="left-section">
    <div class="carousel">
      <div class="slides" id="slides">
        <div class="slide" style="background-image:url('img/banner1.jpg');">
          <h2>Find Companies</h2><p>Browse internship companies available for students.</p>
          <button class="btn" onclick="location.href='companies.php'">Get Started</button>
        </div>
        <div class="slide" style="background-image:url('img/banner2.jpg');">
          <h2>Company Ranking</h2><p>View the latest ranking of internship companies.</p>
          <button class="btn" onclick="location.href='company_ranking.php'">Get Started</button>
        </div>
        <div class="slide" style="background-image:url('img/banner3.jpg');">
          <h2>Career Readiness</h2><p>Check your Career Readiness Score (CRS).</p>
          <button class="btn" onclick="location.href='career_readiness.php'">Get Started</button>
        </div>
      </div>
    </div>

    <!-- Spacer -->
    <div style="height: 30px;"></div> <!-- <-- Add some space -->

    <div class="company-ranking">
     <h2>Company Ranking</h2>
      <div style="max-height: 300px; overflow-y:auto;">
        <table style="width:100%; border-collapse: collapse;">
          <thead>
            <tr style="background:#f0f0f0;">
              <th style="padding:8px; text-align:left;">#</th>
              <th style="padding:8px; text-align:left;">Company</th>
              <th style="padding:8px; text-align:left;">Final Score</th>
            </tr>
          </thead>
          <tbody>
            <?php $rank = 1; foreach($companies as $c): ?>
            <tr style="border-bottom:1px solid #eee;">
              <td style="padding:6px;"><?php echo $rank; ?></td>
              <td style="padding:6px;"><?php echo htmlspecialchars($c['company_name']); ?></td>
              <td style="padding:6px;">
                <?php 
                  if ($c['final_score'] > 0) {
                      echo round($c['final_score'], 1);
                  } else {
                      echo '<span style="font-style:italic; color:grey;">Not yet rated</span>';
                  }
                ?>
              </td>
            </tr>
            <?php $rank++; endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="right-section">
    <div class="progress-card">
      <h2>Your Career Readiness Score</h2>
      <div class="circle-container">
        <div class="wave" id="wave1"></div>
        <div class="wave wave2" id="wave2"></div>
        <div class="progress-text" id="progressText">0%</div>
      </div>
      <p id="progress-label">Your current CRS: 0%</p>
    </div>

    <div class="announcement">
      <h2>Announcements</h2>
         <div class="announcement-list">
            <?php if (!empty($announcements)): ?>
                 <?php foreach ($announcements as $a): ?>
                     <p>
                        <strong>📢 <?php echo htmlspecialchars($a['title']); ?></strong><br><br>
                        <?php echo nl2br(htmlspecialchars($a['content'])); ?><br><br>

                        <?php if (!empty($a['attachment_url'])): ?>
                           <a href="<?php echo htmlspecialchars($a['attachment_url']); ?>" 
                            target="_blank" 
                            style="color:#0E5FB4; text-decoration:none; font-weight:600;">
                             📎 View Attachment
                          </a><br><br>
                        <?php endif; ?>
                        <small>
                             Posted by <?php echo htmlspecialchars($a['posted_by']); ?> 
                             on <?php echo date('d M Y, H:i', strtotime($a['posted_on'])); ?>
                        </small>
                     </p>
                <?php endforeach; ?>
             <?php else: ?>
                <p>No announcements yet.</p>
            <?php endif; ?>
        </div>
    </div>
  </div>
</div>

<script>
const wave1 = document.getElementById("wave1");
const wave2 = document.getElementById("wave2");
const text = document.getElementById("progressText");
const label = document.getElementById("progress-label");

// Use PHP value directly from the database
const target = Math.min(<?php echo (int)$crs_score; ?>, 100); // cap at 100%
let current = 0;

function animateFill() {
  current = 0;
  wave1.style.top = "100%";
  wave2.style.top = "100%";
  text.textContent = "0%";
  label.textContent = "Your current CRS: 0%";

  clearInterval(window.fillAnim);
  window.fillAnim = setInterval(() => {
    if (current >= target) {
      clearInterval(window.fillAnim);
    } else {
      current++;
      const level = 100 - current;
      wave1.style.top = `${level}%`;
      wave2.style.top = `${level}%`;
      text.textContent = `${current}%`;
      label.textContent = `Your current CRS: ${current}%`;
    }
  }, 40); // speed of animation
}

animateFill();
</script>

<script>
const slides = document.getElementById('slides');
let idx = 0;
setInterval(() => { 
  idx = (idx + 1) % slides.children.length;
  slides.style.transform = `translateX(-${idx * 100}%)`; 
}, 5000);
</script>

</body>
</html>
