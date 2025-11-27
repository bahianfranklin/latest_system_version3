<?php
session_start();
if (!isset($_SESSION['locked_user_id'])) {
    header("Location: index.php");
    exit;
}

require 'db.php';
// audit helper (defines logAction)
if (file_exists(__DIR__ . '/AUDIT.php')) {
    require_once __DIR__ . '/AUDIT.php';
}

$userId = $_SESSION['locked_user_id'];
// Fetch user info including profile image
$stmt = $pdo->prepare("SELECT password, username, profile_pic FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    
    // Use existing $user['password']
    if ($user && password_verify($password, $user['password'])) {
        unset($_SESSION['locked_user_id']);
        // restore session user id; the application expects both user_id and user in session
        $_SESSION['user_id'] = $userId;
        // optional: set minimal $_SESSION['user'] if username is available
        if (isset($user['username'])) {
            $_SESSION['user'] = [
                'id' => $userId,
                'username' => $user['username']
            ];
        }

        // 🔥 AUDIT TRAIL — record who unlocked the session
        $details = isset($user['username']) ? "Unlocked session for {$user['username']}" : "Unlocked session for user_id={$userId}";
        if (function_exists('logAction')) {
            logAction($conn, $userId, "LOCKSCREEN", $details);
        } else {
            error_log('LOCKSCREEN: logAction() not available');
        }
        header("Location: index.php");
        exit;
    } else {
        $error = "Invalid password. Try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lock Screen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap and icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    <!-- Custom style -->
    <style>
        body {
            background-color: #0d6efd;
        }
        .lock-screen-wrapper {
            min-height: 90vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            width: 100%;
            max-width: 420px;
        }
        .profile-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: block;
            border: 3px solid #fff;
            box-shadow: 0 0 8px rgba(0,0,0,0.2);
        }
    </style>
</head>
    <body>
        <div class="lock-screen-wrapper">
            
            <div class="card shadow-lg border-0 rounded-lg p-4 text-center">
                <!-- Show user profile image -->
                <?php if (!empty($user['profile_pic'])): ?>
                    <img src="uploads/<?= htmlspecialchars($user['profile_pic']) ?>" alt="User Profile Picture" class="profile-img" />
                <?php else: ?>
                    <!-- fallback icon if no image -->
                    <i class="bi bi-person-circle" style="font-size: 100px; color: white;"></i>
                <?php endif; ?>

                <div class="card-header text-center">
                    <h4 class="fw-bold">Session Locked</h4>
                    <p class="text-muted mb-0">Enter your password to continue</p>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="form-floating mb-3">
                            <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Password" required />
                            <label for="inputPassword">Password</label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Unlock</button>
                        </div>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="relogin.php" class="small">Re-login as another user</a>
                    <script>
                        function relogin() {
                            // Replace current history so Back button won't go back to lock screen
                            window.location.replace('relogin.php');
                        }
                    </script>

                    </div>
                    
                </div>
            </div>
        </div>

        <?php include __DIR__ . '/layout/FOOTER'; ?>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            function goToLogin() {
                window.location.replace("logout.php");
            }

            // Optional: prevent back button behavior
            history.pushState(null, null, location.href);
            window.onpopstate = function () {
                history.go(1);
            };
        </script>
    </body>
</html>
