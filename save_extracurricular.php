<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STUDENT') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$extrac_id = $_POST['id'] ?? '';
$activity = $_POST['activity'] ?? '';
$description = $_POST['description'] ?? '';

if (empty($activity)) {
    echo json_encode(['status' => 'error', 'message' => 'Activity name is required']);
    exit;
}

if ($extrac_id) {
    // UPDATE
    $stmt = $conn->prepare("
        UPDATE extracurricular 
        SET activity=?, description=? 
        WHERE id=? AND student_id=?
    ");
    $stmt->bind_param("ssii", $activity, $description, $extrac_id, $user_id);
    $final_id = $extrac_id;
} else {
    // INSERT
    $stmt = $conn->prepare("
        INSERT INTO extracurricular (student_id, activity, description)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("sss", $user_id, $activity, $description);
    $final_id = null; // will get from insert_id after execute
}

if ($stmt->execute()) {
    if (!$extrac_id) {
        $final_id = $stmt->insert_id;
    }
    echo json_encode([
        'status' => 'success',
        'extrac' => [
            'id' => $final_id,
            'activity' => $activity,
            'description' => $description
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
