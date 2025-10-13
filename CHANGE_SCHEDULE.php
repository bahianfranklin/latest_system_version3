<?php
    session_start();
    require 'db.php';

    if (!isset($_SESSION['user_id'])) {
        die("Please login first.");
    }

    $user_id = $_SESSION['user_id'];

    /** ========== ADD ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $date = $_POST['date'];
        $remarks = $_POST['remarks'];
        $total_hours = $_POST['total_hours'];

        $today = date("Ymd");
        $res = $conn->query("SELECT COUNT(*) as total FROM change_schedule WHERE DATE(datetime_applied)=CURDATE()");
        $row = $res->fetch_assoc();
        $countToday = $row['total'] + 1;
        $appNo = "CS-" . $today . "-" . str_pad($countToday, 2, "0", STR_PAD_LEFT);

        $stmt = $conn->prepare("INSERT INTO change_schedule (application_no, date, remarks, total_hours, applied_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $appNo, $date, $remarks, $total_hours, $user_id);
        $stmt->execute();
    }

    /** ========== EDIT ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id = $_POST['id'];
        $date = $_POST['date'];
        $remarks = $_POST['remarks'];
        $total_hours = $_POST['total_hours'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE change_schedule SET date=?, remarks=?, total_hours=?, status=?, datetime_updated=NOW() WHERE id=?");
        $stmt->bind_param("ssssi", $date, $remarks, $total_hours, $status, $id);
        $stmt->execute();
    }

    /** ========== DELETE ========= */
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $conn->prepare("DELETE FROM change_schedule WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
    }

    /** ========== FETCH ========= */
    $where = ["cs.applied_by = $user_id"];

    // Date Range
    if (!empty($_GET['date_range'])) {
        $dates = explode(" to ", $_GET['date_range']);
        if (count($dates) == 2 && !empty($dates[0]) && !empty($dates[1])) {
            $from = date("Y-m-d 00:00:00", strtotime($dates[0]));
            $to   = date("Y-m-d 23:59:59", strtotime($dates[1]));
            $where[] = "cs.date BETWEEN '$from' AND '$to'";
        }
    }

    // Status
    if (!empty($_GET['status'])) {
        $status = $conn->real_escape_string($_GET['status']);
        $where[] = "cs.status = '$status'";
    }

    $whereSql = (count($where) > 0) ? "WHERE " . implode(" AND ", $where) : "";

    $sql = "SELECT cs.*, u.username 
            FROM change_schedule cs 
            JOIN users u ON cs.applied_by=u.id 
            $whereSql 
            ORDER BY cs.datetime_applied DESC";

    $result = $conn->query($sql);

?>
        <?php include __DIR__ . '/layout/HEADER'; ?>
        <?php include __DIR__ . '/layout/NAVIGATION'; ?>

        <div id="layoutSidenav_content">
            <main class="container-fluid px-4">
            <br>
            <div class="d-flex justify-content-between mb-3">
                <h3>Change Schedule Requests</h3>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">+ Apply Change Schedule</button>
            </div>

            <!-- Filter Form -->
            <div class="card mb-3 p-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Date Coverage</label>
                        <input type="text" class="form-control dateRangePicker" name="date_range" value="<?= isset($_GET['date_range']) ? htmlspecialchars($_GET['date_range']) : '' ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Select</option>
                            <option value="Pending" <?= isset($_GET['status']) && $_GET['status']=="Pending"?"selected":"" ?>>Pending</option>
                            <option value="Approved" <?= isset($_GET['status']) && $_GET['status']=="Approved"?"selected":"" ?>>Approved</option>
                            <option value="Rejected" <?= isset($_GET['status']) && $_GET['status']=="Rejected"?"selected":"" ?>>Rejected</option>
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
                onClose: function(selectedDates, dateStr, instance) {
                    if (selectedDates.length === 2) {
                        const from = instance.formatDate(selectedDates[0], "Y-m-d");
                        const to   = instance.formatDate(selectedDates[1], "Y-m-d");
                        instance.input.value = `${from} to ${to}`;
                    }
                }
            });
            </script>

            <table class="table table-bordered table-hover bg-white">
                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Application No</th>
                        <th>User</th>
                        <th>Date</th>
                        <th>Remarks</th>
                        <th>Total Hours</th>
                        <th>Status</th>
                        <th>Applied At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                    <tbody>
                    <?php if ($result && $result->num_rows > 0): 
                    $i=1; while($row=$result->fetch_assoc()): 

                        // Format applied datetime
                        $appliedAt = date("M d, Y h:i A", strtotime($row['datetime_applied']));
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['application_no']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td><?= htmlspecialchars($row['remarks']) ?></td>
                            <td><?= htmlspecialchars($row['total_hours']) ?></td>
                            <td>
                                <span class="badge 
                                    <?= $row['status']=="Approved"?"bg-success":($row['status']=="Rejected"?"bg-danger":"bg-warning") ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td><?= $appliedAt ?></td> <!-- ✅ Fixed -->
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
                                        <h5 class="modal-title">Edit Change Schedule</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <label>Date</label>
                                        <input type="date" name="date" value="<?= $row['date'] ?>" class="form-control mb-2" required>
                                        <label>Remarks</label>
                                        <textarea name="remarks" class="form-control mb-2"><?= $row['remarks'] ?></textarea>
                                        <label>Total Hours</label>
                                        <input type="number" name="total_hours" value="<?= $row['total_hours'] ?>" class="form-control mb-2" required>
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
                                        <h5 class="modal-title">Delete Change Schedule</h5>
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
                    <tr><td colspan="9" class="text-center text-muted">No records found</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            </main>
            <?php include __DIR__ . '/layout/FOOTER'; ?>
        </div>
        

        <!-- Add Modal -->
        <div class="modal fade" id="addModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">Apply Change Schedule</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label>Date</label>
                            <input type="date" name="date" class="form-control mb-2" required>
                            <label>Remarks</label>
                            <textarea name="remarks" class="form-control mb-2" required></textarea>
                            <label>Total Hours</label>
                            <input type="number" name="total_hours" class="form-control mb-2" required>
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
