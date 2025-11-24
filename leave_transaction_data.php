<?php
session_start();
require 'db.php';
require 'audit.php';
require 'autolock.php';

if (!isset($_SESSION['user'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user']['id'];

$year   = $_GET['year'] ?? '';
$type   = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

// Build description for audit logs
$desc = "Filters -> Year: " . ($year !== "" ? $year : "All") .
        ", Type: " . ($type !== "" ? $type : "All") .
        ", Search: " . ($search !== "" ? $search : "None");

// 🔥 Audit log entry
logAction(
    $conn,
    $user_id,
    "FILTER LEAVE TRANSACTIONS",
    $desc
);

header('Content-Type: application/json');

// Ensure session is available and user is authenticated
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

// require login
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}

$user_id = (int) $_SESSION['user']['id'];

// Read and sanitize inputs
$year   = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : null;
$type   = isset($_GET['type']) && $_GET['type'] !== '' ? $_GET['type'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

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
    WHERE 1=1
";

$params = [];
$types  = '';

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

// Restrict to current logged-in user
$sql .= " AND lr.user_id = ?";
$params[] = $user_id;
$types .= 'i';

// Final sorting
$sql .= " ORDER BY lr.date_applied DESC";

// Prepare statement
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(['error' => $conn->error]);
    exit;
}

// Bind parameters dynamically
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

// Execute query
if (!$stmt->execute()) {
    echo json_encode(['error' => $stmt->error]);
    exit;
}

// Fetch results
$res = $stmt->get_result();
$data = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

// Output as JSON
echo json_encode($data);
