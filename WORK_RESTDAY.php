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

    // 🚫 Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        die("Please login first.");
    }

    $user_id = $_SESSION['user_id'];

    /** ========== ADD ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $date          = $_POST['date'];
        $from_time     = $_POST['from_time'];
        $to_time       = $_POST['to_time'];
        $purpose       = $_POST['purpose'];
        $work_schedule = $_POST['work_schedule'];

        // ✅ Check duplication
        $dupCheck = $conn->prepare("SELECT id FROM work_restday WHERE applied_by = ? AND date = ?");
        $dupCheck->bind_param("is", $user_id, $date);
        $dupCheck->execute();
        $dupCheck->store_result();

        if ($dupCheck->num_rows > 0) {
            echo "<script>alert('⚠ You already filed for this date!');</script>";
        } else {
            // continue with insert...
            $today = date("Ymd");
            $res = $conn->query("SELECT COUNT(*) as total FROM work_restday WHERE DATE(datetime_applied)=CURDATE()");
            $row = $res->fetch_assoc();
            $countToday = $row['total'] + 1;
            $appNo = "WRD-" . $today . "-" . str_pad($countToday, 2, "0", STR_PAD_LEFT);

            $stmt = $conn->prepare("INSERT INTO work_restday 
                (application_no, date, from_time, to_time, purpose, work_schedule, applied_by, datetime_applied) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssssi", $appNo, $date, $from_time, $to_time, $purpose, $work_schedule, $user_id);
            $stmt->execute();

            echo "<script>alert('✅ Request submitted successfully!');</script>";
        }
    }

    /** ========== EDIT ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id            = $_POST['id'];
        $date          = $_POST['date'];
        $from_time     = $_POST['from_time'];
        $to_time       = $_POST['to_time'];
        $purpose       = $_POST['purpose'];
        $work_schedule = $_POST['work_schedule'];
        $status        = $_POST['status'];

        // ✅ Check duplicates excluding current record
        $dupCheck = $conn->prepare("SELECT id FROM work_restday WHERE applied_by = ? AND date = ? AND id != ?");
        $dupCheck->bind_param("isi", $user_id, $date, $id);
        $dupCheck->execute();
        $dupCheck->store_result();

        if ($dupCheck->num_rows > 0) {
            echo "<script>alert('⚠ You already filed for this date!');</script>";
        } else {
            $stmt = $conn->prepare("UPDATE work_restday 
                SET date=?, from_time=?, to_time=?, purpose=?, work_schedule=?, status=?, datetime_updated=NOW() 
                WHERE id=?");
            $stmt->bind_param("ssssssi", $date, $from_time, $to_time, $purpose, $work_schedule, $status, $id);
            $stmt->execute();

            echo "<script>alert('✅ Request updated successfully!');</script>";
        }
    }

    /** ========== DELETE ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM work_restday WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    /** ========== FETCH ========= */
    // ✅ Build filter conditions
    $where = ["wr.applied_by = $user_id"];
    $params = [];
    $types  = "";

    // 📅 Date range filter
    if (!empty($_GET['date_range'])) {
        $range = explode(" to ", $_GET['date_range']);
        if (count($range) == 2) {
            $from = $range[0];
            $to   = $range[1];
            $where[] = "wr.date BETWEEN ? AND ?";
            $params[] = $from;
            $params[] = $to;
            $types   .= "ss";
        }
    }

    // ✅ Status filter
    if (!empty($_GET['status'])) {
        $where[] = "wr.status = ?";
        $params[] = $_GET['status'];
        $types   .= "s";
    }

    // ✅ Build final SQL
    $sql = "SELECT wr.*, u.username 
            FROM work_restday wr 
            JOIN users u ON wr.applied_by = u.id";

    // Add WHERE if needed
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY wr.datetime_applied DESC";

    // ✅ Run query with params if needed
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

     function isApprover($conn, $user_id) {
        $sql = "SELECT 1 FROM approver_assignments WHERE user_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    $approver = isApprover($conn, $user_id);

    // Get employee schedule
    $schedStmt = $conn->prepare("SELECT * FROM employee_schedules WHERE user_id = ?");
    $schedStmt->bind_param("i", $user_id);
    $schedStmt->execute();
    $sched = $schedStmt->get_result()->fetch_assoc();

    // Build allowed rest days array
    $restDays = [];
    $daysMap = [
        'sunday' => 0,
        'monday' => 1,
        'tuesday' => 2,
        'wednesday' => 3,
        'thursday' => 4,
        'friday' => 5,
        'saturday' => 6
    ];

    foreach ($daysMap as $dayName => $dayNum) {
        if ($sched[$dayName] === 'rest_day') {
            $restDays[] = $dayNum;
        }
    }
?>
        <?php include __DIR__ . '/layout/HEADER'; ?>
        <?php include __DIR__ . '/layout/NAVIGATION'; ?>

        <div id="layoutSidenav_content">
        <main class="container-fluid px-4">
            <br>
            <div class="d-flex justify-content-between mb-3">
                <h3>Work on Rest Day Requests</h3>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ Apply Work Rest Day</button>
            </div>

            <div class="card mb-3 p-3">
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="date_range" class="form-control dateRangePicker" 
                            placeholder="Select Date Range" value="<?= $_GET['date_range'] ?? '' ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Pending" <?= ($_GET['status'] ?? '')=="Pending"?"selected":"" ?>>Pending</option>
                            <option value="Approved" <?= ($_GET['status'] ?? '')=="Approved"?"selected":"" ?>>Approved</option>
                            <option value="Rejected" <?= ($_GET['status'] ?? '')=="Rejected"?"selected":"" ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>

            <table class="table table-bordered table-hover bg-white">
                <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Application No</th>
                    <th>User</th>
                    <th>Date</th>
                    <th>From Time</th>
                    <th>To Time</th>
                    <th>Purpose</th>
                    <th>Work Schedule</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php $i=1; while($row=$result->fetch_assoc()): ?>

                    <?php
                   // Status badge
                    $statusClass = "secondary";
                    if ($row['status'] == "Pending")  $statusClass = "warning";
                    if ($row['status'] == "Approved") $statusClass = "success";
                    if ($row['status'] == "Rejected") $statusClass = "danger";
                    ?>

                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= $row['application_no'] ?></td>
                        <td><?= $row['username'] ?></td>
                        <td><?= $row['date'] ?></td>
                        <td><?= date("h:i A", strtotime($row['from_time'])) ?></td>
                        <td><?= date("h:i A", strtotime($row['to_time'])) ?></td>
                        <td><?= htmlspecialchars($row['purpose']) ?></td>
                        <td><?= $row['work_schedule'] ?></td>
                        <td><span class="badge bg-<?= $statusClass ?>"><?= $row['status'] ?></span></td>
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
                                        <h5 class="modal-title">Edit Work Rest Day</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <label>Date</label>
                                        <input type="date" name="date" value="<?= $row['date'] ?>" class="form-control mb-2" required>
                                        <label>From Time</label>
                                        <input type="time" name="from_time" value="<?= $row['from_time'] ?>" class="form-control mb-2" required>
                                        <label>To Time</label>
                                        <input type="time" name="to_time" value="<?= $row['to_time'] ?>" class="form-control mb-2" required>
                                        <label>Purpose</label>
                                        <textarea name="purpose" class="form-control mb-2" required></textarea>
                                        <label>Work Schedule</label>
                                        <select name="work_schedule" class="form-select mb-2" required>
                                            <option value="On-site">On-site</option>
                                            <option value="Work from Home">Work from Home</option>
                                        </select>
                                        <label>Status</label>
                                        <select name="status" class="form-select">
                                            <option <?= $row['status']=="Pending"?"selected":"" ?>>Pending</option>
                                            <option <?= $row['status']=="Approved"?"selected":"" ?>>Approved</option>
                                            <option <?= $row['status']=="Rejected"?"selected":"" ?>>Rejected</option>
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
                                        <h5 class="modal-title">Delete Work Rest Day</h5>
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
                <?php endwhile; ?>
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
                            <h5 class="modal-title">Apply Work Rest Day</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label>Date</label>
                            <input type="date" name="date" class="form-control mb-2" required>
                            <label>From Time</label>
                            <input type="time" name="from_time" class="form-control mb-2" required>
                            <label>To Time</label>
                            <input type="time" name="to_time" class="form-control mb-2" required>
                            <label>Purpose</label>
                            <textarea name="purpose" class="form-control mb-2" required></textarea>
                            <label>Work Schedule</label>
                            <select name="work_schedule" class="form-select mb-2" required>
                                <option value="On-site">On-site</option>
                                <option value="Work from Home">Work from Home</option>
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Submit</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // For Date Range Picker
            flatpickr(".dateRangePicker", {
                mode: "range",
                dateFormat: "Y-m-d",
                onClose: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        const from = instance.formatDate(selectedDates[0], "Y-m-d");
                        const to   = instance.formatDate(selectedDates[1], "Y-m-d");
                        instance.input.value = `${from} to ${to}`;
                    }
                }
            });

            // For Time Picker (12-hour format with AM/PM)
            flatpickr(".timepicker", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "h:i K",  // 12-hour format with AM/PM
                time_24hr: false
            });
        </script>

        <script>
            const allowedRestDays = <?= json_encode($restDays) ?>;

            // For ADD modal
            flatpickr("input[name='date']", {
                dateFormat: "Y-m-d",
                minDate: "today",
                disable: [
                    function(date) {
                        return allowedRestDays.indexOf(date.getDay()) === -1;
                    }
                ]
            });

            // For EDIT modal
            flatpickr("#EditModal input[name='date']", {
                dateFormat: "Y-m-d",
                minDate: "today",
                disable: [
                    function(date) {
                        return allowedRestDays.indexOf(date.getDay()) === -1;
                    }
                ]
            });
        </script>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

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
