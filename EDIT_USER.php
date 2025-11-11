<?php
    session_start();
    require 'db.php';

    $id = $_GET['id'] ?? null;
    if (!$id) {
        header("Location: users.php");
        exit;
    }

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

    // fetch roles for role combobox
    $roles = $conn->query("SELECT id, role_name FROM roles ORDER BY role_name ASC");

    // ✅ Handle update form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // collect and normalize POST inputs
        $name = trim($_POST['name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $civil_status = trim($_POST['civil_status'] ?? '');
        $nationality = trim($_POST['nationality'] ?? '');
        $religion = trim($_POST['religion'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $city_municipality = trim($_POST['city_municipality'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $contact_person_relationship = trim($_POST['contact_person_relationship'] ?? '');
        $contact_person_address = trim($_POST['contact_person_address'] ?? '');
        $contact_person_contact = trim($_POST['contact_person_contact'] ?? '');
        $mother_name = trim($_POST['mother_name'] ?? '');
        $father_name = trim($_POST['father_name'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $mobile_no = trim($_POST['mobile_no'] ?? '');
        $birthday = $_POST['birthday'] ?? null;
        $place_of_birth = trim($_POST['place_of_birth'] ?? '');
        $age = isset($_POST['age']) && $_POST['age'] !== '' ? intval($_POST['age']) : null;
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $status = $_POST['status'] ?? '';
        $role_id = isset($_POST['role_id']) && $_POST['role_id'] !== '' ? intval($_POST['role_id']) : null;

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

        // fetch role name from roles table (if role_id provided)
        $role_name = '';
        if ($role_id) {
            $rstmt = $conn->prepare("SELECT role_name FROM roles WHERE id = ?");
            $rstmt->bind_param('i', $role_id);
            $rstmt->execute();
            $rres = $rstmt->get_result();
            $rrow = $rres->fetch_assoc();
            $role_name = $rrow['role_name'] ?? '';
            $rstmt->close();
        }

        $updateStmt = $conn->prepare("UPDATE users SET 
            name=?, address=?, region=?, province=?, city_municipality=?,
            contact_person=?, contact_person_relationship=?, contact_person_address=?, contact_person_contact=?, 
            mother_name=?, father_name=?, contact=?, mobile_no=?, birthday=?, place_of_birth=?, age=?, 
            email=?, username=?, role=?, role_id=?, status=?, password=?, profile_pic=?,
            gender=?, civil_status=?, nationality=?, religion=?
            WHERE id=?");

        // 28 parameters: 24 strings (name, address, region, province, city_municipality, contact_person, contact_person_relationship, 
        // contact_person_address, contact_person_contact, mother_name, father_name, contact, mobile_no, birthday, place_of_birth,
        // email, username, role_name, status, password, profile_pic, gender, civil_status, nationality, religion),
        // 2 integers (age, role_id), 1 integer (id)
        $types = "ssssssssssssssisssissssssssi";

        $updateStmt->bind_param($types,
            $name, $address, $region, $province, $city_municipality,
            $contact_person, $contact_person_relationship, $contact_person_address, $contact_person_contact, 
            $mother_name, $father_name, $contact, $mobile_no, $birthday, $place_of_birth, $age,
            $email, $username, $role_name, $role_id, $status, $password, $profile_pic,
            $gender, $civil_status, $nationality, $religion, $id
        );

        $updateStmt->execute();
        $updateStmt->close();

        // ✅ Work details fields
        $employee_no   = $_POST['employee_no'] ?? '';
        $bank_account  = $_POST['bank_account_no'] ?? '';
        $sss           = $_POST['sss_no'] ?? '';
        $philhealth    = $_POST['philhealth_no'] ?? '';
        $pagibig       = $_POST['pagibig_no'] ?? '';
        $tin           = $_POST['tin_no'] ?? '';
        $date_hired    = $_POST['date_hired'] ?? '';
        $regularization= $_POST['regularization'] ?? '';
        $branch        = $_POST['branch'] ?? '';
        $department    = $_POST['department'] ?? '';
        $position      = $_POST['position'] ?? '';
        $level         = $_POST['level_desc'] ?? '';
        $tax           = $_POST['tax_category'] ?? '';
        $status_desc   = $_POST['status_desc'] ?? '';
        $leave_rule    = $_POST['leave_rule'] ?? '';

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

        header("Location: users.php?t=" . time());
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
                                <div class="row">
                                    <div class="mb-3">
                                        <label class="form-label">Name</label>
                                        <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? ''); ?>" class="form-control" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Gender</label>
                                        <select name="gender" class="form-select">
                                            <option value="">-- Select --</option>
                                            <option value="Male" <?= (isset($user['gender']) && $user['gender']==='Male')? 'selected':''; ?>>Male</option>
                                            <option value="Female" <?= (isset($user['gender']) && $user['gender']==='Female')? 'selected':''; ?>>Female</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Civil Status</label>
                                        <input type="text" name="civil_status" value="<?= htmlspecialchars($user['civil_status'] ?? ''); ?>" class="form-control">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Age</label>
                                        <input type="number" name="age" value="<?= htmlspecialchars($user['age'] ?? ''); ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nationality</label>
                                        <input type="text" name="nationality" value="<?= htmlspecialchars($user['nationality'] ?? ''); ?>" class="form-control">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Religion</label>
                                        <input type="text" name="religion" value="<?= htmlspecialchars($user['religion'] ?? ''); ?>" class="form-control">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? ''); ?>" class="form-control">
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3"><label class="form-label">Region</label><input type="text" name="region" value="<?= htmlspecialchars($user['region'] ?? ''); ?>" class="form-control"></div>
                                    <div class="col-md-4 mb-3"><label class="form-label">Province</label><input type="text" name="province" value="<?= htmlspecialchars($user['province'] ?? ''); ?>" class="form-control"></div>
                                    <div class="col-md-4 mb-3"><label class="form-label">City / Municipality</label><input type="text" name="city_municipality" value="<?= htmlspecialchars($user['city_municipality'] ?? ''); ?>" class="form-control"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3"><label class="form-label">Contact</label><input type="text" name="contact" value="<?= htmlspecialchars($user['contact'] ?? ''); ?>" class="form-control"></div>
                                    <div class="col-md-4 mb-3"><label class="form-label">Mobile No.</label><input type="text" name="mobile_no" value="<?= htmlspecialchars($user['mobile_no'] ?? ''); ?>" class="form-control"></div>
                                    <div class="col-md-4 mb-3"><label class="form-label">Birthday</label><input type="date" name="birthday" value="<?= htmlspecialchars($user['birthday'] ?? ''); ?>" class="form-control"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3"><label class="form-label">Place of Birth</label><input type="text" name="place_of_birth" value="<?= htmlspecialchars($user['place_of_birth'] ?? ''); ?>" class="form-control"></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">Email</label><input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? ''); ?>" class="form-control"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3"><label class="form-label">Username</label><input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? ''); ?>" class="form-control" required></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">Password (leave blank to keep current)</label><input type="password" name="password" class="form-control"></div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Role</label>
                                        <select name="role_id" class="form-select">
                                            <option value="">-- Select Role --</option>
                                            <?php if ($roles): while($r = $roles->fetch_assoc()): ?>
                                                <option value="<?= $r['id'] ?>" <?= (isset($user['role_id']) && $user['role_id']==$r['id'])? 'selected':''; ?>><?= htmlspecialchars($r['role_name']) ?></option>
                                            <?php endwhile; endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="active" <?= (isset($user['status']) && $user['status']==='active')? 'selected':''; ?>>Active</option>
                                            <option value="inactive" <?= (isset($user['status']) && $user['status']==='inactive')? 'selected':''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <hr>
                                <h6>Emergency Contact</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3"><label class="form-label">Contact Person</label><input type="text" name="contact_person" value="<?= htmlspecialchars($user['contact_person'] ?? ''); ?>" class="form-control"></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">Relationship</label><input type="text" name="contact_person_relationship" value="<?= htmlspecialchars($user['contact_person_relationship'] ?? ''); ?>" class="form-control"></div>
                                </div>
                                <div class="mb-3"><label class="form-label">Contact Person Address</label><input type="text" name="contact_person_address" value="<?= htmlspecialchars($user['contact_person_address'] ?? ''); ?>" class="form-control"></div>
                                <div class="mb-3"><label class="form-label">Contact Person Contact No.</label><input type="text" name="contact_person_contact" value="<?= htmlspecialchars($user['contact_person_contact'] ?? ''); ?>" class="form-control"></div>

                                <div class="row mt-3">
                                    <div class="col-md-6 mb-3"><label class="form-label">Mother's Name</label><input type="text" name="mother_name" value="<?= htmlspecialchars($user['mother_name'] ?? ''); ?>" class="form-control"></div>
                                    <div class="col-md-6 mb-3"><label class="form-label">Father's Name</label><input type="text" name="father_name" value="<?= htmlspecialchars($user['father_name'] ?? ''); ?>" class="form-control"></div>
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
                                <div class="mb-3"><label class="form-label">Employee No *</label><input type="text" name="employee_no" value="<?= htmlspecialchars($user['employee_no']); ?>" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Bank Account No *</label><input type="text" name="bank_account_no" value="<?= htmlspecialchars($user['bank_account_no']); ?>" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">SSS No *</label><input type="text" name="sss_no" value="<?= htmlspecialchars($user['sss_no']); ?>" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">PhilHealth No *</label><input type="text" name="philhealth_no" value="<?= htmlspecialchars($user['philhealth_no']); ?>" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Pag-IBIG No *</label><input type="text" name="pagibig_no" value="<?= htmlspecialchars($user['pagibig_no']); ?>" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">TIN No *</label><input type="text" name="tin_no" value="<?= htmlspecialchars($user['tin_no']); ?>" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Date Hired *</label><input type="date" name="date_hired" value="<?= htmlspecialchars($user['date_hired']); ?>" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Regularization</label><input type="date" name="regularization" value="<?= htmlspecialchars($user['regularization']); ?>" class="form-control"></div>
                                <div class="mb-3"><label class="form-label">Branch *</label><input type="text" name="branch" value="<?= htmlspecialchars($user['branch']); ?>" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Department *</label><input type="text" name="department" value="<?= htmlspecialchars($user['department']); ?>" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Position *</label><input type="text" name="position" value="<?= htmlspecialchars($user['position']); ?>" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Level *</label><input type="text" name="level_desc" value="<?= htmlspecialchars($user['level_desc']); ?>" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Tax Category *</label><input type="text" name="tax_category" value="<?= htmlspecialchars($user['tax_category']); ?>" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Status *</label><input type="text" name="status_desc" value="<?= htmlspecialchars($user['status_desc']); ?>" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Leave Rule</label><input type="text" name="leave_rule" value="<?= htmlspecialchars($user['leave_rule']); ?>" class="form-control"></div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Save Changes</button>
                        <a href="USER_MAINTENANCE.php" class="btn btn-secondary w-100 mt-2">Cancel</a>
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
