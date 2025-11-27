<?php
    session_start();
    require 'db.php';
    require 'autolock.php';
    require 'audit.php';

    // 🚫 Prevent cached pages
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");

    // ✅ Assume logged-in user
    $user_id = $_SESSION['user_id'] ?? 1; // change if needed
    $sessionUser = $_SESSION['user'] ?? null;

    // ✅ Get Leave Balance
    $leave_sql = "SELECT mandatory, vacation_leave, sick_leave FROM leave_credits WHERE user_id = ?";
    $leave = ['mandatory' => 0, 'vacation_leave' => 0, 'sick_leave' => 0];
    if ($stmt = $conn->prepare($leave_sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $f = $result->fetch_assoc();
            if ($f) $leave = $f;
        }
        $stmt->close();
    } else {
        error_log("leave_sql prepare failed: " . $conn->error);
    }

    // ✅ Get Events
    $today = date("Y-m-d");

    // -----------------------------
    // Fetch the latest 3 announcements
    // -----------------------------
    $announcements = []; // default empty so template never breaks

    $announcement_sql = "
        SELECT id, title, description, created_at
        FROM announcements
        ORDER BY created_at DESC
        LIMIT 3
    ";

    if ($stmtA = $conn->prepare($announcement_sql)) {
        $stmtA->execute();
        $resA = $stmtA->get_result();
        if ($resA) {
            while ($row = $resA->fetch_assoc()) {
                $announcements[] = $row;
            }
        }
        $stmtA->close();
    }

    // ------------------ New Tardiness Query ------------------
    date_default_timezone_set('Asia/Manila');
    $conn->query("SET time_zone = '+08:00'");

    // ✅ Get current date details
    $month = date('m');
    $year = date('Y');

    // ✅ Compute Tardiness (Late arrivals only)
    $sql = "
        SELECT 
            COUNT(*) AS times_late,
            SEC_TO_TIME(SUM(TIMESTAMPDIFF(SECOND, 
                STR_TO_DATE(CONCAT(a.log_date, ' ', e.time_in), '%Y-%m-%d %H:%i:%s'),
                a.login_time
            ))) AS total_late
        FROM attendance_logs a
        INNER JOIN employee_working_hours e 
            ON a.user_id = e.user_id 
            AND DAYNAME(a.log_date) = e.work_day
        WHERE a.user_id = ?
        AND MONTH(a.log_date) = ?
        AND YEAR(a.log_date) = ?
        AND a.login_time > STR_TO_DATE(CONCAT(a.log_date, ' ', e.time_in), '%Y-%m-%d %H:%i:%s')
    ";

    // ✅ Run the query safely
    $timesLate = 0;
    $totalLate = "00:00:00";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("iii", $user_id, $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $tardiness = $result->fetch_assoc();
            $timesLate = $tardiness['times_late'] ?? 0;
            $totalLate = $tardiness['total_late'] ?? "00:00:00";
            if ($totalLate === null || $totalLate === '') {
                $totalLate = "00:00:00";
            }
        }
        $stmt->close();
    } else {
        error_log("Tardiness SQL failed: " . $conn->error);
    }

    // User's Pending Leave Requests
    $sqlUserPendingLeave = "SELECT COUNT(*) AS pending FROM leave_requests WHERE user_id = ? AND status = 'Pending'";
    $userPendingLeave = 0;
    if ($stmtLP = $conn->prepare($sqlUserPendingLeave)) {
        $stmtLP->bind_param("i", $user_id);
        $stmtLP->execute();
        $res = $stmtLP->get_result();
        $userPendingLeave = $res->fetch_assoc()['pending'] ?? 0;
        $stmtLP->close();
    } else {
        error_log("prepare failed (pending leave): " . $conn->error);
    }

    // User's Pending Overtime Requests
    $sqlUserPendingOvertime = "SELECT COUNT(*) AS pending FROM overtime WHERE user_id = ? AND status = 'Pending'";
    $userPendingOvertime = 0;
    if ($stmtOP = $conn->prepare($sqlUserPendingOvertime)) {
        $stmtOP->bind_param("i", $user_id);
        $stmtOP->execute();
        $userPendingOvertime = $stmtOP->get_result()->fetch_assoc()['pending'] ?? 0;
        $stmtOP->close();
    } else {
        error_log("prepare failed (pending overtime): " . $conn->error);
    }

    // User's Pending Official Business Requests
    $sqlUserPendingOfficial_Business = "SELECT COUNT(*) AS pending FROM official_business WHERE user_id = ? AND status = 'Pending'";
    $userPendingOfficial_Business = 0;
    if ($stmtOBP = $conn->prepare($sqlUserPendingOfficial_Business)) {
        $stmtOBP->bind_param("i", $user_id);
        $stmtOBP->execute();
        $userPendingOfficial_Business = $stmtOBP->get_result()->fetch_assoc()['pending'] ?? 0;
        $stmtOBP->close();
    } else {
        error_log("prepare failed (pending official business): " . $conn->error);
    }

    // User's Pending Change Schedule Requests
    $sqlUserPendingChange_Schedule = "SELECT COUNT(*) AS pending FROM change_schedule WHERE user_id = ? AND status = 'Pending'";
    $userPendingChange_Schedule = 0;
    if ($stmtCSP = $conn->prepare($sqlUserPendingChange_Schedule)) {
        $stmtCSP->bind_param("i", $user_id);
        $stmtCSP->execute();
        $userPendingChange_Schedule = $stmtCSP->get_result()->fetch_assoc()['pending'] ?? 0;
        $stmtCSP->close();
    } else {
        error_log("prepare failed (pending change schedule): " . $conn->error);
    }

    // User's Pending Failure Clock Requests
    $sqlUserPendingFailure_Clock = "SELECT COUNT(*) AS pending FROM failure_clock WHERE user_id = ? AND status = 'Pending'";
    $userPendingFailure_Clock = 0;
    if ($stmtFCP = $conn->prepare($sqlUserPendingFailure_Clock)) {
        $stmtFCP->bind_param("i", $user_id);
        $stmtFCP->execute();
        $userPendingFailure_Clock = $stmtFCP->get_result()->fetch_assoc()['pending'] ?? 0;
        $stmtFCP->close();
    } else {
        error_log("prepare failed (pending failure clock): " . $conn->error);
    }

    // User's Pending Clock Alteration Requests
    $sqlUserPendingClock_Alteration = "SELECT COUNT(*) AS pending FROM clock_alteration WHERE user_id = ? AND status = 'Pending'";
    $userPendingClock_Alteration = 0;
    if ($stmtCAP = $conn->prepare($sqlUserPendingClock_Alteration)) {
        $stmtCAP->bind_param("i", $user_id);
        $stmtCAP->execute();
        $userPendingClock_Alteration = $stmtCAP->get_result()->fetch_assoc()['pending'] ?? 0;
        $stmtCAP->close();
    } else {
        error_log("prepare failed (pending clock alteration): " . $conn->error);
    }

    // User's Pending Work Restday Requests
    $sqlUserPendingWork_Restday = "SELECT COUNT(*) AS pending FROM work_restday WHERE user_id = ? AND status = 'Pending'";
    $userPendingWork_Restday = 0;
    if ($stmtWRP = $conn->prepare($sqlUserPendingWork_Restday)) {
        $stmtWRP->bind_param("i", $user_id);
        $stmtWRP->execute();
        $userPendingWork_Restday = $stmtWRP->get_result()->fetch_assoc()['pending'] ?? 0;
        $stmtWRP->close();
    } else {
        error_log("prepare failed (pending work restday): " . $conn->error);
    }

    // Check if user did NOT logout today
    $noLogout = 0;
    $sqlNoLogout = "
        SELECT COUNT(*) AS no_logout 
        FROM attendance_logs 
        WHERE user_id = ? 
        AND log_date = CURDATE() 
        AND login_time IS NOT NULL 
        AND logout_time IS NULL
    ";
    $noLogout = 0;
    if ($stmtNL = $conn->prepare($sqlNoLogout)) {
        $stmtNL->bind_param("i", $user_id);
        $stmtNL->execute();
        $resNL = $stmtNL->get_result();
        $rowNL = $resNL->fetch_assoc();
        $noLogout = $rowNL['no_logout'] ?? 0;
        $stmtNL->close();
    } else {
        error_log("prepare failed (no logout): " . $conn->error);
    }

