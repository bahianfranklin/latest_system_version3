<?php
  require 'db.php';
  session_start();
  header('Content-Type: application/json');

  $user_id = $_SESSION['user_id'] ?? 0;
  $current = $_POST['current_password'] ?? '';
  $new = $_POST['new_password'] ?? '';
  $confirm = $_POST['confirm_password'] ?? '';

  if (!$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in.']);
    exit;
  }

  $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result()->fetch_assoc();

  if (!$result || !password_verify($current, $result['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
    exit;
  }

  if ($new !== $confirm) {
    echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
    exit;
  }

  $hashed = password_hash($new, PASSWORD_DEFAULT);
  $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
  $update->bind_param("si", $hashed, $user_id);
  $update->execute();

  echo json_encode(['status' => 'success', 'message' => 'Password successfully updated!']);
?>
