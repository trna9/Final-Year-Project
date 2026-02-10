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

// Fetch user profile picture
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

$company_stmt = $conn->prepare("
    SELECT 
        c.*,

        /* Average evaluation score (0–5) */
        AVG(e.score) AS avg_eval,

        /* Average feedback rating (0–5) */
        AVG(f.rating) AS avg_feedback,

        /* Final score logic */
        (
            CASE
                WHEN AVG(e.score) IS NOT NULL AND AVG(f.rating) IS NOT NULL
                    THEN AVG(e.score) * 0.6 + AVG(f.rating) * 0.4
                WHEN AVG(e.score) IS NOT NULL
                    THEN AVG(e.score)
                WHEN AVG(f.rating) IS NOT NULL
                    THEN AVG(f.rating)
                ELSE 0
            END
        ) AS final_score,

        /* Alumni who completed internship */
        COALESCE(SUM(
            CASE 
                WHEN s.internship_status = 'COMPLETED'
                     AND ie.company_id IS NOT NULL
                THEN 1 ELSE 0
            END
        ), 0) AS alumni_completed_count

    FROM company c
    LEFT JOIN company_evaluation e 
        ON c.company_id = e.company_id
    LEFT JOIN feedback f 
        ON c.company_id = f.company_id 
       AND f.is_visible = 1
    LEFT JOIN internship_experience ie 
        ON c.company_id = ie.company_id
    LEFT JOIN student s 
        ON ie.student_id = s.student_id

    GROUP BY c.company_id
    ORDER BY final_score DESC
");

$company_stmt->execute();
$company_result = $company_stmt->get_result();
$companies = $company_result->fetch_all(MYSQLI_ASSOC);
$company_stmt->close();

$total_companies = count($companies);

foreach ($companies as $i => &$company) {
    $score = $company['final_score'];

    // NOT YET RATED (no evaluation + no feedback)
    if ($company['avg_eval'] == 0 && $company['avg_feedback'] == 0) {

        $company['score_range'] = 
            '<span style="font-style:italic; font-weight:300; color:grey;">TBD</span>';

    // RATED BUT BELOW RECOMMENDATION THRESHOLD
    } elseif ($score < 2.5) {

        $company['score_range'] = 
            '<span style="font-style:italic; font-weight:300; color:grey;">–</span>';

    // CRS MAPPING BASED ON FINAL SCORE
    } elseif ($score < 3.5) {

        $company['score_range'] = "41 - 60";

    } elseif ($score < 4.5) {

        $company['score_range'] = "61 - 80";

    } else {

        $company['score_range'] = "81 - 100";
    }

}
unset($company);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Company Ranking</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { margin:0; font-family:'Nunito',sans-serif; background-color:#F5F5F8; }
header { background-color:#9F5EB7; color:white; padding:16px 24px; display:flex; align-items:center; justify-content:space-between; z-index:1; }
header h1 { margin:0; font-size:22px; font-weight:800; position:absolute; left:50%; transform:translateX(-50%); }
.welcome { display:flex; align-items:center; gap:8px; }
.hamburger { font-size:24px; cursor:pointer; background:none; border:none; color:white; }
.profile-pic { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; aspect-ratio: 1 / 1; flex-shrink: 0; display: block; border: 2px solid white; }

.sidebar { height: 100%; width: 0; position: fixed; top: 0; left: 0; background: linear-gradient(180deg, #2e2040, #1b1524); overflow-x: hidden; transition: 0.4s; padding-top: 80px; border-top-right-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 4px 0 16px rgba(0, 0, 0, 0.4); z-index: 1000; }
.sidebar a { padding: 12px 20px; margin: 8px 16px; text-decoration: none; font-size: 16px; color: #f2f2f2; display: flex; align-items: center; gap: 10px; border-radius: 30px; transition: 0.3s, transform 0.2s, color 0.3s; }
.sidebar a:hover { background: rgba(159, 94, 183, 0.2); color: #d8b4f8; transform: translateX(5px); }
.sidebar a i { font-size: 20px; width: 28px; text-align: center; background: linear-gradient(135deg, #9F5EB7, #6A3A8D); -webkit-background-clip: text; -webkit-text-fill-color: transparent; filter: drop-shadow(1px 1px 2px rgba(0,0,0,0.3)); transition: transform 0.2s ease; }
.sidebar a:hover i { transform: scale(1.2) rotate(-5deg); }
.sidebar .closebtn { position:absolute; top:10px; right:10px; font-size:24px; color:white; cursor:pointer; }

.content { max-width:1100px; margin:40px auto; padding:0 20px; }

.top-ranking { display:flex; gap:20px; justify-content:center; margin-bottom:30px; flex-wrap: wrap; }
.top-card { background:white; width:200px; text-align:center; border-radius:10px; padding:20px; box-shadow:0 4px 12px rgba(0,0,0,0.1); position:relative; }
.top-card img { width:100px; height:100px; object-fit:contain; margin-bottom:10px; }
.top-card .rank-badge { position: absolute; top: -10px; left: -10px; background: #E09D46; color: white; font-weight: 700; width: 32px; height: 32px; border-radius: 50%; font-size: 14px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2);}
.top-card .score { margin-top:8px; padding:2px 10px; border:1px; border-radius:20px; background: #28a745; color: white; font-weight:700; display:inline-block; }
.top-card .alumni-info {
    position: absolute;
    top: 8px;      /* distance from the top of the card */
    right: 8px;    /* distance from the right of the card */
    font-size: 18px; /* icon size */
    color: #6A3A8D; /* icon color */
    cursor: pointer;
}

.top-card .crs-badge {
    display: inline-block;
    margin-top: 8px;
    margin-left: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    color: #9F5EB7;
    border: 1px solid #9F5EB7;
    text-align: center;
}
.top-card .crs-badge span {
    font-style: normal; /* overrides italic if TBD */
}

.top-card .alumni-info {
    position: absolute;
    top: 10px;
    right: 10px;
    cursor: pointer;
}

.top-card .alumni-info i {
    font-size: 20px;
    color: #e03ca1ff;
    cursor: pointer;
    transition: transform 0.15s ease, filter 0.15s ease;
}

/* Hover effect */
.top-card .alumni-info i:hover {
    transform: scale(1.15);
}

.top-card .alumni-info .alumni-box {
    display: none;
    position: absolute;
    bottom: 28px;
    right: 0;
    background: #e03ca1ff;
    color: white;
    padding: 10px 16px;
    border-radius: 20px;
    font-size: 15px;
    white-space: nowrap;
    z-index: 10;
}

.top-card .alumni-info:hover .alumni-box {
    display: block;
}

/* Make the rating cell flexible */
.rating-cell {
    position: relative;
    display: flex;
    justify-content: space-between;   /* rating left, icon right */
    align-items: center;
}

/* Rating text stays normal */
.rating-value {
    font-weight: 600;
}

/* Alumni icon same style as top card */
.table-alumni-info {
    cursor: pointer;
    position: relative;
}

.table-alumni-info i {
    font-size: 20px;
    color: #e03ca1ff;
    cursor: pointer;
    transition: transform 0.15s ease, filter 0.15s ease;
}

/* Hover effect */
.table-alumni-info i:hover {
    transform: scale(1.15);
}

/* Tooltip */
.table-alumni-info .alumni-box {
    display: none;
    position: absolute;
    bottom: 28px;
    right: 0;
    background: #e03ca1ff;
    color: white;
    padding: 10px 16px;
    border-radius: 20px;
    font-size: 15px;
    white-space: nowrap;
    z-index: 10;
}

.table-alumni-info:hover .alumni-box {
    display: block;
}

/* Make TD relative for absolute icon */
.company-table td {
    position: relative;
}
.company-table { width:100%; border-collapse:collapse; background:white; border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
.company-table th, .company-table td { padding:12px 16px; text-align:left; }
.company-table th { background:#f0f0f0; }
.company-table tr:nth-child(even){ background:#f9f9f9; }
.rank-circle { background:#E09D46; color:white; border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-weight:700; margin-right:10px; }
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

<header>
<?php
  $role = strtoupper($_SESSION['role'] ?? '');
  $dashboard_url = ($role === 'STUDENT') ? 'student_dashboard.php' : 'staff_dashboard.php';
?>
  <button class="hamburger" onclick="window.location.href='<?= $dashboard_url ?>'">
      <i class="fas fa-arrow-left"></i>
  </button>
  <h1>Company Ranking</h1>
  <div class="welcome">
    Hi, <?php echo htmlspecialchars($name); ?>
    <a href="<?php echo $role==='STUDENT'?'student_profile.php':'staff_profile.php'; ?>">
      <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile" class="profile-pic">
    </a>
  </div>
</header>

<div class="content">

    <!-- Top 3 companies -->
    <div class="top-ranking">
        <?php for($i=0; $i<3 && $i<count($companies); $i++): ?>
            <div class="top-card">
                <div class="rank-badge"><?php echo $i==0?'1st':($i==1?'2nd':'3rd'); ?></div>
                <img src="<?php echo htmlspecialchars($companies[$i]['logo_url']); ?>" alt="<?php echo htmlspecialchars($companies[$i]['company_name']); ?>">
                <div class="name"><?php echo htmlspecialchars($companies[$i]['company_name']); ?></div>
                
                <!-- Score -->
                <div class="score">
                    <?php 
                        $score = $companies[$i]['final_score'];
                        if($score == 0) {
                            echo '<span style="font-style:italic; font-weight:300; color:grey;">Not yet rated</span>';
                        } else {
                            echo number_format($score, 1);
                        }
                    ?>
                </div>

                <!-- Recommended CRS badge -->
                <div class="crs-badge">
                    <?php echo $companies[$i]['score_range']; ?>
                </div>

                <!-- Alumni tooltip -->
                <?php if($companies[$i]['alumni_completed_count'] > 0): ?>
                    <div class="alumni-info" title="">
                        <i class="fas fa-info-circle"></i>
                        <span class="alumni-box">
                        <em> <strong><?php echo $companies[$i]['alumni_completed_count']; ?></strong> alumni interned here.</em>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>


    <!-- Rest of companies -->
    <table class="company-table">
        <tr>
        <th>Rank</th>
        <th>Company</th>
        <th>Final Score</th>
        <th>Recommended CRS (%)</th>
        </tr>
        <?php
        $rank = 4; // start after top 3
        for($i=3; $i<count($companies); $i++):
        ?>
        <tr>
            <td><div class="rank-circle"><?php echo $rank; ?></div></td>
            <td><?php echo htmlspecialchars($companies[$i]['company_name']); ?></td>
            <td class="rating-cell">
                <span class="rating-value">
                <?php
                $eval = $companies[$i]['avg_eval'];
                $fb = $companies[$i]['avg_feedback'];

                if ($eval == 0 && $fb == 0) {
                    echo '<span style="font-style:italic; font-weight:300; color:grey;">Not yet rated</span>';
                } else {
                    echo number_format($companies[$i]['final_score'], 1);
                }
                ?>
                </span>
            </td>

            <!-- Recommended CRS column with tooltip far right -->
            <td style="position: relative;">
                <?php echo $companies[$i]['score_range']; ?>

                <?php if($companies[$i]['alumni_completed_count'] > 0): ?>
                    <div class="table-alumni-info" style="position:absolute; top:50%; right:20px; transform:translateY(-50%);">
                        <i class="fas fa-info-circle"></i>
                        <span class="alumni-box">
                            <em><strong><?php echo $companies[$i]['alumni_completed_count']; ?></strong> alumni interned here.</em>
                        </span>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
        <?php $rank++; endfor; ?>
    </table>
</div>

<script>
function openSidebar() { document.getElementById('mySidebar').style.width='250px'; }
function closeSidebar() { document.getElementById('mySidebar').style.width='0'; }
</script>

</body>
</html>
