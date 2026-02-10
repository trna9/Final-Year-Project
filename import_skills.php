<?php
require_once 'db.php'; // your DB connection

$csvFile = 'csv/skills.csv';
if (!file_exists($csvFile)) die("CSV file not found.");

// Disable foreign key checks temporarily
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

// Clear skill_master table (optional if you want fresh import)
$conn->query("TRUNCATE TABLE skill_master");

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

if (($handle = fopen($csvFile, "r")) !== false) {
    fgetcsv($handle); // skip header

    $stmt = $conn->prepare("INSERT INTO skill_master (skill_name) VALUES (?)");
    $count = 0;

    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        if (!empty($data[0])) {
            $skill_name = trim($data[0]);
            $skill_name = preg_replace('/[\x00-\x1F\x7F]/u', '', $skill_name); // remove invisible chars

            $stmt->bind_param("s", $skill_name);
            if ($stmt->execute()) $count++;
        }
    }

    $stmt->close();
    fclose($handle);

    echo "Skills imported successfully! Total new skills added: $count";
} else {
    echo "Failed to open CSV file.";
}
?>
