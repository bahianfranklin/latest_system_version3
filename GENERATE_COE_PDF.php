<?php
require 'db.php';
require 'vendor/autoload.php'; // composer autoload

use Dompdf\Dompdf;

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
    d.department
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
    die("No record found.");
}

// ✅ HTML for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
<style>
    body { font-family: DejaVu Sans, sans-serif; }
    .container { padding: 40px; }
    h2 { text-align: center; }
    .content { margin-top: 40px; font-size: 14px; line-height: 1.8; }
    .signature { margin-top: 60px; }
</style>
</head>
<body>
<div class="container">
    <h2>CERTIFICATE OF EMPLOYMENT</h2>
    <div class="content">
        <p>
        This is to certify that <strong>' . htmlspecialchars($data['name']) . '</strong>,
        with Employee No. <strong>' . htmlspecialchars($data['employee_no']) . '</strong>,
        is currently employed with the company as
        <strong>' . htmlspecialchars($data['position']) . '</strong>
        under the <strong>' . htmlspecialchars($data['department']) . '</strong> Department.
        </p>
        <p>
        He/She has been employed with the company since
        <strong>' . date("F d, Y", strtotime($data['date_hired'])) . '</strong>.
        </p>
        <p>
        This certification is issued upon request for whatever legal purpose it may serve.
        </p>
        <p>
        Issued this <strong>' . date("F d, Y") . '</strong>.
        </p>
        <div class="signature">
            <strong>HR Department</strong><br>
            Human Resources
        </div>
    </div>
</div>
</body>
</html>
';

// ✅ Generate PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// ✅ Preview PDF in browser
$dompdf->stream(
    "Certificate_of_Employment_" . $data['employee_no'] . ".pdf",
    ["Attachment" => false] // false = preview, true = download
);
exit;
?>