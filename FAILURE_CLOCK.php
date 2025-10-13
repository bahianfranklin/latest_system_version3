<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    session_start();
    require 'db.php';

    if (!isset($_SESSION['user_id'])) {
        die("Please login first.");
    }

    $user_id = $_SESSION['user_id'];

    // Fetch current user info for profile display
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $user = $userResult->fetch_assoc();

    /** ========== ADD ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $date = $_POST['date'];
        $type = $_POST['type'];
        $time_in = $_POST['time_in'];
        $time_out = $_POST['time_out'];
        $reason = $_POST['reason'];

        // ✅ Check if the user already filed for this date
        $check = $conn->prepare("SELECT COUNT(*) FROM failure_clock WHERE applied_by = ? AND date = ?");
        $check->bind_param("is", $user_id, $date);
        $check->execute();
        $check->bind_result($exists);
        $check->fetch();
        $check->close();

        if ($exists > 0) {
            echo "<script>
                    alert('You already filed a Failure Clock for this date ($date). Duplicate entry not allowed.');
                    window.history.back();
                </script>";
            exit;
        }

        // ✅ Generate new Application No
        $today = date("Ymd");
        $res = $conn->query("SELECT COUNT(*) as total FROM failure_clock WHERE DATE(datetime_applied)=CURDATE()");
        $row = $res->fetch_assoc();
        $countToday = $row['total'] + 1;
        $appNo = "FC-" . $today . "-" . str_pad($countToday, 2, "0", STR_PAD_LEFT);

        // ✅ Insert record
        $stmt = $conn->prepare("INSERT INTO failure_clock (application_no, date, type, time_in, time_out, reason, applied_by) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $appNo, $date, $type, $time_in, $time_out, $reason, $user_id);
        $stmt->execute();

        echo "<script>
                alert('Application submitted successfully!');
                window.location.href = '".$_SERVER['PHP_SELF']."';
            </script>";
    }

    /** ========== EDIT ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $date = $_POST['date'];
        $type = $_POST['type'];
        $time_in = $_POST['time_in'];
        $time_out = $_POST['time_out'];
        $reason = $_POST['reason'];

        // ✅ Check if another record already exists for this date
        $check = $conn->prepare("SELECT COUNT(*) FROM failure_clock WHERE applied_by = ? AND date = ? AND id != ?");
        $check->bind_param("isi", $user_id, $date, $id);
        $check->execute();
        $check->bind_result($exists);
        $check->fetch();
        $check->close();

        if ($exists > 0) {
            echo "<script>
                    alert('You already filed a Failure Clock for this date ($date). Duplicate entry not allowed.');
                    window.history.back();
                </script>";
            exit;
        }

        // ✅ Proceed with update
        $stmt = $conn->prepare("UPDATE failure_clock 
                                SET date=?, type=?, time_in=?, time_out=?, reason=?, datetime_updated=NOW() 
                                WHERE id=? AND applied_by=?");
        $stmt->bind_param("sssssii", $date, $type, $time_in, $time_out, $reason, $id, $user_id);
        $stmt->execute();

        echo "<script>
                alert('Record updated successfully!');
                window.location.href = '".$_SERVER['PHP_SELF']."';
            </script>";
    }

    /** ========== DELETE ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM failure_clock WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    /** ========== FETCH ========= */
    $where = ["fc.applied_by = $user_id"];

    // Date Range Filter
    if (!empty($_GET['date_range'])) {
        $dates = explode(" to ", $_GET['date_range']);
        if (count($dates) == 2 && !empty($dates[0]) && !empty($dates[1])) {
            $from = date("Y-m-d", strtotime($dates[0]));
            $to   = date("Y-m-d", strtotime($dates[1]));
            $where[] = "fc.date BETWEEN '$from' AND '$to'";
        }
    }

    // Status Filter
    if (!empty($_GET['status'])) {
        $status = $conn->real_escape_string($_GET['status']);
        $where[] = "fc.status = '$status'";
    }

    $whereSql = count($where) > 0 ? "WHERE " . implode(" AND ", $where) : "";

    $sql = "SELECT fc.*, u.username 
            FROM failure_clock fc 
            JOIN users u ON fc.applied_by=u.id 
            $whereSql
            ORDER BY fc.datetime_applied DESC";

    $result = $conn->query($sql);

    // Get the start (Monday) and end (Sunday) of the current week
    $monday = date('Y-m-d', strtotime('monday this week'));
    $sunday = date('Y-m-d', strtotime('sunday this week'));
    $today = date('Y-m-d');
