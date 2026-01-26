<?php
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/rbac.php';
    require_once __DIR__ . '/audit.php';
    require_once __DIR__ . '/permissions.php';
    require_once __DIR__ . '/autolock.php';

    /* ===============================
    INSERT
    ================================ */
    if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'insert') {

        $work_detail_id = (int)($_POST['work_detail_id'] ?? 0);
        $department_id  = (int)($_POST['department_id'] ?? 0);

        if ($work_detail_id > 0 && $department_id > 0) {

            // Check duplicate department
            $check = $conn->prepare("
                SELECT COUNT(*) 
                FROM hr_approver_assignments 
                WHERE department_id = ?
            ");
            $check->bind_param("i", $department_id);
            $check->execute();
            $check->bind_result($exists);
            $check->fetch();
            $check->close();

            if ($exists > 0) {
                header("Location: HR_APPROVER_MAINTENANCE.php?error=department_exists");
                exit;
            }

            // Get user_id
            $stmtUser = $conn->prepare("
                SELECT user_id 
                FROM work_details 
                WHERE work_detail_id = ?
            ");
            $stmtUser->bind_param("i", $work_detail_id);
            $stmtUser->execute();
            $stmtUser->bind_result($user_id);
            $stmtUser->fetch();
            $stmtUser->close();

            if ($user_id) {
                $stmt = $conn->prepare("
                    INSERT INTO hr_approver_assignments 
                    (user_id, work_detail_id, department_id)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("iii", $user_id, $work_detail_id, $department_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        header("Location: HR_APPROVER_MAINTENANCE.php?success=1");
        exit;
    }

    /* ===============================
    UPDATE
    ================================ */
    if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'update') {

        $id             = (int)($_POST['id'] ?? 0);
        $work_detail_id = (int)($_POST['work_detail_id'] ?? 0);
        $department_id  = (int)($_POST['department_id'] ?? 0);

        if ($id > 0 && $work_detail_id > 0 && $department_id > 0) {

            $stmtUser = $conn->prepare("
                SELECT user_id 
                FROM work_details 
                WHERE work_detail_id = ?
            ");
            $stmtUser->bind_param("i", $work_detail_id);
            $stmtUser->execute();
            $stmtUser->bind_result($user_id);
            $stmtUser->fetch();
            $stmtUser->close();

            if ($user_id) {
                $stmt = $conn->prepare("
                    UPDATE hr_approver_assignments
                    SET user_id=?, work_detail_id=?, department_id=?
                    WHERE id=?
                ");
                $stmt->bind_param("iiii", $user_id, $work_detail_id, $department_id, $id);
                $stmt->execute();
                $stmt->close();
            }
        }

        header("Location: HR_APPROVER_MAINTENANCE.php?success=1");
        exit;
    }

    /* ===============================
    DELETE
    ================================ */
    if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'delete') {

        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $conn->prepare("
                DELETE FROM hr_approver_assignments WHERE id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: HR_APPROVER_MAINTENANCE.php?success=1");
        exit;
    }

    /* ===============================
    COUNTS
    ================================ */
    $resWith = $conn->query("
        SELECT COUNT(DISTINCT department_id) total 
        FROM hr_approver_assignments
    ");
    $total_with = $resWith->fetch_assoc()['total'];

    $resDept = $conn->query("
        SELECT COUNT(*) total 
        FROM departments
    ");
    $total_depts = $resDept->fetch_assoc()['total'];

    $total_without = $total_depts - $total_with;
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

 
<div id="layoutSidenav_content">
    <main class="container-fluid px-4">
        <br>
        <h4>HR Approver Assignment</h4>

        <!-- COUNTS -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card text-bg-success">
                    <div class="card-body">
                        <h6>Departments with HR Approver</h6>
                        <p class="fs-3"><?= $total_with ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card text-bg-danger">
                    <div class="card-body">
                        <h6>Departments without HR Approver</h6>
                        <p class="fs-3"><?= $total_without ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">❌ This department already has an HR approver.</div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">✅ Saved successfully.</div>
        <?php endif; ?>

        <!-- ASSIGN FORM -->
        <form method="POST">
            <input type="hidden" name="action" value="insert">

            <div class="card p-3 mb-4">
                <div class="mb-3">
                    <label>Select Employee</label>
                    <select class="form-control" name="work_detail_id" required>
                        <option value="">-- Select Employee --</option>
                        <?php
                        $emps = $conn->query("
                            SELECT w.work_detail_id, w.employee_no, w.position, u.name
                            FROM work_details w
                            JOIN users u ON u.id = w.user_id
                            ORDER BY u.name
                        ");
                        while ($e = $emps->fetch_assoc()) {
                            echo "<option value='{$e['work_detail_id']}'>
                                {$e['name']} ({$e['employee_no']} - {$e['position']})
                            </option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Select Department</label>
                    <select class="form-control" name="department_id" required>
                        <option value="">-- Select Department --</option>
                        <?php
                        $depts = $conn->query("
                            SELECT id, department 
                            FROM departments 
                            ORDER BY department
                        ");
                        while ($d = $depts->fetch_assoc()) {
                            echo "<option value='{$d['id']}'>{$d['department']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <button class="btn btn-primary">Assign HR Approver</button>
            </div>
        </form>

        <!-- TABLE -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>User ID</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = $conn->query("
                    SELECT h.id, u.name, w.employee_no, d.department, h.user_id
                    FROM hr_approver_assignments h
                    JOIN work_details w ON w.work_detail_id = h.work_detail_id
                    JOIN users u ON u.id = w.user_id
                    JOIN departments d ON d.id = h.department_id
                    ORDER BY d.department
                ");

                while ($row = $res->fetch_assoc()):
                ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['name'] ?> (<?= $row['employee_no'] ?>)</td>
                    <td><?= $row['department'] ?></td>
                    <td><?= $row['user_id'] ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>
    <?php include __DIR__ . '/layout/FOOTER.php'; ?>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const body = document.body;
        const sidebarToggle = document.querySelector("#sidebarToggle");

        if (sidebarToggle) {
            sidebarToggle.addEventListener("click", function (e) {
            e.preventDefault();
            body.classList.toggle("sb-sidenav-toggled");
            });
        }
    });
</script>
