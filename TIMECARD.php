<?php
// ✅ Prevent output before headers
ob_start();
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
require 'audit.php';
require 'autolock.php';

// ✅ Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: LOGIN.php");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// ✅ Fetch available payroll periods
$sql = "SELECT id, period_code, start_date, end_date 
        FROM payroll_periods 
        WHERE status = 'Open' 
        ORDER BY start_date DESC";
$result = $conn->query($sql);

$attendance_data = [];
$totalHours = 0;
$attendance_by_date = [];

$schedule = [
    'monday'    => 'work_day',
    'tuesday'   => 'work_day',
    'wednesday' => 'work_day',
    'thursday'  => 'work_day',
    'friday'    => 'work_day',
    'saturday'  => 'rest_day',
    'sunday'    => 'rest_day'
];

if (isset($_GET['period_id']) && !empty($_GET['period_id'])) {
    $period_id = $_GET['period_id'];

    $stmt = $conn->prepare("SELECT start_date, end_date FROM payroll_periods WHERE id = ?");
    $stmt->bind_param("i", $period_id);
    $stmt->execute();
    $period = $stmt->get_result()->fetch_assoc();

    if ($period) {
        $start_date = $period['start_date'];
        $end_date = $period['end_date'];

        // 🔥 AUDIT TRAIL
        logAction(
            $conn,
            $user_id,
            "VIEW TIMECARD",
            "Viewed timecard for period $start_date to $end_date"
        );

        // ✅ Fetch attendance logs
        $sql = "SELECT * FROM attendance_logs 
                WHERE user_id = ? AND log_date BETWEEN ? AND ?
                ORDER BY log_date ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $start_date, $end_date);
        $stmt->execute();
        $attendance_result = $stmt->get_result();

        $attendance_data = $attendance_result->fetch_all(MYSQLI_ASSOC);

        // ✅ Build keyed attendance_by_date so we can merge FC records into existing logs
        $attendance_by_date = [];
        foreach ($attendance_data as $a) {
            // prefer log_date column, fall back to date
            $date = $a['log_date'] ?? ($a['date'] ?? null);
            if (!$date) continue;

            $login = $a['login_time'] ?? null;
            $logout = $a['logout_time'] ?? null;

            // make sure times are full datetimes for strtotime
            if ($login && strpos($login, ' ') === false) $login = $date . ' ' . $login;
            if ($logout && strpos($logout, ' ') === false) $logout = $date . ' ' . $logout;

            $attendance_by_date[$date] = [
                'log_date' => $date,
                'login_time' => $login,
                'logout_time' => $logout,
                'work_type' => $a['work_type'] ?? 'onsite',
                'from_fc' => $a['from_fc'] ?? false,
            ];
        }

        // ✅ Fetch Approved Failure to Clock In/Out records
        $sql = "SELECT date, type, time_in, time_out
                FROM failure_clock
                WHERE applied_by = ?
                AND status = 'Approved'
                AND date BETWEEN ? AND ?
                ORDER BY date ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $user_id, $start_date, $end_date);
        $stmt->execute();
        $fc_result = $stmt->get_result();
        $fc_raw = $fc_result->fetch_all(MYSQLI_ASSOC);

        // ✅ Organize FC by date
        $fc_data = [];

        foreach ($fc_raw as $fc) {
            $date = $fc['date'];
            $type = $fc['type'] ?? '';

            if (!isset($fc_data[$date])) {
                $fc_data[$date] = [
                    'date' => $date,
                    'time_in' => null,
                    'time_out' => null
                ];
            }

            // Always apply time_in/time_out if present (DB may store type variations)
            if (!empty($fc['time_in'])) {
                $fc_data[$date]['time_in'] = $fc['time_in'];
            }

            if (!empty($fc['time_out'])) {
                $fc_data[$date]['time_out'] = $fc['time_out'];
            }

            // Backwards compatibility: also use type to infer if columns are oddly named
            $tNorm = strtolower(str_replace('-', ' ', trim($type)));
            if ((strpos($tNorm, 'clock in') !== false || strpos($tNorm, 'clock-in') !== false) && empty($fc_data[$date]['time_in']) && !empty($fc['time_in'])) {
                $fc_data[$date]['time_in'] = $fc['time_in'];
            }
            if ((strpos($tNorm, 'clock out') !== false || strpos($tNorm, 'clock-out') !== false) && empty($fc_data[$date]['time_out']) && !empty($fc['time_out'])) {
                $fc_data[$date]['time_out'] = $fc['time_out'];
            }
        }

        // ✅ STEP 2: Apply Approved Clock Alterations
        // For each approved FC row: upsert into attendance_logs so approved FC persists
        $upsertStmt = $conn->prepare(
            "SELECT id, login_time, logout_time FROM attendance_logs WHERE user_id = ? AND log_date = ? LIMIT 1"
        );

        $insertStmt = $conn->prepare(
            "INSERT INTO attendance_logs (user_id, log_date, login_time, logout_time, work_type) VALUES (?, ?, ?, ?, ?)"
        );

        $updateStmt = $conn->prepare(
            "UPDATE attendance_logs SET login_time = COALESCE(?, login_time), logout_time = COALESCE(?, logout_time) WHERE id = ?"
        );

        foreach ($fc_data as $fc) {
            $date = $fc['date'];
            $time_in = $fc['time_in'] ? ($date . ' ' . $fc['time_in']) : null;
            $time_out = $fc['time_out'] ? ($date . ' ' . $fc['time_out']) : null;

            // Check for existing attendance log
            $upsertStmt->bind_param('is', $user_id, $date);
            $upsertStmt->execute();
            $res = $upsertStmt->get_result();

            if ($row = $res->fetch_assoc()) {
                // Update existing record with FC times if present
                $id = $row['id'];
                $updateStmt->bind_param('ssi', $time_in, $time_out, $id);
                $updateStmt->execute();
            } else {
                // Insert new attendance log
                // Default work_type = 'onsite' when creating from FC
                $work_type = 'onsite';
                $insertStmt->bind_param('issss', $user_id, $date, $time_in, $time_out, $work_type);
                $insertStmt->execute();
            }

            // Update in-memory representation
            if (isset($attendance_by_date[$date])) {
                if ($time_in) $attendance_by_date[$date]['login_time'] = $time_in;
                if ($time_out) $attendance_by_date[$date]['logout_time'] = $time_out;
                $attendance_by_date[$date]['from_fc'] = true;
            } else {
                $attendance_by_date[$date] = [
                    'log_date' => $date,
                    'login_time' => $time_in,
                    'logout_time' => $time_out,
                    'work_type' => 'onsite',
                    'from_fc' => true
                ];
            }
        }

        // Close prepared statements
        $upsertStmt->close();
        $insertStmt->close();
        $updateStmt->close();
    }
}

