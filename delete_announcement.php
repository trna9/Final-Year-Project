<?php
session_start();
require_once 'db.php';

if(!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STAFF'){
    exit('Unauthorized');
}

if(isset($_GET['id'])){
    $staff_id = $_SESSION['user_id'];
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM announcements WHERE announcement_id=? AND posted_by=?");
    $stmt->bind_param("ss", $id, $staff_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: staff_profile.php");
exit;
