<?php
session_start();
require_once 'db.php';

$student_id = $_SESSION['user_id'] ?? null;
if (!$student_id) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Student';

// Fetch student info (join user + student table)
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
$profile_pic = $student['profile_picture'] ?? 'images/default_profile.png';
$query->close();


// Competencies and questions
$competencies = [
    'Communication' => [
        "I can express my ideas clearly in writing.",
        "I communicate effectively verbally in group or one-on-one settings.",
        "I listen actively to understand others’ perspectives.",
        "I adapt my message depending on the audience.",
        "I use non-verbal cues and digital tools effectively to convey information."
    ],
    'Teamwork & Interpersonal' => [
        "I collaborate well with others to achieve shared goals.",
        "I respect and consider different opinions within a team.",
        "I contribute actively and responsibly to group tasks.",
        "I handle conflicts and disagreements constructively.",
        "I support and motivate team members to perform well."
    ],
    'Leadership' => [
        "I guide and motivate others to accomplish shared objectives.",
        "I organize tasks and delegate responsibilities effectively.",
        "I make decisions that consider team strengths and goals.",
        "I demonstrate empathy and positive influence toward others.",
        "I adapt to challenges while maintaining a productive team environment."
    ],
    'Creativity & Problem Solving' => [
        "I analyze problems carefully before taking action.",
        "I develop innovative solutions to complex issues.",
        "I evaluate outcomes and adjust my approach if needed.",
        "I think critically and strategically to overcome challenges.",
        "I synthesize information from multiple sources to make decisions."
    ],
    'Professionalism & Work Ethic' => [
        "I demonstrate integrity and ethical behavior in all tasks.",
        "I take initiative and manage my responsibilities effectively.",
        "I maintain reliable work habits and meet deadlines.",
        "I adapt to workplace standards and expectations.",
        "I strive for high-quality outcomes in my work."
    ],
    'Global / Intercultural Perspective' => [
        "I respect and value perspectives from diverse cultures.",
        "I communicate effectively with individuals from different backgrounds.",
        "I learn from cultural differences to improve collaboration.",
        "I show empathy and inclusiveness in interactions.",
        "I leverage diversity to enhance team performance and understanding."
    ],
    'Digital Technology' => [
        "I use technology efficiently to complete tasks.",
        "I adapt to new digital tools when required.",
        "I leverage technology ethically in problem-solving.",
        "I troubleshoot issues using available digital resources.",
        "I integrate technology to enhance productivity and collaboration."
    ],
    'Career Management' => [
        "I identify and articulate my strengths, skills, and experiences.",
        "I research career opportunities aligned with my goals.",
        "I set short-term and long-term professional goals.",
        "I seek resources, mentors, or guidance for career growth.",
        "I advocate for myself and take steps toward career advancement."
    ],
];

$competency_keys = array_keys($competencies);
$total_steps = count($competency_keys);

$step = intval($_GET['step'] ?? 1);
if ($step < 1) $step = 1;
if ($step > $total_steps) $step = $total_steps;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answers = $_POST['answers'] ?? [];
    $_SESSION['crs'][$competency_keys[$step - 1]] = $answers;

    if ($step < $total_steps) {
        header("Location: crs_assessment.php?step=" . ($step + 1));
    } else {
        $all_answers = $_SESSION['crs'];
        $total_score = 0;
        $count = 0;
        foreach ($all_answers as $vals) {
            $total_score += array_sum($vals);
            $count += count($vals);
        }
        $avg = ($count > 0) ? ($total_score / ($count * 5)) * 100 : 0;
        $result_summary = "Auto-generated summary based on CRS score";

        $stmt = $conn->prepare("INSERT INTO career_readiness_score (student_id, score) 
                                VALUES (?, ?)
                                ON DUPLICATE KEY UPDATE score = VALUES(score), last_updated = CURRENT_TIMESTAMP");
        $stmt->bind_param("sd", $student_id, $avg);
        $stmt->execute();

        unset($_SESSION['crs']);
        header("Location: career_readiness.php?completed=1");
    }
    exit;
}

$current_comp = $competency_keys[$step - 1];
$questions = $competencies[$current_comp];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CRS Assessment Section <?= $step ?></title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { 
    font-family: 'Nunito', sans-serif; 
    background:#f6f8fb; 
    margin:0; 
    padding:0; 
}

