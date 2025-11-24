<?php
    session_start();
    require 'db.php';
    require 'autolock.php';

    $approver_id = $_SESSION['user_id'] ?? null;
    if (!$approver_id) {
        die("Not logged in");
    }

    $user_id = (int) $_SESSION['user_id'];

    // Fetch current user info for profile display
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $user = $userResult->fetch_assoc();

    // 🔹 Pending requests
    // Improved: Readable SQL, consistent aliases, and comments
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
        FROM leave_requests AS lr
        INNER JOIN users AS u ON lr.user_id = u.id
        INNER JOIN work_details AS wd ON u.id = wd.user_id
        INNER JOIN departments AS d ON d.department = wd.department
        INNER JOIN approver_assignments AS aa ON aa.department_id = d.id
        WHERE aa.user_id = ?
          AND lr.status = 'Pending'
        ORDER BY lr.date_applied DESC
    ";

    $stmt = $conn->prepare($sqlPending);
    $stmt->bind_param("i", $approver_id);
    $stmt->execute();
    $pending = $stmt->get_result();

    // 🔹 Approved requests
    $sqlApproved = "
        SELECT lr.application_no, u.name AS employee, d.department, 
            lr.leave_type, lr.date_from, lr.date_to, lr.status, lr.date_action, lr.remarks
        FROM leave_requests lr
        JOIN users u ON lr.user_id = u.id
        JOIN work_details wd ON u.id = wd.user_id
        JOIN departments d ON wd.department = d.department
        JOIN approver_assignments aa ON aa.department_id = d.id
        WHERE aa.user_id = ?
        AND lr.status = 'Approved'
        ORDER BY lr.date_action DESC";

    $stmt2 = $conn->prepare($sqlApproved);
    $stmt2->bind_param("i", $approver_id);
    $stmt2->execute();
    $approved = $stmt2->get_result();

    // 🔹 Rejected requests
    $sqlRejected = "
        SELECT lr.application_no, u.name AS employee, d.department, 
            lr.leave_type, lr.date_from, lr.date_to, lr.status, lr.date_action, lr.remarks
        FROM leave_requests lr
        JOIN users u ON lr.user_id = u.id
        JOIN work_details wd ON u.id = wd.user_id
        JOIN departments d ON wd.department = d.department
        JOIN approver_assignments aa ON aa.department_id = d.id
        WHERE aa.user_id = ?
        AND lr.status = 'Rejected'
        ORDER BY lr.date_action DESC";

    $stmt3 = $conn->prepare($sqlRejected);
    $stmt3->bind_param("i", $approver_id);
    $stmt3->execute();
    $rejected = $stmt3->get_result();

    // Count pending leave requests
    $sqlPendingCount = "
        SELECT COUNT(*) AS pending_count
        FROM leave_requests AS lr
        INNER JOIN users AS u ON lr.user_id = u.id
        INNER JOIN work_details AS wd ON u.id = wd.user_id
        INNER JOIN departments AS d ON d.department = wd.department
        INNER JOIN approver_assignments AS aa ON aa.department_id = d.id
        WHERE aa.user_id = ?
        AND lr.status = 'Pending'
    ";

    $stmtCount = $conn->prepare($sqlPendingCount);
    $stmtCount->bind_param("i", $approver_id);
    $stmtCount->execute();
    $countResult = $stmtCount->get_result();
    $countRow = $countResult->fetch_assoc();
    $pendingCount = $countRow['pending_count'];

?>
        <?php include __DIR__ . '/layout/HEADER'; ?>
        <?php include __DIR__ . '/layout/NAVIGATION'; ?>

        <div id="layoutSidenav_content">
            <main class="container-fluid px-4">
            <br>
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Leaves (PENDING: <?= $pendingCount ?> | APPROVED | REJECTED)</h3>
            </div>

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
                                        <form method="POST" action="UPDATE_LEAVE_STATUS.php">
                                            <input type="hidden" name="application_no" value="<?= $row['application_no'] ?>">
                                            <input type="hidden" name="action" value="Approved">
                                            <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                        </form>
                                        <form method="POST" action="UPDATE_LEAVE_STATUS.php">
                                            <input type="hidden" name="application_no" value="<?= $row['application_no'] ?>">
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
            </br>
            <?php include __DIR__ . '/layout/FOOTER'; ?>
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
        
        
        
        

                       
