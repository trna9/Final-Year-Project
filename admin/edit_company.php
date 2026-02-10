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

// Get company ID from URL
if (!isset($_GET['id'])) {
    header("Location: manage_company.php");
    exit;
}

$company_id = intval($_GET['id']);

// Fetch company data
$stmt = $conn->prepare("SELECT * FROM company WHERE company_id = ?");
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();
$stmt->close();

if (!$company) {
    $_SESSION['error_msg'] = "Company not found.";
    header("Location: manage_company.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['company_name'] ?? '';
    $nature = $_POST['nature'] ?? '';
    $address = $_POST['address'] ?? '';
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $website_link = $_POST['website_link'] ?? null;
    $description = $_POST['description'] ?? '';
    $focus_area = $_POST['focus_area'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';

    // Handle logo upload
    $logo_url = $company['logo_url'];
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/company_logos/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $file_tmp = $_FILES['logo_file']['tmp_name'];
        $file_name = basename($_FILES['logo_file']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if (in_array($file_ext, $allowed)) {
            $new_file_name = uniqid('logo_') . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;
            if (move_uploaded_file($file_tmp, $destination)) {
                $logo_url = 'uploads/company_logos/' . $new_file_name;
            }
        }
    }

    // Update company
    $stmt = $conn->prepare("
        UPDATE company SET 
        company_name=?, nature=?, address=?, email=?, phone=?, website_link=?, logo_url=?, description=?, focus_area=?, city=?, state=?
        WHERE company_id=?
    ");
    $stmt->bind_param(
        "sssssssssssi",
        $company_name, $nature, $address, $email, $phone, $website_link, $logo_url, $description, $focus_area, $city, $state, $company_id
    );

    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Company updated successfully!";
    } else {
        $_SESSION['error_msg'] = "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: manage_company.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Company | FYP System</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* Copy the same CSS from your add_company.php page */
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
.form-field input, .form-field select, .form-field textarea { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ccc; font-family: 'Nunito', sans-serif; box-sizing: border-box; appearance: none; }
textarea { min-height: 80px; }
button.save-btn { grid-column: span 2; display: block; justify-self: end; width: 180px; margin: 30px auto 0 auto; padding: 12px 16px; font-size: 16px; background: #E09D46; border: none; color: white; border-radius: 12px; font-weight: 700; font-family: 'Nunito', sans-serif; cursor: pointer; transition: transform 0.18s ease, background 0.18s ease; }
button.save-btn:hover { transform: scale(1.03); background: #9F5EB7; }
button.save-btn:active { transform: scale(0.96); }
</style>
</head>

<body>

<!-- Sidebar -->
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

<!-- Header -->
<header>
  <button class="hamburger" onclick="window.location.href='manage_company.php'">
    <i class="fas fa-arrow-left"></i>
  </button>

  <h1>Edit Company</h1>

  <div class="welcome">
    Hi, <?= htmlspecialchars($name) ?>
    <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile">
  </div>
</header>

<!-- Main Form Container -->
<div class="container">

  <form action="" method="POST" enctype="multipart/form-data" class="form-grid">

    <div class="form-field">
      <label>Company Name</label>
      <input type="text" name="company_name" required value="<?= htmlspecialchars($company['company_name']) ?>">
    </div>

    <div class="form-field">
      <label>Nature</label>
      <select name="nature" required>
        <?php 
        $natures = ['GOVERNMENT','PRIVATE','MULTINATIONAL','OTHERS'];
        foreach($natures as $n): ?>
          <option value="<?= $n ?>" <?= $company['nature']==$n?'selected':'' ?>><?= $n ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-field full-width">
      <label>Address</label>
      <textarea name="address" required><?= htmlspecialchars($company['address']) ?></textarea>
    </div>

    <div class="form-field">
      <label>Email</label>
      <input type="email" name="email" value="<?= htmlspecialchars($company['email']) ?>">
    </div>

    <div class="form-field">
      <label>Phone</label>
      <input type="text" name="phone" value="<?= htmlspecialchars($company['phone']) ?>">
    </div>

    <div class="form-field">
      <label>Website</label>
      <input type="text" name="website_link" value="<?= htmlspecialchars($company['website_link']) ?>">
    </div>

    <div class="form-field full-width">
      <label>Logo</label>
      <?php if($company['logo_url']): ?>
        <img src="../<?= $company['logo_url'] ?>" alt="Logo" style="height:50px; display:block; margin-bottom:5px;">
      <?php endif; ?>
      <input type="file" name="logo_file">
    </div>

    <div class="form-field full-width">
      <label>Description</label>
      <textarea name="description" required><?= htmlspecialchars($company['description']) ?></textarea>
    </div>

    <div class="form-field">
      <label>Focus Area</label>
      <select name="focus_area">
        <?php
        $focus_areas = ['SOFTWARE DEVELOPMENT','NETWORK & INFRASTRUCTURE','DATA SCIENCE','UI/UX','CYBERSECURITY','BUSINESS IT','ARTIFICIAL INTELLIGENCE','WEB DEVELOPMENT','MOBILE APP DEVELOPMENT','CLOUD COMPUTING','IOT','DIGITAL MARKETING','GAME DEVELOPMENT','OTHERS'];
        foreach($focus_areas as $fa): ?>
          <option value="<?= $fa ?>" <?= $company['focus_area']==$fa?'selected':'' ?>><?= $fa ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-field">
      <label>City</label>
      <input type="text" name="city" required value="<?= htmlspecialchars($company['city']) ?>">
    </div>

    <div class="form-field">
      <label>State</label>
      <input type="text" name="state" required value="<?= htmlspecialchars($company['state']) ?>">
    </div>

    <button type="submit" class="save-btn full-width">Update Company</button>

  </form>

</div>

<script>
function openSidebar(){document.getElementById("mySidebar").style.width="260px";}
function closeSidebar(){document.getElementById("mySidebar").style.width="0";}
</script>

</body>
</html>
