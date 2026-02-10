<?php
session_start();
require 'db.php';
$data = json_decode(file_get_contents('php://input'), true);
$student_id = $_SESSION['user_id'];
$cert_id = $data['cert_id'];

$stmt = $conn->prepare("DELETE FROM certification WHERE cert_id=? AND student_id=?");
$stmt->bind_param("is", $cert_id, $student_id);
$stmt->execute();

echo json_encode(['success'=>true]);
