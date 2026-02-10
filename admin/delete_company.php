<?php
session_start();
require_once '../db.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: ../login.html");
    exit;
}

// Check if company ID is provided
if (isset($_GET['id'])) {
    $company_id = intval($_GET['id']);

    // Delete company
    $stmt = $conn->prepare("DELETE FROM company WHERE company_id = ?");
    $stmt->bind_param("i", $company_id);

    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Company deleted successfully.";
    } else {
        $_SESSION['error_msg'] = "Error deleting company: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();

// Redirect back to manage_company page
header("Location: manage_company.php");
exit;
?>
