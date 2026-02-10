<?php
// login.php
session_start();
include "db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id  = trim($_POST['id'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = strtoupper(trim($_POST['role'] ?? ''));

    if (empty($user_id) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, password, role, name FROM `user` WHERE user_id = ? AND role = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $user_id, $role);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                echo "<script>alert('No account found with this ID for $role.');</script>";
            } else {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    if ($user['role'] === 'STAFF') {
                        $_SESSION['role'] = 'STAFF';
                        $_SESSION['staff_system_role'] = 'SUPERVISOR'; 
                    } else {
                        $_SESSION['role'] = $user['role'];
                    }
                    $_SESSION['name']    = $user['name'];

                    if ($user['role'] === 'STUDENT') {
                        header("Location: student_dashboard.php");
                    } elseif ($user['role'] === 'STAFF') {
                        header("Location: staff_dashboard.php");
                    } elseif ($user['role'] === 'ADMIN') {
                        header("Location: admin/admin_dashboard.php");
                    }
                    exit;
                } else {
                    echo "<script>alert('Incorrect password. Please try again.');</script>";
                }
            }
            $stmt->close();
        } else {
            $error = "Server error: " . $conn->error;
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | FYP System</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>

    /* HEADER */
    header {
      width: 100%;
      background-color: white;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      position: fixed;
      top: 0;
      left: 0;
      display: flex;
      justify-content: center;
      z-index: 10;
      height: 70px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1); /* THIS is the shadow */
    }

    header .header-container {
      width: 100%;
      max-width: 1200px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 40px;
    }

    header .logo {
      display: flex;
      align-items: center;
      gap: 10px; /* space between logos and title */
    }

    header .logo img {
        height: 40px; /* same height for both logos */
        width: auto;
    }

    .header-container .title {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
    }

    header nav {
      display: flex;
      gap: 20px;
    }

    header nav a {
      text-decoration: none;
      color: #333;;
      font-weight: 600;
      padding: 8px 16px;
      border-radius: 20px;
      transition: 0.3s;
    }
    
    header nav a.home {
      background-color: #E09D46; /* purple button */
      color: white;               /* white text */
      padding: 8px 18px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 14px;
      text-decoration: none;
      transition: 0.3s;
    }

    header nav a.home:hover {
      background-color: #f3edf6;
      color: #9F5EB7;
    }

    body {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      height: 95vh;
      margin: 0;
      background-color: #9F5EB7;
      font-family: 'Nunito', sans-serif;
      margin-top: 100px; /* to offset fixed header */
    }
    h1 {
      text-align: center;
      font-weight: 800;
      font-size: 26px;
      color: white;
      margin-bottom: 20px;
    }
    .card {
      background: #fff;
      border-radius: 15px;
      width: 260px;
      height: 260px;
      padding: 24px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .tabs {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 4px;
      margin: -10px auto 20px;
      width: 190px; 
    }
    .tab {
      flex: 1;
      height: 12px;
      padding: 0;
      border-radius: 12px;
      background-color: #E2D8CC;
      cursor: pointer;
      transition: 0.3s;
      border: none;
      line-height: 0;
    }
    .tab.active {
      background-color: #E09D46;
    }
    .role-text {
      text-align: center;
      font-weight: 800;
      font-size: 20px;
      margin-bottom: 16px;
    }
    .separator {
      border: none;
      height: 1.5px;
      background-color: #A8A4B6;
      margin: 10px 0 24px 0;
      width: 100%;
    }
    .form-group {
      display: flex;
      align-items: center;
      margin-bottom: 14px;
    }
    .form-group input {
      width: 100%;                
      padding: 10px 12px;
      border: none;
      border-radius: 8px;
      background-color: #A8A4B6;
      color: black;
      font-weight: 600;
      outline: none;
      font-family: 'Nunito', sans-serif;
      font-size: 14px;
    }
    .form-group input::placeholder {
      color: #e0e0e0;
      font-weight: 400;
    }
    button {
      width: 50%;
      padding: 10px;
      background: #E09D46;
      color: white;
      border: none;
      border-radius: 20px;
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      transition: 0.3s;
      margin: 20px auto 0 auto;
      display: block;
      font-family: 'Nunito', sans-serif;
    }
    button:hover {
      background: #c47a34;
    }
    .register-link {
      margin-top: 14px;
      text-align: center;
      display: block;
      font-size: 14px;
      font-weight: 600;
      color: #DCD9E6;
      text-decoration: underline;
    }
    .error {
      color: red;
      text-align: center;
      margin-bottom: 10px;
      font-weight: 600;
    }

    footer {
      width: 100%;                /* make it full width */
      position: relative;          /* ensures shadow works properly */
      padding: 20px 0;
      text-align: center;
      color: #777;
      font-size: 14px;
      margin-top: 100px;
      margin-bottom: -40px; /* to offset body margin */
      background-color: white;
      box-shadow: 0 -2px 8px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>
  <header>
  <div class="header-container">
    <div class="logo">
      <img src="img/ums-logo.png" alt="UMS Logo">
      <img src="img/fki-logo.png" alt="FKI Logo">
      <!-- <span class="title">FKI Industrial Training System</span> -->
    </div>
    <nav>
      <a href="index.html" class="home">Home</a>
    </nav>
  </div>
</header>

  <h1>Login</h1>

  <div class="card">
    <!-- Tabs -->
    <div class="tabs">
      <button type="button" class="tab active" onclick="switchTab('student')"></button>
      <button type="button" class="tab" onclick="switchTab('staff')"></button>
      <button type="button" class="tab" onclick="switchTab('admin')"></button>
    </div>

    <!-- Role text -->
    <div id="role-title" class="role-text">I am a student.</div>

    <!-- Separator -->
    <hr class="separator">

    <!-- Form -->
    <form action="login.php" method="POST" id="login-form">
      <input type="hidden" name="role" value="student" id="role-input">

      <div class="form-group">
        <input type="text" id="id-input" name="id" placeholder="Matric No." required>
      </div>

      <div class="form-group">
        <input type="password" id="password" name="password" placeholder="Password" required>
      </div>

      <button type="submit">Login</button> 
    </form>
  </div>

  <a href="register.php" class="register-link">Don't have an account? Register now</a>


    <!-- FOOTER -->
  <footer>
    © 2025 FKI Industrial Training System. All Rights Reserved.
  </footer>
<script>
  function switchTab(role) {
    const title = document.getElementById("role-title");
    const idInput = document.getElementById("id-input");
    const roleInput = document.getElementById("role-input");

    document.querySelectorAll(".tab").forEach(btn => btn.classList.remove("active"));

    if (role === "student") {
      document.querySelectorAll(".tab")[0].classList.add("active");
      title.textContent = "I am a student.";
      idInput.placeholder = "Matric No.";
      roleInput.value = "student";
    } else if (role === "staff") {
      document.querySelectorAll(".tab")[1].classList.add("active");
      title.textContent = "I am a staff.";
      idInput.placeholder = "Staff ID.";
      roleInput.value = "staff";
    } else {
      document.querySelectorAll(".tab")[2].classList.add("active");
      title.textContent = "I am an admin.";
      idInput.placeholder = "Admin ID.";
      roleInput.value = "admin";
    }
  }

  const studentRegex = /^BI\d{8}$/i;   // BI + 8 digits
  const staffRegex   = /^FKI\d{3,}$/i; // FKI + 3 or more digits

  const form = document.getElementById("login-form");
  form.addEventListener("submit", function (e) {
    const role = document.getElementById("role-input").value;
    const userId = document.getElementById("id-input").value.trim();

    if (role === "student" && !studentRegex.test(userId)) {
      e.preventDefault();
      alert("Invalid Matric No. Format.\nExample: BI22110001");
      return;
    }
    if (role === "staff" && !staffRegex.test(userId)) {
      e.preventDefault();
      alert("Invalid Staff ID Format.\nExample: FKI0001");
      return;
    }
  });
</script>
</body>
</html>
