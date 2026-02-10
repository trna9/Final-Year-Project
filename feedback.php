<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Student';
$role = strtoupper($_SESSION['role']);

if ($role !== 'STUDENT') {
    echo "Only students can submit feedback here.";
    exit;
}

// Fetch student profile picture
$query = $conn->prepare("SELECT profile_picture FROM student WHERE student_id = ?");
$query->bind_param("s", $user_id);
$query->execute();
$result = $query->get_result();
$student = $result->fetch_assoc();
$profile_pic = $student['profile_picture'] ?? 'images/default_profile.png';
$query->close();

$company_id = $_GET['company_id'] ?? null;
if (!$company_id) {
    echo "No company selected.";
    exit;
}

$back_url = $company_id ? "company_details.php?id=" . urlencode($company_id) : "companies.php";

// Fetch company
$stmt = $conn->prepare("SELECT company_name FROM company WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();
$stmt->close();

if (!$company) {
    echo "Company not found.";
    exit;
}

/* ===============================
   HANDLE FEEDBACK SUBMISSION
   =============================== */
if (isset($_POST['submit_feedback'])) {

    $requiredFields = ['guidance','learning','support','environment','relevance'];

    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || intval($_POST[$field]) < 1) {
            echo "<script>
                    alert('Please rate all criteria before submitting feedback.');
                    window.history.back();
                  </script>";
            exit;
        }
    }

    $guidance = intval($_POST['guidance']);
    $learning = intval($_POST['learning']);
    $support = intval($_POST['support']);
    $environment = intval($_POST['environment']);
    $relevance = intval($_POST['relevance']);

    $comment = trim($_POST['comment'] ?? '');
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;

    $average_score = ($guidance + $learning + $support + $environment + $relevance) / 5;

    $stmt = $conn->prepare(
        "INSERT INTO feedback (student_id, company_id, rating, comment, is_anonymous)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("siisi", $user_id, $company_id, $average_score, $comment, $is_anonymous);
    $stmt->execute();
    $stmt->close();

    echo "<script>
            alert('Feedback submitted successfully!');
            window.location.href = 'company_details.php?id={$company_id}';
          </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Feedback for <?php echo htmlspecialchars($company['company_name']); ?></title>

<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body { margin:0; font-family:'Nunito',sans-serif; background-color:#F5F5F8; }
/* HEADER */
header {
    background-color:#9F5EB7;
    padding:16px 24px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    color:white;
    position:relative;
}
header h1 {
    position:absolute;
    left:50%;
    transform:translateX(-50%);
    margin:0;
    font-size:20px;
    font-weight:800;
}
.profile-pic {
    width:40px; height:40px; border-radius:50%; object-fit:cover;
    border:2px solid white;
}
.hamburger {
    font-size:26px; cursor:pointer; background:none; border:none; color:white;
}
/* SIDEBAR */
.sidebar {
    height:100%;
    width:0;
    position:fixed;
    top:0; left:0;
    background:linear-gradient(180deg,#2e2040,#1b1524);
    overflow-x:hidden;
    transition:0.4s;
    padding-top:80px;
    border-top-right-radius:20px;
    border-bottom-right-radius:20px;
}
.sidebar a {
    padding:12px 22px;
    margin:8px 16px;
    display:flex;
    align-items:center;
    gap:14px;
    color:#eee;
    text-decoration:none;
    font-size:16px;
    border-radius:30px;
}
.sidebar a:hover {
    background:rgba(159,94,183,0.2);
    color:#d8b4f8;
}
.sidebar i { font-size:18px; }
.closebtn {
    position:absolute;
    top:14px; right:16px;
    font-size:24px;
    color:white;
    cursor:pointer;
}
/* MAIN CONTENT WRAPPER */
.main-content { padding:20px; }
/* FORM STYLES */
.wrapper { max-width:800px; margin:0 auto; }
.page-title { background-color:#9F5EB7; color:#fff; padding:10px 20px; font-size:18px; font-weight:700; display:inline-block; margin-bottom:15px; border-radius:5px; }
.container, .container-comment { background-color:#e4dac6; border:2px solid #e09d46; padding:20px 25px; border-radius:5px; margin-bottom:20px; }
.anonymous-area { display:flex; align-items:center; gap:10px; font-weight:700; font-size:14px; margin-bottom:20px; }
li { margin-bottom:25px; }
label.criteria-label { font-weight:700; display:block; margin-bottom:5px; }
small.criteria-desc { font-weight:400; font-style:italic; color:#555; display:block; margin-bottom:10px; }
.star-rating { display:flex; flex-direction:row-reverse; justify-content:left; gap:5px; margin-top:5px; }
.star-rating input { display:none; }
.star-rating label { font-size:28px; color:white; cursor:pointer; transition:0.2s; }
.star-rating input:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label { color:#E09D46; }
.comment { width:96%; min-height:80px; padding:10px; font-family:'Nunito',sans-serif; font-size:14px; border-radius:5px; border:1px solid #ccc; resize:vertical; }
.submit-btn { font-family:'Nunito',sans-serif; background-color:#9F5EB7; color:white; padding:12px 30px; border:none; border-radius:8px; font-size:16px; font-weight:700; cursor:pointer; display:block; margin:0 auto 30px auto; }
.submit-btn:hover { background-color:#E09D46; transform:scale(1.05); }
.switch { position:relative; display:inline-block; width:50px; height:26px; }
.switch input { display:none; }
.slider { position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background:#ccc; transition:.4s; border-radius:26px; }
.slider:before { position:absolute; content:""; height:22px; width:22px; left:2px; bottom:2px; background:white; transition:.4s; border-radius:50%; }
input:checked + .slider { background-color:#E09D46; }
input:checked + .slider:before { transform:translateX(24px); }
</style>
</head>

<body>

<header>
    <button class="hamburger" onclick="window.location.href='<?= $back_url ?>'">
        <i class="fas fa-arrow-left"></i>
    </button>
    <h1>Feedback Form</h1>
    <img src="<?php echo $profile_pic; ?>" class="profile-pic">
</header>

<div class="main-content">
<div class="wrapper">
    <h2 class="page-title">Feedback for <?php echo htmlspecialchars($company['company_name']); ?></h2>

    <form id="feedbackForm" method="POST">
        <div class="container">
            <p>Please rate your experience (1 = Poor, 5 = Excellent).</p>
            <ol>
            <?php
            $criteria = [
                'guidance'=>'Guidance & Supervision',
                'learning'=>'Learning Opportunities',
                'support'=>'Support from Staff',
                'environment'=>'Work Environment',
                'relevance'=>'Relevance to My Studies'
            ];
            foreach ($criteria as $key => $label):
            ?>
                <li>
                    <label class="criteria-label"><?php echo $label; ?></label>
                    <div class="star-rating">
                        <?php for ($i=5; $i>=1; $i--): ?>
                        <input type="radio" name="<?php echo $key; ?>" id="<?php echo $key.$i; ?>" value="<?php echo $i; ?>">
                        <label for="<?php echo $key.$i; ?>"><i class="fa-solid fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                </li>
            <?php endforeach; ?>
            </ol>
        </div>

        <div class="container-comment">
            <label class="criteria-label">Comment (Optional)</label>
            <textarea name="comment" class="comment" placeholder="Write any extra comments here..."></textarea>
        </div>

        <div class="anonymous-area">
            <label class="switch">
                <input type="checkbox" name="is_anonymous" id="is_anonymous">
                <span class="slider"></span>
            </label>
            <span id="anonLabel">Non-Anonymous</span>
        </div>

        <button type="submit" name="submit_feedback" class="submit-btn">Submit Feedback</button>
    </form>
</div>
</div>

<script>
document.getElementById('feedbackForm').addEventListener('submit', function (e) {
    const criteria = ['guidance','learning','support','environment','relevance'];
    for (let key of criteria) {
        if (!document.querySelector(`input[name="${key}"]:checked`)) {
            alert('Please rate all criteria before submitting feedback.');
            e.preventDefault();
            return;
        }
    }
});

const toggle = document.getElementById('is_anonymous');
const label = document.getElementById('anonLabel');
toggle.addEventListener('change', () => {
    label.textContent = toggle.checked ? 'Anonymous' : 'Non-Anonymous';
});
</script>

</body>
</html>
