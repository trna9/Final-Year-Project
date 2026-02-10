<?php
session_start();
require_once '../db.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: ../login.html");
    exit;
}

$announcement_id = $_GET['announcement_id'] ?? null;
if (!$announcement_id) {
    echo "No announcement selected.";
    exit;
}

// Delete announcement
$stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id = ?");
$stmt->bind_param("i", $announcement_id);
if ($stmt->execute()) {
    $stmt->close();
    header("Location: manage_announcements.php"); // redirect back
    exit;
} else {
    echo "Failed to delete announcement.";
    $stmt->close();
}
?>
