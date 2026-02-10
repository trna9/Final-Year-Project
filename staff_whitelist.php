<?php
session_start();
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STAFF') {
    header("Location: login.html");
    exit;
}

require_once 'db.php';

$staff_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Staff';

$selectedStudentId = isset($_GET['id']) && $_GET['id'] !== '' ? $_GET['id'] : null;

// Fetch staff profile picture
$query = $conn->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
$query->bind_param("s", $staff_id);
$query->execute();
$result = $query->get_result();
$staff = $result->fetch_assoc();
$profile_pic = $staff['profile_picture'] ?? 'images/default_profile.png';
$query->close();

// Fetch staff role
$roleQuery = $conn->prepare("SELECT role FROM staff_role WHERE staff_id = ?");
$roleQuery->bind_param("s", $staff_id);
$roleQuery->execute();
$roleResult = $roleQuery->get_result();
$staff_role = ($roleResult->num_rows > 0) ? $roleResult->fetch_assoc()['role'] : '';
$roleQuery->close();

$accessDenied = ($staff_role !== 'SUPERVISOR');

// Fetch students assigned to this staff
if ($selectedStudentId) {
    $stmt = $conn->prepare("
        SELECT s.student_id, u.name 
        FROM student s
        JOIN user u ON s.student_id = u.user_id
        WHERE s.supervisor_id = ? AND s.student_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ss", $staff_id, $selectedStudentId);
} else {
    $stmt = $conn->prepare("
        SELECT s.student_id, u.name 
        FROM student s
        JOIN user u ON s.student_id = u.user_id
        WHERE s.supervisor_id = ?
        ORDER BY u.name ASC
    ");
    $stmt->bind_param("s", $staff_id);
}
$stmt->execute();
$result = $stmt->get_result();
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Whitelist | FYP System</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { margin:0; font-family:'Nunito', sans-serif; background:#F5F5F8; }
header { background:#9F5EB7; color:white; padding:16px 24px; display:flex; justify-content:space-between; align-items:center; position:relative; }
header h1 { margin:0; font-size:22px; font-weight:800; position:absolute; left:50%; transform:translateX(-50%); }
.welcome { display:flex; align-items:center; gap:10px; margin-left:auto; }
.hamburger { font-size:24px; cursor:pointer; background:none; border:none; color:white; }
.sidebar { height:100%; width:0; position:fixed; top:0; left:0; background:linear-gradient(180deg, #2e2040, #1b1524); overflow-x:hidden; transition:0.4s; padding-top:80px; border-top-right-radius:20px; border-bottom-right-radius:20px; box-shadow:4px 0 16px rgba(0,0,0,0.4); z-index:1000; }
.sidebar a { padding:12px 20px; margin:8px 16px; text-decoration:none; font-size:16px; color:#f2f2f2; display:flex; align-items:center; gap:10px; border-radius:30px; transition:0.3s, transform 0.2s, color 0.3s; }
.sidebar a:hover { background: rgba(159, 94, 183, 0.2); color: #d8b4f8; transform: translateX(5px); }
.sidebar a i { font-size:20px; width:28px; text-align:center; background:linear-gradient(135deg, #9F5EB7, #6A3A8D); -webkit-background-clip:text; -webkit-text-fill-color:transparent; filter: drop-shadow(1px 1px 2px rgba(0,0,0,0.3)); transition: transform 0.2s ease; }
.sidebar a:hover i { transform: scale(1.2) rotate(-5deg); }
.sidebar .closebtn { position:absolute; top:10px; right:10px; font-size:24px; color:white; cursor:pointer; }
.profile-pic { width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid #fff; display:block; }

.main-content { padding:40px 60px; background:#F5F5F8; }

.badge-id { display:inline-block; background:#E09D46; color:white; padding:10px 20px; border-radius:10px; font-weight:700; font-size:16px; margin-bottom:15px; }

.whitelist-table { width:100%; border-collapse:collapse; margin:20px 0; box-shadow:0 2px 4px rgba(0,0,0,0.1); border-radius:8px; overflow:hidden; background:#fff; }
.whitelist-table th, .whitelist-table td { padding:14px 18px; border-bottom:1px solid #ddd; text-align:left; }
.whitelist-table th { background:#9F5EB7; color:white; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; }
.whitelist-table tr:last-child td { border-bottom:none; }
.status { font-weight:700; }
.status.PENDING { color:#E09D46; }
.status.APPROVED { color:green; }
.status.REJECTED { color:red; }

.action-buttons a {
    display:inline-block;
    margin-right:5px;
    padding:5px 10px;
    border-radius:5px;
    color:white;
    text-decoration:none;
    font-size:14px;
    font-weight:700;
    font-family:'Nunito', sans-serif;
    transition:0.3s ease;
}
.action-buttons a.approve { background-color:green; }
.action-buttons a.approve:hover { background-color:#35e05dff; transform:scale(1.05); }
.action-buttons a.reject { background-color:red; }
.action-buttons a.reject:hover { background-color:#fb9084ff; transform:scale(1.05); }

.note-box { display:flex; align-items:center; gap:10px; background:#FFE0E0; border-left:6px solid #E05B5B; padding:12px 16px; border-radius:6px; margin-bottom:20px; color:#E05B5B; font-weight:600; }
.note-box i { font-size:20px; }

.return-btn { display:inline-block; background:#9F5EB7; color:white; padding:12px 20px; border-radius:10px; font-weight:600; text-decoration:none; transition:0.3s; }
.return-btn:hover { background:#E09D46; transform:scale(1.05); }
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
  <button class="hamburger" onclick="window.location.href='my_students.php'">
    <i class="fas fa-arrow-left"></i>
  </button>
  <h1>Student Whitelist</h1>
  <div class="welcome">
    Hi, <?= htmlspecialchars($name) ?>
    <a href="staff_profile.php">
      <img src="<?= htmlspecialchars($profile_pic) ?>" alt="Profile" class="profile-pic">
    </a>
  </div>
</header>

<div class="main-content">

<?php if ($accessDenied): ?>
    <div class="note-box">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Oops! You need supervisor privileges to assign students.</span>
    </div>
    <div style="text-align:center; margin-top:20px;">
        <a href="staff_dashboard.php" class="return-btn">Return to Dashboard</a>
    </div>
<?php else: ?>

<?php if (empty($students)): ?>
    <p>No students assigned to you yet.</p>
<?php else: ?>
    <?php foreach ($students as $student): ?>

        <?php
        // Check if this student submitted BLI-01
        $stmt = $conn->prepare("SELECT submission_id FROM bli01_form WHERE student_id=? ORDER BY submitted_on DESC");
        $stmt->bind_param("s", $student['student_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $submission_ids = [];
        while ($row = $result->fetch_assoc()) {
            $submission_ids[] = $row['submission_id'];
        }
        $stmt->close();
        ?>

        <?php if (empty($submission_ids)): ?>
            <div style="text-align:center; margin-top:80px; color:#555;">
                <h2><span><?= htmlspecialchars($student['name']) ?></span> has not submitted the BLI-01 Form yet.</h2>
                <p>Once the student completes it, their list of selected companies will appear here.</p>
            </div>
        <?php else: ?>
            <span class="badge-id">Student ID: <?= htmlspecialchars($student['student_id']) ?></span>

            <table class="whitelist-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Company Name</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $placeholders = implode(',', array_fill(0, count($submission_ids), '?'));
                $types = str_repeat('i', count($submission_ids));
                $query = "SELECT sc.id, c.company_name, sc.approval_status 
                          FROM selected_company sc
                          JOIN company c ON sc.company_id = c.company_id
                          WHERE sc.submission_id IN ($placeholders)
                          ORDER BY c.company_name ASC";

                $stmt = $conn->prepare($query);

                $bind_names[] = $types;
                foreach ($submission_ids as $key => $id) {
                    $bind_name = 'bind' . $key;
                    $$bind_name = $id;
                    $bind_names[] = &$$bind_name;
                }
                call_user_func_array([$stmt, 'bind_param'], $bind_names);

                $stmt->execute();
                $result = $stmt->get_result();
                $i = 1;
                while ($row = $result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['company_name']) ?></td>
                        <td class="status <?= htmlspecialchars($row['approval_status']) ?>"><?= htmlspecialchars($row['approval_status']) ?></td>
                        <td class="action-buttons">
                        <?php if ($row['approval_status'] === 'PENDING'): ?>
                            <a href="update_whitelist.php?id=<?= $row['id'] ?>&status=APPROVED&student_id=<?= $student['student_id'] ?>" 
                            class="approve"
                            onclick="return confirm('Approve this company?')">
                            Approve
                            </a>

                            <a href="update_whitelist.php?id=<?= $row['id'] ?>&status=REJECTED&student_id=<?= $student['student_id'] ?>" 
                            class="reject"
                            onclick="return confirm('Reject this company?')">
                            Reject
                            </a>
                        <?php else: ?>
                        <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; $stmt->close(); ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php endforeach; ?>
<?php endif; ?>

<?php endif; ?>
</div>

</body>
</html>
