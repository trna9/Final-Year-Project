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
$result = $stmt->get_result();
$crs = $result->fetch_assoc();

// Fetch company rankings (same as company_ranking.php)
$stmt = $conn->prepare("
    SELECT 
        c.company_id,
        c.company_name,
        c.logo_url,
        COALESCE(AVG(DISTINCT e.score), 0) AS avg_eval,
        COALESCE(AVG(DISTINCT f.rating), 0) AS avg_feedback,
        (
            CASE
                WHEN AVG(DISTINCT e.score) IS NOT NULL AND AVG(DISTINCT f.rating) IS NOT NULL 
                    THEN AVG(DISTINCT e.score)*0.6 + AVG(DISTINCT f.rating)*0.4
                WHEN AVG(DISTINCT e.score) IS NOT NULL 
                    THEN AVG(DISTINCT e.score)
                WHEN AVG(DISTINCT f.rating) IS NOT NULL 
                    THEN AVG(DISTINCT f.rating)
                ELSE 0
            END
        ) AS final_score
    FROM company c
    LEFT JOIN company_evaluation e ON c.company_id = e.company_id
    LEFT JOIN feedback f ON c.company_id = f.company_id AND f.is_visible = 1
    GROUP BY c.company_id
    ORDER BY final_score DESC
");
$stmt->execute();
$result = $stmt->get_result();
$companies = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$student_score = $crs['score']; // keep student CRS separate

foreach ($companies as $i => &$company) {
    $score = $company['final_score'];

    // NOT YET RATED (no evaluation + no feedback)
    if ($company['avg_eval'] == 0 && $company['avg_feedback'] == 0) {

        $company['score_range'] = 
            '<span style="font-style:italic; font-weight:300; color:grey;">TBD</span>';

    // RATED BUT BELOW RECOMMENDATION THRESHOLD
    } elseif ($score < 2.5) {

        $company['score_range'] = 
            '<span style="font-style:italic; font-weight:300; color:grey;">–</span>';

    // CRS MAPPING BASED ON FINAL SCORE
    } elseif ($score < 3.5) {

        $company['score_range'] = "41 - 60";

    } elseif ($score < 4.5) {

        $company['score_range'] = "61 - 80";

    } else {

        $company['score_range'] = "81 - 100";
    }

}

// Determine student CRS range (use $student_score)
if ($student_score >= 81) $student_range = "81 - 100";
elseif ($student_score >= 61) $student_range = "61 - 80";
elseif ($student_score >= 41) $student_range = "41 - 60";
elseif ($student_score >= 21) $student_range = "21 - 40";
else $student_range = "0 - 20";

// Filter companies
$recommended_companies = array_filter($companies, function($c) use ($student_range){
    return $c['score_range'] === $student_range;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Career Readiness Score | FYP System</title>
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
    min-width: 40px;
    min-height: 40px;
    border-radius: 50%;
    object-fit: cover;
    display: block;
    border: 2px solid #fff;
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

.take-assessment-btn {
  margin-top: 20px;
  background: #9F5EB7;
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 10px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 700;
  font-family: 'Nunito', sans-serif;
  transition: 0.3s;
}
.take-assessment-btn:hover {
  background: #E09D46;
  transform: scale(1.05);
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
.pdf-btn { background: #9F5EB7; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 700;   transition: 0.3s;}
.pdf-btn:hover { background: #5d2472ff; transform: scale(1.05);}

.recommended-companies {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 20px;
  margin-top: 20px;
}

.company-card {
  width: 120px;
  height: 120px;
  background: #f7f0ff;
  border-radius: 50%;
  border: 2px solid #9F5EB7;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 10px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  font-size: 12px;
}

.company-card img {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  object-fit: cover;
  margin-bottom: 8px;
}

.company-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.company-name {
  font-weight: 700;
  font-size: 12px;
  margin-bottom: 4px;
}

.company-score {
  font-size: 11px;
  color: #6b4d99;
}

.recommended-companies a.company-card {
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 120px;
    height: 120px;
    background: #f7f0ff;
    border-radius: 50%;
    border: 2px solid #9F5EB7;
    padding: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    font-size: 12px;
}

.recommended-companies a.company-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

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
  margin-top: 60px;
  margin-bottom: 5px;
  transition: 0.3s;
}
.retake-btn:hover { background: #b37320; transform: scale(1.05); }

.footer-text { text-align: right; font-style: italic; color: #555; margin-top: 30px; }
</style>
</head>
<body>

<!-- SIDEBAR -->
<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="document.getElementById('mySidebar').style.width='0'">&times;</a>
  <a href="student_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
  <a href="companies.php"><i class="fas fa-magnifying-glass"></i> Find Companies</a>
  <a href="company_ranking.php"><i class="fas fa-ranking-star"></i> Company Ranking</a>
  <a href="career_readiness.php"><i class="fas fa-seedling"></i> Career Readiness</a>
  <a href="whitelist.php"><i class="fas fa-list"></i> Whitelist</a> 
  <a href="login.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
</div>

<!-- HEADER -->
<header>
  <button class="hamburger" onclick="window.location.href='student_dashboard.php'">
    <i class="fas fa-arrow-left"></i>
  </button>
  <h1>Career Readiness Score</h1>
  <div class="welcome">
    Hi, <?= htmlspecialchars($name) ?>
    <a href="student_profile.php">
      <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile" class="profile-pic">
    </a>
  </div>
</header>
<div class="main-content">
<?php if (!$crs): ?>
    <div style="text-align:center;margin-top:80px;color:#555;">
        <h2>You haven't completed your Career Readiness Self-Assessment yet.</h2>
        <p>Take the self-assessment to generate your Career Readiness Score and personalized recommendations.</p>
        <button onclick="window.location.href='crs_assessment.php'" class="take-assessment-btn">Take Assessment</button>
    </div>
<?php else: ?>
    <?php
    $score = $crs['score'];

    // Determine CRS level and recommendations
    if ($score >= 81) {
        $level = 'Thriving 🌟';
        $summary = "You are thriving in your career readiness! Keep building your professional brand and network.";
        $recommendations = [
            "💼 Develop your professional brand and use LinkedIn to research your desired career path.",
            "📝 Pursue projects in your chosen field and share your work.",
            "🤝 Maintain a strong network of mentors.",
            "📊 Create a 3-5 year career plan with actionable steps."
        ];
    } elseif ($score >= 61) {
        $level = 'Achieving 💪';
        $summary = "You are making good progress! Focus on specializing and growing your network.";
        $recommendations = [
            "🌐 Attend events to expand your network.",
            "🔬 Narrow down your interests and identify specialization.",
            "📅 Develop a 1-2 year career plan with mentor guidance.",
            "🏆 Complete challenging projects to strengthen skills."
        ];
    } elseif ($score >= 41) {
        $level = 'Aspiring 🚀';
        $summary = "You are starting to build momentum. Explore opportunities and seek guidance.";
        $recommendations = [
            "🧭 Join organizations and events outside your comfort zone.",
            "👩‍🏫 Meet with a career advisor to plan your next year.",
            "🤝 Seek mentors and conduct informational interviews.",
            "🎓 Attend workshops to deepen knowledge."
        ];
    } elseif ($score >= 21) {
        $level = 'Emerging 🌱';
        $summary = "You are beginning to explore career readiness. Focus on self-discovery and small projects.";
        $recommendations = [
            "🏫 Get involved on campus and seek leadership opportunities.",
            "📌 Join or organize projects aligned with your interests.",
            "📝 Complete a career assessment with a counselor.",
            "🗓️ Create a 6-12 month career plan targeting development areas."
        ];
    } else {
        $level = 'Growing 🌼';
        $summary = "You are at the early stages of career readiness. Focus on learning and short-term goals.";
        $recommendations = [
            "📖 Attend workshops and faculty office hours.",
            "🎯 Attend events in areas of interest.",
            "🗓️ Set short-term goals each semester with advisor guidance.",
            "🔧 Learn and leverage campus resources to improve skills."
        ];
    }
    ?>

    <!-- CRS Result Section -->
    <h2 class="crs-title">Your Career Readiness Score</h2>
    <div class="result-container">

        <!-- LEFT: Score Card -->
        <div class="left-section">
            <div class="score-card">
                <h1 class="score-number"><?= number_format($score, 0) ?>%</h1>
                <span class="level-badge">You are <?= htmlspecialchars($level) ?></span>
                <p class="progress-label">
                    <?= ($level === 'Thriving 🌟') ? "Congratulations! <br>You’ve reached the highest level." : "Progress to next level." ?>
                </p>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $score ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Summary Box -->
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

                    <div class="action-buttons">
                        <a href="generate_crs_pdf.php" target="_blank" class="pdf-btn">Download PDF</a>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- End CRS result-container -->

    <p class="footer-text">CRS generated on <?= date('d/m/Y', strtotime($crs['generated_on'])) ?>.</p>

    <!-- Recommended Companies Section -->
    <?php if (!empty($recommended_companies)): ?>
        <h2 class="crs-title" style="margin-top:55px;">Recommended Companies for You</h2>
        <div class="recommended-companies">
            <?php foreach ($recommended_companies as $c): ?>
               <a href="company_details.php?id=<?= urlencode($c['company_id']) ?>" class="company-card">
                  <img src="<?= htmlspecialchars($c['logo_url']) ?>" alt="<?= htmlspecialchars($c['company_name']) ?>">
                  <div class="company-name"><?= htmlspecialchars($c['company_name']) ?></div>
              </a>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align:center;color:#555;">No company recommendations available for your CRS range yet.</p>
    <?php endif; ?>

    <!-- Retake Button -->
    <a href="crs_assessment.php?retake=1" class="retake-btn">Retake Assessment</a>

<?php endif; ?>
</div>
</body>
</html>


