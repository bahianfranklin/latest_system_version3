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

    /**
     * 🔹 Pending Clock Alteration Requests
     */
    $sqlPending = "
        SELECT 
            ca.application_no,
            u.name AS employee,
            d.department,
            ca.date_original,
            ca.time_in_original,
            ca.time_out_original,
            ca.date_new,
            ca.time_in_new,
            ca.time_out_new,
            ca.reason,
            ca.status,
            ca.datetime_applied
        FROM clock_alteration AS ca
        INNER JOIN users AS u ON ca.applied_by = u.id
        INNER JOIN work_details AS wd ON u.id = wd.user_id
        INNER JOIN departments AS d ON d.department = wd.department
        INNER JOIN approver_assignments AS aa ON aa.department_id = d.id
        WHERE aa.user_id = ?
        AND ca.status = 'Pending'
        ORDER BY ca.datetime_applied DESC
    ";

    $stmt = $conn->prepare($sqlPending);
    $stmt->bind_param("i", $approver_id);
    $stmt->execute();
    $pending = $stmt->get_result();

    /**
     * 🔹 Approved Clock Alteration Requests
     */
    $sqlApproved = "
        SELECT 
            ca.application_no,
            u.name AS employee,
            d.department,
            ca.date_original,
            ca.time_in_original,
            ca.time_out_original,
            ca.date_new,
            ca.time_in_new,
            ca.time_out_new,
            ca.reason,
            ca.status,
            ca.datetime_action
        FROM clock_alteration ca
        JOIN users u ON ca.applied_by = u.id
        JOIN work_details wd ON u.id = wd.user_id
        JOIN departments d ON wd.department = d.department
        JOIN approver_assignments aa ON aa.department_id = d.id
        WHERE aa.user_id = ?
        AND ca.status = 'Approved'
        ORDER BY ca.datetime_action DESC
    ";

    $stmt2 = $conn->prepare($sqlApproved);
    $stmt2->bind_param("i", $approver_id);
    $stmt2->execute();
    $approved = $stmt2->get_result();

    /**
     * 🔹 Rejected Clock Alteration Requests
     */
    $sqlRejected = "
        SELECT 
            ca.application_no,
            u.name AS employee,
            d.department,
            ca.date_original,
            ca.time_in_original,
            ca.time_out_original,
            ca.date_new,
            ca.time_in_new,
            ca.time_out_new,
            ca.reason,
            ca.status,
            ca.datetime_action
        FROM clock_alteration ca
        JOIN users u ON ca.applied_by = u.id
        JOIN work_details wd ON u.id = wd.user_id
        JOIN departments d ON wd.department = d.department
        JOIN approver_assignments aa ON aa.department_id = d.id
        WHERE aa.user_id = ?
        AND ca.status = 'Rejected'
        ORDER BY ca.datetime_action DESC
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
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Clock Alteration (PENDING | APPROVED | REJECTED)</h3>
    </div>

    <!-- Pending Requests -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning text-dark fw-bold">
            Pending Clock Alteration Requests
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Application No</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Date (Original)</th>
                            <th>Time In (Original)</th>
                            <th>Time Out (Original)</th>
                            <th>Date (New)</th>
                            <th>Time In (New)</th>
                            <th>Time Out (New)</th>
                            <th>Reason</th>
                            <th>Status</th>
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
                            <td><?= $row['date_original'] ?></td>
                            <td><?= $row['time_in_original'] ?></td>
                            <td><?= $row['time_out_original'] ?></td>
                            <td><?= $row['date_new'] ?></td>
                            <td><?= $row['time_in_new'] ?></td>
                            <td><?= $row['time_out_new'] ?></td>
                            <td><?= $row['reason'] ?></td>
                            <td><span class="badge bg-warning text-dark"><?= $row['status'] ?></span></td>
                            <td><?= $row['datetime_applied'] ?></td>
                            <td class="d-flex gap-1">
                                <form method="POST" action="update_clock_alteration_status.php">
                                    <input type="hidden" name="application_no" value="<?= $row['application_no'] ?>">
                                    <input type="hidden" name="action" value="Approved">
                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form method="POST" action="update_clock_alteration_status.php">
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
            Approved Clock Alteration Requests
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Application No</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Date (Original)</th>
                            <th>Time In (Original)</th>
                            <th>Time Out (Original)</th>
                            <th>Date (New)</th>
                            <th>Time In (New)</th>
                            <th>Time Out (New)</th>
                            <th>Reason</th>
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
                            <td><?= $row['date_original'] ?></td>
                            <td><?= $row['time_in_original'] ?></td>
                            <td><?= $row['time_out_original'] ?></td>
                            <td><?= $row['date_new'] ?></td>
                            <td><?= $row['time_in_new'] ?></td>
                            <td><?= $row['time_out_new'] ?></td>
                            <td><?= $row['reason'] ?></td>
                            <td><span class="badge bg-success"><?= $row['status'] ?></span></td>
                            <td><?= $row['datetime_action'] ?></td>
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
            Rejected Clock Alteration Requests
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Application No</th>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Date (Original)</th>
                            <th>Time In (Original)</th>
                            <th>Time Out (Original)</th>
                            <th>Date (New)</th>
                            <th>Time In (New)</th>
                            <th>Time Out (New)</th>
                            <th>Reason</th>
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
                            <td><?= $row['date_original'] ?></td>
                            <td><?= $row['time_in_original'] ?></td>
                            <td><?= $row['time_out_original'] ?></td>
                            <td><?= $row['date_new'] ?></td>
                            <td><?= $row['time_in_new'] ?></td>
                            <td><?= $row['time_out_new'] ?></td>
                            <td><?= $row['reason'] ?></td>
                            <td><span class="badge bg-danger"><?= $row['status'] ?></span></td>
                            <td><?= $row['datetime_action'] ?></td>
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

