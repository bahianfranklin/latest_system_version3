<?php
   if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/rbac.php';
    require_once __DIR__ . '/audit.php';
    require_once __DIR__ . '/permissions.php';
    require_once __DIR__ . '/autolock.php';

    /* 🔐 RBAC GUARD */
    if (!canView('directory')) {
        http_response_code(403);
        exit('Access Denied');
    }

    if (!isset($_SESSION['user_id'])) {
        die("Please login first.");
    }

    $user_id = $_SESSION['user_id'];

    /** ========== ADD ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $ob_date = $_POST['ob_date'];
        $from_time = $_POST['from_time'];
        $to_time = $_POST['to_time'];
        $purpose = $_POST['purpose'];
        $location = $_POST['location'];

        $today = date("Ymd");
        $res = $conn->query("SELECT COUNT(*) as total FROM official_business WHERE DATE(datetime_applied)=CURDATE()");
        $row = $res->fetch_assoc();
        $countToday = $row['total'] + 1;
        $appNo = "OB-" . $today . "-" . str_pad($countToday, 2, "0", STR_PAD_LEFT);

        $stmt = $conn->prepare("INSERT INTO official_business (application_no, ob_date, from_time, to_time, purpose, location, applied_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $appNo, $ob_date, $from_time, $to_time, $purpose, $location, $user_id);
        $stmt->execute();

        // 🔥 AUDIT TRAIL FOR ADDING OFFICIAL BUSINESS
        logAction($conn, $user_id, "ADD OFFICIAL BUSINESS", "AppNo: $appNo | Date: $ob_date | $from_time-$to_time | $purpose | $location");
    }

    /** ========== EDIT ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $ob_date = $_POST['ob_date'];
        $from_time = $_POST['from_time'];
        $to_time = $_POST['to_time'];
        $purpose = $_POST['purpose'];
        $location = $_POST['location'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE official_business 
            SET ob_date=?, from_time=?, to_time=?, purpose=?, location=?, status=?, datetime_updated=NOW() 
            WHERE id=?");
        $stmt->bind_param("ssssssi", $ob_date, $from_time, $to_time, $purpose, $location, $status, $id);
        $stmt->execute();

        // 🔥 AUDIT TRAIL FOR EDITING OFFICIAL BUSINESS
        logAction($conn, $user_id, "EDIT OFFICIAL BUSINESS", "ID: $id | Status: $status | Date: $ob_date | $from_time-$to_time | $purpose | $location");
    }

    /** ========== DELETE ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];

        // 1️⃣ Fetch details before deleting
        $stmtInfo = $conn->prepare("
            SELECT application_no, ob_date, from_time, to_time, purpose, location
            FROM official_business WHERE id=?
        ");
        $stmtInfo->bind_param("i", $id);
        $stmtInfo->execute();
        $resInfo = $stmtInfo->get_result();
        $data = $resInfo->fetch_assoc();

        if ($data) {
            $details = "AppNo: {$data['application_no']} | Date: {$data['ob_date']} | "
                    . "{$data['from_time']}-{$data['to_time']} | {$data['purpose']} | {$data['location']}";
        } else {
            $details = "OB ID: $id (Record not found before delete)";
        }

        // 2️⃣ Perform deletion
        $stmt = $conn->prepare("DELETE FROM official_business WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // 3️⃣ Audit log
        logAction($conn, $user_id, "DELETE OFFICIAL BUSINESS", $details);
    }

    /** ========== FETCH ========= */
    $where = ["ob.applied_by = $user_id"];

    // Date Range
    if (!empty($_GET['date_range'])) {
        $dates = explode(" to ", $_GET['date_range']);
        if (count($dates) == 2 && !empty($dates[0]) && !empty($dates[1])) {
           $from = date("Y-m-d", strtotime($dates[0]));
           $to   = date("Y-m-d", strtotime($dates[1]));
           $where[] = "ob.ob_date BETWEEN '$from' AND '$to'";
        }
    }

    // Status
    if (!empty($_GET['status'])) {
        $status = $conn->real_escape_string($_GET['status']);
        $where[] = "ob.status = '$status'";
    }

    $whereSql = (count($where) > 0) ? "WHERE " . implode(" AND ", $where) : "";

    $sql = "SELECT ob.*, u.username 
            FROM official_business ob 
            JOIN users u ON ob.applied_by=u.id 
            $whereSql 
            ORDER BY ob.datetime_applied DESC";

    $result = $conn->query($sql);

    $filter_details = "Filters: ";

    if (!empty($_GET['date_range'])) {
        $filter_details .= "Date Range: {$_GET['date_range']}; ";
    }

    if (!empty($_GET['status'])) {
        $filter_details .= "Status: {$_GET['status']}; ";
    }

    if ($filter_details == "Filters: ") {
        $filter_details .= "None";
    }

    // Audit Trail for filtering official business
    logAction($conn, $user_id, "FILTER OFFICIAL BUSINESS", $filter_details);
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4">
        <br>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Official Business Requests</h3>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ Apply Official Business</button>
        </div>

        <!-- Filter Form -->
        <div class="card mb-3 p-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Date Coverage</label>
                    <input type="text" class="form-control dateRangePicker" name="date_range"
                        value="<?= isset($_GET['date_range']) ? htmlspecialchars($_GET['date_range']) : '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All</option>
                        <option value="Pending" <?= (isset($_GET['status']) && $_GET['status']=="Pending")?"selected":"" ?>>Pending</option>
                        <option value="Approved" <?= (isset($_GET['status']) && $_GET['status']=="Approved")?"selected":"" ?>>Approved</option>
                        <option value="Rejected" <?= (isset($_GET['status']) && $_GET['status']=="Rejected")?"selected":"" ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100">Filter</button>
                </div>
            </form>
        </div>

        <script>
            flatpickr(".dateRangePicker", {
                mode: "range",
                dateFormat: "Y-m-d",
                enableTime: false,
                onClose: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        const from = instance.formatDate(selectedDates[0], "Y-m-d");
                        const to   = instance.formatDate(selectedDates[1], "Y-m-d");
                        instance.input.value = `${from} to ${to}`;
                    }
                }
            });
        </script>

        <!-- Table -->
        <table class="table table-bordered table-hover bg-white">
            <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Application No</th>
                <th>User</th>
                <th>OB Date</th>
                <th>From</th>
                <th>To</th>
                <th>Purpose</th>
                <th>Location</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php 
            if ($result && $result->num_rows > 0): 
                $i=1; 
                while($row=$result->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['application_no']) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['ob_date']) ?></td>
                    <td><?= date("h:i A", strtotime($row['from_time'])) ?></td>
                    <td><?= date("h:i A", strtotime($row['to_time'])) ?></td>
                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td>
                        <span class="badge <?= $row['status']=="Approved"?"bg-success":($row['status']=="Rejected"?"bg-danger":"bg-warning") ?>">
                            <?= $row['status'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($row['status'] === 'Pending'): ?>
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">Edit</button>
                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>">Delete</button>
                        <?php else: ?>
                            <span class="text-muted">WITH UPDATED STATUS</span>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Edit Modal -->
                <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title">Edit Official Business</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label>OB Date</label>
                                    <input type="date" name="ob_date" value="<?= $row['ob_date'] ?>" class="form-control mb-2" required>
                                    <label>From Time</label>
                                    <input type="time" name="from_time" value="<?= $row['from_time'] ?>" class="form-control mb-2" required>
                                    <label>To Time</label>
                                    <input type="time" name="to_time" value="<?= $row['to_time'] ?>" class="form-control mb-2" required>
                                    <label>Purpose</label>
                                    <textarea name="purpose" class="form-control mb-2"><?= $row['purpose'] ?></textarea>
                                    <label>Location</label>
                                    <input type="text" name="location" value="<?= $row['location'] ?>" class="form-control mb-2" required>
                                    <label>Status</label>
                                    <select name="status" class="form-select">
                                        <option value="Pending" <?= $row['status']=="Pending"?"selected":"" ?>>Pending</option>
                                        <option value="Approved" <?= $row['status']=="Approved"?"selected":"" ?>>Approved</option>
                                        <option value="Rejected" <?= $row['status']=="Rejected"?"selected":"" ?>>Rejected</option>
                                    </select>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Update</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Delete Official Business</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    Are you sure to delete <strong><?= $row['application_no'] ?></strong>?
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            <?php endwhile; else: ?>
                <tr><td colspan="10" class="text-center text-muted">No records found</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </main>
    <?php include __DIR__ . '/layout/FOOTER.php'; ?>
</div>

                <!-- Add Modal -->
                <div class="modal fade" id="addModal" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="modal-content">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title">Apply Official Business</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label>OB Date</label>
                                    <input type="date" name="ob_date" class="form-control mb-2" required>
                                    <label>From Time</label>
                                    <input type="time" name="from_time" class="form-control mb-2" required>
                                    <label>To Time</label>
                                    <input type="time" name="to_time" class="form-control mb-2" required>
                                    <label>Purpose</label>
                                    <textarea name="purpose" class="form-control mb-2" required></textarea>
                                    <label>Location</label>
                                    <input type="text" name="location" class="form-control mb-2" required>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-success">Submit</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>


        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script>
            flatpickr(".dateRangePicker", {
                mode: "range",
                dateFormat: "Y-m-d",
                onChange: function (selectedDates, dateStr, instance) {
                    // optionally, if you want to fill hidden inputs or something
                }
            });
        </script>
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
    </body>
</html>
