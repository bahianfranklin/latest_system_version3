<?php
  require 'db.php';
  session_start();
  header('Content-Type: application/json');

  // audit helper (defines logAction)
  if (file_exists(__DIR__ . '/AUDIT.php')) {
    require_once __DIR__ . '/AUDIT.php';
  }

  $user_id = $_SESSION['user_id'] ?? 0;
  $current = $_POST['current_password'] ?? '';
  $new = $_POST['new_password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
  }

  // Fetch current hashed password
  $select = $conn->prepare("SELECT password FROM users WHERE id = ?");
  if (!$select) {
    error_log('UPDATE_PASSWORD: select prepare failed: ' . $conn->error);
    echo json_encode(['status' => 'error', 'message' => 'Server error.']);
    exit;
  }
  $select->bind_param('i', $user_id);
  $select->execute();
  $res = $select->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $select->close();

  if (!$row || !password_verify($current, $row['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
    exit;
  }

  if ($new !== $confirm) {
    echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
    exit;
  }

  $hashed = password_hash($new, PASSWORD_DEFAULT);
  $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
  if (!$update) {
    error_log('UPDATE_PASSWORD: update prepare failed: ' . $conn->error);
    echo json_encode(['status' => 'error', 'message' => 'Server error.']);
    exit;
  }
  $update->bind_param('si', $hashed, $user_id);
  $ok = $update->execute();
  $update->close();

  if (!$ok) {
    error_log('UPDATE_PASSWORD: update execute failed: ' . $conn->error);
    echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
    exit;
  }

  // Audit trail: call and capture result (logAction now returns true/false)
  $auditLogged = null;
  if (function_exists('logAction')) {
    try {
      $auditLogged = (bool) logAction($conn, $user_id, 'CHANGE PASSWORD', 'User changed password');
      if (!$auditLogged) {
        error_log('UPDATE_PASSWORD: logAction executed but returned false');
      }
    } catch (Throwable $e) {
      $auditLogged = false;
      error_log('UPDATE_PASSWORD: logAction threw: ' . $e->getMessage());
    }
  } else {
    $auditLogged = false;
    error_log('UPDATE_PASSWORD: logAction() not available');
  }

  echo json_encode(['status' => 'success', 'message' => 'Password successfully updated!', 'audit_logged' => $auditLogged]);
  exit;
?>
