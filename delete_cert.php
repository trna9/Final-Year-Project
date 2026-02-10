<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STUDENT') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cert_id = $_POST['cert_id'] ?? '';

if(empty($cert_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing cert ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM certification WHERE cert_id=? AND student_id=?");
$stmt->bind_param("is", $cert_id, $user_id);

if($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database delete failed']);
}

$stmt->close();
$conn->close();
?>
