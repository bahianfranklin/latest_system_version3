<?php
    session_start();
    require 'db.php';

    // 🚫 Prevent cached pages
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");

    // ✅ Assume logged-in user
    $user_id = $_SESSION['user_id'] ?? 1; // change if needed

    // ✅ Get Leave Balance
    $leave_sql = "SELECT mandatory, vacation_leave, sick_leave FROM leave_credits WHERE user_id = ?";
    $stmt = $conn->prepare($leave_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $leave = $result->fetch_assoc();

    // ✅ Get Events
    $today = date("Y-m-d");

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
                                    Welcome, <?= htmlspecialchars($user['name']); ?> 👋
                                </h3>
                                <h5 class="text-muted mb-0"><?= date('l, F j, Y'); ?></h5>
                            </div>
                        </div>
                    </div>

                    <div class="container">
                        <h1 class="mb-4"></h1>
                        <!-- Work From Home Section -->
                        <div class="card mb-3">
                            <div class="card-header bg-secondary text-white">Work From Home</div>
                            <div class="card-body text-center">
                                <?php
                                $day = strtolower(date("l")); // e.g. "monday"

                                $sql = "SELECT `$day` AS today_schedule FROM employee_schedules WHERE user_id = ?";
                                $stmt = $conn->prepare($sql);
                                $row = [];
                                if (!$stmt) {
                                    echo '<div class="alert alert-danger">SQL Prepare failed: ' . htmlspecialchars($conn->error) . '<br>Query: ' . htmlspecialchars($sql) . '<br>Check if the column <b>' . htmlspecialchars($day) . '</b> exists in employees_schedule.</div>';
                                } else {
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $row = $result->fetch_assoc();
                                }

                                // check if today is WFH
                                if ($row && $row['today_schedule'] === "work_from_home") {
                                    // check current status in attendance_logs
                                    $check = $conn->prepare("SELECT login_time, logout_time 
                                                            FROM attendance_logs 
                                                            WHERE user_id = ? AND log_date = ? AND work_type = 'work_from_home'");
                                    $log_date = date("Y-m-d");
                                    $check->bind_param("is", $user_id, $log_date);
                                    $check->execute();
                                    $checkResult = $check->get_result();
                                    $log = $checkResult->fetch_assoc();

                                    if (!$log) {
                                        // not logged in yet
                                        echo '<button class="btn btn-success" onclick="wfhAction(\'login\')">Login</button>';
                                    } elseif ($log['login_time'] && !$log['logout_time']) {
                                        // logged in but not yet logged out
                                        echo '<button class="btn btn-danger" onclick="wfhAction(\'logout\')">Logout</button>';
                                        echo '<p class="mt-2 text-muted">Logged in at: ' . date("h:i A", strtotime($log['login_time'])) . '</p>';
                                    } elseif ($log['login_time'] && $log['logout_time']) {
                                        // already logged in & out
                                        echo '<p class="text-success">✔ You have logged in and out today.</p>';
                                        echo '<p class="text-muted">In: ' . date("h:i A", strtotime($log['login_time'])) . 
                                            ' | Out: ' . date("h:i A", strtotime($log['logout_time'])) . '</p>';
                                    }
                                } else {
                                    echo "<p class='text-muted'>Today is not a WFH schedule.</p>";
                                }
                                ?>
                                <p id="wfhStatus" class="mt-2"></p>
                            </div>
                        </div>

                        <!-- Leave Balance -->
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">Leave Balance</div>
                            <div class="card-body">
                                <div id="leaveBalance">
                                    Loading leave balance...
                                </div>
                            </div>
                        </div>

                        <!-- Events -->
                        <div class="card mb-3">
                            <div class="card-header bg-success text-white">Events</div>
                            <div class="card-body">
                                <div id="eventSection">
                                    Loading events...
                                </div>
                            </div>
                        </div>
                            
                        <!-- Work Schedule -->
                        <div class="card mb-3">
                            <div class="card-header bg-warning">Work Schedule</div>
                            <div id="scheduleSection">
                                Loading schedule...
                            </div>
                        </div>

                        <!-- Payroll Period -->
                        <div class="card mb-3">
                            <div class="card-header bg-info">Current Payroll Period</div>
                            <div class="card-body">
                                <div id="payrollSection">
                                    Loading payroll...
                                </div>
                            </div>
                        </div>

                        <?php if ($approver): ?>
                            <div class="card mb-3">
                                <div class="card-header bg-danger text-white">Pending</div>
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
                        <?php endif; ?>
                    </div>
                <?php include __DIR__ . '/layout/FOOTER'; ?>
            </div>
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

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>


