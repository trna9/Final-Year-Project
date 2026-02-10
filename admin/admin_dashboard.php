<?php
session_start();
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: ../login.php");
    exit;
}

require_once '../db.php';

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Admin';

// Count total students
$studentCountQuery = $conn->query("SELECT COUNT(*) AS total FROM student");
$studentCount = $studentCountQuery->fetch_assoc()['total'] ?? 0;

// Count total staff
$staffCountQuery = $conn->query("SELECT COUNT(*) AS total FROM staff");
$staffCount = $staffCountQuery->fetch_assoc()['total'] ?? 0;

// Count total companies
$companyCountQuery = $conn->query("
    SELECT COUNT(*) AS total 
    FROM company 
    WHERE status = 'approved'
");
$companyCount = $companyCountQuery->fetch_assoc()['total'] ?? 0;



// use static image for admin profile
$profile_pic = '../img/admin_avatar.jpg';

// --- Handle announcement posting ---
if (isset($_POST['post_announcement'])) {
    $title = $_POST['announcement_title'];
    $content = $_POST['announcement_content'];
    $visibility = $_POST['announcement_visibility'];
    $posted_by = $user_id;

    $attachment_url = NULL;
    if (isset($_FILES['announcement_attachment']) && $_FILES['announcement_attachment']['error'] === 0) {
        $uploadDir = '../../uploads/'; // go up one level to main uploads
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true); // create if missing

        $filename = time() . '_' . basename($_FILES['announcement_attachment']['name']);
        $targetFile = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['announcement_attachment']['tmp_name'], $targetFile)) {
            // Store the relative path to the uploads folder for links
            $attachment_url = '../../uploads/' . $filename; 
        }
    }

    $stmt = $conn->prepare("INSERT INTO announcements (title, content, posted_by, visibility, attachment_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $title, $content, $posted_by, $visibility, $attachment_url);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']); // reload page
    exit;
}

// Fetch announcements from the database
$announcementQuery = $conn->prepare("
    SELECT a.title, a.content, a.posted_on, a.attachment_url, u.name AS posted_by
    FROM announcements a
    LEFT JOIN user u ON a.posted_by = u.user_id
    ORDER BY a.posted_on DESC
    LIMIT 10
");
$announcementQuery->execute();
$announcementResult = $announcementQuery->get_result();
$announcements = $announcementResult->fetch_all(MYSQLI_ASSOC);
$announcementQuery->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard | FYP System</title>
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

    .dashboard-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    .dashboard-card h2 {
      font-size: 18px;
      color: #9F5EB7;
      margin-bottom: 10px;
    }
    .dashboard-card p {
      font-size: 14px;
      color: #333;
      line-height: 1.6;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
      margin-top: 15px;
    }
    .stat-box {
      background: #f1e8f7;
      border-radius: 10px;
      padding: 15px;
      text-align: center;
      font-weight: 700;
      color: #6A3A8D;
      font-size: 16px;
    }

    .profile-pic {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid white;
    }
    .btn {
      display: inline-block;
      background-color: #9F5EB7;
      color: white;
      padding: 10px 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      margin-top: 10px;
      font-weight: 700;
      font-family: 'Nunito', sans-serif;
      transition: background 0.3s ease;
    }
    .btn:hover {
      background-color: #E09D46;
      transform: scale(1.05);
    }

    .container-card {
    background: #fff; /* white box */
    padding: 30px 24px;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    max-width: 900px;
    margin: 30px auto; /* center horizontally and spacing from top */
    transition: transform 0.3s;
    }

    .container-card:hover {
        transform: translateY(-3px);
    }

    .container-card textarea:focus {
        outline:none;
        border-color:#9F5EB7;
        box-shadow:0 0 6px rgba(159,94,183,0.3);
    }

    .container-card button:hover {
        background:#c47a34;
        transform:scale(1.05);
    }

    .container-card label:hover {
        color:#6A3A8D;
    }

    .announcement-list {
      overflow-y: auto;
      max-height: 380px;
    }
    .announcement-list p {
      font-size: 13px;
      margin: 8px 0;
      padding: 8px;
      border-bottom: 1px solid #eee;
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

  </style>
</head>
<body>

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
  <button class="hamburger" onclick="document.getElementById('mySidebar').style.width='260px'">
    <i class="fas fa-bars"></i>
  </button>
  <h1>Admin Dashboard</h1>
  <div class="welcome">
    Hi, <?php echo htmlspecialchars($name); ?>
    <a href="admin_profile.php">
      <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-pic">
    </a>
  </div>
</header>

<div class="main-layout">
  <div class="left-section">
    <div class="dashboard-card">
      <h2>System Overview</h2>
      <div class="stats-grid">
        <div class="stat-box">👩‍🎓 <?php echo $studentCount; ?> Students</div>
        <div class="stat-box">👨‍🏫 <?php echo $staffCount; ?> Staff</div>
        <div class="stat-box">🏢 <?php echo $companyCount; ?> Companies</div>
      </div>
    </div>

<!-- Announcement Posting Form -->
    <div class="container-card" style="max-width:1000px; margin-top: 30px;">
      <div style="display:flex; gap:12px; align-items:flex-start;">
        <form method="POST" enctype="multipart/form-data" style="flex:1; margin-left:15px;">
          <input type="text" name="announcement_title" placeholder="Title" required
                style="width:95%;resize:none;padding:12px;border-radius:12px;border:1px solid #ccc;font-family:'Nunito',sans-serif;font-size:14px; margin-bottom:16px;">
          <textarea name="announcement_content" placeholder="Write your announcement here..." 
                    required 
                    style="width:95%;resize:none;padding:12px;border-radius:12px;border:1px solid #ccc;font-family:'Nunito',sans-serif;font-size:14px;min-height:100px;"></textarea>
          <div style="display:flex; align-items:center; justify-content:space-between; width:100%; margin-top:8px;">
            <div style="display:flex; gap:10px; align-items:center;">
              <input type="file" name="announcement_attachment" style="display:none;" id="attachFile">
              <label for="attachFile" style="cursor:pointer;color:#9F5EB7;font-weight:600;">
                <i class="fas fa-paperclip"></i> Add Attachment
              </label>
              <select name="announcement_visibility" required
                      style="border-radius:12px;border:1px solid #ccc;padding:4px 8px;font-family:'Nunito',sans-serif;">
                <option value="ALL">All</option>
                <option value="STUDENTS_ONLY">Students Only</option>
                <option value="STAFF_ONLY">Staff Only</option>
              </select>
            </div>
            <button type="submit" name="post_announcement" 
                    style="background:#E09D46;color:white;padding:8px 20px;border:none;border-radius:20px;font-weight:700; font-family:'Nunito',sans-serif; cursor:pointer;transition:0.3s; margin-right:15px;">
              Post
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="right-section">
    <div class="dashboard-card">
        <h2>Announcements</h2>
         <div class="announcement-list">
            <?php if (!empty($announcements)): ?>
                 <?php foreach ($announcements as $a): ?>
                     <p>
                        <strong>📢 <?php echo htmlspecialchars($a['title']); ?></strong><br><br>
                        <?php echo nl2br(htmlspecialchars($a['content'])); ?><br><br>

                        <?php if (!empty($a['attachment_url'])): ?>
                           <a href="../<?php echo htmlspecialchars($a['attachment_url']); ?>" 
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
function openSidebar(){document.getElementById("mySidebar").style.width="260px";}
function closeSidebar(){document.getElementById("mySidebar").style.width="0";}
</script>

</body>
</html>
