<?php
require 'db.php';
session_start();

if (!isset($_GET['token'])) {
    die("Invalid request.");
}

$token = $_GET['token'];

// Get token record
$stmt = $conn->prepare("
    SELECT * FROM password_resets 
    WHERE token = ? AND expires_at >= NOW()
");
$stmt->bind_param("s", $token);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Token is invalid or expired.");
}

$email = $data['email'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm']);

    if ($password !== $confirm) {
        $_SESSION['error'] = "Passwords do not match!";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
        $stmt->execute();

        // Delete token
        $conn->query("DELETE FROM password_resets WHERE email='$email'");

        $_SESSION['success'] = "Password updated! Please log in.";
        header("Location: login.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body class="bg-primary">

<div class="container mt-5">
    <main>
        <div class="card p-4">
            <h3>Reset Password</h3>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error']; ?></div>
                <?php unset($_SESSION['error']); endif; ?>

            <form method="POST">
                <label>New Password</label>
                <input type="password" name="password" class="form-control" required>

                <label>Confirm Password</label>
                <input type="password" name="confirm" class="form-control" required>

                <button class="btn btn-primary mt-3">Reset Password</button>
            </form>
        </div>
    </main>
</div>

</body>
</html>
