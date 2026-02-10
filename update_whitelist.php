<?php
session_start();
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STAFF') {
    header("Location: login.html");
    exit;
}

require_once 'db.php';

// Ensure required GET parameters exist
if (!isset($_GET['id'], $_GET['status'], $_GET['student_id'])) {
    die('Invalid request');
}

$id = intval($_GET['id']);
$status = strtoupper($_GET['status']);
$student_id = $_GET['student_id']; // keep the student context

// Validate status
if (!in_array($status, ['APPROVED','REJECTED'])) die('Invalid status');

// Update the selected company
$stmt = $conn->prepare("UPDATE selected_company SET approval_status=? WHERE id=?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();
$stmt->close();

// Redirect back to the whitelist page of the same student
header("Location: staff_whitelist.php?id=" . urlencode($student_id));
exit;
?>
