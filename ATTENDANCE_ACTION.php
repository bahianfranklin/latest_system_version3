<?php
session_start();
date_default_timezone_set('Asia/Manila'); // ✅ Fix timezone
require 'db.php';
$conn->query("SET time_zone = '+08:00'"); // ✅ Align MySQL timezone
header('Content-Type: application/json');

// ✅ Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id   = $_SESSION['user_id'];
$action    = $_POST['action'] ?? '';
$type      = $_POST['type'] ?? ''; // 'onsite' or 'wfh'
$work_type = ($type === 'wfh') ? 'work_from_home' : 'onsite';
$now       = date("Y-m-d H:i:s"); // full timestamp
$today     = date("Y-m-d");

// 🧠 Fetch the latest open attendance log (logout_time IS NULL)
$stmt = $conn->prepare("
    SELECT * FROM attendance_logs
    WHERE user_id = ? AND logout_time IS NULL
    ORDER BY login_time DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$open_log = $stmt->get_result()->fetch_assoc();

if ($action === 'login') {

    // ✅ Prevent duplicate logins (same day + type)
    $stmt2 = $conn->prepare("
        SELECT id FROM attendance_logs
        WHERE user_id = ? AND log_date = ? AND work_type = ?
    ");
    $stmt2->bind_param("iss", $user_id, $today, $work_type);
    $stmt2->execute();
    $existing_today = $stmt2->get_result()->fetch_assoc();

    if ($existing_today) {
        echo json_encode(['error' => 'Already logged in today.']);
        exit;
    }

    // ✅ Create new attendance record
    $insert = $conn->prepare("
        INSERT INTO attendance_logs (user_id, log_date, login_time, work_type)
        VALUES (?, ?, ?, ?)
    ");
    $insert->bind_param("isss", $user_id, $today, $now, $work_type);
    $insert->execute();

    echo json_encode(['success' => 'Login successful at ' . date("h:i A")]);

} elseif ($action === 'logout') {

    // ✅ If there’s an open log (even from yesterday), close it
    if ($open_log) {
        $update = $conn->prepare("
            UPDATE attendance_logs
            SET logout_time = ?
            WHERE id = ?
        ");
        $update->bind_param("si", $now, $open_log['id']);
        $update->execute();

        echo json_encode(['success' => 'Logout successful at ' . date("h:i A")]);
    } else {
        // 🧩 If no open session, check for today's record
        $stmt3 = $conn->prepare("
            SELECT * FROM attendance_logs
            WHERE user_id = ? AND log_date = ? AND work_type = ? AND logout_time IS NULL
        ");
        $stmt3->bind_param("iss", $user_id, $today, $work_type);
        $stmt3->execute();
        $today_log = $stmt3->get_result()->fetch_assoc();

        if ($today_log) {
            $update = $conn->prepare("
                UPDATE attendance_logs
                SET logout_time = ?
                WHERE id = ?
            ");
            $update->bind_param("si", $now, $today_log['id']);
            $update->execute();

            echo json_encode(['success' => 'Logout successful at ' . date("h:i A")]);
        } else {
            echo json_encode(['error' => 'No active session found to log out.']);
        }
    }

} else {
    echo json_encode(['error' => 'Invalid action.']);
}

