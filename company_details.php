<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$role = strtoupper($_SESSION['role']); // STUDENT, STAFF, ADMIN

$company_id = $_GET['id'] ?? null;
if (!$company_id) {
    echo "No company selected.";
    exit;
}

// Fetch user profile picture
if ($role === 'STUDENT') {
    $query = $conn->prepare("SELECT profile_picture FROM student WHERE student_id = ?");
} else {
    $query = $conn->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
}
$query->bind_param("s", $user_id);
$query->execute();
$result = $query->get_result();
$user_data = $result->fetch_assoc();
$profile_pic = $user_data['profile_picture'] ?? 'images/default_profile.png';
$query->close();

// Fetch company details
$stmt = $conn->prepare("SELECT * FROM company WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();
$stmt->close();

if (!$company) {
    echo "Company not found.";
    exit;
}

// Handle evaluation submission (Staff/Admin only)
$message = '';
if (($role === 'STAFF' || $role === 'ADMIN') && isset($_POST['submit_evaluation'])) {
    $score = floatval($_POST['score'] ?? 0);
    $remarks = $_POST['comments'] ?? '';

    if($score < 1 || $score > 10){
        $message = "Score must be between 1 and 10.";
    } else {
        $stmt = $conn->prepare("INSERT INTO company_evaluation (company_id, evaluator_id, score, remarks) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $company_id, $user_id, $score, $remarks);
        $stmt->execute();
        $stmt->close();
        $message = "Evaluation submitted successfully.";
    }
}

// Handle company edit (Staff/Admin)
$edit_message = '';
if (($role === 'STAFF' || $role === 'ADMIN') && isset($_POST['update_company'])) {
    $name_edit = $_POST['name'] ?? '';
    $address = $_POST['address'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $website = $_POST['website'] ?? '';
    $focus_area = $_POST['focus_area'] ?? $company['focus_area'];
    $description = $_POST['description'] ?? '';

    // Handle logo file upload
    $logo_url = $company['logo_url']; // keep current logo by default
    if(isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK){
        $upload_dir = "uploads/";
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $tmp_name = $_FILES['logo_file']['tmp_name'];
        $ext = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
        $new_file = $upload_dir . "company_".$company_id.".".$ext;
        if(move_uploaded_file($tmp_name, $new_file)){
            $logo_url = $new_file;
        }
    }

    $stmt = $conn->prepare("UPDATE company SET company_name=?, address=?, email=?, phone=?, website_link=?, logo_url=?, focus_area=?, description=? WHERE company_id=?");
    $stmt->bind_param("ssssssssi", $name_edit, $address, $email, $phone, $website, $logo_url, $focus_area, $description, $company_id);
    if($stmt->execute()){
        $edit_message = "Company updated successfully.";
        // Refresh company data
        $company['company_name'] = $name_edit;
        $company['address'] = $address;
        $company['email'] = $email;
        $company['phone'] = $phone;
        $company['website_link'] = $website;
        $company['logo_url'] = $logo_url;
        $company['focus_area'] = $focus_area;
        $company['description'] = $description;
    } else {
        $edit_message = "Failed to update company.";
    }
    $stmt->close();
}

// Fetch average rating
$stmt = $conn->prepare("SELECT AVG(score) as avg_score, COUNT(*) as total_evals FROM company_evaluation WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$rating_data = $result->fetch_assoc();
$avg_score = $rating_data['avg_score'] ?? 0;
$total_evals = $rating_data['total_evals'] ?? 0;
$stmt->close();

$canGiveFeedback = false;

if ($role === 'STUDENT') {
    // Check if this student completed internship at this company
    $check = $conn->prepare("
        SELECT 1
        FROM internship_experience ie
        JOIN student s ON ie.student_id = s.student_id
        WHERE ie.student_id = ? 
        AND ie.company_id = ? 
        AND s.internship_status = 'COMPLETED'
        LIMIT 1
    ");
    $check->bind_param("ss", $user_id, $company_id);
    $check->execute();
    $resultCheck = $check->get_result();

    if ($resultCheck->num_rows > 0) {
        $canGiveFeedback = true;
    }

    $check->close();
}

// Count total feedbacks for the company
$feedback_count_query = $conn->prepare("
  SELECT COUNT(*) AS total_feedbacks 
  FROM feedback 
  WHERE company_id = ? AND is_visible = 1
");
$feedback_count_query->bind_param("i", $company_id);
$feedback_count_query->execute();
$feedback_result = $feedback_count_query->get_result()->fetch_assoc();
$total_feedbacks = $feedback_result['total_feedbacks'] ?? 0;

?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($company['company_name']); ?> | Company Details</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { margin:0; font-family:'Nunito',sans-serif; background-color:#F5F5F8; }
header { background-color:#9F5EB7; color:white; padding:16px 24px; display:flex; align-items:center; justify-content:space-between; z-index:1; }
header h1 { margin:0; font-size:22px; font-weight:800; position:absolute; left:50%; transform:translateX(-50%); }
.welcome { display:flex; align-items:center; gap:8px; }
.hamburger { font-size:24px; cursor:pointer; background:none; border:none; color:white; }
.profile-pic { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; aspect-ratio: 1 / 1; flex-shrink: 0; display: block; border: 2px solid white; }

.sidebar { height: 100%; width: 0; position: fixed; top: 0; left: 0; background: linear-gradient(180deg, #2e2040, #1b1524); overflow-x: hidden; transition: 0.4s; padding-top: 80px; border-top-right-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 4px 0 16px rgba(0, 0, 0, 0.4); z-index: 1000; }
.sidebar a { padding: 12px 20px; margin: 8px 16px; text-decoration: none; font-size: 16px; color: #f2f2f2; display: flex; align-items: center; gap: 10px; border-radius: 30px; transition: 0.3s, transform 0.2s, color 0.3s; }
.sidebar a:hover { background: rgba(159, 94, 183, 0.2); color: #d8b4f8; transform: translateX(5px); }
.sidebar a i { font-size: 20px; width: 28px; text-align: center; background: linear-gradient(135deg, #9F5EB7, #6A3A8D); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(1px 1px 2px rgba(0,0,0,0.3)); transition: transform 0.2s ease; }
.sidebar a:hover i { transform: scale(1.2) rotate(-5deg); }
.sidebar .closebtn { position:absolute; top:10px; right:10px; font-size:24px; color:white; cursor:pointer; }

.content { max-width:900px; margin:40px auto; padding:0 20px; }

.company-card {
  background: white;
  border-radius: 15px;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  padding: 20px;
  position: relative;
  margin-bottom: 40px;
}

.company-header {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  align-items: center;
}

.company-logo { flex: 0 0 180px; height: 180px; border-radius: 15px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
.company-logo img { width: 100%; height: 100%; object-fit: cover; }

.company-info { flex: 1; display: flex; flex-direction: column; gap: 10px; }
.company-info h2 { margin: 0; font-size: 28px; font-weight: 800; color: #333; }

.company-meta p { margin: 4px 0; font-size: 14px; color: #555; }
.company-meta i { color: #9F5EB7; margin-right: 6px; }
.avg-rating { margin-top: 10px; font-weight:700; color: #E09D46; font-size:16px; display: flex; align-items: center; gap: 10px; }
.avg-rating a { font-weight:700; color:#9F5EB7; text-decoration:none; }
.avg-rating a:hover { text-decoration:underline; }

.company-description { margin-top:20px; background:white; padding:20px; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
.company-description h3 { margin-top:0; }

.separator { border: none; border-top: 1.5px solid #ddd; margin: 20px 0; }

/* Edit icon button top-right */
.edit-btn {
  position: absolute;
  top: 15px;
  right: 15px;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  background:#9F5EB7;
  color:white;
  font-size:18px;
  display:flex;
  align-items:center;
  justify-content:center;
  cursor:pointer;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  transition: 0.3s;
}
.edit-btn:hover { background:#E09D46; transform:scale(1.1); }

/* Evaluate button bottom-left */
.evaluate-btn-container {
  margin-top: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  justify-content: center;
  align-items: center;
}
.evaluate-btn {
  padding: 10px 24px;
  background:#E09D46;
  color:white;
  border:none;
  border-radius:8px;
  font-weight:700;
  cursor:pointer;
  font-family:'Nunito',sans-serif;
  font-size:16px;
  transition:0.3s;
}
.evaluate-btn:hover { background:#9F5EB7; transform:scale(1.05); }

/* Back button */
.back-button { margin: 15px 0 0 20px; }
.back-button button { padding: 8px 16px; background: #9F5EB7; color: white; border: none; border-radius: 6px; font-weight: 700; cursor: pointer; font-family: 'Nunito',sans-serif; font-size: 14px; transition: 0.3s; }
.back-button button:hover { background: #E09D46; transform: scale(1.05); }
.back-button i { margin-right:6px; }

/* Modal styles */
.modal {
  display: none;
  position: fixed; 
  z-index: 2000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0,0,0,0.5);
  padding-top: 60px;
}
.modal-content {
  background-color: white;
  margin: 5% auto;
  padding: 20px;
  border-radius: 10px;
  width: 90%;
  max-width: 600px;
  box-shadow:0 2px 8px rgba(0,0,0,0.3);
  position: relative;
}
.modal .close {
  color: #aaa;
  position: absolute;
  top: 12px;
  right: 16px;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
  transition: 0.2s;
}
.modal .close:hover { color: #E09D46; }

/* Keep edit form style inside modal */
.edit-form label { display:block; margin-top:10px; font-weight:600; }
.edit-form input, .edit-form textarea, .edit-form select { width:100%; padding:8px; margin-top:4px; border:1px solid #ccc; border-radius:6px; font-family:'Nunito',sans-serif; box-sizing: border-box; appearance: none; }
.edit-form button { margin-top:12px; padding:10px 20px; background:#9F5EB7; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:700; font-family:'Nunito',sans-serif; }
.edit-form button:hover { background:#E09D46; transform:scale(1.05); }

.form-btn-container {
  display: flex;
  justify-content: center; 
  margin-top: 5px;        
}

.focus-area {
  margin-top: 15px;
}

.focus-area span {
  display: inline-block;
  background: #9F5EB7; 
  color: white;
  font-weight: 700;
  padding: 6px 14px;
  border-radius: 5px;
  font-size: 14px;
  text-align: center;
  cursor: default;
  transition: 0.3s;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="document.getElementById('mySidebar').style.width='0'">&times;</a>
  <?php if($role === 'STUDENT'): ?>
    <a href="student_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
    <a href="companies.php"><i class="fas fa-magnifying-glass"></i> Find Companies</a>
    <a href="company_ranking.php"><i class="fas fa-ranking-star"></i> Company Ranking</a>
    <a href="career_readiness.php"><i class="fas fa-seedling"></i> Career Readiness</a>
    <a href="whitelist.php"><i class="fas fa-list"></i> Whitelist</a> 
  <?php else: ?>
    <a href="staff_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
    <a href="my_students.php"><i class="fas fa-users"></i> My Students</a>
    <a href="companies.php"><i class="fas fa-building"></i> Companies</a>
    <a href="company_ranking.php"><i class="fas fa-ranking-star"></i> Company Ranking</a>
  <?php endif; ?>
  <a href="login.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
</div>

<header>
  <button class="hamburger" onclick="window.location.href='companies.php'">
    <i class="fas fa-arrow-left"></i>
  </button>
  <h1>Company Details</h1>
  <div class="welcome">
    Hi, <?php echo htmlspecialchars($name); ?>
    <a href="<?php echo $role==='STUDENT'?'student_profile.php':'staff_profile.php'; ?>">
      <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-pic">
    </a>
  </div>
</header>

<div class="content">
  <div class="company-card">
    <?php if($role==='STAFF' || $role==='ADMIN'): ?>
      <button class="edit-btn" onclick="openEditModal()" title="Edit Company">
        <i class="fas fa-pen"></i>
      </button>
    <?php endif; ?>

    <div class="company-header">
      <div class="company-logo">
        <img src="<?php echo htmlspecialchars($company['logo_url']) . '?t=' . time(); ?>" alt="<?php echo htmlspecialchars($company['company_name']); ?>">
      </div>

      <div class="company-info">
        <h2><?php echo htmlspecialchars($company['company_name']); ?></h2>
        <div class="company-meta">
          <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($company['address']); ?></p>
          <p><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($company['email']); ?></p>
          <p><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($company['phone']); ?></p>
          <p><i class="fa-solid fa-globe"></i> 
            <a href="<?php echo htmlspecialchars($company['website_link']); ?>" target="_blank"><?php echo htmlspecialchars($company['website_link']); ?></a>
          </p>
        </div>
        <div class="avg-rating">
          ⭐ <?php echo round($avg_score,2)." / 5"; ?> 
          <!-- <a href="past_evaluations.php?id=<?php echo $company_id; ?>">
            Evaluations (<?php echo $total_evals; ?>)
          </a> -->
          | <a href="past_review.php?company_id=<?php echo $company_id; ?>">
              Reviews (<?php echo $total_feedbacks; ?>)
            </a>
        </div>
      </div>
    </div>

    <!-- Focus Area -->
    <?php if(!empty($company['focus_area'])): ?>
      <div class="focus-area">
        <span><?php echo htmlspecialchars($company['focus_area']); ?></span>
      </div>
    <?php endif; ?>

    <div class="separator"></div>

    <h3>Description</h3>
    <p><?php echo nl2br(htmlspecialchars($company['description'])); ?></p>

  </div>

  <!-- Bottom Evaluate / Feedback Buttons -->
  <div class="evaluate-btn-container">
  <?php if($role==='STAFF' || $role==='ADMIN'): ?>
      <a href="evaluation.php?company_id=<?php echo $company_id; ?>">
        <button class="evaluate-btn">Evaluate Company</button>
      </a>
      <?php elseif($role==='STUDENT'): ?>
          <?php if ($canGiveFeedback): ?>
              <a href="feedback.php?company_id=<?php echo $company_id; ?>">
                  <button class="evaluate-btn">Give Feedback</button>
              </a>
          <?php endif; ?>
      <?php endif; ?>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h2>Edit Company</h2>
    <form method="POST" enctype="multipart/form-data" class="edit-form">
      <label>Company Name</label>
      <input type="text" name="name" value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
      <label>Address</label>
      <input type="text" name="address" value="<?php echo htmlspecialchars($company['address']); ?>" required>
      <label>Email</label>
      <input type="email" name="email" value="<?php echo htmlspecialchars($company['email']); ?>">
      <label>Phone</label>
      <input type="text" name="phone" value="<?php echo htmlspecialchars($company['phone']); ?>">
      <label>Website</label>
      <input type="url" name="website" value="<?php echo htmlspecialchars($company['website_link']); ?>">
      <label>Focus Area</label>
      <input type="text" name="focus_area" value="<?php echo htmlspecialchars($company['focus_area']); ?>">
      <label>Logo Upload</label>
      <input type="file" name="logo_file" accept="image/*">
      <label>Description</label>
      <textarea name="description" rows="4"><?php echo htmlspecialchars($company['description']); ?></textarea>
      <div class="form-btn-container">
        <button type="submit" name="update_company">Update Company</button>
      </div>
    </form>
  </div>
</div>

<script>
function openSidebar(){ document.getElementById('mySidebar').style.width='250px'; }
function openEditModal(){ document.getElementById('editModal').style.display='block'; }
function closeEditModal(){ document.getElementById('editModal').style.display='none'; }
window.onclick = function(event){
  if(event.target==document.getElementById('editModal')) closeEditModal();
}
</script>
</body>
</html>
