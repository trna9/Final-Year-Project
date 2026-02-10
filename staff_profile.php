<?php
session_start();
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STAFF') {
    header("Location: login.html");
    exit;
}

require_once 'db.php';

$staff_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Staff';

// --- Handle profile form submission ---
if (isset($_POST['save_profile'])) {
    $program_code = $_POST['program_code'] ?? null;

    // Handle profile picture upload
    $profile_picture_path = '';
    $stmt = $conn->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
    $stmt->bind_param("s", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $staff_data = $result->fetch_assoc();
    $profile_picture_path = $staff_data['profile_picture'] ?? 'uploads/default_profile.png';

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
        $targetFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
            $profile_picture_path = $targetFile;
        }
    }

    // Update staff table
    $stmt = $conn->prepare("UPDATE staff SET program_code = ?, profile_picture = ? WHERE staff_id = ?");
    $stmt->bind_param("sss", $program_code, $profile_picture_path, $staff_id);
    $stmt->execute();

    // Update role in staff_role table
    $stmt2 = $conn->prepare("INSERT INTO staff_role (staff_id, role) VALUES (?, 'SUPERVISOR') 
                            ON DUPLICATE KEY UPDATE role = 'SUPERVISOR'");
    $stmt2->bind_param("s", $staff_id);
    $stmt2->execute();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// --- Fetch staff data ---
$query = $conn->prepare("
    SELECT s.staff_id, s.profile_picture, s.program_code, p.program_name, sr.role
    FROM staff s
    LEFT JOIN program p ON s.program_code = p.program_code
    LEFT JOIN staff_role sr ON s.staff_id = sr.staff_id
    WHERE s.staff_id = ?
");
$query->bind_param("s", $staff_id);
$query->execute();
$result = $query->get_result();
$staff = $result->fetch_assoc();
$profilePicture = !empty($staff['profile_picture']) ? $staff['profile_picture'] : 'uploads/default_profile.png';

// --- Fetch program list for dropdown ---
$programList = [];
$programQuery = $conn->query("SELECT program_code, program_name FROM program ORDER BY program_code ASC");
while ($row = $programQuery->fetch_assoc()) {
    $programList[] = $row;
}

// --- Handle announcement posting ---
if (isset($_POST['post_announcement'])) {
    $title = $_POST['announcement_title'];
    $content = $_POST['announcement_content'];
    $visibility = $_POST['announcement_visibility'];
    $posted_by = $staff_id;

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

// --- Update announcement ---
if(isset($_POST['update_announcement'])) {
    $id = $_POST['announcement_id'];
    $title = $_POST['announcement_title'];
    $content = $_POST['announcement_content'];
    $visibility = $_POST['announcement_visibility'];

    $attachment_url = null;
    if(isset($_FILES['announcement_attachment']) && $_FILES['announcement_attachment']['error'] === 0){
        $uploadDir = 'uploads/';
        if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = time().'_'.basename($_FILES['announcement_attachment']['name']);
        $targetFile = $uploadDir.$filename;
        if(move_uploaded_file($_FILES['announcement_attachment']['tmp_name'], $targetFile)){
            $attachment_url = $targetFile;
        }
    }

    if($attachment_url){
        $stmt = $conn->prepare("UPDATE announcements SET title=?, content=?, visibility=?, attachment_url=?, last_edited=NOW() WHERE announcement_id=? AND posted_by=?");
        $stmt->bind_param("ssssss", $title, $content, $visibility, $attachment_url, $id, $staff_id);
    } else {
        $stmt = $conn->prepare("UPDATE announcements SET title=?, content=?, visibility=?, last_edited=NOW() WHERE announcement_id=? AND posted_by=?");
        $stmt->bind_param("sssss", $title, $content, $visibility, $id, $staff_id);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// --- Fetch announcements ---
$announcements = [];
$stmt = $conn->prepare("SELECT * FROM announcements WHERE posted_by = ? ORDER BY posted_on DESC");
$stmt->bind_param("s", $staff_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $announcements[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Profile | FYP System</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { margin: 0; font-family: 'Nunito', sans-serif; background: white; color: #333; }
a { text-decoration: none; color: inherit; }
header { background-color: #9F5EB7; color: white; padding: 16px 24px; display: flex; align-items: center; position: relative; z-index: 100; }
header h1 { font-size: 22px; font-weight: 800; position: absolute; left: 50%; transform: translateX(-50%); margin: 0; text-align: center; }
.welcome { display: flex; align-items: center; gap: 10px; margin-left: auto; }
.welcome img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid white; }

.sidebar { height: 100%; width: 0; position: fixed; top: 0; left: 0; background: linear-gradient(180deg, #2e2040, #1b1524); overflow-x: hidden; transition: 0.4s; padding-top: 80px; border-top-right-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 4px 0 16px rgba(0, 0, 0, 0.4); z-index: 1000; }
.sidebar a { display: flex; align-items: center; gap: 10px; padding: 12px 20px; margin: 8px 16px; font-size: 16px; color: #f2f2f2; border-radius: 30px; transition: 0.3s, transform 0.2s, color 0.3s; }
.sidebar a i { font-size: 20px; width: 28px; text-align: center; background: linear-gradient(135deg, #9F5EB7, #6A3A8D); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(1px 1px 2px rgba(0, 0, 0, 0.3)); transition: transform 0.2s ease; }
.sidebar a:hover { background: rgba(159, 94, 183, 0.2); color: #d8b4f8; transform: translateX(5px); }
.sidebar a:hover i { transform: scale(1.2) rotate(-5deg); }
.sidebar .closebtn { position: absolute; top: 10px; right: 10px; font-size: 24px; color: white; cursor: pointer; }

.hamburger { font-size: 24px; cursor: pointer; background: none; border: none; color: white; }

.container { width: 100%; margin: 0; padding: 0; }
.top-row { display: flex; flex-wrap: wrap; align-items: center; gap: 20px; padding: 30px 24px; }
.profile-picture-container { flex: 0 0 180px; display: flex; justify-content: center; }
.profile-picture-left { width: 160px; height: 160px; object-fit: cover; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.3s; }
.profile-picture-left:hover { transform: scale(1.05); }
.profile-info { flex: 1 1 300px; }
.profile-info h2 { font-weight: 800; margin-bottom: 8px; color: #4a2b68; }
.profile-info p { margin: 4px 0; color: #555; }

.announcement-buttons {
    display: flex;
    justify-content: flex-end; /* push buttons to the right */
    gap: 8px; /* space between Edit and Delete */
    margin-top: 10px;
}

.btn-edit {
    background-color: #9F5EB7;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    box-shadow: 0 3px 6px rgba(0,0,0,0.15);
    transition: 0.2s;
    font-family: 'Nunito', sans-serif;
    margin-top: 15px;
}

.btn-edit:hover {
    background-color: #E09D46;
    box-shadow: 0 4px 10px rgba(0,0,0,0.25);
}

.btn-delete {
    background-color: #e74c3c;
    color: white;
    border: none;
    padding: 8px 14px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    margin-left: 6px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.15);
    transition: 0.2s;
    font-family: 'Nunito', sans-serif;
    margin-top: 15px;
}

.btn-delete:hover {
    background-color: #c0392b; /* darker red */
    box-shadow: 0 4px 10px rgba(0,0,0,0.25);
}

.announcement-section { background: #fff; padding: 25px 35px; border-radius: 12px; margin-top: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.announcement-section h2 { margin-top: 0; font-size: 22px; font-weight: 800; color: #9F5EB7; }

.divider { border: none; border-top: 1.5px solid #9e9e9eff; margin: 20px 0; }

.announcement-item {
    background: #faf8fc;
    padding: 16px 18px;
    border-radius: 12px;
    margin-bottom: 16px;
    transition: 0.3s;
    background: #f0e6f8
}
.announcement-item:hover { transform: translateY(-3px); }
.announcement-item strong { color: #4a2b68; font-size: 17px; display: block; margin-bottom: 4px; }
.announcement-item span { color: #7a5ca1; font-size: 14px; }
.announcement-item p { color: #444; line-height: 1.6; margin: 10px 0; }
.announcement-item a { color: #0E5FB4; font-weight: 600; text-decoration: none; }
.announcement-item small { display: block; margin-top: 8px; color: #777; font-size: 12px; }

.announcement-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.dropdown {
    position: relative;
    display: inline-block;
}

.dropbtn {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #555;
    padding: 0;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    background-color: white;
    min-width: 100px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    border-radius: 8px;
    z-index: 10;
}

.dropdown-content a {
    color: #333;
    padding: 8px 12px;
    text-decoration: none;
    display: block;
    font-size: 14px;
    font-family: 'Nunito', sans-serif;
}

.dropdown-content a:hover {
    background-color: #f0e6f8;
    color: #9F5EB7;
    font-weight: 600;
}

.dropdown:hover .dropdown-content {
    display: block;
}

.modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); overflow-y: auto; }
.modal-content { background: white; margin: 60px auto; padding: 20px 30px; border-radius: 16px; max-width: 700px; max-height: 80vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
.close { float: right; font-size: 22px; cursor: pointer; color: #9F5EB7; font-weight: bold; }

.form-field { margin-bottom: 15px; }
.form-field label { display: block; margin-top: 8px; font-weight: bolder; color: #9F5EB7; }
.form-field input, .form-field select, .form-field textarea { width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; font-family: 'Nunito', sans-serif; }

form button { display: block; margin: 16px auto 0; background: #E09D46; color: white; border: none; padding: 10px 20px; border-radius: 10px; font-family: 'Nunito', sans-serif; font-weight: 700; cursor: pointer; }
form button:hover { background: #c47a34; }
.role-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 5px;
    background: #9F5EB7;   /* golden/orange */
    color: white;
    font-weight: 700;
    font-size: 14px;
    margin-top: 6px;
}

.role-badge.SUPERVISOR { background: #8c3fa8ff; }
.role-badge.COORDINATOR { background: #e14545ff; }
.role-badge.LECTURER { background: #3c76b9ff; }
</style>
</head>
<body>

<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="document.getElementById('mySidebar').style.width='0'">&times;</a>
  <a href="staff_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
  <a href="assign_students.php"><i class="fas fa-user-plus"></i> Assign Students</a>
  <a href="my_students.php"><i class="fas fa-users"></i> My Students</a>
  <a href="companies.php"><i class="fas fa-building"></i> Companies</a>
  <a href="company_ranking.php"><i class="fas fa-star"></i> Company Ranking</a>
  <a href="login.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
</div>

<header>
   <button class="hamburger" onclick="window.location.href='staff_dashboard.php'">
    <i class="fas fa-arrow-left"></i>
  </button>
  <h1>Staff Profile</h1>
  <div class="welcome">
    Hi, <?php echo htmlspecialchars($name); ?>
    <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile">
  </div>
</header>

<div class="container">
    <div class="top-row">
      <div class="profile-picture-container">
        <img src="<?php echo htmlspecialchars($profilePicture); ?>" class="profile-picture-left" alt="Profile Picture">
      </div>
      <div class="profile-info">
        <h2><?php echo htmlspecialchars($name); ?></h2>
        <p><strong><?php echo htmlspecialchars($staff['staff_id']); ?></strong></p>
        <p><?php echo htmlspecialchars($staff['program_name'] ?? 'Not set'); ?></p>
        <?php if (!empty($staff['role'])): ?>
          <span class="role-badge <?php echo $staff['role']; ?>">
            <?php echo htmlspecialchars($staff['role']); ?>
          </span>
        <?php endif; ?>
      </div>
      <button class="btn-edit" onclick="openModal()"><i class="fas fa-pen"></i> Edit</button>
    </div>

    <hr class="divider">

    <div class="announcement-section">
        <h2>My Announcements</h2>
        <?php if (!empty($announcements)): ?>
          <?php foreach ($announcements as $ann): ?>
              <div class="announcement-item" data-id="<?php echo $ann['announcement_id']; ?>">
                <div class="announcement-header">
                    <strong><?php echo htmlspecialchars($ann['title']); ?></strong>
                    <div class="dropdown">
                        <button class="dropbtn">⋮</button>
                        <div class="dropdown-content">
                            <a href="javascript:void(0)" onclick="openEditModal(<?php echo $ann['announcement_id']; ?>)">Edit</a>
                            <a href="javascript:void(0)" onclick="deleteAnnouncement(<?php echo $ann['announcement_id']; ?>)">Delete</a>
                        </div>
                    </div>
                </div>
                <span>(<?php echo htmlspecialchars($ann['visibility']); ?>)</span>
                <p><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
                <?php if (!empty($ann['attachment_url'])): ?>
                    <a href="<?php echo htmlspecialchars($ann['attachment_url']); ?>" target="_blank">📎 Attachment</a>
                <?php endif; ?>
                <br>
                <small>Posted: <?php echo $ann['posted_on']; ?><?php if (!empty($ann['last_edited'])): ?> | Last Edited: <?php echo $ann['last_edited']; ?><?php endif; ?></small>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
            <p>No announcements yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div id="editAnnouncementModal" class="modal" style="display:none;">
  <div class="modal-content">
    <span class="close" onclick="closeEditModal()">&times;</span>
    <h2>Edit Announcement</h2>
    <form id="editAnnouncementForm" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="announcement_id" id="edit_announcement_id">
      <div class="form-field">
        <label for="edit_announcement_title">Title</label>
        <input type="text" name="announcement_title" id="edit_announcement_title" required>
      </div>
      <div class="form-field">
        <label for="edit_announcement_content">Content</label>
        <textarea name="announcement_content" id="edit_announcement_content" rows="4" required></textarea>
      </div>
      <div class="form-field">
        <label for="edit_announcement_visibility">Visibility</label>
        <select name="announcement_visibility" id="edit_announcement_visibility">
          <option value="ALL">All</option>
          <option value="STUDENTS_ONLY">Students Only</option>
          <option value="STAFF_ONLY">Staff Only</option>
        </select>
      </div>
      <div class="form-field">
        <label for="edit_announcement_attachment">Attachment (optional)</label>
        <input type="file" name="announcement_attachment" id="edit_announcement_attachment" accept="image/*,.pdf,.doc,.docx">
      </div>
      <button type="submit" name="update_announcement">Save Changes</button>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h2>Edit Profile</h2>
    <form method="POST" enctype="multipart/form-data">
      <div class="form-field">
        <label>Program</label>
        <select name="program_code" required>
          <option value="">-- Select Program --</option>
          <?php foreach ($programList as $p): ?>
            <option value="<?php echo $p['program_code']; ?>" <?php if (($staff['program_code'] ?? '') == $p['program_code']) echo 'selected'; ?>>
              <?php echo htmlspecialchars($p['program_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-field">
        <label>Profile Picture</label>
        <input type="file" name="profile_picture" accept="image/*">
      </div>
      <button type="submit" name="save_profile">Save Changes</button>
    </form>
  </div>
</div>

<script>
function openModal() { document.getElementById("editModal").style.display = "block"; }
function closeModal() { document.getElementById("editModal").style.display = "none"; }

window.onclick = function(event) {
    const modal = document.getElementById("editModal");
    if (event.target == modal) closeModal();
}

function openEditModal(id) {
    const annDiv = document.querySelector(`.announcement-item[data-id='${id}']`);
    const title = annDiv.querySelector("strong").innerText;
    const content = annDiv.querySelector("p").innerText;
    const visibility = annDiv.querySelector("span").innerText.replace(/[()]/g, '');

    document.getElementById("edit_announcement_id").value = id;
    document.getElementById("edit_announcement_title").value = title;
    document.getElementById("edit_announcement_content").value = content;
    document.getElementById("edit_announcement_visibility").value = visibility;

    document.getElementById("editAnnouncementModal").style.display = "block";
}

function closeEditModal() {
    document.getElementById("editAnnouncementModal").style.display = "none";
}

window.onclick = function(event) {
    const modal = document.getElementById("editAnnouncementModal");
    if(event.target == modal) closeEditModal();
}

function deleteAnnouncement(id) {
    if(confirm("Are you sure you want to delete this announcement?")) {
        window.location.href = `delete_announcement.php?id=${id}`;
    }
}
</script>
</body>
</html>
