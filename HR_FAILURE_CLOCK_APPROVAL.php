<?php
session_start();
require 'db.php';
require 'autolock.php';

$hr_id = $_SESSION['user_id'] ?? null;
if (!$hr_id) {
    die("Not logged in");
}

$user_id = (int) $_SESSION['user_id'];

/* ===============================
   CURRENT USER
================================ */
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* ===============================
   PENDING
================================ */

$sqlPending = "
SELECT
    fc.application_no,
    u.name AS employee,
    d.department,
    fc.date,
    fc.type,
    fc.time_in,
    fc.time_out,
    fc.reason,
    fc.status,
    fc.datetime_applied
FROM failure_clock fc
JOIN users u ON fc.applied_by = u.id
JOIN work_details wd ON u.id = wd.user_id
JOIN departments d ON wd.department = d.department
JOIN hr_approver_assignments ha ON ha.department_id = d.id
WHERE ha.user_id = ?
AND fc.status = 'Pending'
ORDER BY fc.datetime_applied DESC
";

$stmt = $conn->prepare($sqlPending);
$stmt->bind_param("i", $hr_id);
$stmt->execute();
$pending = $stmt->get_result();

/* ===============================
   APPROVED
================================ */

$sqlApproved = "
SELECT
    fc.application_no,
    u.name AS employee,
    d.department,
    fc.date,
    fc.type,
    fc.time_in,
    fc.time_out,
    fc.reason,
    fc.status,
    fc.datetime_action,
    ap.name AS approver_name
FROM failure_clock fc
JOIN users u ON fc.applied_by = u.id
JOIN work_details wd ON u.id = wd.user_id
JOIN departments d ON wd.department = d.department
JOIN hr_approver_assignments ha ON ha.department_id = d.id
LEFT JOIN users ap ON fc.approver_id = ap.id
WHERE ha.user_id = ?
AND fc.status = 'Approved'
ORDER BY fc.datetime_action DESC
";

$stmt2 = $conn->prepare($sqlApproved);
$stmt2->bind_param("i", $hr_id);
$stmt2->execute();
$approved = $stmt2->get_result();

/* ===============================
   REJECTED
================================ */

$sqlRejected = "
SELECT
    fc.application_no,
    u.name AS employee,
    d.department,
    fc.date,
    fc.type,
    fc.time_in,
    fc.time_out,
    fc.reason,
    fc.status,
    fc.datetime_action,
    ap.name AS approver_name
FROM failure_clock fc
JOIN users u ON fc.applied_by = u.id
JOIN work_details wd ON u.id = wd.user_id
JOIN departments d ON wd.department = d.department
JOIN hr_approver_assignments ha ON ha.department_id = d.id
LEFT JOIN users ap ON fc.approver_id = ap.id
WHERE ha.user_id = ?
AND fc.status = 'Rejected'
ORDER BY fc.datetime_action DESC
";

$stmt3 = $conn->prepare($sqlRejected);
$stmt3->bind_param("i", $hr_id);
$stmt3->execute();
$rejected = $stmt3->get_result();

?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4">

        <h3 class="mt-3">Failure Clock Approval</h3>

        <!-- ================= PENDING ================= -->

        <div class="card shadow mb-4">
            <div class="card-header bg-warning fw-bold">Pending</div>

            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Application</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Applied</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = $pending->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['application_no'] ?></td>
                            <td><?= $row['employee'] ?></td>
                            <td><?= $row['department'] ?></td>
                            <td><?= $row['date'] ?></td>
                            <td><?= $row['type'] ?></td>
                            <td><?= $row['time_in'] ?></td>
                            <td><?= $row['time_out'] ?></td>
                            <td><?= $row['reason'] ?></td>
                            <td><span class="badge bg-warning"><?= $row['status'] ?></span></td>
                            <td><?= $row['datetime_applied'] ?></td>

                            <td class="d-flex gap-1">

                                <form action="UPDATE_FAILURE_CLOCK_STATUS_HR.php" method="POST">
                                    <input type="hidden" name="application_no" value="<?= $row['application_no'] ?>">
                                    <input type="hidden" name="action" value="Approved">
                                    <button class="btn btn-success btn-sm">Approve</button>
                                </form>

                                <form action="UPDATE_FAILURE_CLOCK_STATUS_HR.php" method="POST">
                                    <input type="hidden" name="application_no" value="<?= $row['application_no'] ?>">
                                    <input type="hidden" name="action" value="Rejected">
                                    <button class="btn btn-danger btn-sm">Reject</button>
                                </form>

                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- ================= APPROVED ================= -->

        <div class="card shadow mb-4">
            <div class="card-header bg-success text-white fw-bold">Approved</div>

            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Application</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Reason</th>
                        <th>Approved By</th>
                        <th>Status</th>
                        <th>Action Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = $approved->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['application_no'] ?></td>
                            <td><?= $row['employee'] ?></td>
                            <td><?= $row['department'] ?></td>
                            <td><?= $row['date'] ?></td>
                            <td><?= $row['type'] ?></td>
                            <td><?= $row['time_in'] ?></td>
                            <td><?= $row['time_out'] ?></td>
                            <td><?= $row['reason'] ?></td>
                            <td><?= $row['approver_name'] ?? '-' ?></td>
                            <td><span class="badge bg-success"><?= $row['status'] ?></span></td>
                            <td><?= $row['datetime_action'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- ================= REJECTED ================= -->

        <div class="card shadow mb-4">
            <div class="card-header bg-danger text-white fw-bold">Rejected</div>

            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Application</th>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Reason</th>
                        <th>Rejected By</th>
                        <th>Status</th>
                        <th>Action Date</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = $rejected->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['application_no'] ?></td>
                            <td><?= $row['employee'] ?></td>
                            <td><?= $row['department'] ?></td>
                            <td><?= $row['date'] ?></td>
                            <td><?= $row['type'] ?></td>
                            <td><?= $row['time_in'] ?></td>
                            <td><?= $row['time_out'] ?></td>
                            <td><?= $row['reason'] ?></td>
                            <td><?= $row['approver_name'] ?? '-' ?></td>
                            <td><span class="badge bg-danger"><?= $row['status'] ?></span></td>
                            <td><?= $row['datetime_action'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </main>
    <?php include __DIR__ . '/layout/FOOTER.php'; ?>
</div>