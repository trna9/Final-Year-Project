<?php
session_start();
require_once '../db.php';

// Allow only admin access
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: ../login.html");
    exit;
}

// Validate user_id parameter
if (!isset($_GET['user_id'])) {
    header("Location: manage_users.php");
    exit;
}

$user_id = $_GET['user_id'];

// Prevent admin from deleting themselves
if ($user_id === $_SESSION['user_id']) {
    echo "<script>
        alert('You cannot delete your own admin account.');
        window.location.href='manage_users.php';
    </script>";
    exit;
}

// Check if user exists
$check = $conn->prepare("SELECT role FROM user WHERE user_id = ?");
$check->bind_param("s", $user_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo "<script>
        alert('User not found.');
        window.location.href='manage_users.php';
    </script>";
    exit;
}

$row = $result->fetch_assoc();
$role = strtoupper($row['role']);

// Delete related records depending on role
if ($role === 'STUDENT') {

    // Example: delete CRS records first (if exists)
    $delCrs = $conn->prepare("DELETE FROM career_readiness_score WHERE student_id = ?");
    $delCrs->bind_param("s", $user_id);
    $delCrs->execute();

    // Then delete student
    $delStudent = $conn->prepare("DELETE FROM student WHERE student_id = ?");
    $delStudent->bind_param("s", $user_id);
    $delStudent->execute();
}

if ($role === 'STAFF') {

    // ✅ DELETE CHILD TABLE FIRST
    $delStaffRole = $conn->prepare("DELETE FROM staff_role WHERE staff_id = ?");
    $delStaffRole->bind_param("s", $user_id);
    $delStaffRole->execute();

    // Then delete staff
    $delStaff = $conn->prepare("DELETE FROM staff WHERE staff_id = ?");
    $delStaff->bind_param("s", $user_id);
    $delStaff->execute();
}

// Finally delete the user record
$deleteUser = $conn->prepare("DELETE FROM user WHERE user_id = ?");
$deleteUser->bind_param("s", $user_id);

if ($deleteUser->execute()) {
    echo "<script>
        alert('User and related records deleted successfully.');
        window.location.href='manage_users.php';
    </script>";
} else {
    echo "<script>
        alert('Error deleting user.');
        window.location.href='manage_users.php';
    </script>";
}
?>
