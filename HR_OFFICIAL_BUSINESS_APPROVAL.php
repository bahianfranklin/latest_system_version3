<?php
session_start();
require 'db.php';
require 'autolock.php';

$approver_id = $_SESSION['user_id'] ?? null;
if (!$approver_id) {
    die("Not logged in");
}

$user_id = (int) $_SESSION['user_id'];

/* 🔹 Fetch logged-in HR user info */
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();
$user = $userResult->fetch_assoc();

/* ======================================================
   🔹 PENDING OFFICIAL BUSINESS (HR)
====================================================== */
$sqlPending = "
    SELECT 
        ob.application_no,
        u.name AS employee,
        d.department,
        ob.ob_date,
        ob.from_time,
        ob.to_time,
        ob.purpose,
        ob.location,
        ob.status,
        ob.datetime_applied
    FROM official_business ob
    INNER JOIN users u 
        ON ob.applied_by = u.id
    INNER JOIN work_details wd 
        ON wd.user_id = u.id
    INNER JOIN departments d 
        ON d.department = wd.department
    INNER JOIN hr_approver_assignments haa 
        ON haa.department_id = d.id
    WHERE haa.user_id = ?
      AND ob.status = 'Pending'
    ORDER BY ob.datetime_applied DESC
";

$stmtPending = $conn->prepare($sqlPending);
if (!$stmtPending) {
    die("SQL ERROR (Pending): " . $conn->error);
}
$stmtPending->bind_param("i", $approver_id);
$stmtPending->execute();
$pending = $stmtPending->get_result();

/* ======================================================
   🔹 APPROVED OFFICIAL BUSINESS (HR)
====================================================== */
$sqlApproved = "
SELECT 
    ob.application_no,
    u.name AS employee,
    d.department,
    ob.ob_date,
    ob.from_time,
    ob.to_time,
    ob.purpose,
    ob.location,
    ob.status,
    ob.datetime_action,
    ua.name AS approved_by
FROM official_business ob
INNER JOIN users u ON ob.applied_by = u.id
INNER JOIN work_details wd ON wd.user_id = u.id
INNER JOIN departments d ON d.department = wd.department
LEFT JOIN users ua ON ob.approved_by = ua.id
INNER JOIN hr_approver_assignments haa ON haa.department_id = d.id
WHERE haa.user_id = ?
  AND ob.status = 'Approved'
ORDER BY ob.datetime_action DESC
";

$stmtApproved = $conn->prepare($sqlApproved);
if (!$stmtApproved) {
    die("SQL ERROR (Approved): " . $conn->error);
}
$stmtApproved->bind_param("i", $approver_id);
$stmtApproved->execute();
$approved = $stmtApproved->get_result();

/* ======================================================
   🔹 REJECTED OFFICIAL BUSINESS (HR)
====================================================== */
$sqlRejected = "
SELECT 
    ob.application_no,
    u.name AS employee,
    d.department,
    ob.ob_date,
    ob.from_time,
    ob.to_time,
    ob.purpose,
    ob.location,
    ob.status,
    ob.datetime_action,
    ua.name AS rejected_by   -- reuse approved_by
FROM official_business ob
INNER JOIN users u ON ob.applied_by = u.id
INNER JOIN work_details wd ON wd.user_id = u.id
INNER JOIN departments d ON d.department = wd.department
LEFT JOIN users ua ON ob.approved_by = ua.id   -- same column
INNER JOIN hr_approver_assignments haa ON haa.department_id = d.id
WHERE haa.user_id = ?
  AND ob.status = 'Rejected'
ORDER BY ob.datetime_action DESC
";

$stmtRejected = $conn->prepare($sqlRejected);
if (!$stmtRejected) {
    die("SQL ERROR (Rejected): " . $conn->error);
}
$stmtRejected->bind_param("i", $approver_id);
$stmtRejected->execute();
$rejected = $stmtRejected->get_result();

?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
<main class="container-fluid px-4">
<br>

<h3>Official Business (HR Approval)</h3>

<!-- ===================== PENDING ===================== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-warning fw-bold">
        Pending Official Business Requests
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Application No</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Purpose</th>
                    <th>Location</th>
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
                    <td><?= $row['ob_date'] ?></td>
                    <td><?= $row['from_time'] ?></td>
                    <td><?= $row['to_time'] ?></td>
                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td><span class="badge bg-warning"><?= $row['status'] ?></span></td>
                    <td><?= $row['datetime_applied'] ?></td>
                    <td class="d-flex gap-1">
                        <form method="POST" action="UPDATE_OFFICIAL_BUSINESS_STATUS_HR.php">
                            <input type="hidden" name="application_no" value="<?= $row['application_no'] ?>">
                            <input type="hidden" name="action" value="Approved">
                            <button class="btn btn-success btn-sm">Approve</button>
                        </form>
                        <form method="POST" action="UPDATE_OFFICIAL_BUSINESS_STATUS_HR.php">
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
</div>

<!-- ===================== APPROVED ===================== -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white fw-bold">
        Approved Official Business Requests
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Application No</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Purpose</th>
                    <th>Location</th>
                    <th>Approved By</th>
                    <th>Status</th>
                    <th>Date Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $approved->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['application_no'] ?></td>
                    <td><?= $row['employee'] ?></td>
                    <td><?= $row['department'] ?></td>
                    <td><?= $row['ob_date'] ?></td>
                    <td><?= $row['from_time'] ?></td>
                    <td><?= $row['to_time'] ?></td>
                    <td><?= $row['purpose'] ?></td>
                    <td><?= $row['location'] ?></td>
                    <td><?= htmlspecialchars($row['approved_by']) ?></td>
                    <td><span class="badge bg-success"><?= $row['status'] ?></span></td>
                    <td><?= $row['datetime_action'] ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===================== REJECTED ===================== -->
<div class="card shadow-sm">
    <div class="card-header bg-danger text-white fw-bold">
        Rejected Official Business Requests
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Application No</th>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Purpose</th>
                    <th>Location</th>
                    <th>Rejected By</th>
                    <th>Status</th>
                    <th>Date Action</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $rejected->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['application_no'] ?></td>
                    <td><?= $row['employee'] ?></td>
                    <td><?= $row['department'] ?></td>
                    <td><?= $row['ob_date'] ?></td>
                    <td><?= $row['from_time'] ?></td>
                    <td><?= $row['to_time'] ?></td>
                    <td><?= $row['purpose'] ?></td>
                    <td><?= $row['location'] ?></td>
                    <td><?= htmlspecialchars($row['rejected_by']) ?></td>
                    <td><span class="badge bg-danger"><?= $row['status'] ?></span></td>
                    <td><?= $row['datetime_action'] ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
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