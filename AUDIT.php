<?php
function logAction($conn, $user_id, $action, $description = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    $sql = "INSERT INTO audit_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
    $query = $conn->prepare($sql);
    if (!$query) {
        error_log('logAction: prepare failed: ' . $conn->error);
        return false;
    }
    $query->bind_param("issss", $user_id, $action, $description, $ip, $agent);
    $ok = $query->execute();
    if (!$ok) {
        error_log('logAction: execute failed: ' . $conn->error);
        return false;
    }
    $query->close();
    return true;
}
?>