// ✅ Prepare attendance events for FullCalendar
$events = [];
if (!empty($attendance_data)) {
    $today = date("Y-m-d");

    foreach ($attendance_data as $row) {
        $log_date = $row['log_date'];
        $login = $row['login_time'] ? date("h:i A", strtotime($row['login_time'])) : "No Login";
        $logout = $row['logout_time'] ? date("h:i A", strtotime($row['logout_time'])) : "No Logout";

        $title = "Login: $login\nLogout: $logout";
        $color = isset($row['from_fc']) && $row['from_fc'] ? "#ffc107" : "#28a745"; // yellow if FC

        $events[] = [
            'title' => $title,
            'start' => $log_date,
            'color' => $color,
        ];
    }

    // Add rest days only; no absents for future dates
    $current = strtotime($start_date);
    $end = strtotime($end_date);
    $attendance_by_date = array_column($attendance_data, null, 'log_date');

    while ($current <= $end) {
        $date = date("Y-m-d", $current);
        $day = strtolower(date("l", $current));

        // Skip future dates
        if ($date > $today) {
            $current = strtotime("+1 day", $current);
            continue;
        }

        if (!isset($attendance_by_date[$date])) {
            if ($schedule[$day] === 'rest_day') {
                $events[] = [
                    'title' => 'Rest Day',
                    'start' => $date,
                    'color' => '#6c757d', // gray
                ];
            } else {
                $events[] = [
                    'title' => 'Absent',
                    'start' => $date,
                    'color' => '#dc3545', // red
                ];
            }
        }

        $current = strtotime("+1 day", $current);
    }
}

// ✅ Fetch approved overtime (from table `overtime`)
$sql = "SELECT date, from_time, to_time, purpose 
        FROM overtime 
        WHERE applied_by = ? 
          AND date BETWEEN ? AND ? 
          AND status = 'Approved'
        ORDER BY date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$ot_result = $stmt->get_result();
$overtime_data = $ot_result->fetch_all(MYSQLI_ASSOC);

