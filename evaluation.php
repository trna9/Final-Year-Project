<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$role = strtoupper($_SESSION['role']); // STAFF or ADMIN only

if($role !== 'STAFF' && $role !== 'ADMIN') {
    echo "You are not authorized to evaluate companies.";
    exit;
}

// Fetch staff profile pic
$query = $conn->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
$query->bind_param("s", $user_id);
$query->execute();
$result = $query->get_result();
$staff = $result->fetch_assoc();
$profile_pic = $staff['profile_picture'] ?? 'images/default_profile.png';
$query->close();

// Get company ID
$company_id = $_GET['company_id'] ?? null;
if (!$company_id) {
    echo "No company selected.";
    exit;
}

$back_url = $company_id ? "company_details.php?id=" . urlencode($company_id) : "companies.php";

// Fetch company data
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

// Handle submission
if (isset($_POST['submit_evaluation'])) {
    $criteria_fields = ['supervision','learning','communication','environment','relevance'];

    // Server-side validation: ensure all criteria have a rating
    foreach ($criteria_fields as $field) {
        if (!isset($_POST[$field]) || intval($_POST[$field]) < 1) {
            echo "<script>
                    alert('Please rate all criteria before submitting the evaluation.');
                    window.history.back();
                  </script>";
            exit;
        }
    }

    $supervision = intval($_POST['supervision']);
    $learning = intval($_POST['learning']);
    $communication = intval($_POST['communication']);
    $environment = intval($_POST['environment']);
    $relevance = intval($_POST['relevance']);
    $comment = trim($_POST['comment'] ?? '');

    $average_score = ($supervision + $learning + $communication + $environment + $relevance) / 5;
    $remarks = "Supervision: $supervision, Learning: $learning, Communication: $communication, Environment: $environment, Relevance: $relevance";

    $stmt = $conn->prepare("INSERT INTO company_evaluation (company_id, evaluator_id, score, remarks, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isdss", $company_id, $user_id, $average_score, $remarks, $comment);
    $stmt->execute();
    $stmt->close();

    // Update company average rating
    $stmt_avg = $conn->prepare("SELECT AVG(score) AS new_avg FROM company_evaluation WHERE company_id = ?");
    $stmt_avg->bind_param("i", $company_id);
    $stmt_avg->execute();
    $result_avg = $stmt_avg->get_result();
    $row_avg = $result_avg->fetch_assoc();
    $new_cumulative_avg = $row_avg['new_avg'];
    $stmt_avg->close();

    $stmt_update = $conn->prepare("UPDATE company SET average_rating = ? WHERE company_id = ?");
    $stmt_update->bind_param("di", $new_cumulative_avg, $company_id);
    $stmt_update->execute();
    $stmt_update->close();

    echo "<script>
        alert('Evaluation submitted successfully!');
        window.location.href = 'company_details.php?id={$company_id}';
    </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Evaluate <?php echo htmlspecialchars($company['company_name']); ?></title>

<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* BODY AND FORM STYLES SAME AS YOUR ORIGINAL CODE */
body { margin:0; font-family: 'Nunito', sans-serif; background-color: #F5F5F8; }
header { background-color:#9F5EB7; padding:16px 24px; display:flex; align-items:center; justify-content:space-between; color:white; position:relative; }
header h1 { position:absolute; left:50%; transform:translateX(-50%); margin:0; font-size:22px; font-weight:800; }
.hamburger {
    font-size:26px; cursor:pointer; background:none; border:none; color:white;
}
.profile-pic { width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid white; }
.main-content { padding:30px; }
.page-title{background-color:#9F5EB7;color:#fff;padding:10px 20px;font-size:18px;font-weight:700;display:inline-block;margin-bottom:15px;border-radius:5px;}
.container,.container-comment{background-color:#e4dac6;border:2px solid #e09d46;padding:20px 25px;border-radius:5px;margin-bottom:20px;}
li{margin-bottom:25px;}
.criteria-label{font-weight:700;display:block;margin-bottom:5px;}
.criteria-desc{font-style:italic;color:#555;margin-bottom:10px;display:block;}
.star-rating { display:flex; flex-direction:row-reverse; justify-content:left; gap:5px; margin-top:5px; }
.star-rating input{display:none;}
.star-rating label{font-size:28px;color:white;cursor:pointer;}
.star-rating input:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label {color:#E09D46;}
.comment{width:96%;min-height:80px;padding:10px;border-radius:5px;border:1px solid #ccc; font-family: 'Nunito', sans-serif;}
.submit-btn{font-family: 'Nunito', sans-serif; background:#9F5EB7;color:white;padding:12px 30px;border:none;border-radius:8px;font-size:16px;font-weight:700;cursor:pointer;display:block;margin:0 auto 30px;}
.submit-btn:hover{background:#E09D46;transform:scale(1.05);}
.wrapper { max-width:800px; margin:0 auto; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.getElementById('evaluationForm').addEventListener('submit', function(e){
        const criteria = ['supervision','learning','communication','environment','relevance'];
        for(let key of criteria){
            if(!document.querySelector(`input[name="${key}"]:checked`)){
                alert('Please rate all criteria before submitting the evaluation.');
                e.preventDefault();
                return;
            }
        }
    });
});
</script>
</head>
<body>

<header>
    <button class="hamburger" onclick="window.location.href='<?= $back_url ?>'">
        <i class="fas fa-arrow-left"></i>
    </button>
    <h1>Company Evaluation</h1>
    <img src="<?php echo $profile_pic; ?>" class="profile-pic">
</header>

<div class="main-content">
<div class="wrapper">

    <h2 class="page-title">Evaluation for <?php echo htmlspecialchars($company['company_name']); ?></h2>

    <div class="container">
        <p>Please rate this company from 1 to 5 for each category.</p>
        <form id="evaluationForm" method="POST">
            <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
            <ol>
                <?php
                $criteria = [
                    'supervision'=>'Supervision Quality',
                    'learning'=>'Learning Opportunities',
                    'communication'=>'Communication & Feedback',
                    'environment'=>'Work Environment',
                    'relevance'=>'Relevance to Programme'
                ];
                $desc = [
                    'supervision'=>'How well did the company guide interns?',
                    'learning'=>'Were there opportunities for skill development?',
                    'communication'=>'Was feedback clear and helpful?',
                    'environment'=>'Was the workplace safe and professional?',
                    'relevance'=>'Was the work relevant to the program?'
                ];
                foreach($criteria as $key=>$label):
                ?>
                <li>
                    <label class="criteria-label"><?php echo $label; ?></label>
                    <small class="criteria-desc">(<?php echo $desc[$key]; ?>)</small>
                    <div class="star-rating">
                        <?php for($i=5;$i>=1;$i--): ?>
                        <input type="radio" name="<?php echo $key; ?>" id="<?php echo $key.$i; ?>" value="<?php echo $i; ?>">
                        <label for="<?php echo $key.$i; ?>"><i class="fa-solid fa-star"></i></label>
                        <?php endfor; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ol>
    </div>

    <div class="container-comment">
        <label class="criteria-label">Comment (Optional):</label>
        <textarea name="comment" form="evaluationForm" class="comment" placeholder="Write any extra comments here..."></textarea>
    </div>

    <button type="submit" form="evaluationForm" name="submit_evaluation" class="submit-btn">Submit Evaluation</button>
    </form>
</div>
</div>

</body>
</html>
