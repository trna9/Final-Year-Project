<?php
session_start();
header('Content-Type: application/json');
require 'db.php';

if(!isset($_SESSION['user_id']) || strtoupper($_SESSION['role'])!=='STUDENT') {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$experience_id = $_POST['experience_id'] ?? '';

if(empty($experience_id)) {
    echo json_encode(['status'=>'error','message'=>'Missing ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM internship_experience WHERE experience_id=? AND student_id=?");
$stmt->bind_param("is",$experience_id,$user_id);

if($stmt->execute()) echo json_encode(['status'=>'success']);
else echo json_encode(['status'=>'error','message'=>'Database delete failed']);

$stmt->close();
$conn->close();
