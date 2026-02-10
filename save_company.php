<?php
session_start();
require_once 'db.php';

// --- Only allow logged in staff or student ---
if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['role']), ['STAFF','STUDENT'])) {
    header("Location: login.html");
    exit;
}

$user_id = (string)$_SESSION['user_id'];
$role    = strtoupper($_SESSION['role']);

// --- Only accept POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

// --- Sanitize form inputs ---
$company_name  = $_POST['company_name'] ?? '';
$nature        = $_POST['nature'] ?? '';
$address       = $_POST['address'] ?? '';
$email         = $_POST['email'] ?? '';
$phone         = $_POST['phone'] ?? '';
$website_link  = $_POST['website_link'] ?? '';
$description   = $_POST['description'] ?? '';
$focus_area    = $_POST['focus_area'] ?? '';
$city          = $_POST['city'] ?? '';
$state         = $_POST['state'] ?? '';

// --- Handle logo upload ---
$logo_url = null;

if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/company_logos/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $file_tmp  = $_FILES['logo_file']['tmp_name'];
    $file_name = basename($_FILES['logo_file']['name']);
    $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['jpg','jpeg','png','gif'];

    if (!in_array($ext, $allowed_ext)) {
        die("Invalid logo file type. Only jpg, jpeg, png, gif allowed.");
    }

    $new_file_name = uniqid('logo_') . '.' . $ext;
    $save_path = $upload_dir . $new_file_name;

    if (move_uploaded_file($file_tmp, $save_path)) {
        $logo_url = $save_path;
    } else {
        die("Logo upload failed. Check folder permissions.");
    }
}

// --- Determine status ---
$status = ($role === 'STAFF') ? 'approved' : 'pending';

// --- Insert into DB ---
$stmt = $conn->prepare("
    INSERT INTO company 
    (company_name, nature, address, email, phone, website_link, logo_url, description, focus_area, city, state, created_by, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) die("Prepare failed: " . $conn->error);

$stmt->bind_param(
    "sssssssssssss",
    $company_name,
    $nature,
    $address,
    $email,
    $phone,
    $website_link,
    $logo_url,
    $description,
    $focus_area,
    $city,
    $state,
    $user_id,
    $status
);

if ($stmt->execute()) {
    $_SESSION['success_msg'] = ($role === 'STAFF')
        ? "Company added successfully!"
        : "Company submitted successfully and pending admin approval.";
} else {
    die("Insert failed: " . $stmt->error);
}

$stmt->close();
$conn->close();

header("Location: companies.php");
exit;
?>
