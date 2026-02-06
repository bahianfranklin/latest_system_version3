<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$baseUrl = "https://api.mandbox.com/apitest/v1/contact.php";

// Fetch records using GET
$url = $baseUrl . "?action=view";

$result = file_get_contents($url);

// Remove junk before/after JSON (Mandbox APIs sometimes echo extra text)
$result = preg_replace('/^[^\{]+/', '', $result);
$result = preg_replace('/[^\}]+$/', '', $result);

$dataArray = json_decode($result, true);

$records = $dataArray['data'] ?? [];
?>

<!DOCTYPE html>
<html>
<head>
<title>Contact List</title>

<style>
table {
    border-collapse: collapse;
    width: 100%;
}
th, td {
    border: 1px solid #ccc;
    padding: 8px;
}
th {
    background: #f2f2f2;
}
</style>

</head>
<body>

<h2>Contact List</h2>

<?php if (!empty($records)): ?>

<table>
<tr>
    <th>ID</th>
    <th>Full Name</th>
    <th>Address</th>
    <th>Contact No</th>
</tr>

<?php foreach ($records as $row): ?>

<tr>
    <td><?= htmlspecialchars($row['record_id'] ?? '') ?></td>
    <td><?= htmlspecialchars($row['fullname'] ?? '') ?></td>
    <td><?= htmlspecialchars($row['address'] ?? '') ?></td>
    <td><?= htmlspecialchars($row['contact_no'] ?? '') ?></td>
</tr>

<?php endforeach; ?>

</table>

<?php else: ?>

<p>No records found.</p>

<?php endif; ?>

</body>
</html>
