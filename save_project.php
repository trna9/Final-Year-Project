<?php
session_start();
header('Content-Type: application/json');

// Must be logged in as STUDENT
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STUDENT') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];

// Get POST data
$project_id     = $_POST['project_id'] ?? '';
$project_title  = $_POST['project_title'] ?? '';
$project_link   = $_POST['project_link'] ?? '';
$description    = $_POST['description'] ?? '';

// Validation
if (empty($project_title)) {
    echo json_encode(['status' => 'error', 'message' => 'Project title is required.']);
    exit;
}

if ($project_id) {
    // ---------------------------
    // UPDATE EXISTING PROJECT
    // ---------------------------
    $stmt = $conn->prepare("
        UPDATE project 
        SET project_title=?, project_link=?, description=? 
        WHERE project_id=? AND student_id=?
    ");
    $stmt->bind_param("sssii", $project_title, $project_link, $description, $project_id, $user_id);

} else {
    // ---------------------------
    // INSERT NEW PROJECT
    // ---------------------------
    $stmt = $conn->prepare("
        INSERT INTO project (student_id, project_title, project_link, description)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("ssss", $user_id, $project_title, $project_link, $description);
}

if ($stmt->execute()) {

    // For new project, get inserted ID
    if (!$project_id) {
        $project_id = $stmt->insert_id;
    }

    // Return updated/created project data
    echo json_encode([
        'status' => 'success',
        'project' => [
            'project_id'    => $project_id,
            'project_title' => $project_title,
            'project_link'  => $project_link,
            'description'   => $description
        ]
    ]);

} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
