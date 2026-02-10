<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STUDENT') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

$id = $_POST['id'] ?? '';
$role_title = $_POST['role_title'] ?? '';
$organization = $_POST['organization'] ?? '';
$year = $_POST['year'] ?? null;

if (empty($role_title) || empty($organization)) {
    echo json_encode(['status' => 'error', 'message' => 'Role title and organization are required']);
    exit;
}

if ($id) {
    // UPDATE
    $stmt = $conn->prepare("
        UPDATE leadership_role 
        SET role_title=?, organization=?, year=? 
        WHERE id=? AND student_id=?
    ");
    $stmt->bind_param("ssiii", $role_title, $organization, $year, $id, $user_id);
    $final_id = $id;
} else {
    // INSERT
    $stmt = $conn->prepare("
        INSERT INTO leadership_role (student_id, role_title, organization, year)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("sssi", $user_id, $role_title, $organization, $year);
    $final_id = null;
}

if ($stmt->execute()) {
    if (!$id) $final_id = $stmt->insert_id;

    echo json_encode([
        'status' => 'success',
        'leadership' => [
            'id' => $final_id,
            'role_title' => $role_title,
            'organization' => $organization,
            'year' => $year
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
