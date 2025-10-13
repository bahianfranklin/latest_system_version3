<?php
    session_start();
    require 'db.php';

    $errors = [];
    $success = "";

    // ✅ Show success message from session
    if (isset($_SESSION['success'])) {
        $success = $_SESSION['success'];
        unset($_SESSION['success']);
    }

    // ✅ Re-generate employee number before inserting
            $query = "SELECT MAX(employee_no) AS last_no FROM work_details";
            $result = $conn->query($query);
            $row = $result->fetch_assoc();

            if ($row['last_no']) {
                $lastNum = intval(substr($row['last_no'], 5)); // NOTE: Changed from 3 to 5 to skip "ABIC-"
                $newNum = $lastNum + 1;
            } else {
                $newNum = 1;
            }

            $employee_no = "ABIC-" . str_pad($newNum, 7, "0", STR_PAD_LEFT);

    // ✅ Handle form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $name     = trim($_POST['name']);
        $address  = trim($_POST['address']);
        $contact  = trim($_POST['contact']);
        $birthday = $_POST['birthday'];
        $email    = trim($_POST['email']);
        $username = trim($_POST['username']);
        $role     = $_POST['role'];
        $status   = $_POST['status'];
        $password = trim($_POST['password']);
        $profile_pic = null;

        // ✅ User Info Validation
        if (empty($name))     $errors[] = "Name is required.";
        if (empty($address))  $errors[] = "Address is required.";
        if (empty($contact))  $errors[] = "Contact is required.";
        if (empty($birthday)) $errors[] = "Birthdate is required.";
        if (empty($username)) $errors[] = "Username is required.";
        if (empty($password)) $errors[] = "Password is required.";

        if (!preg_match("/^[0-9]+$/", $_POST['contact'])) 
            $errors[] = "Contact must be numbers only.";

        // ✅ Work Details Validation
        if (empty($_POST['bank_account_no'])) $errors[] = "Bank Account No is required.";
        if (empty($_POST['sss_no']))          $errors[] = "SSS No is required.";
        if (empty($_POST['philhealth_no']))   $errors[] = "PhilHealth No is required.";
        if (empty($_POST['pagibig_no']))      $errors[] = "Pag-IBIG No is required.";
        if (empty($_POST['tin_no']))          $errors[] = "TIN No is required.";
        if (empty($_POST['date_hired']))      $errors[] = "Date Hired is required.";
        if (empty($_POST['branch']))          $errors[] = "Branch is required.";
        if (empty($_POST['department']))      $errors[] = "Department is required.";
        if (empty($_POST['position']))        $errors[] = "Position is required.";
        if (empty($_POST['level_desc']))      $errors[] = "Level is required.";
        if (empty($_POST['tax_category']))    $errors[] = "Tax Category is required.";
        if (empty($_POST['status_desc']))     $errors[] = "Employment Status is required.";

        // ✅ Numeric validations
        if (!preg_match("/^[0-9]+$/", $_POST['bank_account_no'])) 
            $errors[] = "Bank Account No must be numbers only.";
        if (!preg_match("/^[0-9]+$/", $_POST['sss_no'])) 
            $errors[] = "SSS No must be numbers only.";
        if (!preg_match("/^[0-9]+$/", $_POST['philhealth_no'])) 
            $errors[] = "PhilHealth No must be numbers only.";
        if (!preg_match("/^[0-9]+$/", $_POST['pagibig_no'])) 
            $errors[] = "Pag-IBIG No must be numbers only.";
        if (!preg_match("/^[0-9]+$/", $_POST['tin_no'])) 
            $errors[] = "TIN No must be numbers only.";

        // ✅ Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) $errors[] = "Username already exists.";
        $stmt->close();

        // ✅ Check if email already exists (if provided)
        if (!empty($email)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = "Email already exists.";
            $stmt->close();
        }

        // ✅ Handle profile picture upload
        if (!empty($_FILES['profile_pic']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $fileName = time() . "_" . basename($_FILES["profile_pic"]["name"]);
            $targetFilePath = $targetDir . $fileName;

            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFilePath)) {
                $profile_pic = $fileName;
            } else {
                $errors[] = "Failed to upload profile picture.";
            }
        }

        $profile_pic = "img_temp.png";
        if (!empty($_FILES['profile_pic']['name'])) {
            $fileName = time() . "_" . basename($_FILES["profile_pic"]["name"]);
            $targetFilePath = $targetDir . $fileName;

            if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFilePath)) {
                $profile_pic = $fileName;
            }
        }

        // ✅ If no errors, insert into database
        if (empty($errors)) {

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO users (name, address, contact, birthday, email, username, role, status, password, profile_pic) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $name, $address, $contact, $birthday, $email, $username, $role, $status, $hashedPassword, $profile_pic);

            if ($stmt->execute()) {
            // Get the last inserted user_id
            $user_id = $conn->insert_id;
            $stmt->close();

            // Step 2: Insert into work_details
            $stmt2 = $conn->prepare("INSERT INTO work_details 
                (employee_no, bank_account_no, sss_no, philhealth_no, pagibig_no, tin_no, date_hired, regularization, 
                branch, department, position, level_desc, tax_category, status_desc, leave_rule, created_at, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)");

            $stmt2->bind_param("sssssssssssssssi", 
                $employee_no, $_POST['bank_account_no'], $_POST['sss_no'], $_POST['philhealth_no'], 
                $_POST['pagibig_no'], $_POST['tin_no'], $_POST['date_hired'], $_POST['regularization'], 
                $_POST['branch'], $_POST['department'], $_POST['position'], $_POST['level_desc'], 
                $_POST['tax_category'], $_POST['status_desc'], $_POST['leave_rule'], $user_id);

            if ($stmt2->execute()) {
                //$success = "User and Work Details added successfully!";
                $_SESSION['success'] = "User and Work Details added successfully!";
                header("Location: add_user1-Copy.php");
                exit();
            } else {
                $errors[] = "Failed to insert work details: " . $stmt2->error;
            }
            $stmt2->close();

            } else {
            $errors[] = "Failed to insert user: " . $stmt->error;
            $stmt->close();
            }
        }
    }

    // ✅ Fetch all branches for dropdown
    $branches = $conn->query("SELECT id, branch FROM branches ORDER BY branch ASC");
    $Departments = $conn->query("SELECT id, department FROM departments ORDER BY department ASC");
    $Positions = $conn->query("SELECT id, position FROM positions ORDER BY position ASC");
    $Levels = $conn->query("SELECT id, level FROM levels ORDER BY level ASC");
    $Taxs = $conn->query("SELECT id, tax_category FROM tax_categories ORDER BY tax_category ASC");
    $Statuses = $conn->query("SELECT id, status FROM statuses ORDER BY status ASC");

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Add User</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Add New User</h4>
                </div>
                <div class="card-body">

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">

                <!-- Tabs -->
                <ul class="nav nav-tabs" id="employeeTab" role="tablist">
                    <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="user-tab" data-bs-toggle="tab" data-bs-target="#user" type="button" role="tab">User Info</button>
                    </li>
                    <li class="nav-item" role="presentation">
                    <button class="nav-link" id="work-tab" data-bs-toggle="tab" data-bs-target="#work" type="button" role="tab">Work Details</button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content mt-3" id="employeeTabContent">

                    <!-- ✅ User Info Tab -->
                    <div class="tab-pane fade show active" id="user" role="tabpanel">

                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address *</label>
                        <input type="text" name="address" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Contact *</label>
                        <input type="text" name="contact" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Birthday</label>
                        <input type="date" name="birthday" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                        <option value="">-- Select Role --</option>
                        <option value="superadmin">Superadmin</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Profile Picture</label>
                        <input type="file" name="profile_pic" class="form-control" onchange="previewImage(event)">
                        <div class="mt-2">
                        <img id="preview" src="uploads/default.png" class="img-thumbnail" width="120">
                        </div>
                    </div>
                    </div>

                    <!-- ✅ Work Details Tab -->
                    <div class="tab-pane fade" id="work" role="tabpanel">

                    <div class="mb-3">
                        <label class="form-label">Employee No *</label>
                        <input type="text" value="<?php echo $employee_no; ?>" readonly class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bank Account No *</label>
                        <input type="text" name="bank_account_no" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">SSS No *</label>
                        <input type="text" name="sss_no" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">PhilHealth No *</label>
                        <input type="text" name="philhealth_no" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Pag-IBIG No *</label>
                        <input type="text" name="pagibig_no" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">TIN No *</label>
                        <input type="text" name="tin_no" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date Hired *</label>
                        <input type="date" name="date_hired" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Regularization</label>
                        <input type="date" name="regularization" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Branch *</label>
                         <select name="branch" class="form-select" required>
                            <option value="">-- Select Branch --</option>
                            <?php while ($row = $branches->fetch_assoc()): ?>
                                <option value="<?= $row['branch'] ?>"><?= htmlspecialchars($row['branch']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Department *</label>
                        <select name="department" class="form-select" required>
                            <option value="">-- Select Department --</option>
                            <?php while ($row = $Departments->fetch_assoc()): ?>
                                <option value="<?= $row['department'] ?>"><?= htmlspecialchars($row['department']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Position *</label>
                        <select name="position" class="form-select" required>
                            <option value="">-- Select Position --</option>
                            <?php while ($row = $Positions->fetch_assoc()): ?>
                                <option value="<?= $row['position'] ?>"><?= htmlspecialchars($row['position']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Level *</label>
                        <select name="level_desc" class="form-select" required>
                            <option value="">-- Select Level --</option>
                            <?php while ($row = $Levels->fetch_assoc()): ?>
                                <option value="<?= $row['level'] ?>"><?= htmlspecialchars($row['level']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tax Category *</label>
                        <select name="tax_category" class="form-select" required>
                            <option value="">-- Select Tax Category --</option>
                            <?php while ($row = $Taxs->fetch_assoc()): ?>
                                <option value="<?= $row['tax_category'] ?>"><?= htmlspecialchars($row['tax_category']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status_desc" class="form-select" required>
                            <option value="">-- Select Status --</option>
                            <?php while ($row = $Statuses->fetch_assoc()): ?>
                                <option value="<?= $row['status'] ?>"><?= htmlspecialchars($row['status']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Leave Rule</label>
                        <input type="text" name="leave_rule" class="form-control">
                    </div>
                    </div>
                </div>

                <!-- ✅ Buttons -->
                <button type="submit" class="btn btn-success w-100">Add User</button>
                <a href="users.php" class="btn btn-secondary w-100 mt-2">Back</a>
                </form>
                </div>
            </div>
        </div>

        <script>
        // ✅ Instant preview when selecting a new file
        function previewImage(event) {
            const output = document.getElementById('preview');
            output.src = URL.createObjectURL(event.target.files[0]);
        }

        <script>
        setTimeout(function() {
            const msg = document.getElementById('successMsg');
            if (msg) {
                msg.style.transition = "opacity 0.5s ease";
                msg.style.opacity = 0;
                setTimeout(() => msg.style.display = 'none', 500); // wait for fade out
            }
        }, 5000);
        </script>

        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    </body>  
</html>
