<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

require_once 'db.php';

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'User';
$role = strtoupper($_SESSION['role']); // STUDENT, STAFF, ADMIN

// Fetch profile picture based on role
if ($role === 'STUDENT') {
    $query = $conn->prepare("SELECT profile_picture FROM student WHERE student_id = ?");
} else {
    $query = $conn->prepare("SELECT profile_picture FROM staff WHERE staff_id = ?");
}
$query->bind_param("s", $user_id);
$query->execute();
$result = $query->get_result();
$user_data = $result->fetch_assoc();
$profile_pic = $user_data['profile_picture'] ?? 'images/default_profile.png';
$query->close();

// --- Get filters/search ---
$company_search = $_GET['company'] ?? '';
$city_search = $_GET['city'] ?? '';
$nature_filter = $_GET['nature'] ?? [];
$focus_filter = $_GET['focus'] ?? [];

// Build SQL query dynamically
$sql = "SELECT * FROM company WHERE status = 'approved'"; // only approved companies
$params = [];
$types = '';

if ($company_search) {
    $sql .= " AND company_name LIKE ?";
    $params[] = "%$company_search%";
    $types .= 's';
}

if ($city_search) {
    $sql .= " AND city LIKE ?";
    $params[] = "%$city_search%";
    $types .= 's';
}

if (!empty($nature_filter)) {
    $placeholders = implode(',', array_fill(0, count($nature_filter), '?'));
    $sql .= " AND nature IN ($placeholders)";
    foreach ($nature_filter as $n) {
        $params[] = $n;
        $types .= 's';
    }
}

