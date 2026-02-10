<?php
session_start();
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'ADMIN') {
    header("Location: ../login.html");
    exit;
}

require_once '../db.php';
require_once('../tcpdf/tcpdf.php'); // path to TCPDF

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_crs.php");
    exit;
}

$crs_id = intval($_GET['id']); // sanitize input

// Fetch CRS record along with student info
$stmt = $conn->prepare("
    SELECT crs.student_id, crs.score, crs.generated_on, u.name
    FROM career_readiness_score crs
    JOIN user u ON crs.student_id = u.user_id
    WHERE crs.crs_id = ?
");
$stmt->bind_param("i", $crs_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('CRS record not found.'); window.location='manage_crs.php';</script>";
    exit;
}

$crs = $result->fetch_assoc();
$student_id = $crs['student_id'];
$name = $crs['name'];
$score = $crs['score'];
$generated_on = date('d M Y', strtotime($crs['generated_on']));

// Determine level & recommendations
if ($score >= 81) {
    $level = 'Thriving';
    $summary = "You are thriving in your career readiness! Keep building your professional brand and network.";
    $recommendations = [
        "Develop your professional brand and use LinkedIn to research your desired career path.",
        "Pursue projects in your chosen field and share your work.",
        "Maintain a strong network of mentors.",
        "Create a 3-5 year career plan with actionable steps."
    ];
} elseif ($score >= 61) {
    $level = 'Achieving';
    $summary = "You are making good progress! Focus on specializing and growing your network.";
    $recommendations = [
        "Attend events to expand your professional network.",
        "Narrow down your interests and identify specialization.",
        "Develop a 1-2 year career plan with mentor guidance.",
        "Complete challenging projects to strengthen skills."
    ];
} elseif ($score >= 41) {
    $level = 'Aspiring';
    $summary = "You are starting to build momentum. Explore opportunities and seek guidance.";
    $recommendations = [
        "Join organizations and events outside your comfort zone.",
        "Meet with a career advisor to plan your next year.",
        "Seek mentors and conduct informational interviews.",
        "Attend workshops to deepen knowledge."
    ];
} elseif ($score >= 21) {
    $level = 'Emerging';
    $summary = "You are beginning to explore career readiness. Focus on self-discovery and small projects.";
    $recommendations = [
        "Get involved on campus and seek leadership opportunities.",
        "Join or organize projects aligned with your interests.",
        "Complete a career assessment with a counselor.",
        "Create a 6-12 month career plan targeting development areas."
    ];
} else {
    $level = 'Growing';
    $summary = "You are at the early stages of career readiness. Focus on learning and short-term goals.";
    $recommendations = [
        "Attend workshops and faculty office hours.",
        "Attend events in areas of interest.",
        "Set short-term goals each semester with advisor guidance.",
        "Leverage campus resources to improve skills."
    ];
}

// Create PDF
$pdf = new TCPDF();
$pdf->SetCreator('Career Readiness System');
$pdf->SetAuthor('Admin');
$pdf->SetTitle("Career Readiness Report - $student_id");
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();

// Header
$pdf->SetFont('helvetica', 'B', 18);
$pdf->Cell(0, 12, 'Career Readiness Report', 0, 1, 'C');
$pdf->Ln(4);

// Student Info
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(40, 8, 'Student Name:', 0, 0);
$pdf->Cell(0, 8, $name, 0, 1);
$pdf->Cell(40, 8, 'Student ID:', 0, 0);
$pdf->Cell(0, 8, $student_id, 0, 1);
$pdf->Ln(10);

// Score Box
$pdf->SetFont('helvetica', 'B', 14);
$pdf->SetTextColor(159, 94, 183); // purple
$pdf->Cell(0, 10, "Career Readiness Score: {$score}%", 0, 1, 'L');
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(224, 157, 70); // orange
$pdf->Cell(0, 8, "Level: {$level}", 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(6);

// Summary
$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor(123, 76, 160);
$pdf->Cell(0, 8, 'Result Summary', 0, 1);
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 8, $summary);
$pdf->Ln(6);

// Recommendations
$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor(123, 76, 160);
$pdf->Cell(0, 8, 'Recommendations', 0, 1);
$pdf->SetFont('helvetica', '', 12);
$pdf->SetTextColor(0, 0, 0);

foreach ($recommendations as $rec) {
    $pdf->MultiCell(0, 8, "• {$rec}", 0, 'L', false, 1);
}
$pdf->Ln(10);

// Generated Date
$pdf->SetFont('helvetica', 'I', 10);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 8, "Generated on: $generated_on", 0, 1, 'R');

// Output PDF
$pdf->Output("Career_Readiness_Report_{$student_id}.pdf", 'I');
?>
