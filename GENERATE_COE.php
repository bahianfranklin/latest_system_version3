<?php
require 'db.php';

// ✅ Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Access denied. Only POST requests are allowed.");
}

// ✅ Validate POST data
if (empty($_POST['user_id']) || empty($_POST['department_id'])) {
    die("Incomplete data. Please select employee and department.");
}

$user_id = (int) $_POST['user_id'];
$department_id = (int) $_POST['department_id'];

// ✅ Fetch employee and department data
$sql = "
SELECT 
    u.name,
    wd.employee_no,
    wd.position,
    wd.date_hired,
    d.department AS dept_name
FROM users u
INNER JOIN work_details wd ON u.id = wd.user_id
INNER JOIN departments d ON d.id = wd.department
WHERE u.id = ? AND d.id = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Statement preparation failed: " . $conn->error);
}
$stmt->bind_param("ii", $user_id, $department_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("No record found. Please check employee and department match.");
}

// ✅ Display basic COE info
?>

<h2 style="text-align:center;">CERTIFICATE OF EMPLOYMENT</h2>

<p>
This is to certify that <strong><?= htmlspecialchars($data['name']) ?></strong>,
with Employee No. <strong><?= htmlspecialchars($data['employee_no']) ?></strong>,
is employed with the company as a <strong><?= htmlspecialchars($data['position']) ?></strong>
under the <strong><?= htmlspecialchars($data['dept_name']) ?></strong> Department.
</p>

<p>
He/She has been employed since
<strong><?= date("F d, Y", strtotime($data['date_hired'])) ?></strong>.
</p>

<p>
Issued this <?= date("F d, Y") ?> for whatever legal purpose it may serve.
</p>

<br>
<form action="GENERATE_COE_PDF.php" method="POST">
    <input type="hidden" name="user_id" value="<?= $user_id ?>">
    <input type="hidden" name="department_id" value="<?= $department_id ?>">
    <button type="submit">Generate PDF</button>
</form>
