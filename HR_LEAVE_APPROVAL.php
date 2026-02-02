<?php
session_start();
require 'db.php';
require 'autolock.php';
require 'audit.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$approver_id = (int) $_SESSION['user_id'];

// Check if user is an assigned HR approver
$stmt = $conn->prepare("
    SELECT 1 FROM hr_approver_assignments WHERE user_id = ? LIMIT 1
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    die("Access denied");
}

/* ================================
   FETCH PENDING REQUESTS
================================ */
$sqlPending = "
    SELECT 
        lr.application_no,
        u.name AS employee,
        d.department,
        lr.leave_type,
        lr.date_from,
        lr.date_to,
        lr.status,
        lr.remarks,
        lr.date_applied
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    JOIN work_details wd ON u.id = wd.user_id
    JOIN departments d ON d.department = wd.department
    JOIN hr_approver_assignments haa ON haa.department_id = d.id
    WHERE haa.user_id = ?
      AND lr.status = 'Pending'
    ORDER BY lr.date_applied DESC
";

$stmt = $conn->prepare($sqlPending);

if (!$stmt) {
    die("SQL ERROR: " . $conn->error);
}

$stmt = $conn->prepare($sqlPending);
$stmt->bind_param("i", $approver_id);
$stmt->execute();
$pending = $stmt->get_result();

/* ================================
   COUNT PENDING
================================ */
$sqlCount = "
    SELECT COUNT(*) AS total
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    JOIN work_details wd ON u.id = wd.user_id
    JOIN departments d ON d.department = wd.department
    JOIN hr_approver_assignments haa ON haa.department_id = d.id
    WHERE haa.user_id = ?
      AND lr.status = 'Pending'
";

$stmtC = $conn->prepare($sqlCount);
$stmtC->bind_param("i", $approver_id);
$stmtC->execute();
$pendingCount = $stmtC->get_result()->fetch_assoc()['total'];

/* ================================
   FETCH APPROVED REQUESTS
================================ */
$sqlApproved = "
    SELECT 
        lr.application_no,
        u.name AS employee,
        d.department,
        lr.leave_type,
        lr.date_from,
        lr.date_to,
        lr.status,
        lr.date_action,
        lr.remarks,
        COALESCE(ua.name, ua2.name) AS approved_by
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    JOIN work_details wd ON u.id = wd.user_id
    JOIN departments d ON d.department = wd.department
    JOIN hr_approver_assignments haa ON haa.department_id = d.id
    LEFT JOIN users ua ON lr.approver_id = ua.id
    LEFT JOIN users ua2 ON haa.user_id = ua2.id
    WHERE haa.user_id = ?
      AND lr.status = 'Approved'
    ORDER BY lr.date_action DESC
";

$stmtApproved = $conn->prepare($sqlApproved);
$stmtApproved->bind_param("i", $approver_id);
$stmtApproved->execute();
$approved = $stmtApproved->get_result();

/* ================================
   FETCH REJECTED REQUESTS
================================ */
$sqlRejected = "
    SELECT 
        lr.application_no,
        u.name AS employee,
        d.department,
        lr.leave_type,
        lr.date_from,
        lr.date_to,
        lr.status,
        lr.date_action,
        lr.remarks,
        COALESCE(ua.name, ua2.name) AS rejected_by
    FROM leave_requests lr
    JOIN users u ON lr.user_id = u.id
    JOIN work_details wd ON u.id = wd.user_id
    JOIN departments d ON d.department = wd.department
    JOIN hr_approver_assignments haa ON haa.department_id = d.id
    LEFT JOIN users ua ON lr.approver_id = ua.id
    LEFT JOIN users ua2 ON haa.user_id = ua2.id
    WHERE haa.user_id = ?
      AND lr.status = 'Rejected'
    ORDER BY lr.date_action DESC
";

$stmtRejected = $conn->prepare($sqlRejected);
$stmtRejected->bind_param("i", $approver_id);
$stmtRejected->execute();
$rejected = $stmtRejected->get_result();

print_r(scandir(__DIR__ . '/layout'));
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4 mt-4">
        <h3>Pending Leave Requests (<?= $pendingCount ?>)</h3>

        <!-- Pending Requests -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark fw-bold">
                Pending Leave Requests
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Application No</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Leave Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Date Applied</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $pending->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['application_no'] ?></td>
                                <td><?= $row['employee'] ?></td>
                                <td><?= $row['department'] ?></td>
                                <td><?= $row['leave_type'] ?></td>
                                <td><?= $row['date_from'] ?></td>
                                <td><?= $row['date_to'] ?></td>
                                <td><span class="badge bg-warning text-dark"><?= $row['status'] ?></span></td>
                                <td><?= $row['remarks'] ?></td>
                                <td><?= $row['date_applied'] ?></td>
                                <td class="d-flex gap-1">
                                    <form method="POST" action="UPDATE_LEAVE_STATUS_HR.php">
                                        <input type="hidden" name="application_no" value="<?= $row['application_no'] ?>">
                                        <input type="hidden" name="action" value="Approved">
                                        <input type="hidden" name="redirect" value="HR_LEAVE_APPROVAL.php">
                                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                    </form>
                                    <form method="POST" action="UPDATE_LEAVE_STATUS_HR.php">
                                        <input type="hidden" name="application_no" value="<?= $row['application_no'] ?>">
                                        <input type="hidden" name="action" value="Rejected">
                                        <input type="hidden" name="redirect" value="HR_LEAVE_APPROVAL.php">
                                        <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Approved Requests -->
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white fw-bold">
                Approved Leave Requests
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Application No</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Leave Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Approved By</th>
                                <th>Status</th>
                                <th>Date Action</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $approved->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['application_no'] ?></td>
                                <td><?= $row['employee'] ?></td>
                                <td><?= $row['department'] ?></td>
                                <td><?= $row['leave_type'] ?></td>
                                <td><?= $row['date_from'] ?></td>
                                <td><?= $row['date_to'] ?></td>
                                <td><?= $row['approved_by'] ?></td>
                                <td><span class="badge bg-success"><?= $row['status'] ?></span></td>
                                <td><?= $row['date_action'] ?></td>
                                <td><?= $row['remarks'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Rejected Requests -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-danger text-white fw-bold">
                Rejected Leave Requests
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Application No</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Leave Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Rejected By</th>
                                <th>Status</th>
                                <th>Date Action</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $rejected->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['application_no'] ?></td>
                                <td><?= $row['employee'] ?></td>
                                <td><?= $row['department'] ?></td>
                                <td><?= $row['leave_type'] ?></td>
                                <td><?= $row['date_from'] ?></td>
                                <td><?= $row['date_to'] ?></td>
                                <td><?= $row['rejected_by'] ?></td>
                                <td><span class="badge bg-danger"><?= $row['status'] ?></span></td>
                                <td><?= $row['date_action'] ?></td>
                                <td><?= $row['remarks'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

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
