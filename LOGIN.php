<?php
    session_start();
    require 'db.php';
    // audit helper (defines logAction)
    if (file_exists(__DIR__ . '/AUDIT.php')) {
        require_once __DIR__ . '/AUDIT.php';
    }

    // Initialize error variable to avoid "undefined" warnings
    $error = "";

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);

        $stmt = $conn->prepare("
            SELECT id, name, address, contact, birthday, email, username, role, status, profile_pic, password
            FROM users 
            WHERE username = ?
        ");
        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && is_array($user)) {
            // ✅ Check if account is inactive
            if ($user['status'] === 'inactive') {
                $error = "Your account is inactive. Please contact admin.";
            } else {
                // ⚠️ Check if password is hashed or plain text
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    // ✅ Save user info to session
                    $_SESSION['user'] = $user;
                    $_SESSION['user_id'] = $user['id'];

                    // ✅ Insert login history
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $stmtLog = $conn->prepare("INSERT INTO user_logs (user_id, login_time, ip_address) VALUES (?, NOW(), ?)");
                    $stmtLog->bind_param("is", $user['id'], $ip);
                    $stmtLog->execute();

                    $_SESSION['log_id'] = $conn->insert_id;

                    // Audit trail (before redirect)
                    if (function_exists('logAction')) {
                        logAction($conn, $user['id'], "LOGIN", "User logged in");
                    } else {
                        error_log('logAction() not available — AUDIT.php not included');
                    }

                    // Redirect to dashboard
                    header("Location: INDEX.php");
                    exit();
                } else {
                    $error = "Invalid username or password!";
                }
            }
        } else {
            $error = "Invalid username or password!";
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Login</title>
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="stylesheet" href="style.css">
    </head>
    <body class="d-flex flex-column min-vh-100 bg-primary">
        <div id="layoutAuthentication" class="d-flex flex-column flex-grow-1">
            <div id="layoutAuthentication_content" class="flex-grow-1">
                <main>
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-lg-5">
                                <div class="card shadow-lg border-0 rounded-lg mt-5">
                                    <div class="card-header"><h3 class="text-center font-weight-light my-4">Welcome, Please Login your Account</h3></div>
                                    <div class="card-body">
                                        <form method="POST" action="">
                                            <!-- Success message -->
                                            <?php if (!empty($_SESSION['success'])): ?>
                                                <div class="alert alert-success">
                                                    <?= htmlspecialchars($_SESSION['success']); ?>
                                                </div>
                                                <?php unset($_SESSION['success']); ?>
                                            <?php endif; ?>

                                            <!-- Error message -->
                                            <?php if (!empty($error)): ?>
                                                <div class="alert alert-danger">
                                                    <?= htmlspecialchars($error); ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="form-floating mb-3">
                                                <input class="form-control" id="inputUsername" name="username" type="text" placeholder="Username" required />
                                                <label for="inputUsername">Username</label>
                                            </div>
                                            <div class="form-floating mb-3 position-relative">
                                                <input class="form-control" id="inputPassword" name="password" type="password" placeholder="Password" required />
                                                <label for="inputPassword">Password</label>
                                                <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3" id="togglePassword" style="cursor: pointer;"></i>
                                            </div>
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" id="inputRememberPassword" type="checkbox" />
                                                <label class="form-check-label" for="inputRememberPassword">Remember Password</label>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mt-4 mb-0">
                                                <a class="small" href="password.html">Forgot Password?</a>
                                                <button type="submit" class="btn btn-primary">Login</button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="card-footer text-center py-3">
                                        <div class="small"><a href="REGISTER.PHP">Need an account? Sign up!</a></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
            <?php include __DIR__ . '/layout/FOOTER'; ?>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
        <script src="js/scripts.js"></script>

        <script>
            const togglePassword = document.querySelector('#togglePassword');
            const passwordInput = document.querySelector('#inputPassword');

            togglePassword.addEventListener('click', function () {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // toggle icon between eye and eye-slash
                this.classList.toggle('bi-eye');
                this.classList.toggle('bi-eye-slash');
            });
        </script>
    </body>
</html>
