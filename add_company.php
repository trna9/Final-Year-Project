<?php
session_start();
require_once 'db.php';

// --- Enable full error reporting ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Check login ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = strtoupper($_SESSION['role'] ?? '');
$name = $_SESSION['name'] ?? 'User';

$profilePicture = 'uploads/default_profile.png';

if ($role === 'STAFF') {
    // Fetch only for staff
    $stmt = $conn->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
} else {
    // Fetch only for student
    $stmt = $conn->prepare("SELECT profile_picture FROM student WHERE student_id = ?");
}

if ($stmt) {
    $stmt->bind_param("s", $user_id); // $user_id must be the correct staff_id or student_id from session
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['profile_picture'])) {
            $profilePicture = $row['profile_picture'];
        }
    }
    $stmt->close();
}

// --- Initialize messages ---
$success_msg = '';
$error_msg = '';

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name  = $_POST['company_name'] ?? '';
    $nature        = $_POST['nature'] ?? '';
    $address       = $_POST['address'] ?? '';
    $email         = $_POST['email'] ?? '';
    $phone         = $_POST['phone'] ?? '';
    $website_link  = $_POST['website_link'] ?? '';
    $description   = $_POST['description'] ?? '';
    $focus_area    = $_POST['focus_area'] ?? '';
    $city          = $_POST['city'] ?? '';
    $state         = $_POST['state'] ?? '';

    // --- Handle file upload ---
    $logo_path = '';
    if (!empty($_FILES['logo_file']['name'])) {
        $ext = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('logo_') . "." . $ext;
        $target_dir = "uploads/";
        if (!move_uploaded_file($_FILES['logo_file']['tmp_name'], $target_dir . $filename)) {
            $error_msg = "Logo upload failed. Check folder permissions.";
        } else {
            $logo_path = $target_dir . $filename;
        }
    }

    if (empty($error_msg)) {
        $status = ($role === 'STAFF') ? 'approved' : 'pending';

        // --- Prepare and execute insert ---
        $stmt = $conn->prepare("
            INSERT INTO company 
            (company_name, nature, address, email, phone, website_link, logo_url, description, focus_area, city, state, created_by, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            $error_msg = "Prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param(
                "sssssssssssss",
                $company_name, $nature, $address, $email, $phone, $website_link,
                $logo_path, $description, $focus_area, $city, $state, $user_id, $status
            );

            if ($stmt->execute()) {
                $success_msg = ($role === 'STAFF') 
                    ? "Company added successfully." 
                    : "Company submitted and pending admin approval.";
            } else {
                $error_msg = "Insert failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Company | FYP System</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Your original styles preserved */
body { margin:0; font-family:'Nunito',sans-serif; background:#f5f5f5; color:#333; }
a { text-decoration:none; color:inherit; }

header { background-color:#9F5EB7; color:white; padding:16px 24px; display:flex; align-items:center; position:relative; z-index:100; }
header h1 { font-size:22px; font-weight:800; position:absolute; left:50%; transform:translateX(-50%); margin:0; }
.welcome { display:flex; align-items:center; gap:10px; margin-left:auto; }
.welcome img { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid white; }
.hamburger { font-size:24px; cursor:pointer; background:none; border:none; color:white; }

.sidebar { height: 100%; width: 0; position: fixed; top: 0; left: 0; background: linear-gradient(180deg, #2e2040, #1b1524); overflow-x: hidden; transition: 0.4s; padding-top: 80px; border-top-right-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 4px 0 16px rgba(0, 0, 0, 0.4); z-index: 1000; }
.sidebar a { padding: 12px 20px; margin: 8px 16px; text-decoration: none; font-size: 16px; color: #f2f2f2; display: flex; align-items: center; gap: 10px; border-radius: 30px; transition: 0.3s, transform 0.2s, color 0.3s; }
.sidebar a:hover { background: rgba(159, 94, 183, 0.2); color: #d8b4f8; transform: translateX(5px); }
.sidebar .closebtn { position: absolute; top: 10px; right: 10px; font-size: 24px; color: white; cursor: pointer; }

.container { width: 100%; max-width: 1400px; background: white; margin: 0 auto; padding: 40px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); min-height: 100vh; box-sizing: border-box; display: flex; flex-direction: column; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px 40px; }
.full-width { grid-column: span 2; }

label { display: block; margin-bottom: 6px; font-weight: bolder; color: #9F5EB7; }
.form-field input, .form-field select, .form-field textarea { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-family: 'Nunito', sans-serif; box-sizing: border-box; }
textarea { min-height: 80px; }

button.save-btn { grid-column: span 2; display: block; justify-self: end; width: 180px; margin: 30px 0 0 0; padding: 12px 16px; font-size: 16px; background: #E09D46; border: none; color: white; border-radius: 12px; font-weight: 700; font-family: 'Nunito', sans-serif; cursor: pointer; transition: transform 0.18s ease, background 0.18s ease; }
button.save-btn:hover { transform: scale(1.03); background: #9F5EB7; }
button.save-btn:active { transform: scale(0.96); }

.success-msg { background: #D4EDDA; color: #155724; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #C3E6CB; }
.error-msg { background:#F8D7DA; color:#721C24; padding:12px 16px; border-radius:6px; margin-bottom:20px; border:1px solid #F5C6CB; }

.note-box { display:flex; align-items:center; gap:10px; background:#EDE0F8; border-left:6px solid #9F5EB7; padding:12px 16px; border-radius:6px; margin-bottom:40px; color:#9F5EB7; font-weight:600; }
.note-box i { font-size:20px; }
</style>
</head>
<body>

<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="closeSidebar()">&times;</a>
  <a href="dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
  <a href="companies.php"><i class="fas fa-building"></i> Companies</a>
  <a href="../login.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
</div>

<header>
  <button class="hamburger" onclick="window.location.href='companies.php'">
    <i class="fas fa-arrow-left"></i>
  </button>
  <h1>Add Company</h1>
  <div class="welcome">
    Hi, <?= htmlspecialchars($name) ?>
    <img src="<?= htmlspecialchars($profilePicture) ?>" alt="Profile">
  </div>
</header>

<div class="container">

<?php if($role === 'STUDENT'): ?>
<div class="note-box">
    <i class="fas fa-info-circle"></i>
    <span>Your company submission will require admin approval before it becomes visible to others.</span>
</div>
<?php endif; ?>

<form action="save_company.php" method="POST" enctype="multipart/form-data" class="form-grid">

    <div class="form-field">
      <label>Company Name</label>
      <input type="text" name="company_name" required>
    </div>

    <div class="form-field">
      <label>Nature</label>
      <select name="nature" required>
        <option value="GOVERNMENT">GOVERNMENT</option>
        <option value="PRIVATE">PRIVATE</option>
        <option value="MULTINATIONAL">MULTINATIONAL</option>
        <option value="OTHERS">OTHERS</option>
      </select>
    </div>

    <div class="form-field full-width">
      <label>Address</label>
      <textarea name="address" required></textarea>
    </div>

    <div class="form-field">
      <label>Email</label>
      <input type="email" name="email">
    </div>

    <div class="form-field">
      <label>Phone</label>
      <input type="text" name="phone">
    </div>

    <div class="form-field">
      <label>Website</label>
      <input type="text" name="website_link">
    </div>

    <div class="form-field full-width">
      <label>Logo</label>
      <input type="file" name="logo_file">
    </div>

    <div class="form-field full-width">
      <label>Description</label>
      <textarea name="description" required></textarea>
    </div>

    <div class="form-field">
      <label>Focus Area</label>
      <select name="focus_area">
        <option value="SOFTWARE DEVELOPMENT">SOFTWARE DEVELOPMENT</option>
        <option value="NETWORK & INFRASTRUCTURE">NETWORK & INFRASTRUCTURE</option>
        <option value="DATA SCIENCE">DATA SCIENCE</option>
        <option value="UI/UX">UI/UX</option>
        <option value="CYBERSECURITY">CYBERSECURITY</option>
        <option value="BUSINESS IT">BUSINESS IT</option>
        <option value="ARTIFICIAL INTELLIGENCE">ARTIFICIAL INTELLIGENCE</option>
        <option value="WEB DEVELOPMENT">WEB DEVELOPMENT</option>
        <option value="MOBILE APP DEVELOPMENT">MOBILE APP DEVELOPMENT</option>
        <option value="CLOUD COMPUTING">CLOUD COMPUTING</option>
        <option value="IOT">IOT</option>
        <option value="DIGITAL MARKETING">DIGITAL MARKETING</option>
        <option value="GAME DEVELOPMENT">GAME DEVELOPMENT</option>
        <option value="OTHERS">OTHERS</option>
      </select>
    </div>

    <div class="form-field">
      <label>City</label>
      <input type="text" name="city" required>
    </div>

    <div class="form-field">
      <label>State</label>
      <input type="text" name="state" required>
    </div>

    <button type="submit" class="save-btn full-width">Save Company</button>

</form>
</div>

<script>
function openSidebar(){document.getElementById("mySidebar").style.width="260px";}
function closeSidebar(){document.getElementById("mySidebar").style.width="0";}
</script>

</body>
</html>
