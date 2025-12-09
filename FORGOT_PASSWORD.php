<?php
require 'db.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Check if email exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user) {
        // Create token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Insert token into DB
        $stmt = $conn->prepare("
            INSERT INTO password_resets (email, token, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sss", $email, $token, $expires);
        $stmt->execute();

        // Reset link
        $resetLink = "http://yourwebsite.com/RESET_PASSWORD.php?token=" . $token;

        // Send email
        mail(
            $email,
            "Password Reset Request",
            "Click the link to reset your password: $resetLink"
        );

        $_SESSION['success'] = "Reset link sent to your email.";
    } else {
        $_SESSION['error'] = "Email not found!";
    }

    header("Location: FORGOT_PASSWORD.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
    <body class="bg-primary">
        <div class="container mt-5">
            <main>
                <div class="card p-4 mx-auto" style="max-width: 500px;">
                    <h3>Forgot Password</h3>

                    <?php if (!empty($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success']; ?></div>
                        <?php unset($_SESSION['success']); endif; ?>

                    <?php if (!empty($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error']; ?></div>
                        <?php unset($_SESSION['error']); endif; ?>

                    <form method="POST">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control" required>

                        <button class="btn btn-primary mt-3">Send Reset Link</button>
                        <a href="LOGIN.php" class="btn btn-secondary mt-3">Back</a>
                    </form>
                </div>
            </main>
        </div>
        <br>
        <?php include __DIR__ . '/layout/FOOTER.php'; ?>
    </body>
</html>
