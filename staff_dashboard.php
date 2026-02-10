<?php
session_start();
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STAFF') {
    header("Location: login.html");
    exit;
}

require_once 'db.php'; // your DB connection

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Staff';

// Fetch staff's profile picture
$query = $conn->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
$query->bind_param("s", $user_id);
$query->execute();
$result = $query->get_result();
$staff = $result->fetch_assoc();
$profile_pic = $staff['profile_picture'] ?? 'images/default_profile.png';
$query->close();

// --- Handle announcement posting ---
if (isset($_POST['post_announcement'])) {
    $title = $_POST['announcement_title'];
    $content = $_POST['announcement_content'];
    $visibility = $_POST['announcement_visibility'];
    $posted_by = $user_id;

    $attachment_url = NULL;
    if (isset($_FILES['announcement_attachment']) && $_FILES['announcement_attachment']['error'] === 0) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = time() . '_' . basename($_FILES['announcement_attachment']['name']);
        $targetFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['announcement_attachment']['tmp_name'], $targetFile)) {
            $attachment_url = $targetFile;
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
    WHERE a.visibility = 'ALL' OR a.visibility = 'STAFF_ONLY'
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
  <title>Staff Dashboard | FYP System</title>
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

    .announcement  {
      background: white;
      border-radius: 12px;
      padding: 16px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .announcement h2{
      font-size: 18px;
      margin-bottom: 12px;
      color: #9F5EB7;
    }
    .announcement-list {
      overflow-y: auto;
      max-height: 400px;
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

    .form-field { margin-bottom: 15px; }
    .form-field label { display: block; margin-top: 8px; font-weight: bolder; color: #9F5EB7; }
    .form-field input, .form-field select, .form-field textarea { width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; font-family: 'Nunito', sans-serif; }

    .form-button {
        display: block;
        margin: 16px auto 0;
        background: #E09D46;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-family: 'Nunito', sans-serif;
        font-weight: 700;
        cursor: pointer;
    }
    .form-button:hover { background: #c47a34; }



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

  </style>
</head>
<body>

<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="document.getElementById('mySidebar').style.width='0'">&times;</a>
  <a href="staff_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
  <a href="my_students.php"><i class="fas fa-users"></i> My Students</a>
  <a href="companies.php"><i class="fas fa-building"></i> Companies</a>
  <a href="company_ranking.php"><i class="fas fa-star"></i> Company Ranking</a>
  <a href="login.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
</div>

<header>
  <button class="hamburger" onclick="document.getElementById('mySidebar').style.width='250px'">
    <i class="fas fa-bars"></i>
  </button>
  <h1>Staff Dashboard</h1>
  <div class="welcome">
    Hi, <?php echo htmlspecialchars($name); ?>
    <a href="staff_profile.php">
      <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-pic">
    </a>
  </div>
</header>

<div class="main-layout">
  <div class="left-section">
    <!-- Carousel -->
    <div class="carousel">
      <div class="slides" id="slides">
        <div class="slide" style="background-image:url('img/staff_banner1.jpg');">
          <h2>My Students</h2>
          <p>Monitor students’ progress and help them choose the right internship path.</p>
          <button class="btn" onclick="location.href='my_students.php'">View Students</button>
        </div>
        <div class="slide" style="background-image:url('img/staff_banner2.jpg');">
          <h2>Evaluate Companies</h2>
          <p>Assess and rank internship companies based on student feedback and performance.</p>
          <button class="btn" onclick="location.href='companies.php'">View Companies</button>
        </div>
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
              <input type="file" name="announcement_attachment" style="display:none;" id="attachFileStaff">
              <label for="attachFileStaff" style="cursor:pointer;color:#9F5EB7;font-weight:600;">
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
          <div>
      </div>
    </div>
  </div>
</div>

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
