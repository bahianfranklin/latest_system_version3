<?php
    session_start();
    require 'db.php';

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["error" => "Not logged in"]);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $today = date("Y-m-d");
    $action = $_POST['action'] ?? null;

    if ($action === "login") {
        $stmt = $conn->prepare("INSERT INTO attendance_logs (user_id, log_date, login_time, work_type)
                                VALUES (?, ?, NOW(), 'work_from_home')
                                ON DUPLICATE KEY UPDATE login_time = IFNULL(login_time, NOW())");
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        echo json_encode(["success" => "WFH Login recorded"]);
    }
    elseif ($action === "logout") {
        $stmt = $conn->prepare("UPDATE attendance_logs 
                                SET logout_time = NOW() 
                                WHERE user_id = ? AND log_date = ? AND work_type = 'work_from_home'");
        $stmt->bind_param("is", $user_id, $today);
        $stmt->execute();
        echo json_encode(["success" => "WFH Logout recorded"]);
    }
    else {
        echo json_encode(["error" => "Invalid action"]);
    }
