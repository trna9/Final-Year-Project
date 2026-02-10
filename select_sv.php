<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STUDENT') {
    header("Location: login.html");
    exit;
}

$student_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Student';

// Fetch student profile picture and current supervisor
$profileQuery = $conn->prepare("SELECT profile_picture, supervisor_id FROM student WHERE student_id = ?");
$profileQuery->bind_param("s", $student_id);
$profileQuery->execute();
$profileResult = $profileQuery->get_result();
$profileRow = $profileResult->fetch_assoc();
$profile_picture = !empty($profileRow['profile_picture']) 
    ? $profileRow['profile_picture']  // assume full path stored in DB
    : 'img/default_profile.png';
$currentSupervisorId = $profileRow['supervisor_id'] ?? null;
$profileQuery->close();

// Fetch current supervisor name if assigned
$currentSupervisorName = null;
if ($currentSupervisorId) {
    $svQuery = $conn->prepare("
        SELECT u.name AS supervisor_name
        FROM staff s
        JOIN user u ON s.staff_id = u.user_id
        WHERE s.staff_id = ?
    ");
    $svQuery->bind_param("s", $currentSupervisorId);
    $svQuery->execute();
    $svResult = $svQuery->get_result();
    if ($svResult->num_rows > 0) {
        $currentSupervisorName = $svResult->fetch_assoc()['supervisor_name'];
    }
    $svQuery->close();
}

// Handle "Select Supervisor" action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supervisor_id'])) {
    $supervisor_id = $_POST['supervisor_id'];

    // Fetch selected supervisor name
    $svNameQuery = $conn->prepare("SELECT u.name AS supervisor_name FROM staff s JOIN user u ON s.staff_id = u.user_id WHERE s.staff_id = ?");
    $svNameQuery->bind_param("s", $supervisor_id);
    $svNameQuery->execute();
    $svNameResult = $svNameQuery->get_result();
    $selectedSupervisorName = $svNameResult->fetch_assoc()['supervisor_name'];
    $svNameQuery->close();

    // Update supervisor
    $update = $conn->prepare("UPDATE student SET supervisor_id = ? WHERE student_id = ?");
    $update->bind_param("ss", $supervisor_id, $student_id);
    $update->execute();
    $update->close();

    echo "<script>
        alert('You have successfully selected ".addslashes($selectedSupervisorName)." as your supervisor.');
        window.location.href='select_sv.php';
    </script>";
    exit;
}

