<?php
session_start();
require 'db.php';
require 'autolock.php';
require 'audit.php';

// ✅ Ensure user is logged in
$approver_id = $_SESSION['user_id'] ?? null;
if (!$approver_id) {
    die("Not logged in");
}

// Fetch current user info for profile display
$user_id = (int) $approver_id;
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

/**
 * 🔹 Pending Change Schedule Requests
 */
$sqlPending = "
SELECT 
    cs.application_no,
    u.name AS employee,
    wd.department,
    cs.date,
    cs.remarks,
    cs.total_hours,
    cs.status,
    cs.datetime_applied
FROM change_schedule cs
JOIN users u ON cs.applied_by = u.id
JOIN work_details wd ON wd.user_id = u.id
WHERE cs.status = 'Pending'
ORDER BY cs.datetime_applied DESC
";

$stmt = $conn->prepare($sqlPending);
$stmt->execute();
$pending = $stmt->get_result();


/**
 * 🔹 Approved Change Schedule Requests
 */
$sqlApproved = "
SELECT 
    cs.application_no,
    u.name AS employee,
    wd.department,
    cs.date,
    cs.remarks,
    cs.total_hours,
    cs.status,
    cs.datetime_action
FROM change_schedule cs
JOIN users u ON cs.applied_by = u.id
JOIN work_details wd ON wd.user_id = u.id
JOIN hr_approver_assignments aa 
    ON aa.department_id = u.department_id
WHERE cs.status = 'Approved'
AND aa.user_id = ?
ORDER BY cs.datetime_action DESC
";

$stmt2 = $conn->prepare($sqlApproved);
$stmt2->bind_param("i", $approver_id);
$stmt2->execute();
$approved = $stmt2->get_result();

/**
 * 🔹 Rejected Change Schedule Requests
 */
$sqlRejected = "
SELECT 
    cs.application_no,
    u.name AS employee,
    wd.department,
    cs.date,
    cs.remarks,
    cs.total_hours,
    cs.status,
    cs.datetime_action
FROM change_schedule cs
JOIN users u ON cs.applied_by = u.id
JOIN work_details wd ON wd.user_id = u.id
JOIN hr_approver_assignments aa 
    ON aa.department_id = u.department_id
WHERE cs.status = 'Rejected'
AND aa.user_id = ?
ORDER BY cs.datetime_action DESC
";

$stmt3 = $conn->prepare($sqlRejected);
$stmt3->bind_param("i", $approver_id);
$stmt3->execute();
$rejected = $stmt3->get_result();

?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4">
        <br>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Change Schedule (PENDING | APPROVED | REJECTED)</h3>
        </div>

        <!-- Pending Requests -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-warning text-dark fw-bold">
                Pending Change Schedule Requests
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Application No</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Date</th>
                                <th>Remarks</th>
                                <th>Total Hours</th>
                                <th>Status</th>
                                <th>Date Applied</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $pending->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['application_no']) ?></td>
                                <td><?= htmlspecialchars($row['employee']) ?></td>
                                <td><?= htmlspecialchars($row['department']) ?></td>
                                <td><?= htmlspecialchars($row['date']) ?></td>
                                <td><?= htmlspecialchars($row['remarks']) ?></td>
                                <td><?= htmlspecialchars($row['total_hours']) ?></td>
                                <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><?= htmlspecialchars($row['datetime_applied']) ?></td>
                                <td class="d-flex gap-1">
                                    <form method="POST" action="UPDATE_CHANGE_SCHEDULE_HR.php">
                                        <input type="hidden" name="application_no" value="<?= htmlspecialchars($row['application_no']) ?>">
                                        <input type="hidden" name="action" value="Approved">
                                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                    </form>
                                    <form method="POST" action="UPDATE_CHANGE_SCHEDULE_HR.php">
                                        <input type="hidden" name="application_no" value="<?= htmlspecialchars($row['application_no']) ?>">
                                        <input type="hidden" name="action" value="Rejected">
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
                Approved Change Schedule Requests
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Application No</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Date</th>
                                <th>Remarks</th>
                                <th>Total Hours</th>
                                <th>Status</th>
                                <th>Date Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $approved->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['application_no']) ?></td>
                                <td><?= htmlspecialchars($row['employee']) ?></td>
                                <td><?= htmlspecialchars($row['department']) ?></td>
                                <td><?= htmlspecialchars($row['date']) ?></td>
                                <td><?= htmlspecialchars($row['remarks']) ?></td>
                                <td><?= htmlspecialchars($row['total_hours']) ?></td>
                                <td><span class="badge bg-success"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><?= htmlspecialchars($row['datetime_action']) ?></td>
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
                Rejected Change Schedule Requests
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Application No</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Date</th>
                                <th>Remarks</th>
                                <th>Total Hours</th>
                                <th>Status</th>
                                <th>Date Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $rejected->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['application_no']) ?></td>
                                <td><?= htmlspecialchars($row['employee']) ?></td>
                                <td><?= htmlspecialchars($row['department']) ?></td>
                                <td><?= htmlspecialchars($row['date']) ?></td>
                                <td><?= htmlspecialchars($row['remarks']) ?></td>
                                <td><?= htmlspecialchars($row['total_hours']) ?></td>
                                <td><span class="badge bg-danger"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><?= htmlspecialchars($row['datetime_action']) ?></td>
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
    const sidebarToggle = document.querySelector("#sidebarToggle");
    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", function (e) {
            e.preventDefault();
            document.body.classList.toggle("sb-sidenav-toggled");
        });
    }
});
</script>