// ✅ Overtime Events (blue)
foreach ($overtime_data as $ot) {
    $ot_date = $ot['date'];
    $from = $ot['from_time'] ? date("h:i A", strtotime($ot['from_time'])) : '';
    $to   = $ot['to_time'] ? date("h:i A", strtotime($ot['to_time'])) : '';
    $purpose = $ot['purpose'] ?? '';
    
    $title = "Overtime (Approved)\n$from - $to\nPurpose: $purpose";
    $color = "#007bff"; // blue
    $events[] = [
        'title' => $title,
        'start' => $ot_date,
        'color' => $color,
    ];
}

// ✅ Fetch approved leaves within the selected payroll period
$sql = "SELECT leave_type, type, date_from, date_to, remarks 
    FROM leave_requests 
    WHERE user_id = ? 
        AND status = 'Approved' 
        AND (
            (date_from BETWEEN ? AND ?) OR 
            (date_to BETWEEN ? AND ?) OR 
            (? BETWEEN date_from AND date_to) OR 
            (? BETWEEN date_from AND date_to)
        )
    ORDER BY date_from ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssss", $user_id, $start_date, $end_date, $start_date, $end_date, $start_date, $end_date);
    $stmt->execute();
    $leave_result = $stmt->get_result();
    $leaves = $leave_result->fetch_all(MYSQLI_ASSOC);

    // ✅ Insert leave days into the attendance_by_date array
    foreach ($leaves as $leave) {
        $leave_type = ucfirst($leave['leave_type']);
        $pay_type   = ucfirst($leave['type']); // "With Pay" or "Without Pay"
        $remarks    = $leave['remarks'] ?? '';
        $from_date  = $leave['date_from'];
        $to_date    = $leave['date_to'];

        // Loop through all dates in the leave period
        $start = strtotime($from_date);
        $end   = strtotime($to_date);

        while ($start <= $end) {
            $date = date('Y-m-d', $start);

            // ✅ Override existing "Absent" entries only (don’t overwrite attendance logs)
            if (!isset($attendance_by_date[$date])) {
                $attendance_by_date[$date] = [
                    'log_date'    => $date,
                    'login_time'  => null,
                    'logout_time' => null,
                    'work_type'   => 'leave',
                    'leave_info'  => "$leave_type ($pay_type)",
                    'remarks'     => $remarks,
                ];
            } elseif (empty($attendance_by_date[$date]['login_time']) && empty($attendance_by_date[$date]['logout_time'])) {
                // Mark leave even if attendance log exists but empty
                $attendance_by_date[$date]['work_type'] = 'leave';
                $attendance_by_date[$date]['leave_info'] = "$leave_type ($pay_type)";
                $attendance_by_date[$date]['remarks'] = $remarks;
            }

            // ✅ Add to calendar
            $title = "Leave: $leave_type\nType: $pay_type";
            if (!empty($remarks)) $title .= "\nRemarks: $remarks";

            $events[] = [
                'title' => $title,
                'start' => $date,
                'color' => ($pay_type === 'With Pay') ? '#0d6efd' : '#6c757d' // blue for paid, gray for unpaid
            ];

            $start = strtotime('+1 day', $start);
        }
    }

    // ✅ Rebuild array and recalculate total hours (attendance + paid leave)
    $attendance_data = array_values($attendance_by_date);
    $totalHours = 0;

    foreach ($attendance_data as $row) {
        if (!empty($row['login_time']) && !empty($row['logout_time'])) {
            // Regular attendance hours
            $totalHours += (strtotime($row['logout_time']) - strtotime($row['login_time'])) / 3600;
        } elseif (
            isset($row['work_type']) && strtolower($row['work_type']) === 'leave' &&
            isset($row['leave_info']) && stripos($row['leave_info'], 'With Pay') !== false
        ) {
            // Paid leave = default 8 hours
            $totalHours += 8;
        }
    }
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<!-- ✅ Include Bootstrap + FullCalendar -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">

