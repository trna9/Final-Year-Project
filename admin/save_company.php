<?php
session_start();
require_once '../db.php';

// Only allow admin
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: ../login.html");
    exit;
}

$admin_id = $_SESSION['user_id']; // current admin user

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['company_name'] ?? '';
    $nature = $_POST['nature'] ?? '';
    $address = $_POST['address'] ?? '';
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'] ?? null;
    $website_link = $_POST['website_link'] ?? null;
    $description = $_POST['description'] ?? '';
    $focus_area = $_POST['focus_area'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';

    // Handle logo upload
    $logo_url = null;
    if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/company_logos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_tmp = $_FILES['logo_file']['tmp_name'];
        $file_name = basename($_FILES['logo_file']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed)) {
            $new_file_name = uniqid('logo_') . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;
            if (move_uploaded_file($file_tmp, $destination)) {
                $logo_url = 'uploads/company_logos/' . $new_file_name;
            }
        }
    }

    // Insert into database with created_by and status = 'approved'
    $stmt = $conn->prepare("
        INSERT INTO company 
        (company_name, nature, address, email, phone, website_link, logo_url, description, focus_area, city, state, created_by, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')
    ");
    $stmt->bind_param(
        "ssssssssssss",
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
        $admin_id // created_by
    );

    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Company added successfully!";
    } else {
        $_SESSION['error_msg'] = "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: manage_company.php");
    exit;
} else {
    echo "Invalid request method.";
}
?>
