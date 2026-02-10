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
$company_id = $_POST['company_id'] ?? '';
$position = $_POST['position'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';
$reflection = $_POST['reflection'] ?? '';
$cert_url = $_POST['internship_cert_url'] ?? '';

if(empty($company_id) || empty($position) || empty($start_date) || empty($end_date)) {
    echo json_encode(['status'=>'error','message'=>'Required fields missing']);
    exit;
}

if($experience_id) {
    $stmt = $conn->prepare("
        UPDATE internship_experience
        SET company_id=?, position=?, start_date=?, end_date=?, reflection=?, internship_cert_url=?
        WHERE experience_id=? AND student_id=?
    ");
    $stmt->bind_param("isssssis",$company_id,$position,$start_date,$end_date,$reflection,$cert_url,$experience_id,$user_id);
} else {
    $stmt = $conn->prepare("
        INSERT INTO internship_experience
        (student_id, company_id, position, start_date, end_date, reflection, internship_cert_url)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sisssss",$user_id,$company_id,$position,$start_date,$end_date,$reflection,$cert_url);
}

if($stmt->execute()){
    if(!$experience_id) $experience_id = $stmt->insert_id;
    $c = $conn->query("SELECT company_name FROM company WHERE company_id=".$company_id)->fetch_assoc();
    echo json_encode([
        'status'=>'success',
        'internship'=>[
            'experience_id'=>$experience_id,
            'company_name'=>$c['company_name'],
            'position'=>$position,
            'start_date'=>$start_date,
            'end_date'=>$end_date,
            'reflection'=>$reflection,
            'internship_cert_url'=>$cert_url
        ]
    ]);
} else {
    echo json_encode(['status'=>'error','message'=>$stmt->error]);
}
$stmt->close();
$conn->close();