?>
        <?php include __DIR__ . '/layout/HEADER'; ?>
        <?php include __DIR__ . '/layout/NAVIGATION'; ?>

        <div id="layoutSidenav_content">
        <main class="container-fluid px-4">
            <br>
            <div class="d-flex justify-content-between mb-3">
                <h3>Failure to Clock In/Out</h3>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ Apply Failure Clock</button>
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
                        <th>Type</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Applied At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
            <?php $i=1; while($row=$result->fetch_assoc()): ?>
                <?php
                    // Format times
                    $timeIn  = $row['time_in']  ? date("h:i A", strtotime($row['time_in'])) : "-";
                    $timeOut = $row['time_out'] ? date("h:i A", strtotime($row['time_out'])) : "-";

                    // Format applied datetime
                    $appliedAt = date("M d, Y h:i A", strtotime($row['datetime_applied']));

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
                    <td><?= $row['type'] ?></td>
                    <td><?= $timeIn ?></td>
                    <td><?= $timeOut ?></td>
                    <td><?= $row['reason'] ?></td>
                    <td><span class="badge bg-<?= $statusClass ?>"><?= $row['status'] ?></span></td>
                    <td><?= $appliedAt ?></td>
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
                                        <h5 class="modal-title">Edit Failure Clock</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <label>Date</label>
                                        <input type="date" 
                                            name="date" 
                                            value="<?= $row['date'] ?>" 
                                            class="form-control mb-2" 
                                            required
                                            min="<?= $monday ?>" 
                                            max="<?= min($today, $sunday) ?>">
                                        <label>Type</label>
                                        <select name="type" class="form-select mb-2" id="typeSelect<?= $row['id'] ?>">
                                            <option value="Clock-In & Out" <?= $row['type']=="Clock-In & Out"?"selected":"" ?>>Clock-In & Out</option>
                                            <option value="Clock-Out" <?= $row['type']=="Clock-Out"?"selected":"" ?>>Clock-Out</option>
                                        </select>

                                        <label>Time In</label>
                                        <input type="time" name="time_in" value="<?= $row['time_in'] ?>" class="form-control mb-2" id="timeIn<?= $row['id'] ?>">
                                        <label>Time Out</label>
                                        <input type="time" name="time_out" value="<?= $row['time_out'] ?>" class="form-control mb-2">
                                        <label>Reason</label>
                                        <textarea name="reason" class="form-control mb-2"><?= $row['reason'] ?></textarea>
                                        <label>Status</label>
                                        <select class="form-select" disabled>
                                            <option <?= $row['status']=="Pending"?"selected":"" ?>>Pending</option>
                                            <option <?= $row['status']=="Approved"?"selected":"" ?>>Approved</option>
                                            <option <?= $row['status']=="Rejected"?"selected":"" ?>>Rejected</option>
                                        </select>
                                        <!-- hidden real status value for form submission -->
                                        <input type="hidden" name="status" value="<?= $row['status'] ?>">
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
                                        <h5 class="modal-title">Delete Failure Clock</h5>
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
            <?php include __DIR__ . '/layout/FOOTER'; ?>
        </div>
    </div>

        <!-- Add Modal -->
        <div class="modal fade" id="addModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">Apply Failure Clock</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label>Date</label>
                            <input type="date" 
                                name="date" 
                                class="form-control mb-2" 
                                required
                                min="<?= $monday ?>" 
                                max="<?= min($today, $sunday) ?>">
                            <label>Type</label>
                            <select name="type" class="form-select mb-2" id="typeSelectAdd">
                                <option value="Clock-In & Out">Clock-In & Out</option>
                                <option value="Clock-Out">Clock-Out</option>
                            </select>

                            <label>Time In</label>
                            <input type="time" name="time_in" id="timeInAdd" class="form-control mb-2">

                            <label>Time Out</label>
                            <input type="time" name="time_out" id="timeOutAdd" class="form-control mb-2">

                            <label>Reason</label>
                            <textarea name="reason" class="form-control mb-2" required></textarea>
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
            document.addEventListener("DOMContentLoaded", function () {
                flatpickr(".dateRangePicker", {
                    mode: "range",
                    dateFormat: "Y-m-d",
                    onChange: function (selectedDates, dateStr, instance) {
                        // optionally, if you want to fill hidden inputs or something
                    }
                });
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

        <script>
        document.addEventListener("DOMContentLoaded", function () {
            const typeSelectAdd = document.getElementById("typeSelectAdd");
            const timeInAdd = document.getElementById("timeInAdd");

            // Function to toggle time_in field based on type
            function toggleTimeIn() {
                if (typeSelectAdd.value === "Clock-Out") {
                    timeInAdd.disabled = true;
                    timeInAdd.value = ""; // clear input if disabled
                } else {
                    timeInAdd.disabled = false;
                }
            }

            // Initialize and bind event listener
            toggleTimeIn();
            typeSelectAdd.addEventListener("change", toggleTimeIn);
        });
        </script>

        <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Loop through all edit modals
            document.querySelectorAll("[id^='typeSelect']").forEach(function(select) {
                const id = select.id.replace("typeSelect", "");
                const timeInField = document.getElementById("timeIn" + id);

                function toggleTimeInEdit() {
                    if (select.value === "Clock-Out") {
                        timeInField.disabled = true;
                        timeInField.value = "";
                    } else {
                        timeInField.disabled = false;
                    }
                }

                toggleTimeInEdit();
                select.addEventListener("change", toggleTimeInEdit);
            });
        });
        </script>