if (!empty($focus_filter)) {
    $placeholders = implode(',', array_fill(0, count($focus_filter), '?'));
    $sql .= " AND focus_area IN ($placeholders)";
    foreach ($focus_filter as $f) {
        $params[] = $f;
        $types .= 's';
    }
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$companies = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Internship Companies | FYP System</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { margin:0; font-family:'Nunito',sans-serif; background-color:#F5F5F8; }
header { background-color:#9F5EB7; color:white; padding:16px 24px; display:flex; align-items:center; justify-content:space-between; z-index:1; }
header h1 { margin:0; font-size:22px; font-weight:800; position:absolute; left:50%; transform:translateX(-50%); }
.welcome { display:flex; align-items:center; gap:8px; }
.hamburger { font-size:24px; cursor:pointer; background:none; border:none; color:white; }
.sidebar { height:100%; width:0; position:fixed; top:0; left:0; background:linear-gradient(180deg,#2e2040,#1b1524); overflow-x:hidden; transition:0.4s; padding-top:80px; border-top-right-radius:20px; border-bottom-right-radius:20px; box-shadow:4px 0 16px rgba(0,0,0,0.4); z-index:1000; }
.sidebar a { padding:12px 20px; margin:8px 16px; text-decoration:none; font-size:16px; color:#f2f2f2; display:flex; align-items:center; gap:10px; border-radius:30px; transition:0.3s, transform 0.2s, color 0.3s; }
.sidebar a:hover { background: rgba(159, 94, 183, 0.2); color:#d8b4f8; transform:translateX(5px); }
.sidebar a i { font-size:20px; width:28px; text-align:center; background:linear-gradient(135deg,#9F5EB7,#6A3A8D); -webkit-background-clip:text; -webkit-text-fill-color:transparent; filter:drop-shadow(1px 1px 2px rgba(0,0,0,0.3)); transition: transform 0.2s ease; }
.sidebar a:hover i { transform: scale(1.2) rotate(-5deg); }
.sidebar .closebtn { position:absolute; top:10px; right:10px; font-size:24px; color:white; cursor:pointer; }
    .profile-pic {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      aspect-ratio: 1 / 1;
      flex-shrink: 0; 
      display: block;
      border: 2px solid white;
    }

.search-bar { display:flex; justify-content:center; align-items:center; gap:12px; margin:30px auto 40px; width:100%; max-width:1200px; padding:0 20px; flex-wrap:wrap; }
.search-input { flex:2; position:relative; display:flex; max-width:500px; }
.search-input.short { flex:1; position:relative; display:flex; max-width:280px; }
.search-input input { flex:1; padding:8px 12px 8px 36px; border-radius:10px; border:1.5px solid #E09D46; font-size:15px; font-family:'Nunito',sans-serif; }
.search-input input:focus { outline:none; border-color:#9F5EB7; box-shadow:0 0 0 2px rgba(160,107,222,0.4); }
.search-input i { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:black; }
.search-btn { padding:8px 20px; border-radius:20px; border:none; background:#E09D46; color:white; font-size:15px; font-weight:700; font-family:'Nunito',sans-serif; cursor:pointer; height:36px; transition:0.3s; }
.search-btn:hover { background:#9F5EB7; transform:scale(1.05); }

.content { display:flex; gap:80px; padding:20px 0; margin:0; align-items:flex-start; }
.filter-sidebar { width:280px; background:#E8E0F0; border-radius:0 10px 10px 0; box-shadow:2px 0 8px rgba(0,0,0,0.1); padding-bottom:20px; flex-shrink:0; margin-top:30px;  }
.filter-header { display:flex; align-items:center; gap:8px; font-weight:700; cursor:pointer; background:#E09D46; color:white; padding:12px 16px; border-radius:0 10px 0 0; position:relative; }
.filter-header::after { content:""; display:block; height:2px; background:#d2d2d2; width:100%; position:absolute; bottom:0; left:0; }
.filter-header i { font-size:18px; }
.filter-content { margin-top:15px; display:flex; flex-direction:column; gap:10px; padding:0 16px 10px; }
.filter-content h4 { margin:10px 0 5px; font-size:15px; color:#333; }
.filter-content label { display:block; font-size:14px; cursor:pointer; }

.main-section { width:850px; max-width:100%; }
.results-header { display:flex; align-items:center; gap:10px; margin-bottom:20px; }
.results-header h2 { font-size:22px; font-weight:800; color:#000; margin:0; }
.results-count { background:#9F5EB7; color:white; font-weight:700; border-radius:6px; padding:4px 12px; font-size:14px; }
.company-card { background:white; border-radius:10px; padding:16px; margin-bottom:16px; box-shadow:0 2px 8px rgba(0,0,0,0.1); display:flex; justify-content:space-between; align-items:flex-start; }
.company-info { display:flex; align-items:flex-start; gap:16px; flex:1; }
.company-logo { width:100px; height:100px; object-fit:contain; border-radius:8px; }
.company-details h3 { margin:0; font-size:18px; font-weight:700; }
.company-details p { margin-top:6px; font-size:14px; color:#444; line-height:1.5; }
.company-meta { display:flex; flex-direction:column; align-items:flex-end; gap:10px; }
.company-meta p { margin:0; font-size:14px; color:#222; display:flex; align-items:center; gap:6px; }
.details-btn { background:#9F5EB7; border:none; color:white; border-radius:8px; padding:6px 14px; font-size:14px; font-weight:600; font-family:'Nunito',sans-serif; cursor:pointer; transition:0.3s; }
.details-btn:hover { background:#853a9b; transform:scale(1.05); }
.add-btn {
  display: inline-block;
  background: #E09D46;
  color: white;
  padding: 10px 20px;
  border-radius: 10px;
  font-weight: 700;
  text-decoration: none;
  transition:0.3s; 
}
.add-btn:hover {
  background: #d89133ff;
  transform:scale(1.05); 
}
</style>
</head>
<body>

<!-- Sidebar -->
<div id="mySidebar" class="sidebar">
  <a href="javascript:void(0)" class="closebtn" onclick="document.getElementById('mySidebar').style.width='0'">&times;</a>
  <?php if($role === 'STUDENT'): ?>
    <a href="student_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
    <a href="companies.php"><i class="fas fa-magnifying-glass"></i> Find Companies</a>
    <a href="company_ranking.php"><i class="fas fa-ranking-star"></i> Company Ranking</a>
    <a href="career_readiness.php"><i class="fas fa-seedling"></i> Career Readiness</a>
    <a href="whitelist.php"><i class="fas fa-list"></i> Whitelist</a> 
  <?php else: ?>
    <a href="staff_dashboard.php"><i class="fas fa-house"></i> Dashboard</a>
    <a href="assign_students.php"><i class="fas fa-user-plus"></i> Assign Students</a>
    <a href="my_students.php"><i class="fas fa-users"></i> My Students</a>
    <a href="companies.php"><i class="fas fa-building"></i> Companies</a>
    <a href="company_ranking.php"><i class="fas fa-ranking-star"></i> Company Ranking</a>
  <?php endif; ?>
  <a href="login.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
</div>

<!-- Header -->
<header>
  <?php
  $role = strtoupper($_SESSION['role'] ?? ''); // get role from session
  if ($role === 'STUDENT') {
      $dashboard_url = 'student_dashboard.php';
  } else {
      $dashboard_url = 'staff_dashboard.php';
  }
  ?>

  <button class="hamburger" onclick="window.location.href='<?= $dashboard_url ?>'">
      <i class="fas fa-arrow-left"></i>
  </button>
  <h1>Internship Companies</h1>
  <div class="welcome">
    Hi, <?php echo htmlspecialchars($name); ?>
    <a href="<?php echo $role==='STUDENT'?'student_profile.php':'staff_profile.php'; ?>">
      <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-pic">
    </a>
  </div>
</header>


<!-- Main Content -->
<div class="content">
  <!-- Filter Sidebar -->
  <div class="filter-sidebar">
    <form method="get" id="filterForm">
      <div class="filter-header"><i class="fas fa-filter"></i> Filters</div>
      <div class="filter-content">
        <h4>Nature of Company</h4>
        <?php $natures = ['Government','Private','Multinational','Others']; ?>
        <?php foreach($natures as $n): ?>
        <label>
          <input type="checkbox" name="nature[]" value="<?php echo $n; ?>" <?php echo in_array($n,$nature_filter)?'checked':''; ?> onchange="this.form.submit()">
          <?php echo $n; ?>
        </label>
        <?php endforeach; ?>

        <h4>Area of Focus</h4>
        <?php 
        $focus_options = [
            'SOFTWARE DEVELOPMENT','NETWORK & INFRASTRUCTURE','DATA SCIENCE','UI/UX','CYBERSECURITY',
            'BUSINESS IT','ARTIFICIAL INTELLIGENCE','WEB DEVELOPMENT','MOBILE APP DEVELOPMENT',
            'CLOUD COMPUTING','IOT','DIGITAL MARKETING','GAME DEVELOPMENT','OTHERS'
        ]; 
        ?>
        <?php foreach($focus_options as $f): ?>
        <label>
          <input type="checkbox" name="focus[]" value="<?php echo $f; ?>" <?php echo in_array($f, $focus_filter) ? 'checked' : ''; ?> onchange="this.form.submit()">
          <?php echo $f; ?>
        </label>
        <?php endforeach; ?>
      </div>
    </form>
  </div>

  <!-- Company list -->
  <div class="main-section">
  <form method="get" class="search-bar">
    <div class="search-input">
      <i class="fas fa-magnifying-glass"></i>
      <input type="text" name="company" placeholder="Search company" value="<?php echo htmlspecialchars($company_search); ?>">
    </div>
    <div class="search-input short">
      <i class="fas fa-location-dot"></i>
      <input type="text" name="city" placeholder="Location" value="<?php echo htmlspecialchars($city_search); ?>">
    </div>
    <button type="submit" class="search-btn">Search</button>
  </form>

  <div class="results-header" style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
    <div style="display:flex; align-items:center; gap:10px;">
      <h2>Total Companies</h2>
      <div class="results-count"><?php echo count($companies); ?> Results</div>
    </div>
      <a href="add_company.php" class="add-btn"><i class="fas fa-plus"></i> Add New Company</a>
  </div>

  <?php if(count($companies) > 0): ?>
    <?php foreach($companies as $company): ?>
    <div class="company-card">
      <div class="company-info">
        <img src="<?php echo htmlspecialchars($company['logo_url']); ?>" alt="<?php echo htmlspecialchars($company['company_name']); ?>" class="company-logo">
        <div class="company-details">
          <h3><?php echo htmlspecialchars($company['company_name']); ?></h3>
          <p><?php echo htmlspecialchars($company['description']); ?></p>
        </div>
      </div>
      <div class="company-meta">
        <p><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($company['address']); ?></p>
        
        <!-- Details button for everyone -->
        <form method="get" action="company_details.php">
          <input type="hidden" name="id" value="<?php echo $company['company_id']; ?>">
          <button type="submit" class="details-btn">Details</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No companies found.</p>
  <?php endif; ?>
</div>

<script>
// Close sidebar when clicking outside
document.addEventListener('click', function(e){
  const sidebar = document.getElementById('mySidebar');
  if(!sidebar.contains(e.target) && !e.target.closest('.hamburger')){
    sidebar.style.width = '0';
  }
});
</script>
</body>
</html>
