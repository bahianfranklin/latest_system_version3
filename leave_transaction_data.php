<?php
// Set JSON header FIRST before any output
header('Content-Type: application/json');

// Start output buffering to capture any unexpected output
ob_start();

session_start();

// Suppress any output from required files
$error_reporting = error_reporting(E_ALL);
ini_set('display_errors', '0');

require 'db.php';

// Restore error reporting
error_reporting($error_reporting);
ini_set('display_errors', '1');

// Clear any buffered output from required files
ob_clean();

// Re-enable output buffering for our JSON
ob_start();

require 'audit.php';
require 'autolock.php';

// Clear buffered output
ob_clean();

// Check if user is logged in
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'unauthenticated']);
    ob_end_flush();
    exit();
}

$user_id = (int) $_SESSION['user']['id'];

// Get filter parameters
$year   = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : null;
$type   = isset($_GET['type']) && $_GET['type'] !== '' ? $_GET['type'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

// Build description for audit logs
$desc = "Filters -> Year: " . ($year !== null ? $year : "All") .
        ", Type: " . ($type !== null ? $type : "All") .
        ", Search: " . (!empty($search) ? $search : "None");

// Audit log entry
logAction(
    $conn,
    $user_id,
    "FILTER LEAVE TRANSACTIONS",
    $desc
);

// Build base query
$sql = "
    SELECT 
        lr.leave_type,
        lr.type AS transaction_type,
        lr.credit_value,
        CONCAT('Leave No: ', lr.application_no, ' | Date Used: ', lr.date_from, ' - ', lr.date_to) AS remarks,
        lr.date_applied,
        u.name AS created_by
    FROM leave_requests lr
    LEFT JOIN users u ON lr.approver_id = u.id
    WHERE lr.user_id = ?
";

$params = [$user_id];
$types  = 'i';

// Filter by year
if ($year !== null) {
    $sql .= " AND YEAR(lr.date_applied) = ?";
    $params[] = $year;
    $types .= 'i';
}

// Filter by leave type
if ($type !== null && strcasecmp($type, 'All') !== 0) {
    $sql .= " AND lr.leave_type = ?";
    $params[] = $type;
    $types .= 's';
}

// Search filter
if (!empty($search)) {
    $sql .= " AND (lr.application_no LIKE ? OR lr.remarks LIKE ? OR u.name LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';
}

// Final sorting
$sql .= " ORDER BY lr.date_applied DESC";

// Prepare and execute statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    ob_clean();
    echo json_encode(['error' => 'Prepare error: ' . $conn->error]);
    ob_end_flush();
    exit;
}

// Bind parameters dynamically
$stmt->bind_param($types, ...$params);

// Execute query
if (!$stmt->execute()) {
    ob_clean();
    echo json_encode(['error' => 'Execute error: ' . $stmt->error]);
    ob_end_flush();
    exit;
}

// Fetch results
$res = $stmt->get_result();
$data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Clear any remaining buffered output
ob_clean();

// Output as JSON
echo json_encode($data);
ob_end_flush();
?>