?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <h2 class="mt-4">Employee Dashboard</h2>
            <!-- Row for Welcome + Date -->
            <div class="container mt-5">
                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="mb-0">
                            Welcome, <?= htmlspecialchars($sessionUser['name'] ?? ($user['name'] ?? 'User')); ?> 👋
                        </h3>
                        <h5 class="text-muted mb-0"><?= date('l, F j, Y'); ?></h5>
                    </div>
                </div>
            </div>

            <div class="container">
                <div class="row g-3">

                <!-- Announcements (full width) -->
                <div class="col-12">
                    <div class="card mb-3 mt-4">
                        <div class="card-header bg-dark text-white">📢 Announcements</div>
                        <div class="card-body" style="max-height: 250px; overflow-y: auto;">
                            <?php if (!empty($announcements)): ?>
                                <?php foreach ($announcements as $a): ?>
                                    <div class="border-bottom mb-2 pb-2">
                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($a['title']); ?></h6>
                                        <p class="mb-1"><?= nl2br(htmlspecialchars($a['description'])); ?></p>
                                        <small class="text-muted">Posted on <?= date('F j, Y g:i A', strtotime($a['created_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No announcements at the moment.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

               <!-- Attendance Section -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header bg-secondary text-white">Attendance</div>
                        <div class="card-body text-center">
                            <?php
                            date_default_timezone_set('Asia/Manila'); // ✅ Fix timezone
                            $conn->query("SET time_zone = '+08:00'"); // ✅ Align MySQL timezone
                            $day = strtolower(date("l"));
                            $log_date = date("Y-m-d");

                            // ✅ Get today's schedule
                            $sql = "SELECT `$day` AS today_schedule FROM employee_schedules WHERE user_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $row = $result->fetch_assoc();
                            $todaySchedule = $row['today_schedule'] ?? '';

                            // ✅ Determine work type
                            $work_type = ($todaySchedule === "work_from_home") ? "work_from_home" : "onsite";

                            // ✅ Fetch today's attendance log
                            $check = $conn->prepare("
                                SELECT login_time, logout_time 
                                FROM attendance_logs 
                                WHERE user_id = ? AND log_date = ? AND work_type = ?
                            ");
                            $check->bind_param("iss", $user_id, $log_date, $work_type);
                            $check->execute();
                            $log = $check->get_result()->fetch_assoc();

                            if ($work_type === "work_from_home") {
                                echo "<h5>🏠 Work From Home</h5>";

                                if (!$log) {
                                    echo '<button class="btn btn-success" onclick="attendanceAction(\'wfh\', \'login\')">Login</button>';
                                } elseif ($log['login_time'] && !$log['logout_time']) {
                                    echo '<button class="btn btn-danger" onclick="attendanceAction(\'wfh\', \'logout\')">Logout</button>';
                                    echo '<p class="mt-2 text-muted">Logged in at: ' . date("h:i A", strtotime($log['login_time'])) . '</p>';
                                } else {
                                    echo '<p class="text-success">✔ You have logged in and out today.</p>';
                                    echo '<p class="text-muted">In: ' . date("h:i A", strtotime($log['login_time'])) .
                                        ' | Out: ' . date("h:i A", strtotime($log['logout_time'])) . '</p>';
                                }
                            } else {
                                echo "<h5>🏢 Onsite Work</h5>";

                                if ($log && $log['login_time']) {
                                    echo '<p><b>Login:</b> ' . date("h:i A", strtotime($log['login_time'])) . '</p>';
                                } else {
                                    echo '<p class="text-muted">No login record today.</p>';
                                }

                                if ($log && $log['logout_time']) {
                                    echo '<p><b>Logout:</b> ' . date("h:i A", strtotime($log['logout_time'])) . '</p>';
                                } else {
                                    echo '<p class="text-muted">No logout record today.</p>';
                                }
                            }
                            ?>
                            <p id="attendanceStatus" class="mt-2"></p>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header bg-danger text-white">Tardiness Frequency</div>
                        <div class="card-body text-center">
                            <p><b><?= date('F Y'); ?>:</b> <?= $timesLate ?> time(s) late</p>
                            <p><b>Total Late Time:</b> <?= $totalLate ?></p>
                            <a href="TARDINESS_REPORT.PHP" class="btn btn-sm btn-outline-light mt-2">View Detail Here</a>
                        </div>
                    </div>
                </div>

                <!-- Leave Balance -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">Leave Balance</div>
                        <div class="card-body text-center">
                        <div id="leaveBalance" class="mb-3">
                            Loading leave balance...
                        </div>
                        <a href="LEAVE_APPLICATION.php" class="btn btn-primary">
                            Apply for Leave
                        </a>
                        </div>
                    </div>
                </div>

                <!-- Events -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">Events</div>
                        <div class="card-body"><div id="eventSection">Loading events...</div></div>
                    </div>
                </div>

                <!-- Work Schedule -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header bg-warning">Work Schedule</div>
                        <div class="card-body" id="scheduleSection">Loading schedule...</div>
                    </div>
                </div>

                <!-- Payroll Period -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header bg-info">Current Payroll Period</div>
                        <div class="card-body"><div id="payrollSection">Loading payroll...</div></div>
                    </div>
                </div>

                <!-- Users Pending Leaves and No Logout -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card mb-3">

                        <!-- Pending Requests -->
                        <div class="card-header bg-info text-white">User Pending Requests</div>
                        <div class="card-body">

                            <!-- One row per request -->
                            <a href="LEAVE_APPLICATION.php" class="text-decoration-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Pending Leave Requests</span>
                                    <span class="fw-bold text-primary"><?= $userPendingLeave ?></span>
                                </div>

                            <a href="OVERTIME.php" class="text-decoration-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Pending Overtime Requests</span>
                                    <span class="fw-bold text-primary"><?= $userPendingOvertime ?></span>
                                </div>
                            </a>

                            <a href="OFFICIAL_BUSINESS.php" class="text-decoration-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Pending Official Business Requests</span>
                                    <span class="fw-bold text-primary"><?= $userPendingOfficial_Business ?></span>
                                </div>
                            </a>
                            
                            <a href="CHANGE_SCHEDULE.php" class="text-decoration-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Pending Change Schedule Requests</span>
                                    <span class="fw-bold text-primary"><?= htmlspecialchars($userPendingChange_Schedule) ?></span>
                                </div>
                            </a>

                            <a href="FAILURE_CLOCK.php" class="text-decoration-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Pending Failure to Clock In/Out</span>
                                    <span class="fw-bold text-primary"><?= $userPendingFailure_Clock ?></span>
                                </div>
                            </a>

                            <a href="CLOCK_ALTERATION.php" class="text-decoration-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Pending Clock Alteration Requests</span>
                                    <span class="fw-bold text-primary"><?= $userPendingClock_Alteration ?></span>
                                </div>
                            </a>

                            <a href="WORK_RESTDAY.php" class="text-decoration-none">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Pending Work Restday Requests</span>
                                    <span class="fw-bold text-primary"><?= $userPendingWork_Restday ?></span>
                                </div>
                            </a>
                        </div>

                        <!-- No Logout -->
                        <div class="card-header bg-danger text-white">No Logout Today</div>
                        <div class="card-body text-center">
                            <?php if ($noLogout > 0): ?>
                                <h3 class="fw-bold text-danger"><?= $noLogout ?></h3>
                                <p class="text-muted">You forgot to logout today.</p>
                                <a href="failure_clock.php" class="btn btn-sm btn-outline-danger">Submit Failure to Logout</a>
                            <?php else: ?>
                                <h5 class="text-success">✔ You are logged out today.</h5>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($approver): ?>
                    <div class="col-12">
                        <div class="card mb-3">
                            <div class="card-header bg-danger text-white">Approver's Pending</div>
                            <div class="card-body">
                                <div class="dropdown-item p-2 mb-2 border-bottom">
                                    <a href="pending_leaves.php" class="d-flex align-items-center text-decoration-none text-dark">
                                        <i class="fa fa-plane me-2"></i>
                                        <span>Leave</span>
                                        <span class="badge bg-danger ms-auto" id="pendingLeaves">0</span>
                                    </a>
                                </div>

                                <div class="dropdown-item p-2 mb-2 border-bottom">
                                    <a href="approver_overtime.php" class="d-flex align-items-center text-decoration-none text-dark">
                                        <i class="fa fa-clock me-2"></i>
                                        <span>Overtime</span>
                                        <span class="badge bg-danger ms-auto" id="pendingOvertime">0</span>
                                    </a>
                                </div>

                                <div class="dropdown-item p-2 mb-2 border-bottom">
                                    <a href="approver_official_business.php" class="d-flex align-items-center text-decoration-none text-dark">
                                        <i class="fa fa-briefcase me-2"></i>
                                        <span>Official Business</span>
                                        <span class="badge bg-danger ms-auto" id="pendingOB">0</span>
                                    </a>
                                </div>

                                <div class="dropdown-item p-2 mb-2 border-bottom">
                                    <a href="approver_change_schedule.php" class="d-flex align-items-center text-decoration-none text-dark">
                                        <i class="fa fa-calendar-check me-2"></i>
                                        <span>Change Schedule</span>
                                        <span class="badge bg-danger ms-auto" id="pendingCS">0</span>
                                    </a>
                                </div>

                                <div class="dropdown-item p-2 mb-2 border-bottom">
                                    <a href="approver_failure_clock.php" class="d-flex align-items-center text-decoration-none text-dark">
                                        <i class="fa fa-exclamation-triangle me-2"></i>
                                        <span>Failure to Clock</span>
                                        <span class="badge bg-danger ms-auto" id="pendingFC">0</span>
                                    </a>
                                </div>

                                <div class="dropdown-item p-2 mb-2 border-bottom">
                                    <a href="approver_clock_alteration.php" class="d-flex align-items-center text-decoration-none text-dark">
                                        <i class="fa fa-edit me-2"></i>
                                        <span>Clock Alteration</span>
                                        <span class="badge bg-danger ms-auto" id="pendingCA">0</span>
                                    </a>
                                </div>

                                <div class="dropdown-item p-2">
                                    <a href="approver_work_restday.php" class="d-flex align-items-center text-decoration-none text-dark">
                                        <i class="fa fa-sun me-2"></i>
                                        <span>Work Rest Day</span>
                                        <span class="badge bg-danger ms-auto" id="pendingWR">0</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php include __DIR__ . '/layout/FOOTER'; ?>
        </div>
    </main>
</div> 

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="js/scripts.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
<script src="assets/demo/chart-area-demo.js"></script>
<script src="assets/demo/chart-bar-demo.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
<script src="js/datatables-simple-demo.js"></script>

<!-- Auto-refresh dashboard data every 5 seconds -->
<script>
    function fetchDashboard() {
        fetch("DASHBOARD_DATA.php")
            .then(res => res.json())
            .then(data => { 
                console.log(data);
                if (data.error) {
                    document.getElementById("leaveBalance").innerHTML = "<p>Error: " + data.error + "</p>";
                    return;
                }

                // ✅ Update Leave Balance
                document.getElementById("leaveBalance").innerHTML = `
                    <p><b>Mandatory Leave:</b> ${data.leave?.mandatory ?? 0}</p>
                    <p><b>Vacation Leave:</b> ${data.leave?.vacation_leave ?? 0}</p>
                    <p><b>Sick Leave:</b> ${data.leave?.sick_leave ?? 0}</p>
                `;

                // ✅ Update Events
                let bdays = data.birthdays.length 
                    ? data.birthdays.map(b => `<p>${b.name} (${new Date(b.birthday).toLocaleDateString('en-US',{month:'short',day:'numeric'})})</p>`).join("")
                    : "<p>No birthdays today</p>";

                let holidays = data.holidays.length
                    ? data.holidays.map(h => `<p><b>${h.title}</b> - ${new Date(h.date).toLocaleDateString()}</p>`).join("")
                    : "<p>No upcoming holidays</p>";

                document.getElementById("eventSection").innerHTML = `
                    <h6>🎂 Birthdays Today</h6>${bdays}
                    <h6 class="mt-3">📅 Upcoming Holidays</h6>${holidays}
                `;

                // ✅ Update Work Schedule
                if (data.schedule) {
                    document.getElementById("scheduleSection").innerHTML = `
                        <table class="table table-bordered">
                            <tr><th>Monday</th><td>${data.schedule.monday}</td></tr>
                            <tr><th>Tuesday</th><td>${data.schedule.tuesday}</td></tr>
                            <tr><th>Wednesday</th><td>${data.schedule.wednesday}</td></tr>
                            <tr><th>Thursday</th><td>${data.schedule.thursday}</td></tr>
                            <tr><th>Friday</th><td>${data.schedule.friday}</td></tr>
                            <tr><th>Saturday</th><td>${data.schedule.saturday}</td></tr>
                            <tr><th>Sunday</th><td>${data.schedule.sunday}</td></tr>
                        </table>
                    `;
                } else {
                    document.getElementById("scheduleSection").innerHTML = "<p>No schedule set</p>";
                }

                // ✅ Update Payroll
                if (data.period) {
                    document.getElementById("payrollSection").innerHTML = `
                        <p><b>Period Code:</b> ${data.period.period_code}</p>
                        <p><b>Start:</b> ${data.period.start_date} | <b>End:</b> ${data.period.end_date}</p>
                        <p><b>Cutoff:</b> ${data.period.cutoff}</p>
                    `;
                } else {
                    document.getElementById("payrollSection").innerHTML = "<p>No payroll period found</p>";
                }

                // ✅ Update Pending Approvals (only for approvers)
                if (data.pending) {
                    document.getElementById("pendingLeaves").innerText = data.pending.leaves;
                    document.getElementById("pendingOvertime").innerText = data.pending.overtime;
                    document.getElementById("pendingOB").innerText = data.pending.official_business;
                    document.getElementById("pendingCS").innerText = data.pending.change_schedule;
                    document.getElementById("pendingFC").innerText = data.pending.failure_clock;
                    document.getElementById("pendingCA").innerText = data.pending.clock_alteration;
                    document.getElementById("pendingWR").innerText = data.pending.work_restday;
                    document.querySelector(".card.bg-danger").style.display = "block"; // show card
                } else {
                    document.querySelector(".card.bg-danger").style.display = "none"; // hide card if not approver
                }
            });
    }

    // Run immediately and auto-refresh every 5s
    fetchDashboard();
    setInterval(fetchDashboard, 5000);
</script>

<script>
    function wfhAction(action) {
        fetch("WFH_ACTION.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: "action=" + action
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("wfhStatus").innerHTML = `<b>${data.success}</b>`;
                setTimeout(() => location.reload(), 1000); // refresh to update buttons
            } else {
                document.getElementById("wfhStatus").innerHTML = `<span class="text-danger">${data.error}</span>`;
            }
        })
        .catch(err => {
            document.getElementById("wfhStatus").innerHTML = "<span class='text-danger'>Error processing request</span>";
        });
    }
</script>

<script>
    function attendanceAction(type, action) {
        fetch("ATTENDANCE_ACTION.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: `type=${type}&action=${action}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("attendanceStatus").innerHTML = `<b>${data.success}</b>`;
                setTimeout(() => location.reload(), 1000);
            } else {
                document.getElementById("attendanceStatus").innerHTML = `<span class="text-danger">${data.error}</span>`;
            }
        })
        .catch(err => {
            document.getElementById("attendanceStatus").innerHTML = "<span class='text-danger'>Error processing request.</span>";
        });
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>


