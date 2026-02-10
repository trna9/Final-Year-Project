<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location:../login.html");
    exit;
}

if(isset($_GET['id'])){
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE company SET status='approved' WHERE company_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}

header("Location: manage_company.php");
exit;
?>
