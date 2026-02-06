<?php
session_start();
require 'db.php';
require 'autolock.php';

$approver_id = $_SESSION['user_id'] ?? null;
if (!$approver_id)
    die("Not logged in");

/* ================= USER PROFILE ================= */
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $approver_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

/* ================= PENDING ================= */
$sqlPending = "
SELECT wr.*,u.name employee,d.department
FROM work_restday wr
JOIN users u ON wr.applied_by=u.id
JOIN work_details wd ON u.id=wd.user_id
JOIN departments d ON wd.department=d.department
JOIN hr_approver_assignments ha ON ha.department_id=d.id
WHERE ha.user_id=? AND wr.status='Pending'
ORDER BY wr.datetime_applied DESC
";
$stmt = $conn->prepare($sqlPending);
$stmt->bind_param("i", $approver_id);
$stmt->execute();
$pending = $stmt->get_result();

/* ================= APPROVED ================= */
$sqlApproved = "
SELECT wr.*,u.name employee,d.department,ap.name approver_name
FROM work_restday wr
JOIN users u ON wr.applied_by=u.id
JOIN work_details wd ON u.id=wd.user_id
JOIN departments d ON wd.department=d.department
JOIN hr_approver_assignments ha ON ha.department_id=d.id
LEFT JOIN users ap ON wr.approver_id=ap.id
WHERE ha.user_id=? AND wr.status='Approved'
ORDER BY wr.datetime_action DESC
";
$stmt = $conn->prepare($sqlApproved);
$stmt->bind_param("i", $approver_id);
$stmt->execute();
$approved = $stmt->get_result();

/* ================= REJECTED ================= */
$sqlRejected = "
SELECT wr.*,u.name employee,d.department,ap.name approver_name
FROM work_restday wr
JOIN users u ON wr.applied_by=u.id
JOIN work_details wd ON u.id=wd.user_id
JOIN departments d ON wd.department=d.department
JOIN hr_approver_assignments ha ON ha.department_id=d.id
LEFT JOIN users ap ON wr.approver_id=ap.id
WHERE ha.user_id=? AND wr.status='Rejected'
ORDER BY wr.datetime_action DESC
";
$stmt = $conn->prepare($sqlRejected);
$stmt->bind_param("i", $approver_id);
$stmt->execute();
$rejected = $stmt->get_result();

include 'layout/HEADER';
include 'layout/NAVIGATION';
?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4">

        <h3 class="mt-3">HR Work Restday Approval</h3>

        <!-- ================= PENDING ================= -->
        <div class="card shadow mb-4">
            <div class="card-header bg-warning fw-bold">Pending</div>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>App No</th>
                        <th>Employee</th>
                        <th>Dept</th>
                        <th>Date</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Applied</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $pending->fetch_assoc()): ?>
                        <tr>
                            <td><?= $r['application_no'] ?></td>
                            <td><?= $r['employee'] ?></td>
                            <td><?= $r['department'] ?></td>
                            <td><?= $r['date'] ?></td>
                            <td><?= $r['from_time'] ?></td>
                            <td><?= $r['to_time'] ?></td>
                            <td><?= $r['work_schedule'] ?></td>
                            <td><span class="badge bg-warning"><?= $r['status'] ?></span></td>
                            <td><?= $r['datetime_applied'] ?></td>
                            <td class="d-flex gap-1">
                                <form method="POST" action="UPDATE_WORK_RESTDAY_STATUS_HR.php">
                                    <input type="hidden" name="application_no" value="<?= $r['application_no'] ?>">
                                    <input type="hidden" name="action" value="Approved">
                                    <button class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form method="POST" action="UPDATE_WORK_RESTDAY_STATUS_HR.php">
                                    <input type="hidden" name="application_no" value="<?= $r['application_no'] ?>">
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
            <div class="card-header bg-success text-white">Approved</div>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>App</th>
                        <th>Employee</th>
                        <th>Dept</th>
                        <th>Date</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Schedule</th>
                        <th>Approved By</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $approved->fetch_assoc()): ?>
                        <tr>
                            <td><?= $r['application_no'] ?></td>
                            <td><?= $r['employee'] ?></td>
                            <td><?= $r['department'] ?></td>
                            <td><?= $r['date'] ?></td>
                            <td><?= $r['from_time'] ?></td>
                            <td><?= $r['to_time'] ?></td>
                            <td><?= $r['work_schedule'] ?></td>
                            <td><?= $r['approver_name'] ?></td>
                            <td><span class="badge bg-success">Approved</span></td>
                            <td><?= $r['datetime_action'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- ================= REJECTED ================= -->
        <div class="card shadow">
            <div class="card-header bg-danger text-white">Rejected</div>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>App</th>
                        <th>Employee</th>
                        <th>Dept</th>
                        <th>Date</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Schedule</th>
                        <th>Rejected By</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $rejected->fetch_assoc()): ?>
                        <tr>
                            <td><?= $r['application_no'] ?></td>
                            <td><?= $r['employee'] ?></td>
                            <td><?= $r['department'] ?></td>
                            <td><?= $r['date'] ?></td>
                            <td><?= $r['from_time'] ?></td>
                            <td><?= $r['to_time'] ?></td>
                            <td><?= $r['work_schedule'] ?></td>
                            <td><?= $r['approver_name'] ?></td>
                            <td><span class="badge bg-danger">Rejected</span></td>
                            <td><?= $r['datetime_action'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

    </main>
    <?php include 'layout/FOOTER'; ?>
</div>