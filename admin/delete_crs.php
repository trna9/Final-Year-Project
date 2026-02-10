<?php
session_start();
require_once '../db.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: ../login.html");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_crs.php");
    exit;
}

$crs_id = intval($_GET['id']); // sanitize input

// Delete the CRS record
$stmt = $conn->prepare("DELETE FROM career_readiness_score WHERE crs_id = ?");
$stmt->bind_param("i", $crs_id);
$stmt->execute();
$stmt->close();

header("Location: manage_crs.php?msg=deleted");
exit;
?>