header { background:#9F5EB7; color:white; padding:16px 24px; display:flex; align-items:center; justify-content:space-between; position:relative; }
header h1 { margin:0; font-size:22px; font-weight:800; position:absolute; left:50%; transform:translateX(-50%); }
.welcome { display:flex; align-items:center; gap:8px; }
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
.container { 
    max-width: 900px; 
    margin: 40px auto; 
    background: #fff; 
    padding: 30px; 
    border-radius: 10px; 
    box-shadow:0 8px 20px rgba(0,0,0,0.1);
}

.section-title {
    background: #9F5EB7;
    color: white;
    padding: 15px 20px;
    border-radius: 10px;
    text-align: center;
    font-size: 22px;
    font-weight: 800;
    margin-bottom: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.progress-bar { 
    background:#eee; 
    border-radius: 10px; 
    overflow:hidden; 
    margin-bottom: 25px; 
    height: 22px; 
}
.progress-bar-fill { 
    background: #E09D46; 
    height: 100%; 
    width: 0%; 
    text-align:center; 
    color:white; 
    font-size:12px; 
    line-height:22px; 
    transition: width 0.5s; 
    font-weight:600;
    border-radius: 10px;
}
.legend {
    display: flex;
    justify-content: space-between;
    background: #f7f2ec;
    padding: 12px 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    font-size: 14px;
    color: #555;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}
.question-card {
    background: #f7f0ff;
    border-radius: 16px;
    padding: 25px 30px;
    margin-bottom: 20px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
    border-left: 6px solid #9F5EB7;
    transition: transform 0.2s;
}
.question-card:hover {
    transform: translateY(-2px);
}
.question-text {
    margin-bottom: 15px;
    font-weight: 600;
    font-size: 15px;
}
.scale-label {
    margin-right: 25px;
    font-weight: 500;
}
.button-container {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}
button:not(.hamburger) { 
    padding: 12px 28px;
    font-size: 16px;
    background: #9F5EB7; 
    color:white; 
    border:none; 
    border-radius:12px; 
    cursor:pointer;
    font-weight:700;
    font-family: 'Nunito', sans-serif;  
    transition: transform 0.2s;
}
button:not(.hamburger):hover { 
    background: #E09D46; 
    transform: scale(1.05);
}
button.previous { background: #9F5EB7; }
button.previous:hover { background: #E09D46; }
</style>
<script>
function updateProgressBar() {
    let step = <?= $step ?>;
    let total = <?= $total_steps ?>;
    let percent = Math.round((step-1)/total * 100);
    document.getElementById('progress-fill').style.width = percent + '%';
    document.getElementById('progress-fill').textContent = percent + '%';
}
window.onload = updateProgressBar;
</script>
</head>

<body>

<header>
  <button class="hamburger" onclick="window.location.href='career_readiness.php'">
    <i class="fas fa-arrow-left"></i>
  </button>
  <h1>Career Readiness Assessment</h1>
  <div class="welcome">
    Hi, <?= htmlspecialchars($name) ?>
      <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile" class="profile-pic">
  </div>
</header>

<div class="container">

<div class="section-title">Section <?= $step ?> of <?= $total_steps ?>: <?= htmlspecialchars($current_comp) ?></div>

<div class="progress-bar">
    <div class="progress-bar-fill" id="progress-fill">0%</div>
</div>

<div class="legend">
    <span>1 = Strongly Disagree</span>
    <span>2 = Disagree</span>
    <span>3 = Neutral</span>
    <span>4 = Agree</span>
    <span>5 = Strongly Agree</span>
</div>

<form method="POST">
    <?php foreach ($questions as $i => $q): ?>
        <div class="question-card">
            <p class="question-text"><?= ($i+1) ?>. <?= htmlspecialchars($q) ?></p>
            <div class="scale">
                <?php for ($val=1; $val<=5; $val++): ?>
                    <label class="scale-label">
                        <input type="radio" name="answers[<?= $i ?>]" value="<?= $val ?>" required
                        <?php if(isset($_SESSION['crs'][$current_comp][$i]) && $_SESSION['crs'][$current_comp][$i]==$val) echo 'checked'; ?>
                        > <?= $val ?>
                    </label>
                <?php endfor; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="button-container">
        <?php if ($step > 1): ?>
            <button type="button" class="previous" onclick="window.location.href='crs_assessment.php?step=<?= $step - 1 ?>'">Previous Section</button>
        <?php else: ?>
            <div></div>
        <?php endif; ?>
        <button type="submit"><?= ($step < $total_steps) ? 'Next Section' : 'Submit Assessment' ?></button>
    </div>
</form>
</div>

</body>
</html>
