<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STUDENT') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

$student_id = $_SESSION['user_id'];
$project_id = $data['project_id'] ?? '';

if (!$project_id) {
    echo json_encode(['success' => false, 'message' => 'Missing project_id']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM project WHERE project_id=? AND student_id=?");
$stmt->bind_param("ii", $project_id, $student_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