// Fetch all supervisors
$supervisors = [];
$stmt = $conn->prepare("
    SELECT st.staff_id, u.name AS supervisor_name, sr.role
    FROM staff st
    JOIN user u ON st.staff_id = u.user_id
    JOIN staff_role sr ON st.staff_id = sr.staff_id
    WHERE sr.role = 'SUPERVISOR'
    ORDER BY u.name ASC
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $supervisors[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Select Supervisor | FYP System</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { margin:0; font-family:'Nunito',sans-serif; background:#f5f5f5; color:#333; }
a { text-decoration:none; color:inherit; }

header { background-color:#9F5EB7; color:white; padding:16px 24px; display:flex; align-items:center; position:relative; z-index:100; }
header h1 { font-size:22px; font-weight:800; position:absolute; left:50%; transform:translateX(-50%); margin:0; }
.welcome { display:flex; align-items:center; gap:10px; margin-left:auto; }
.welcome img { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid white; }
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
.sidebar { height:100%; width:0; position:fixed; top:0; left:0; background:linear-gradient(180deg,#2e2040,#1b1524); overflow-x:hidden; transition:0.4s; padding-top:80px; border-top-right-radius:20px; border-bottom-right-radius:20px; box-shadow:4px 0 16px rgba(0,0,0,0.4); z-index:1000; }
.sidebar a { display:flex; align-items:center; gap:10px; padding:12px 20px; margin:8px 16px; font-size:16px; color:#f2f2f2; border-radius:30px; transition:0.3s, transform 0.2s, color 0.3s; }
.sidebar a i { font-size:20px; width:28px; text-align:center; background:linear-gradient(135deg,#9F5EB7,#6A3A8D); -webkit-background-clip:text; -webkit-text-fill-color:transparent; filter:drop-shadow(1px 1px 2px rgba(0,0,0,0.3)); }
.sidebar a:hover { background: rgba(159,94,183,0.2); color:#d8b4f8; transform:translateX(5px); }
.sidebar .closebtn { position:absolute; top:10px; right:10px; font-size:24px; color:white; cursor:pointer; }
.hamburger { font-size:24px; cursor:pointer; background:none; border:none; color:white; }

.container { max-width:1000px; margin:40px auto; padding:0 20px; }

table { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
th, td { padding:14px 16px; text-align:left; }
th { background:#9F5EB7; color:white; font-weight:700; text-transform:uppercase; font-size:14px; }
tr:nth-child(even) { background:#faf7ff; }
tr:hover { background:#f0e5ff; }

.select-btn { background:#9F5EB7; color:white; border:none; border-radius:6px; padding:8px 12px; cursor:pointer; font-weight:600; font-family:'Nunito',sans-serif; transition:0.3s; }
.select-btn:hover { background:#E09D46; transform:scale(1.05); }
.select-btn:disabled { background:#ccc; cursor:not-allowed; transform:none; }

.note-box { display:flex; align-items:center; gap:10px; background:#EDE0F8; border-left:6px solid #9F5EB7; padding:12px 16px; border-radius:6px; margin-bottom:20px; color:#9F5EB7; font-weight:600; }
.note-box i { font-size:20px; }

.current-sv { display:flex; align-items:center; gap:10px; background:#FFF4E5; border-left:6px solid #E09D46; padding:12px 16px; border-radius:6px; margin-bottom:20px; color:#E09D46; font-weight:600; }
.current-sv i { font-size:20px; }

.empty-msg { text-align:center; font-size:16px; color:#555; margin-top:30px; }
</style>
</head>
<body>

<header>
  <button class="hamburger" onclick="window.location.href='student_dashboard.php'">
    <i class="fas fa-arrow-left"></i>
  </button>
  <h1>Select Supervisor</h1>
  <div class="welcome">
    Hi, <?= htmlspecialchars($name) ?>
    <a href="student_profile.php">
      <img src="<?= htmlspecialchars($profile_picture) ?>" alt="Profile" class="profile-pic">
    </a>
  </div>
</header>

<div class="container">

    <?php if ($currentSupervisorName): ?>
        <div class="current-sv">
            <i class="fas fa-user-check"></i>
            <span>Current Academic Supervisor: <strong><?= htmlspecialchars($currentSupervisorName) ?></strong></span>
        </div>
    <?php endif; ?>

    <div class="note-box">
        <i class="fas fa-info-circle"></i>
        <span>
            <?= $currentSupervisorName 
                ? "If you wish to change supervisor, you may select from the list below." 
                : "Please select your academic supervisor from the list below." ?>
        </span>
    </div>

    <?php if (empty($supervisors)): ?>
        <p class="empty-msg">No supervisors available at the moment.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Supervisor Name</th>
                    <th>Role</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($supervisors as $sv): ?>
                <tr>
                    <td><?= htmlspecialchars($sv['supervisor_name']) ?></td>
                    <td><?= htmlspecialchars($sv['role']) ?></td>
                    <td>
                        <form method="POST" style="margin:0;" onsubmit="return confirmSelection('<?= htmlspecialchars($sv['supervisor_name']) ?>', <?= $currentSupervisorId ? 'true' : 'false' ?>)">
                            <input type="hidden" name="supervisor_id" value="<?= htmlspecialchars($sv['staff_id']) ?>">
                            <button type="submit" class="select-btn" <?= ($currentSupervisorId == $sv['staff_id']) ? 'disabled' : '' ?>>
                                <i class="fas fa-user-plus"></i> <?= ($currentSupervisorId == $sv['staff_id']) ? 'Selected' : 'Select' ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function openSidebar() { document.getElementById("mySidebar").style.width="250px"; }
function closeSidebar() { document.getElementById("mySidebar").style.width="0"; }

function confirmSelection(supervisorName, hasCurrent) {
    if (hasCurrent) {
        return confirm('You already have a supervisor. Are you sure you want to change to ' + supervisorName + '?');
    } else {
        return confirm('Are you sure you want to select ' + supervisorName + ' as your supervisor?');
    }
}
</script>
</body>
</html>
