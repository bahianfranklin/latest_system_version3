<?php

    session_start();
    require 'db.php';
    require 'audit.php';

    // --- FILTER INPUTS ---
    $search = $_GET['search'] ?? '';
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';

    if (!isset($_SESSION['user'])) {
        header("Location: LOGIN.php");
        exit();
    }

    $user_id = $_SESSION['user']['id'];

    // Build description for audit
    $desc = "Filters -> Search: " . (!empty($search) ? $search : "None") .
            ", From: " . (!empty($from) ? $from : "None") .
            ", To: " . (!empty($to) ? $to : "None");

    // 🔥 Audit Trail Entry
    logAction(
        $conn,
        $user_id,
        "FILTER AUDIT LOGS",
        $desc
    );

    // --- SQL QUERY ---
    $sql = "SELECT a.*, u.name 
            FROM audit_logs a 
            LEFT JOIN users u ON a.user_id = u.id
            WHERE 1";


    // --- FILTER INPUTS ---
    $search = $_GET['search'] ?? '';
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';

    // --- SQL QUERY ---
    $sql = "SELECT a.*, u.name 
            FROM audit_logs a 
            LEFT JOIN users u ON a.user_id = u.id
            WHERE 1";

    // Search filter
    if (!empty($search)) {
        $sql .= " AND (
                    u.name LIKE ? OR
                    a.action LIKE ? OR
                    a.description LIKE ? OR
                    a.ip_address LIKE ? OR
                    a.user_agent LIKE ?
                )";
    }

    // Date range filter
    if (!empty($from)) {
        $sql .= " AND a.created_at >= ?";
    }
    if (!empty($to)) {
        $sql .= " AND a.created_at <= ?";
    }

    $sql .= " ORDER BY a.id DESC";

    $stmt = $conn->prepare($sql);

    $bindTypes = "";
    $params = [];

    // Search bind
    if (!empty($search)) {
        $s = "%$search%";
        $bindTypes .= "sssss";
        $params = array_merge($params, [$s, $s, $s, $s, $s]);
    }

    // From Date
    if (!empty($from)) {
        $bindTypes .= "s";
        $params[] = $from;
    }

    // To Date
    if (!empty($to)) {
        $bindTypes .= "s";
        $params[] = $to;
    }

    if (!empty($params)) {
        $stmt->bind_param($bindTypes, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
?>


<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main>
        <div class="container mt-3">
            <h3 class="mb-4">Audit Trail</h3>
        </div>

        <div class="container">
            <form method="GET" class="row g-3 mb-3">

                <!-- Search Bar -->
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" 
                        placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <!-- From Date -->
                <div class="col-md-3">
                    <input type="datetime-local" name="from" class="form-control"
                        value="<?= htmlspecialchars($from) ?>">
                </div>

                <!-- To Date -->
                <div class="col-md-3">
                    <input type="datetime-local" name="to" class="form-control"
                        value="<?= htmlspecialchars($to) ?>">
                </div>

                <!-- Submit Button -->
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>

        <table class="table table-bordered table-striped mt-3">
            <thead class="table-dark">
                <tr>
                    <th>User</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>IP Address</th>
                    <th>Device</th>
                    <th>Date Time</th>
                </tr>
            </thead>

            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['name'] ?></td>
                    <td><?= $row['action'] ?></td>
                    <td><?= $row['description'] ?></td>
                    <td><?= $row['ip_address'] ?></td>
                    <td><?= $row['user_agent'] ?></td>
                    <td><?= $row['created_at'] ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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