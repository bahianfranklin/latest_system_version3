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
     * 🔹 Pending Official Business Requests
     */
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
        FROM official_business AS ob
        INNER JOIN users AS u ON ob.applied_by = u.id
        INNER JOIN work_details AS wd ON u.id = wd.user_id
        INNER JOIN departments AS d ON d.department = wd.department
        INNER JOIN approver_assignments AS aa ON aa.department_id = d.id
        WHERE aa.user_id = ?
        AND ob.status = 'Pending'
        ORDER BY ob.datetime_applied DESC
    ";

    $stmt = $conn->prepare($sqlPending);
    $stmt->bind_param("i", $approver_id);
    $stmt->execute();
    $pending = $stmt->get_result();

    /**
     * 🔹 Approved Official Business Requests
     */
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
            ob.datetime_action
        FROM official_business ob
        JOIN users u ON ob.applied_by = u.id
        JOIN work_details wd ON u.id = wd.user_id
        JOIN departments d ON wd.department = d.department
        JOIN approver_assignments aa ON aa.department_id = d.id
        WHERE aa.user_id = ?
        AND ob.status = 'Approved'
        ORDER BY ob.datetime_action DESC
    ";

    $stmt2 = $conn->prepare($sqlApproved);
    $stmt2->bind_param("i", $approver_id);
    $stmt2->execute();
    $approved = $stmt2->get_result();

    /**
     * 🔹 Rejected Official Business Requests
     */
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
            ob.datetime_action
        FROM official_business ob
        JOIN users u ON ob.applied_by = u.id
        JOIN work_details wd ON u.id = wd.user_id
        JOIN departments d ON wd.department = d.department
        JOIN approver_assignments aa ON aa.department_id = d.id
        WHERE aa.user_id = ?
        AND ob.status = 'Rejected'
        ORDER BY ob.datetime_action DESC
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
        <h3>Official Business (PENDING | APPROVED | REJECTED)</h3>
    </div>

    <!-- Pending Requests -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning text-dark fw-bold">
            Pending Official Business Requests
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
                            <td><?= $row['application_no'] ?></td>
                            <td><?= $row['employee'] ?></td>
                            <td><?= $row['department'] ?></td>
                            <td><?= $row['ob_date'] ?></td>
                            <td><?= $row['from_time'] ?></td>
                            <td><?= $row['to_time'] ?></td>
                            <td><?= $row['purpose'] ?></td>
                            <td><?= $row['location'] ?></td>
                            <td><span class="badge bg-warning text-dark"><?= $row['status'] ?></span></td>
                            <td><?= $row['datetime_applied'] ?></td>
                            <td class="d-flex gap-1">
                                <form method="POST" action="update_official_business_status.php">
                                    <input type="hidden" name="application_no" value="<?= $row['application_no'] ?>">
                                    <input type="hidden" name="action" value="Approved">
                                    <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form method="POST" action="update_official_business_status.php">
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
            Approved Official Business Requests
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
                            <th>From</th>
                            <th>To</th>
                            <th>Purpose</th>
                            <th>Location</th>
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
            Rejected Official Business Requests
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
                            <th>From</th>
                            <th>To</th>
                            <th>Purpose</th>
                            <th>Location</th>
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
                            <td><?= htmlspecialchars($row['ob_date']) ?></td>
                            <td><?= htmlspecialchars($row['from_time']) ?></td>
                            <td><?= htmlspecialchars($row['to_time']) ?></td>
                            <td><?= htmlspecialchars($row['purpose']) ?></td>
                            <td><?= htmlspecialchars($row['location']) ?></td>
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

