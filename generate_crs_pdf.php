<?php
session_start();
if (!isset($_SESSION['user_id']) || strtoupper($_SESSION['role']) !== 'STUDENT') {
    header("Location: login.html");
    exit;
}

require_once 'db.php';
require_once('tcpdf/tcpdf.php');

$student_id = $_SESSION['user_id'];
$name = $_SESSION['name'] ?? 'Student';

// Fetch latest CRS data (ensures most recent submission)
$stmt = $conn->prepare("
    SELECT score, generated_on
    FROM career_readiness_score
    WHERE student_id = ?
    ORDER BY generated_on DESC, crs_id DESC
    LIMIT 1
");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('You haven’t completed your Career Readiness Self-Assessment yet.'); window.location='career_readiness.php';</script>";
    exit;
}

$crs = $result->fetch_assoc();
$score = (int)$crs['score'];
$generated_on = date('d M Y', strtotime($crs['generated_on']));

// Determine level & summary
if ($score >= 81) {
    $level = 'Thriving';
    $summary = "You are thriving in your career readiness! You demonstrate strong self-awareness, planning, and professional behaviors.";
    $recommendations = [
        "Develop your professional brand and strengthen your LinkedIn presence.",
        "Pursue advanced projects or internships in your chosen field.",
        "Maintain strong relationships with mentors and industry professionals.",
        "Create a 3–5 year career plan with clear milestones."
    ];
} elseif ($score >= 61) {
    $level = 'Achieving';
    $summary = "You are making good progress in preparing for your future career. With focused effort, you can reach the next level.";
    $recommendations = [
        "Attend networking events and career fairs.",
        "Identify a specialization aligned with your strengths.",
        "Create a 1–2 year career development plan.",
        "Take on challenging projects to build confidence."
    ];
} elseif ($score >= 41) {
    $level = 'Aspiring';
    $summary = "You are building momentum toward career readiness. Exploration and guidance will help you grow further.";
    $recommendations = [
        "Join student organizations or professional clubs.",
        "Meet with a career advisor for structured planning.",
        "Seek mentors and conduct informational interviews.",
        "Participate in workshops and skill-building activities."
    ];
} elseif ($score >= 21) {
    $level = 'Emerging';
    $summary = "You are beginning your career readiness journey. This is a great time for self-discovery and involvement.";
    $recommendations = [
        "Get involved in campus activities and leadership roles.",
        "Participate in small projects related to your interests.",
        "Complete career assessments to identify strengths.",
        "Create a short-term (6–12 month) development plan."
    ];
} else {
    $level = 'Growing';
    $summary = "You are at the early stage of career readiness. Focus on learning, exploration, and building good habits.";
    $recommendations = [
        "Attend workshops and academic support sessions.",
        "Explore different career paths through talks and events.",
        "Set simple, achievable goals each semester.",
        "Use campus resources to develop basic skills."
    ];
}

// ---------- CREATE PDF ----------
$pdf = new TCPDF();
$pdf->SetCreator('Career Readiness System');
$pdf->SetAuthor('Career Readiness Portal');
$pdf->SetTitle('Career Readiness Report');
$pdf->SetMargins(15, 20, 15);
$pdf->AddPage();

// ===== HEADER BANNER =====
$pdf->SetFillColor(159, 94, 183);
$pdf->Rect(0, 0, 210, 38, 'F');

$pdf->SetTextColor(255);
$pdf->SetFont('dejavusans', 'B', 20);
$pdf->SetXY(0, 12);
$pdf->Cell(210, 10, 'Career Readiness Report', 0, 1, 'C');

$pdf->SetFont('dejavusans', '', 11);
$pdf->SetXY(0, 24);
$pdf->Cell(210, 8, 'Personal Career Development Summary', 0, 1, 'C');

// ===== STUDENT INFO CARD =====
$pdf->Ln(14);
$pdf->SetFillColor(245, 240, 255);
$pdf->RoundedRect(15, $pdf->GetY(), 180, 38, 6, '1111', 'F');

$pdf->SetTextColor(0);
$pdf->SetFont('dejavusans', 'B', 13);
$pdf->SetXY(22, $pdf->GetY()+6);
$pdf->Cell(0, 6, 'Student Information');

$pdf->SetFont('dejavusans', '', 12);
$pdf->SetXY(22, $pdf->GetY()+14);
$pdf->Cell(0, 6, "Name: $name");
$pdf->Ln(6);
$pdf->SetX(22);
$pdf->Cell(0, 6, "Student ID: $student_id");

// ===== SCORE CARD =====
$pdf->Ln(20);
$pdf->SetFillColor(247, 240, 255);
$pdf->RoundedRect(15, $pdf->GetY(), 180, 52, 6, '1111', 'F');

$pdf->SetFont('dejavusans', 'B', 30);
$pdf->SetTextColor(159, 94, 183);
$pdf->SetXY(15, $pdf->GetY()+8);
$pdf->Cell(180, 14, "{$score}%", 0, 1, 'C');

$pdf->SetFont('dejavusans', 'B', 14);
$pdf->SetTextColor(224, 157, 70);
$pdf->Cell(180, 10, "Level: $level", 0, 1, 'C');

// ===== PROGRESS BAR =====
$barWidth = 150;
$filledWidth = ($score / 100) * $barWidth;

$pdf->Ln(4); // move bar up a bit
$y = $pdf->GetY();

// Background bar (light gray)
$pdf->SetFillColor(224, 216, 240);
$pdf->RoundedRect(30, $y, $barWidth, 8, 4, '1111', 'F'); // 4mm radius

// Filled portion (green)
$pdf->SetFillColor(95, 194, 85);
$pdf->RoundedRect(30, $y, $filledWidth, 8, 4, '1111', 'F');

$pdf->Ln(24); // space after bar

// ===== SUMMARY =====
$pdf->SetFillColor(247, 242, 236);
$pdf->RoundedRect(15, $pdf->GetY(), 180, 36, 6, '1111', 'F');

$pdf->SetFont('dejavusans', 'B', 13);
$pdf->SetTextColor(224, 157, 70);
$pdf->SetXY(22, $pdf->GetY()+6);
$pdf->Cell(0, 8, 'Result Summary');

$pdf->SetFont('dejavusans', '', 12);
$pdf->SetTextColor(50);
$pdf->SetXY(22, $pdf->GetY()+14);
$pdf->MultiCell(170, 8, $summary, 0, 'L'); // left-aligned

// ===== RECOMMENDATIONS =====
$pdf->Ln(14);
$pdf->SetFillColor(240, 235, 250);
$pdf->RoundedRect(15, $pdf->GetY(), 180, 60, 6, '1111', 'F');

$pdf->SetFont('dejavusans', 'B', 13);
$pdf->SetTextColor(123, 76, 160);
$pdf->SetXY(22, $pdf->GetY()+6);
$pdf->Cell(0, 8, 'Personalized Recommendations');

$pdf->SetFont('dejavusans', '', 12);
$pdf->SetTextColor(0);
$pdf->SetX(22);  // fix X for proper alignment
$pdf->Ln(16);    // spacing below header

foreach ($recommendations as $rec) {
    $pdf->SetX(22);  // ensures left alignment for each line
    $pdf->MultiCell(170, 8, "✔ $rec", 0, 'L');
}

// ===== FOOTER =====
$pdf->Ln(12);
$pdf->SetFont('dejavusans', 'I', 10);
$pdf->SetTextColor(120);
$pdf->Cell(0, 8, "Generated on: $generated_on", 0, 1, 'R');

// Output
$pdf->Output("Career_Readiness_Report_{$student_id}.pdf", 'I');
?>