<div id="layoutSidenav_content">
    <main class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
            <h3>Timecard</h3>
        </div>

        <!-- Payroll Period -->
        <form method="GET" action="" class="row g-3 mb-4">
            <div class="col-md-6 col-lg-4">
                <label for="period" class="form-label fw-semibold">Select Payroll Period:</label>
                <select name="period_id" id="period" class="form-select" required>
                    <option value="">-- Select Period --</option>
                    <?php while ($row = $result->fetch_assoc()) { ?>
                        <option value="<?= $row['id']; ?>" 
                            <?= (isset($_GET['period_id']) && $_GET['period_id'] == $row['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['period_code']) ?> 
                            (<?= htmlspecialchars($row['start_date']) ?> → <?= htmlspecialchars($row['end_date']) ?>)
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary w-100">View</button>
            </div>
        </form>

        <?php if (!empty($attendance_data)) { ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    Attendance Records for <?= htmlspecialchars($user['name']) ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered text-center align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Login</th>
                                    <th>Logout</th>
                                    <th>Work Type</th>
                                    <th>Hours Worked</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendance_data as $row) { 
                                    $hours = 0;
                                    if (!empty($row['login_time']) && !empty($row['logout_time'])) {
                                        // Regular attendance hours
                                        $hours = (strtotime($row['logout_time']) - strtotime($row['login_time'])) / 3600;
                                    } elseif (
                                        isset($row['work_type']) && strtolower($row['work_type']) === 'leave' &&
                                        isset($row['leave_info']) && stripos($row['leave_info'], 'With Pay') !== false
                                    ) {
                                        // Paid leave = default 8 hours
                                        $hours = 8;
                                    }
                                    // highlight FC entries
                                    $isFC = isset($row['from_fc']) && $row['from_fc'];
                                ?>
                                    <tr class="<?= $isFC ? 'table-warning' : '' ?>">
                                        <td><?= htmlspecialchars($row['log_date']) ?></td>
                                        <td><?= $row['login_time'] ? date("h:i A", strtotime($row['login_time'])) : '' ?></td>
                                        <td><?= $row['logout_time'] ? date("h:i A", strtotime($row['logout_time'])) : '' ?></td>
                                        <td>
                                            <?php
                                                $work_type = strtolower($row['work_type']);
                                                $color_class = 'bg-warning'; // Default

                                                switch ($work_type) {
                                                    case 'onsite':
                                                        $color_class = 'bg-success';
                                                        break;
                                                    case 'remote':
                                                        $color_class = 'bg-info';
                                                        break;
                                                    case 'leave':
                                                        $color_class = 'bg-primary';
                                                        break;
                                                    case 'absent':
                                                        $color_class = 'bg-danger';
                                                        break;
                                                    case 'restday':
                                                        $color_class = 'bg-secondary';
                                                        break;
                                                }
                                            ?>
                                            <span class="badge <?= $color_class ?>">
                                                <?= ucfirst($work_type) ?>
                                                <?php if (isset($row['leave_info'])) echo "<br><small>{$row['leave_info']}</small>"; ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($hours, 2) ?> hrs</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info fw-semibold mt-3">
                        Total Hours Worked: <?= number_format($totalHours, 2) ?> hrs
                    </div>
                </div>
            </div>

            <!-- ✅ FullCalendar View -->
            <div class="card shadow-sm mb-5">
                <div class="card-header bg-dark text-white">Calendar View</div>
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
                <!-- ✅ Legend -->
                <div class="mt-2 d-flex flex-wrap gap-3 justify-content-center">
                    <div><span class="badge bg-success me-2">&nbsp;</span> Present (Login/Logout)</div>
                    <div><span class="badge bg-primary me-2">&nbsp;</span> Overtime Approved</div>
                    <div><span class="badge bg-warning text-dark me-2">&nbsp;</span> Approved Clock Alteration</div>
                    <div><span class="badge bg-secondary me-2">&nbsp;</span> Rest Day</div>
                    <div><span class="badge bg-danger me-2">&nbsp;</span> Absent (past only)</div>
                    <br>
                    <br>
                </div>
            </div>
        <?php } else { ?>
            <div class="alert alert-warning">Please select a payroll period to view attendance.</div>
        <?php } ?>
    </main>

    <?php include __DIR__ . '/layout/FOOTER.php'; ?>
</div>

<!-- ✅ Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/rrule@6.1.11/index.global.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const events = <?= json_encode($events) ?>;
    const calendarEl = document.getElementById('calendar');

     if (calendarEl) {
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            height: 650,
            events: events,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            eventDisplay: 'block',
            eventTextColor: '#fff',
            eventDidMount: function(info) {
                // Show tooltip on hover
                new bootstrap.Tooltip(info.el, {
                    title: info.event.title.replace(/\n/g, '<br>'),
                    html: true,
                    placement: 'top'
                });
            }
        });
        calendar.render();
    }

    const sidebarToggle = document.querySelector("#sidebarToggle");
    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", function(e) {
            e.preventDefault();
            document.body.classList.toggle("sb-sidenav-toggled");
        });
    }
});
</script>
