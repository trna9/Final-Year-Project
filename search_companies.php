<?php
require_once 'db.php';

$company_search = $_GET['company'] ?? '';
$city_search = $_GET['city'] ?? '';

$sql = "SELECT * FROM company WHERE 1=1";
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

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p>No companies found.</p>";
    exit;
}

while ($company = $result->fetch_assoc()): ?>
  <div class="company-card">
    <div class="company-info">
      <img src="<?= htmlspecialchars($company['logo_url']) ?>" alt="<?= htmlspecialchars($company['company_name']) ?>" class="company-logo">
      <div class="company-details">
        <h3><?= htmlspecialchars($company['company_name']) ?></h3>
        <p><?= htmlspecialchars($company['description']) ?></p>
      </div>
    </div>
    <div class="company-meta">
      <p><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($company['address']) ?></p>
      <form method="get" action="company_details.php">
        <input type="hidden" name="id" value="<?= $company['company_id'] ?>">
        <button type="submit" class="details-btn">Details</button>
      </form>
    </div>
  </div>
<?php endwhile;

$stmt->close();
$conn->close();
