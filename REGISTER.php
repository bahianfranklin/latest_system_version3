<?php
session_start();
require 'db.php';

$error = ''; // initialize error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $contact = trim($_POST['contact']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // ✅ All fields required first
    if (empty($name) || empty($address) || empty($contact) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    }
    // ✅ Name validation: letters, spaces, hyphens
    elseif (!preg_match("/^[a-zA-Z\s-]+$/", $name)) {
        $error = "Name can only contain letters, spaces, and hyphens.";
    }
    // ✅ Contact validation: exactly 12 digits
    elseif (!preg_match("/^\d{12}$/", $contact)) {
        $error = "Contact number must be exactly 12 digits.";
    }
    // ✅ Password confirmation
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    }

    // ✅ If no errors, continue with database check
    if (empty($error)) {
        // Check if username, contact, or name already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE name = ? OR username = ? OR contact = ?");
        $stmt->bind_param("sss", $name, $username, $contact);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Name, Username, or Contact already exists!";
        } else {
            // Insert new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO users (name, address, contact, username, password) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("sssss", $name, $address, $contact, $username, $hashedPassword);

            if ($insert->execute()) {
                $_SESSION['success'] = "Registration successful! You can now login.";
                header("Location: login.php");
                exit();
            } else {
                $error = "Something went wrong. Please try again!";
            }
        }
        $stmt->close();
    }
}
?>


<!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Register</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
            <link rel="stylesheet" href="style.css">
            <!-- Bootstrap Icons -->
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
        </head>
    <body class="bg-primary">
        <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="card p-4 shadow" style="max-width: 400px; width:100%;">
                <h2 class="text-center mb-3">Register</h2>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" onsubmit="return validatePassword()">
                    <div class="mb-3">
                        <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="address" class="form-control" placeholder="Address" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="contact" class="form-control" placeholder="Contact" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                    </div>

                    <!-- Password with eye toggle -->
                    <div class="mb-3 position-relative">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                        <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3"
                        id="togglePassword" style="cursor:pointer;"></i>
                    </div>

                    <!-- Confirm Password with eye toggle -->
                    <div class="mb-3 position-relative">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm Password" required>
                        <i class="bi bi-eye-slash position-absolute top-50 end-0 translate-middle-y me-3"
                        id="toggleConfirmPassword" style="cursor:pointer;"></i>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>

                <p class="mt-3 text-center">
                    Already registered? <a href="LOGIN.php">Login</a>
                </p>
            </div>
        </div>

        <div id="layoutAuthentication_footer">
            <?php include __DIR__ . '/layout/FOOTER'; ?>
        </div>

        <script>
            function validateForm() {
                const name = document.querySelector("input[name='name']").value.trim();
                const contact = document.querySelector("input[name='contact']").value.trim();

                // Name: letters, spaces, hyphens
                const nameRegex = /^[a-zA-Z\s-]+$/;
                if (!nameRegex.test(name)) {
                    alert("Name can only contain letters, spaces, and hyphens.");
                    return false;
                }

                // Contact: exactly 12 digits
                const contactRegex = /^\d{12}$/;
                if (!contactRegex.test(contact)) {
                    alert("Contact number must be exactly 12 digits.");
                    return false;
                }

                return true; // all validations passed, allow form submission
            }
        </script>


        <script>
            // ✅ Toggle for password
            const togglePassword = document.querySelector("#togglePassword");
            const password = document.querySelector("#password");

            togglePassword.addEventListener("click", function () {
                const type = password.getAttribute("type") === "password" ? "text" : "password";
                password.setAttribute("type", type);
                this.classList.toggle("bi-eye");
                this.classList.toggle("bi-eye-slash");
            });

            // ✅ Toggle for confirm password
            const toggleConfirmPassword = document.querySelector("#toggleConfirmPassword");
            const confirmPassword = document.querySelector("#confirm_password");

            toggleConfirmPassword.addEventListener("click", function () {
                const type = confirmPassword.getAttribute("type") === "password" ? "text" : "password";
                confirmPassword.setAttribute("type", type);
                this.classList.toggle("bi-eye");
                this.classList.toggle("bi-eye-slash");
            });

            // ✅ Check if passwords match before submit
            function validatePassword() {
                if (password.value !== confirmPassword.value) {
                    alert("Passwords do not match!");
                    return false;
                }
                return true;
            }
        </script>
    </body>
</html>
