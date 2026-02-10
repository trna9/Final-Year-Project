<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STUDENT') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once 'db.php'; // your DB connection

$user_id = $_SESSION['user_id'];

// Get POST data
$cert_id = $_POST['cert_id'] ?? '';
$cert_name = $_POST['cert_name'] ?? '';
$issuer = $_POST['issuer'] ?? '';
$cert_url = $_POST['cert_url'] ?? '';
$date_obtained = $_POST['date_obtained'] ?? null;

if (empty($cert_name)) {
    echo json_encode(['status' => 'error', 'message' => 'Certification name is required.']);
    exit;
}

if($cert_id) {
    // Update existing
    $stmt = $conn->prepare("UPDATE certification SET cert_name=?, issuer=?, cert_url=?, date_obtained=? WHERE cert_id=? AND student_id=?");
    $stmt->bind_param("ssssis", $cert_name, $issuer, $cert_url, $date_obtained, $cert_id, $user_id);
} else {
    // Insert new
    $stmt = $conn->prepare("INSERT INTO certification (student_id, cert_name, issuer, cert_url, date_obtained) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $user_id, $cert_name, $issuer, $cert_url, $date_obtained);
}

if ($stmt->execute()) {
    $cert_id = $stmt->insert_id;
    echo json_encode([
        'status' => 'success',
        'cert' => [
            'cert_id' => $cert_id,
            'cert_name' => $cert_name,
            'issuer' => $issuer,
            'cert_url' => $cert_url,
            'date_obtained' => $date_obtained
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
