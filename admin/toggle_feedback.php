<?php
session_start();
require_once '../db.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: ../login.html");
    exit;
}

if (isset($_GET['feedback_id'])) {
    $feedback_id = $_GET['feedback_id'];

    // Toggle visibility
    $stmt = $conn->prepare("UPDATE feedback SET is_visible = NOT is_visible WHERE feedback_id = ?");
    $stmt->bind_param("i", $feedback_id);
    $stmt->execute();
}

// Redirect back
header("Location: manage_feedback.php");
exit;
