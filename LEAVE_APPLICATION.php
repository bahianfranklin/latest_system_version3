<?php
    // ==============================
    // Leave Requests Management
    // ==============================

    // Enable error reporting for debugging (disable in production)
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    session_start();
    require 'db.php';
    require 'autolock.php';
    require 'audit.php';

    // ✅ Ensure logged in
    if (!isset($_SESSION['user_id'])) {
        die("Please login first.");
    }
    $user_id = (int) $_SESSION['user_id'];

    /* ✅ Fix: Initialize $action here */
    $action = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? null;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? null;
    }

    // ==============================
    // Fetch Current User Info
    // ==============================
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $user = $userResult->fetch_assoc();
    if (!$user) {
        die("User not found.");
    }

    // ==============================
    // Handle Add Leave Request
    // ==============================
    if ($action === 'add') {
        $leave_type   = $_POST['leave_type'];
        $type         = $_POST['type']; // With Pay / Without Pay
        $credit_value = (float) $_POST['credit_value'];
        $date_from    = $_POST['date_from'];
        $date_to      = $_POST['date_to'];
        $remarks      = $_POST['remarks'];

        // Validate date format
        if (!DateTime::createFromFormat('Y-m-d', $date_from) || !DateTime::createFromFormat('Y-m-d', $date_to)) {
            die("Invalid date format.");
        }

        // ==============================
        // 1️⃣ Check for overlapping leave dates
        // ==============================
        $stmtCheck = $conn->prepare("
            SELECT COUNT(*) as cnt 
            FROM leave_requests 
            WHERE user_id = ? 
            AND status != 'Rejected' 
            AND (
                    (date_from <= ? AND date_to >= ?)  
                OR (date_from <= ? AND date_to >= ?)  
                OR (date_from >= ? AND date_to <= ?)  
            )
        ");
        $stmtCheck->bind_param("issssss", $user_id, $date_from, $date_from, $date_to, $date_to, $date_from, $date_to);
        $stmtCheck->execute();
        $overlap = $stmtCheck->get_result()->fetch_assoc();

        if ($overlap['cnt'] > 0) {
            echo "<script>
                alert('❌ Error: You already filed a leave within this date range.');
                window.history.back();
            </script>";
            exit;
        }

        // ==============================
        // 2️⃣ Prevent "With Pay" if credits are zero
        // ==============================
        if ($type === "With Pay") {
            $stmtCredit = $conn->prepare("
                SELECT mandatory, vacation_leave, sick_leave 
                FROM leave_credits 
                WHERE user_id = ?
            ");
            $stmtCredit->bind_param("i", $user_id);
            $stmtCredit->execute();
            $creditRow = $stmtCredit->get_result()->fetch_assoc();

            if (!$creditRow) {
                echo "<script>
                    alert('❌ Error: No leave credits found for this user.');
                    window.history.back();
                </script>";
                exit;
            }

            $availableCredit = 0;
            if ($leave_type === "Mandatory Leave") {
                $availableCredit = (int)$creditRow['mandatory'];
            } elseif ($leave_type === "Vacation Leave") {
                $availableCredit = (int)$creditRow['vacation_leave'];
            } elseif ($leave_type === "Sick Leave") {
                $availableCredit = (int)$creditRow['sick_leave'];
            }

            if ($availableCredit <= 0) {
               echo "<script>
                alert('❌ Error: You cannot file $leave_type with pay because your credits are already zero.');
                window.history.back();
            </script>";
            exit;
            }
        }

        // ==============================
        // 3️⃣ Generate Application No
        // ==============================
        $today = date("Ymd");
        $sql = "SELECT COUNT(*) as total 
                FROM leave_requests 
                WHERE user_id = $user_id AND DATE(date_applied) = CURDATE()";
        $res = $conn->query($sql);
        if (!$res) {
            die("Error counting leave_requests: " . $conn->error);
        }
        $row = $res->fetch_assoc();
        $countToday = $row['total'] + 1;

        $appNo = "L-" . $today . "-" . $user_id . "-" . str_pad($countToday, 2, "0", STR_PAD_LEFT);

        // ==============================
        // 4️⃣ Insert leave request
        // ==============================
        $stmt2 = $conn->prepare("
            INSERT INTO leave_requests 
            (application_no, user_id, leave_type, type, credit_value, date_from, date_to, remarks, date_applied) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt2->bind_param("sissdsss", $appNo, $user_id, $leave_type, $type, $credit_value, $date_from, $date_to, $remarks);

        if (!$stmt2->execute()) {
            die("Insert failed: " . $stmt2->error);
        }

        // 🔥 AUDIT TRAIL
        logAction($conn, $user_id, "CREATED LEAVE APPLICATION", "Added: $leave_type from $date_from to $date_to");

        // ✅ Redirect after add
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // ==============================
    // Handle Edit
    // ==============================
    if ($action === 'edit') {
    $id           = (int) $_POST['id'];
    $leave_type   = $_POST['leave_type'];
    $type         = $_POST['type'];
    $credit_value = (float) $_POST['credit_value'];
    $date_from    = $_POST['date_from'];
    $date_to      = $_POST['date_to'];
    $remarks      = $_POST['remarks'];
    $status       = $_POST['status'];

    // Validate date format
    if (!DateTime::createFromFormat('Y-m-d', $date_from) || !DateTime::createFromFormat('Y-m-d', $date_to)) {
        die("Invalid date format.");
    }

    // ==============================
    // 1️⃣ Check for overlapping leave dates (excluding current record)
    // ==============================
    $stmtCheck = $conn->prepare("
        SELECT COUNT(*) as cnt 
        FROM leave_requests 
        WHERE user_id = ? 
          AND id != ? 
          AND status != 'Rejected' 
          AND (
                (date_from <= ? AND date_to >= ?)  
             OR (date_from <= ? AND date_to >= ?)  
             OR (date_from >= ? AND date_to <= ?)  
          )
    ");
    $stmtCheck->bind_param("iissssss", $user_id, $id, $date_from, $date_from, $date_to, $date_to, $date_from, $date_to);
    $stmtCheck->execute();
    $overlap = $stmtCheck->get_result()->fetch_assoc();

    if ($overlap['cnt'] > 0) {
        echo "<script>
            alert('❌ Error: You already have another leave within this date range.');
            window.history.back();
        </script>";
        exit;
    }

    // ==============================
    // 2️⃣ Prevent "With Pay" if credits are zero
    // ==============================
    if ($type === "With Pay") {
        $stmtCredit = $conn->prepare("
            SELECT mandatory, vacation_leave, sick_leave 
            FROM leave_credits 
            WHERE user_id = ?
        ");
        $stmtCredit->bind_param("i", $user_id);
        $stmtCredit->execute();
        $creditRow = $stmtCredit->get_result()->fetch_assoc();

        if (!$creditRow) {
            echo "<script>
                alert('❌ Error: No leave credits found for this user.');
                window.history.back();
            </script>";
            exit;
        }

        $availableCredit = 0;
        if ($leave_type === "Mandatory Leave") {
            $availableCredit = (int)$creditRow['mandatory'];
        } elseif ($leave_type === "Vacation Leave") {
            $availableCredit = (int)$creditRow['vacation_leave'];
        } elseif ($leave_type === "Sick Leave") {
            $availableCredit = (int)$creditRow['sick_leave'];
        }

        if ($availableCredit <= 0) {
            echo "<script>
                alert('❌ Error: You cannot file $leave_type with pay because your credits are already zero.');
                window.history.back();
            </script>";
            exit;
        }

    }

        // ==============================
        // 3️⃣ Update record
        // ==============================
        $stmt = $conn->prepare("
            UPDATE leave_requests 
            SET leave_type=?, type=?, credit_value=?, date_from=?, date_to=?, remarks=?, status=?, date_updated=NOW() 
            WHERE id=? AND user_id=?
        ");
        $stmt->bind_param("ssdssssii", $leave_type, $type, $credit_value, $date_from, $date_to, $remarks, $status, $id, $user_id);

        if (!$stmt->execute()) {
            die("Update failed: " . $stmt->error);
        }

        // 🔥 AUDIT TRAIL
        logAction($conn, $user_id, "UPDATED LEAVE APPLICATION", "Updated: $leave_type from $date_from to $date_to");

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // ==============================
    // Handle Delete
    // ==============================
    if ($action === 'delete') {
        $id = (int) $_POST['id'];

        // 1️⃣ Fetch record BEFORE deleting it
        $stmtInfo = $conn->prepare("
            SELECT application_no, leave_type, date_from, date_to, remarks 
            FROM leave_requests 
            WHERE id=? AND user_id=?
        ");
        $stmtInfo->bind_param("ii", $id, $user_id);
        $stmtInfo->execute();
        $resultInfo = $stmtInfo->get_result();
        $deletedData = $resultInfo->fetch_assoc();

        if ($deletedData) {
            // Prepare readable audit text
            $details = "AppNo: {$deletedData['application_no']}, "
                    . "Type: {$deletedData['leave_type']}, "
                    . "From: {$deletedData['date_from']}, "
                    . "To: {$deletedData['date_to']}, "
                    . "Remarks: {$deletedData['remarks']}";
        } else {
            // Fallback (should not happen)
            $details = "Leave ID: $id (record not found before deletion)";
        }

        // 2️⃣ Perform Delete
        $stmt4 = $conn->prepare("DELETE FROM leave_requests WHERE id=? AND user_id=?");
        $stmt4->bind_param("ii", $id, $user_id);

        if (!$stmt4->execute()) {
            die("Delete failed: " . $stmt4->error);
        }

        // 3️⃣ Audit Trail with FULL DETAILS
        logAction($conn, $user_id, "DELETED LEAVE APPLICATION", $details);

        // 4️⃣ Redirect
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // ==============================
    // Build Filtering Logic
    // ==============================
    $where = ["lr.user_id = $user_id"];

    if (!empty($_GET['date_range'])) {
        $dates = explode(" to ", $_GET['date_range']);
        if (count($dates) === 2 && !empty($dates[0]) && !empty($dates[1])) {
            $from = $conn->real_escape_string($dates[0]);
            $to   = $conn->real_escape_string($dates[1]);
            $where[] = "lr.date_from >= '$from' AND lr.date_to <= '$to'";
        }
    }

    if (!empty($_GET['leave_type'])) {
        $leave_type = $conn->real_escape_string($_GET['leave_type']);
        $where[] = "lr.leave_type = '$leave_type'";
    }

    if (!empty($_GET['status'])) {
        $status = $conn->real_escape_string($_GET['status']);
        $where[] = "lr.status = '$status'";
    }

    $whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

    // 🔥 AUDIT TRAIL
    logAction($conn, $user_id, "FILTER LEAVE REQUESTS", "Filters: " . json_encode($_GET));
    
    // ==============================
    // Fetch Leave Requests
    // ==============================
    $sql = "
        SELECT lr.*, u.username 
        FROM leave_requests lr 
        JOIN users u ON lr.user_id = u.id
        $whereSQL
        ORDER BY lr.date_applied DESC
    ";
    $result = $conn->query($sql);
    if (!$result) {
        die("Query Failed: " . $conn->error . " -- SQL: " . $sql);
    }

    function isApprover($conn, $user_id) {
        $sql = "SELECT 1 FROM approver_assignments WHERE user_id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    $approver = isApprover($conn, $user_id);
?>
        <?php include __DIR__ . '/layout/HEADER'; ?>
        <?php include __DIR__ . '/layout/NAVIGATION'; ?>

        <div id="layoutSidenav_content">
            <main class="container-fluid px-4">
                <br>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="mb-0">Leave Requests</h2>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fa-solid fa-plus"></i> Apply Leave
                    </button>          
                </div>
                <div class="card mb-3 p-3">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Date Coverage</label>
                            <input type="text" class="form-control dateRangePicker" name="date_range" value="<?= htmlspecialchars($_GET['date_range'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Leave Type</label>
                            <select class="form-select" name="leave_type">
                                <option value="">Select</option>
                                <option value="Vacation Leave" <?= isset($_GET['leave_type']) && $_GET['leave_type'] == "Vacation Leave" ? "selected" : "" ?>>Vacation Leave</option>
                                <option value="Sick Leave" <?= isset($_GET['leave_type']) && $_GET['leave_type'] == "Sick Leave" ? "selected" : "" ?>>Sick Leave</option>
                                <option value="Mandatory Leave" <?= isset($_GET['leave_type']) && $_GET['leave_type'] == "Madatory Leave" ? "selected" : "" ?>>Mandatory Leave</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">Select</option>
                                <option value="Pending" <?= isset($_GET['status']) && $_GET['status'] == "Pending" ? "selected" : "" ?>>Pending</option>
                                <option value="Approved" <?= isset($_GET['status']) && $_GET['status'] == "Approved" ? "selected" : "" ?>>Approved</option>
                                <option value="Rejected" <?= isset($_GET['status']) && $_GET['status'] == "Rejected" ? "selected" : "" ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">Filter</button>
                        </div>
                    </form>
                </div>

                <table class="table table-bordered table-hover bg-white">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Application No</th>
                            <th>User</th>
                            <th>Leave Type</th>
                            <th>Type</th>
                            <th>Credit</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Remarks</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['application_no']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['leave_type']) ?></td>
                            <td><?= htmlspecialchars($row['type']) ?></td>
                            <td><?= htmlspecialchars($row['credit_value']) ?></td>
                            <td><?= htmlspecialchars($row['date_from']) ?></td>
                            <td><?= htmlspecialchars($row['date_to']) ?></td>
                            <td><?= htmlspecialchars($row['remarks']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td>
                                <?php if ($row['status'] === 'Pending'): ?>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
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
                                            <h5 class="modal-title">Edit Leave Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-2">
                                                <label>Leave Type</label>
                                                <select class="form-select" name="leave_type" required>
                                                    <option value="Vacation Leave" <?= $row['leave_type'] == "Vacation Leave" ? "selected" : "" ?>>Vacation Leave</option>
                                                    <option value="Sick Leave" <?= $row['leave_type'] == "Sick Leave" ? "selected" : "" ?>>Sick Leave</option>
                                                    <option value="Mandatory Leave" <?= $row['leave_type'] == "Mandatory Leave" ? "selected" : "" ?>>Mandatory Leave</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label>Type</label>
                                                <select class="form-select" name="type" required>
                                                    <option value="With Pay" <?= $row['type'] == "With Pay" ? "selected" : "" ?>>With Pay</option>
                                                    <option value="Without Pay" <?= $row['type'] == "Without Pay" ? "selected" : "" ?>>Without Pay</option>
                                                </select>
                                            </div>
                                            <div class="mb-2">
                                                <label>Credit Value</label>
                                                <input type="number" step="0.5" class="form-control" name="credit_value" value="<?= htmlspecialchars($row['credit_value']) ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label>Date From</label>
                                                <input type="text" class="form-control datepicker" name="date_from" value="<?= htmlspecialchars($row['date_from']) ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label>Date To</label>
                                                <input type="text" class="form-control datepicker" name="date_to" value="<?= htmlspecialchars($row['date_to']) ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label>Remarks</label>
                                                <textarea class="form-control" name="remarks" required><?= htmlspecialchars($row['remarks']) ?></textarea>
                                            </div>
                                            <div class="mb-2">
                                                <label>Status</label>
                                                <select class="form-select" name="status" required>
                                                    <option value="Pending" <?= $row['status'] == "Pending" ? "selected" : "" ?>>Pending</option>
                                                    <option value="Approved" <?= $row['status'] == "Approved" ? "selected" : "" ?>>Approved</option>
                                                    <option value="Rejected" <?= $row['status'] == "Rejected" ? "selected" : "" ?>>Rejected</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-primary">Update</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="closeEditModal">Close</button>
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
                                            <h5 class="modal-title">Cancel Leave Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            Are you sure you want to cancel <strong><?= htmlspecialchars($row['application_no']) ?></strong>?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-danger">Proceed</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Back</button>
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
                        <h5 class="modal-title">Apply Leave Request</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label>Leave Type</label>
                            <select class="form-select" name="leave_type" required>
                                <option value="">-- Select --</option>
                                <option value="Vacation Leave">Vacation Leave</option>
                                <option value="Sick Leave">Sick Leave</option>
                                <option value="Mandatory Leave">Mandatory Leave</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label>Type</label>
                            <select class="form-select" name="type" required>
                                <option value="">-- Select --</option>
                                <option value="With Pay">With Pay</option>
                                <option value="Without Pay">Without Pay</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label>Credit Value</label>
                            <input type="number" step="0.5" class="form-control" name="credit_value" required>
                        </div>
                        <div class="mb-2">
                            <label>Date From</label>
                            <input type="text" class="form-control datepicker" name="date_from" required>
                        </div>
                        <div class="mb-2">
                            <label>Date To</label>
                            <input type="text" class="form-control datepicker" name="date_to" required>
                        </div>
                        <div class="mb-2">
                            <label>Remarks</label>
                            <textarea class="form-control" name="remarks" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Add</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="closeAddModal">Close</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // ✅ Range picker for filters
            flatpickr(".dateRangePicker", {
                mode: "range",
                dateFormat: "Y-m-d"
            });

            // ✅ Single date pickers for leave application (From / To)
            flatpickr(".datepicker", {
                dateFormat: "Y-m-d",
                allowInput: true
            });
        });
    </script>

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

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let closeAddBtn = document.getElementById("closeAddModal");
            if (closeAddBtn) {
                closeAddBtn.addEventListener("click", function () {
                    let addForm = document.querySelector("#addModal form");
                    if (addForm) addForm.reset();
                });
            }

            let closeEditBtn = document.getElementById("closeEditModal");
            if (closeEditBtn) {
                closeEditBtn.addEventListener("click", function () {
                    let editForm = document.querySelector("#editModal form");
                    if (editForm) editForm.reset();
                });
            }
        });
    </script>
