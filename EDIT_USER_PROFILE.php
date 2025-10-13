<?php
    session_start();
    require 'db.php';

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: INDEX.php");
    exit;
    }
    $id = (int) $_GET['id'];

    // ✅ Fetch user with work details
    $stmt = $conn->prepare("SELECT u.*, w.employee_no, w.bank_account_no, w.sss_no, w.philhealth_no, 
                                w.pagibig_no, w.tin_no, w.date_hired, w.regularization, w.branch, 
                                w.department, w.position, w.level_desc, w.tax_category, 
                                w.status_desc, w.leave_rule
                            FROM users u
                            LEFT JOIN work_details w ON u.id = w.user_id
                            WHERE u.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        echo "User not found!";
        exit;
    }

    // ✅ Handle update form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $name = $_POST['name'];
        $address = $_POST['address'];
        $contact = $_POST['contact'];
        $birthday = $_POST['birthday'];
        $email = $_POST['email'];
        $username = $_POST['username'];
        $role = $_POST['role'];
        $status = $_POST['status'];

        // Keep old password if empty
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : $user['password'];

        // Handle profile picture
        $profile_pic = $user['profile_pic'] ?: "default.png";
        if (!empty($_FILES['profile_pic']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            $fileName = time() . "_" . basename($_FILES["profile_pic"]["name"]);
            $targetFilePath = $targetDir . $fileName;

            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFilePath)) {
                    $profile_pic = $fileName;
                }
            }
        }

        // ✅ Update users table
        $updateStmt = $conn->prepare("UPDATE users 
            SET name=?, address=?, contact=?, birthday=?, email=?, username=?, role=?, status=?, password=?, profile_pic=? 
            WHERE id=?");
        $updateStmt->bind_param("ssssssssssi", 
            $name, $address, $contact, $birthday, $email, $username, $role, $status, $password, $profile_pic, $id
        );
        $updateStmt->execute();

        // ✅ Work details fields
        $employee_no   = $_POST['employee_no'];
        $bank_account  = $_POST['bank_account_no'];
        $sss           = $_POST['sss_no'];
        $philhealth    = $_POST['philhealth_no'];
        $pagibig       = $_POST['pagibig_no'];
        $tin           = $_POST['tin_no'];
        $date_hired    = $_POST['date_hired'];
        $regularization= $_POST['regularization'];
        $branch        = $_POST['branch'];
        $department    = $_POST['department'];
        $position      = $_POST['position'];
        $level         = $_POST['level_desc'];
        $tax           = $_POST['tax_category'];
        $status_desc   = $_POST['status_desc'];
        $leave_rule    = $_POST['leave_rule'];

        // ✅ Check if work_details exists
        $checkStmt = $conn->prepare("SELECT user_id FROM work_details WHERE user_id=?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            // Update existing
            $workStmt = $conn->prepare("UPDATE work_details 
                SET employee_no=?, bank_account_no=?, sss_no=?, philhealth_no=?, pagibig_no=?, tin_no=?, 
                    date_hired=?, regularization=?, branch=?, department=?, position=?, level_desc=?, 
                    tax_category=?, status_desc=?, leave_rule=? 
                WHERE user_id=?");
            $workStmt->bind_param("sssssssssssssssi", 
                $employee_no, $bank_account, $sss, $philhealth, $pagibig, $tin,
                $date_hired, $regularization, $branch, $department, $position, $level,
                $tax, $status_desc, $leave_rule, $id
            );
        } else {
            // Insert new
            $workStmt = $conn->prepare("INSERT INTO work_details 
                (user_id, employee_no, bank_account_no, sss_no, philhealth_no, pagibig_no, tin_no, 
                date_hired, regularization, branch, department, position, level_desc, 
                tax_category, status_desc, leave_rule) 
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $workStmt->bind_param("isssssssssssssss", 
                $id, $employee_no, $bank_account, $sss, $philhealth, $pagibig, $tin,
                $date_hired, $regularization, $branch, $department, $position, $level,
                $tax, $status_desc, $leave_rule
            );
        }
        $workStmt->execute();

        // ✅ Refresh session if editing logged-in user
        if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $id) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $_SESSION['user'] = $result->fetch_assoc();
        }

        header("Location: INDEX.php?t=" . time());
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Edit User Information</h4>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">

                        <!-- Tabs -->
                        <ul class="nav nav-tabs" id="employeeTab" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" id="user-tab" data-bs-toggle="tab" data-bs-target="#user" type="button">User Info</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" id="work-tab" data-bs-toggle="tab" data-bs-target="#work" type="button">Work Details</button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content mt-3" id="employeeTabContent">

                            <!-- User Info Tab -->
                            <div class="tab-pane fade show active" id="user">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']); ?>" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <input type="text" name="address" value="<?= htmlspecialchars($user['address']); ?>" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Contact</label>
                                    <input type="text" name="contact" value="<?= htmlspecialchars($user['contact']); ?>" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Birthday</label>
                                    <input type="date" name="birthday" value="<?= htmlspecialchars($user['birthday']); ?>" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']); ?>" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password (leave blank to keep current)</label>
                                    <input type="password" name="password" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select name="role" class="form-select" required>
                                        <option <?= $user['role'] === 'superadmin' ? 'selected' : '' ?>>superadmin</option>
                                        <option <?= $user['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
                                        <option <?= $user['role'] === 'user' ? 'selected' : '' ?>>user</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" required>
                                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Profile Picture</label>
                                    <input type="file" name="profile_pic" class="form-control" onchange="previewImage(event)">
                                    <div class="mt-2">
                                        <img id="preview" src="uploads/<?= htmlspecialchars($user['profile_pic'] ?: 'default.png'); ?>?t=<?= time(); ?>" class="img-thumbnail" width="120">
                                    </div>
                                </div>
                            </div>

                            <!-- Work Details Tab -->
                            <div class="tab-pane fade" id="work">
                                <h6 class="text-center">***Viewing Details Only!***</h6>
                                <div class="mb-3"><label class="form-label">Employee No *</label><input type="text" name="employee_no" value="<?= htmlspecialchars($user['employee_no']); ?>" class="form-control" readonly></div>
                                <div class="mb-3"><label class="form-label">Bank Account No *</label><input type="text" name="bank_account_no" value="<?= htmlspecialchars($user['bank_account_no']); ?>" class="form-control" readonly></div>
                                <div class="mb-3"><label class="form-label">SSS No *</label><input type="text" name="sss_no" value="<?= htmlspecialchars($user['sss_no']); ?>" class="form-control" readonly></div>
                                <div class="mb-3"><label class="form-label">PhilHealth No *</label><input type="text" name="philhealth_no" value="<?= htmlspecialchars($user['philhealth_no']); ?>" class="form-control" readonly></div>
                                <div class="mb-3"><label class="form-label">Pag-IBIG No *</label><input type="text" name="pagibig_no" value="<?= htmlspecialchars($user['pagibig_no']); ?>" class="form-control" readonly></div>
                                <div class="mb-3"><label class="form-label">TIN No *</label><input type="text" name="tin_no" value="<?= htmlspecialchars($user['tin_no']); ?>" class="form-control" readonly></div>
                                <div class="mb-3"><label class="form-label">Date Hired *</label><input type="date" name="date_hired" value="<?= htmlspecialchars($user['date_hired']); ?>" class="form-control" readonly></div>
                                <div class="mb-3"><label class="form-label">Regularization</label><input type="date" name="regularization" value="<?= htmlspecialchars($user['regularization']); ?>" class="form-control" readonly></div>
                                <div class="mb-3"><label class="form-label">Branch *</label><input type="text" name="branch" value="<?= htmlspecialchars($user['branch']); ?>" class="form-control" readonly></div>
                                <div class="mb-3"><label class="form-label">Department *</label><input type="text" name="department" value="<?= htmlspecialchars($user['department']); ?>" class="form-control" readonly></div>
                                <div class="mb-3"><label class="form-label">Position *</label><input type="text" name="position" value="<?= htmlspecialchars($user['position']); ?>" class="form-control" readonly></div>
                                <div class="mb-3"><label class="form-label">Level *</label><input type="text" name="level_desc" value="<?= htmlspecialchars($user['level_desc']); ?>" class="form-control" readonly></div>
                                <div class="mb-3"><label class="form-label">Tax Category *</label><input type="text" name="tax_category" value="<?= htmlspecialchars($user['tax_category']); ?>" class="form-control" readonly></div>
                                <div class="mb-3"><label class="form-label">Status *</label><input type="text" name="status_desc" value="<?= htmlspecialchars($user['status_desc']); ?>" class="form-control" readonly></div>
                                <div class="mb-3"><label class="form-label">Leave Rule</label><input type="text" name="leave_rule" value="<?= htmlspecialchars($user['leave_rule']); ?>" class="form-control" readonly></div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Save Changes</button>
                        <a href="INDEX.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function previewImage(event) {
            const output = document.getElementById('preview');
            output.src = URL.createObjectURL(event.target.files[0]);
        }
        </script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
