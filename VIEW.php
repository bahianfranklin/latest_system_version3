<?php
    session_start();
    require 'db.php';

    // 🚫 Prevent cached pages
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }

    $user = $_SESSION['user'];

    // ✅ Assume logged-in user
    $user_id = $_SESSION['user_id'] ?? 1; // change if needed

    // ✅ Get Leave Balance
    $leave_sql = "SELECT mandatory, vacation_leave, sick_leave FROM leave_credits WHERE user_id = ?";
    $stmt = $conn->prepare($leave_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $leave = $stmt->get_result()->fetch_assoc();

    // ✅ Get Events
    $today = date("Y-m-d");
?>

<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
        <title>Welcome | Dashboard</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
            <!-- Navbar Brand-->
            <a class="navbar-brand ps-3" href="index.php">Start Bootstrap</a>
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
            <!-- Navbar-->
             <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0"></form>
                <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="#!">Settings</a></li>
                            <li><a class="dropdown-item" href="#!">Activity Log</a></li>
                            <li><hr class="dropdown-divider" /></li>
                            <li><a class="dropdown-item" href="LOGOUT.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </form>
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <a class="nav-link" href="VIEW.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Dashboard
                            </a>
                            <div class="sb-sidenav-menu-heading">Interface</div>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
                                <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
                                Layouts
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav">
                                    <a class="nav-link" href="layout-static.html">Static Navigation</a>
                                    <a class="nav-link" href="layout-sidenav-light.html">Light Sidenav</a>
                                </nav>
                            </div>
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages" aria-expanded="false" aria-controls="collapsePages">
                                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                                Pages
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapsePages" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionPages">
                                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#pagesCollapseAuth" aria-expanded="false" aria-controls="pagesCollapseAuth">
                                        Authentication
                                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                                    </a>
                                    <div class="collapse" id="pagesCollapseAuth" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordionPages">
                                        <nav class="sb-sidenav-menu-nested nav">
                                            <a class="nav-link" href="login.html">Login</a>
                                            <a class="nav-link" href="register.html">Register</a>
                                            <a class="nav-link" href="password.html">Forgot Password</a>
                                        </nav>
                                    </div>
                                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#pagesCollapseError" aria-expanded="false" aria-controls="pagesCollapseError">
                                        Error
                                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                                    </a>
                                    <div class="collapse" id="pagesCollapseError" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordionPages">
                                        <nav class="sb-sidenav-menu-nested nav">
                                            <a class="nav-link" href="401.html">401 Page</a>
                                            <a class="nav-link" href="404.html">404 Page</a>
                                            <a class="nav-link" href="500.html">500 Page</a>
                                        </nav>
                                    </div>
                                </nav>
                            </div>
                            <div class="sb-sidenav-menu-heading">Addons</div>
                            <a class="nav-link" href="charts.html">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                                Charts
                            </a>
                            <a class="nav-link" href="tables.html">
                                <div class="sb-nav-link-icon"><i class="fas fa-table"></i></div>
                                Tables
                            </a>
                        </div>
                    </div>
                    <div class="sb-sidenav-footer">
                        <div class="small">Logged in as:</div>
                        Start Bootstrap
                    </div>
                </nav>
            </div>

        <!-- WELCOME, DETAILS CODE -->
        <div class="container mt-5">
            <div class="card p-4">
                <!-- Row for Welcome + Date -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="mb-0">
                        Welcome, <?= htmlspecialchars($user['name']); ?> 👋
                        <!-- Toggle button -->
                        <button class="btn btn-sm btn-outline-primary ms-2" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#userDetails" 
                                aria-expanded="false" aria-controls="userDetails">
                            ▼
                        </button>
                    </h2>
                    <h5 class="text-muted mb-0"><?= date('l, F j, Y'); ?></h5>
                </div>

            <!-- Hidden details (dropdown) -->
            <div class="collapse mt-3" id="userDetails">

                    <!-- Profile Picture -->
                        <?php 
                            $profilePath = "uploads/" . $user['profile_pic']; 
                            if (!empty($user['profile_pic']) && file_exists($profilePath)): ?>
                                <div class="mb-3 text-center">
                                    <img src="<?= $profilePath ?>?t=<?= time(); ?>" 
                                        alt="Profile Picture" 
                                        class="img-thumbnail rounded-circle" 
                                        style="width:150px; height:150px; object-fit:cover;">
                                </div>
                            <?php else: ?>
                                <div class="mb-3 text-center">
                                    <img src="uploads/default.png" 
                                        class="rounded-circle border border-2" 
                                        style="width:120px; height:120px; object-fit:cover;">
                                </div>
                            <?php endif; 
                        ?>

                        <p><strong>Address:</strong> <?= htmlspecialchars($user['address']); ?></p>
                        <p><strong>Contact:</strong> <?= htmlspecialchars($user['contact']); ?></p>
                        <p><strong>Birthday:</strong> 
                            <?= !empty($user['birthday']) ? date("F d, Y", strtotime($user['birthday'])) : "—"; ?>
                        </p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
                        <p><strong>Username:</strong> <?= htmlspecialchars($user['username']); ?></p>
                        <p><strong>Role:</strong> <?= htmlspecialchars($user['role']); ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($user['status']); ?></p>

                        <a href="edit_user-profile.php?id=<?= $user['id']; ?>" class="btn btn-primary">Edit</a>
                        <a href="logout.php" class="btn btn-danger">Logout</a>
                    </div>

                </div>
            </div>

        <!-- Bootstrap JS (needed for collapse to work) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

        <br>
        <br>
        <br>
        <div class="container">
            <h3 class="mb-4">Employee Dashboard</h3>

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
        
            <div class="card mb-3">
                <div class="card-header bg-danger">Pending</div>
                    <div class="card-body">
                        <li><a class="dropdown-item" href="pending_leaves.php">
                            <i class="fa fa-plane"></i> Leave 
                            <span class="badge bg-danger" id="pendingLeaves">0</span>
                        </a></li>

                        <li><a class="dropdown-item" href="approver_overtime.php">
                            <i class="fa fa-clock"></i> Overtime 
                            <span class="badge bg-danger" id="pendingOvertime">0</span>
                        </a></li>

                        <li><a class="dropdown-item" href="approver_official_business.php">
                            <i class="fa fa-briefcase"></i> Official Business 
                            <span class="badge bg-danger" id="pendingOB">0</span>
                        </a></li>

                        <li><a class="dropdown-item" href="approver_change_schedule.php">
                            <i class="fa fa-calendar-check"></i> Change Schedule 
                            <span class="badge bg-danger" id="pendingCS">0</span>
                        </a></li>

                        <li><a class="dropdown-item" href="approver_failure_clock.php">
                            <i class="fa fa-exclamation-triangle"></i> Failure to Clock 
                            <span class="badge bg-danger" id="pendingFC">0</span>
                        </a></li>

                        <li><a class="dropdown-item" href="approver_clock_alteration.php">
                            <i class="fa fa-edit"></i> Clock Alteration 
                            <span class="badge bg-danger" id="pendingCA">0</span>
                        </a></li>

                        <li><a class="dropdown-item" href="approver_work_restday.php">
                            <i class="fa fa-sun"></i> Work Rest Day 
                            <span class="badge bg-danger" id="pendingWR">0</span>
                        </a></li>
                    </div>
                </div>
            </div>
        </div>

    <script>
    function fetchDashboard() {
        fetch("dashboard_data.php")
            .then(res => res.json())
            .then(data => {
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
    
    </body>
</html>
