<?php
// register.php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user_id  = trim($_POST['id'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = strtoupper(trim($_POST['role'] ?? ''));

    if (empty($user_id) || empty($name) || empty($email) || empty($password) || empty($role)) {
        echo "All fields are required.";
        exit;
    }

    // validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
        exit;
    }

    // validate ID format
    if ($role === "STUDENT" && !preg_match('/^BI\d{8}$/', $user_id)) {
        echo "Invalid Matric No. Format! Example: BI22110001";
        exit;
    }
    if ($role === "STAFF" && !preg_match('/^FKI\d{4,}$/', $user_id)) {
        echo "Invalid Staff ID Format! Example: FKI0001";
        exit;
    }

    // hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // check if user_id or email already exists
    $check = $conn->prepare("SELECT 1 FROM `user` WHERE user_id = ? OR email = ? LIMIT 1");
    if (!$check) {
        echo "Server error: " . $conn->error;
        exit;
    }
    $check->bind_param("ss", $user_id, $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "This user ID is already registered! Please log in instead.";
        $check->close();
        $conn->close();
        exit;
    }
    $check->close();

    // insert new user
    $stmt = $conn->prepare("INSERT INTO `user` (user_id, name, email, password, role) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo "Server error: " . $conn->error;
        exit;
    }
    $stmt->bind_param("sssss", $user_id, $name, $email, $hashedPassword, $role);

    if ($stmt->execute()) {
    // if user is a student, add to student table
        if ($role === "STUDENT") {
            $studentStmt = $conn->prepare("INSERT INTO student (student_id) VALUES (?)");
            if ($studentStmt) {
                $studentStmt->bind_param("s", $user_id);
                $studentStmt->execute();
                $studentStmt->close();
            }
        } elseif ($role === "STAFF") {
            $staffStmt = $conn->prepare("INSERT INTO staff (staff_id) VALUES (?)");
            if ($staffStmt) {
                $staffStmt->bind_param("s", $user_id);
                $staffStmt->execute();
                $staffStmt->close();
            }

             // Automatically assign SUPERVISOR role
            $staffRoleStmt = $conn->prepare("
                INSERT INTO staff_role (staff_id, role) 
                VALUES (?, 'SUPERVISOR')
            ");
            if ($staffRoleStmt) {
                $staffRoleStmt->bind_param("s", $user_id);
                $staffRoleStmt->execute();
                $staffRoleStmt->close();
            }
        }

        echo "Registration successful";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
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
      background-color: #9F5EB7; /* purple button */
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
      background-color: #E09D46;
      font-family: 'Nunito', sans-serif;
      margin-top: 100px; /* to offset fixed header */
    }
    h2 {
      text-align: center;
      font-weight: 800;
      font-size: 26px;
      color: white;
      margin-bottom: 20px;
    }
    .card {
      background: #fff; 
      border-radius: 15px;
      width: 420px;
      padding: 24px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .tabs {
      display: flex;
      justify-content: center;
      gap: 4px;
      margin: 0 auto 20px;
      width: 130px; 
    } 
    .tab {
      flex: 1;
      height: 12px;
      border-radius: 12px;
      background-color: #DDDBE4; 
      cursor: pointer;
      transition: 0.3s;
    }
    .tab:hover { background-color: #9F5EB7; }
    .tab.active { background-color: #9F5EB7; }
    .role-text { text-align: center; font-weight: 800; font-size: 20px; margin-bottom: 16px; }
    .separator { border: none; height: 1.3px; background-color: #A8A4B6; margin: 10px 0 24px 0; width: 100%; }
    .form-group { display: flex; align-items: center; margin-bottom: 14px; }
    .form-group label { width: 90px; font-weight: 700; font-size: 15px; }
    .form-group input {
      flex: 1;
      width: 70%;
      padding: 8px;
      border: none;
      border-radius: 8px;
      background-color: #A8A4B6;
      color: black;
      font-weight: 600;
      font-family: 'Nunito', sans-serif;
      outline: none;
    }
    .form-group input::placeholder { font-weight: 400; color: #ece9f1; }
    button {
      width: 50%;
      padding: 10px;
      background: #9F5EB7;
      color: white;
      border: none;
      border-radius: 20px;
      font-weight: 700;
      cursor: pointer;
      transition: 0.3s;
      margin: 25px auto 0 auto;
      display: block;
      font-family: 'Nunito', sans-serif;
      font-size: 14px;
    }
    button:hover { background: #7d4492; }

    .login-link {
      margin-top: 14px;
      text-align: center;
      display: block;
      font-size: 14px;
      font-weight: 600;
      color: #DCD9E6;
      text-decoration: underline;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0; top: 0;
      width: 100%; height: 100%;
      background-color: rgba(0,0,0,0.6);
      align-items: center; justify-content: center;
    }
    .modal-content {
      position: relative;
      background: #fff;
      padding: 20px;
      border-radius: 16px;
      text-align: center;
      max-width: 450px;
      width: 90%;
      height: 430px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    .modal h2 { margin-top: 15px; color: #333; }
    .modal button {
      margin-top: 20px;
      padding: 10px 20px;
      border: none;
      background: #9F5EB7;
      color: #fff;
      border-radius: 8px;
      cursor: pointer;
    }
    .modal-close {
      position: absolute;
      top: 4px;
      right: 18px;
      font-size: 32px;
      font-weight: bold;
      color: #333;
      cursor: pointer;
      transition: 0.2s;
    }
    .modal-close:hover {
      color: #9F5EB7;
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

  <h2>Register</h2>

  <div class="card">
    <div class="tabs">
      <div class="tab active" onclick="setRole('student')"></div>
      <div class="tab" onclick="setRole('staff')"></div>
    </div>

    <div id="roleText" class="role-text">I am a student.</div>
    <hr class="separator">

    <form id="registerForm" class="form" method="POST">
      <div class="form-group">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" placeholder="e.g. John Doe" required>
      </div>
      <div class="form-group">
        <label id="idLabel" for="id">Matric No.</label>
        <input type="text" name="id" id="id" placeholder="e.g. BI22110001" required>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="e.g. name@email.com" required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" placeholder="Enter your password" required>
      </div>
      <input type="hidden" name="role" id="role" value="student">
      <button type="submit">Register</button>
    </form>
  </div>

  <a href="login.php" class="login-link">Already have an account? Login</a>

  <!-- Success Modal -->
  <div id="successModal" class="modal">
    <div class="modal-content">
      <span class="modal-close" onclick="closeModal()">&times;</span>
      <dotlottie-player 
        src="animation/Thumbs up birdie.json" 
        background="transparent" 
        speed="1" 
        style="width: 200px; height: 200px; margin: auto;" 
        loop 
        autoplay>
      </dotlottie-player>
      <h2>Welcome on board,<br><span id="modalName"></span></h2>
      <p id="modalText"></p>
      <button onclick="goToLogin()">Log In</button>
    </div>
  </div>
  <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>
  <script>
    function setRole(role) {
      const tabs = document.querySelectorAll('.tab');
      tabs.forEach(tab => tab.classList.remove('active'));
      const roleText = document.getElementById('roleText');
      const idLabel = document.getElementById('idLabel');
      const idInput = document.getElementById('id');
      const hiddenRole = document.getElementById('role');

      if (role === 'student') {
        tabs[0].classList.add('active');
        roleText.innerText = "I am a student.";
        idLabel.innerText = "Matric No.";
        idInput.placeholder = "e.g. BI22110001";
        hiddenRole.value = "student";
      } else {
        tabs[1].classList.add('active');
        roleText.innerText = "I am a staff.";
        idLabel.innerText = "Staff ID.";
        idInput.placeholder = "e.g. FKI0001";
        hiddenRole.value = "staff";
      } 
    }

    const form = document.getElementById('registerForm');
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      const formData = new FormData(form);

      fetch("register.php", {
        method: "POST",
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        if (data.includes("successful")) {
          const name = document.getElementById('name').value.trim() || "User";
          const role = document.getElementById('role').value;
          showModal(name, role);
        } else {
          alert(data);
        }
      })
      .catch(err => console.error("Error:", err));
    });

    function showModal(name, role) {
      document.getElementById("modalName").innerText = name + "!";
      const modalText = document.getElementById("modalText");
      if (role === 'student') {
        modalText.innerHTML = "Your account is all set up.<br>You can now log in and start exploring opportunities.";
      } else if (role === 'staff') {
        modalText.innerHTML = "Your account is all set up.<br>You can now log in and manage your tasks.";
      }
      document.getElementById("successModal").style.display = "flex";
    }

    function closeModal() {
      document.getElementById("successModal").style.display = "none";
    }

    function goToLogin() {
      window.location.href = "login.php";
    }

    window.onclick = function(event) {
      const modal = document.getElementById("successModal");
      if (event.target === modal) {
        closeModal();
      }
    }
  </script>
    <!-- FOOTER -->
    <footer>
      © 2025 FKI Industrial Training System. All Rights Reserved.
    </footer>
</body>
</html>
