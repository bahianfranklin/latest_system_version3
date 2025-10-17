<?php
session_start();
require 'db.php'; // ✅ Your database connection

// // Set timeout duration (e.g., 30 minutes)
// $timeout_duration = 1800;
// if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
//     session_unset();
//     session_destroy();
//     header("Location: LOGIN.php?timeout=1");
//     exit();
// }
// $_SESSION['LAST_ACTIVITY'] = time(); // update last activity

// --- Get payroll periods for the dropdown ---
$periods = [];
$periodQuery = $conn->query("SELECT id, period_code, start_date, end_date FROM payroll_periods ORDER BY start_date DESC");
while ($row = $periodQuery->fetch_assoc()) {
    $periods[] = $row;
}

// --- Check if a payroll period is selected ---
$selected_period_id = isset($_GET['period_id']) ? intval($_GET['period_id']) : 0;

$groupedData = [];
$totals = []; // ✅ for total lates and minutes

if ($selected_period_id > 0) {
    $user_id = $_SESSION['user']['id']; // or adjust based on your session structure

    $sql = "
        SELECT 
            u.name AS employee_name,
            al.log_date,
            ewh.work_day,
            TIME_FORMAT(ewh.time_in, '%H:%i') AS scheduled_time_in,
            TIME_FORMAT(al.login_time, '%H:%i') AS actual_time_in,
            TIMESTAMPDIFF(
                MINUTE,
                ewh.time_in,
                TIME(al.login_time)
            ) AS tardy_minutes
        FROM attendance_logs al
        JOIN employee_working_hours ewh 
            ON ewh.user_id = al.user_id 
            AND DAYNAME(al.log_date) = ewh.work_day
        JOIN payroll_periods pp 
            ON al.log_date BETWEEN pp.start_date AND pp.end_date
        JOIN users u 
            ON u.id = al.user_id
        WHERE 
            pp.id = ?
            AND u.id = ?
            AND TIME(al.login_time) > ewh.time_in
        ORDER BY u.name, al.log_date
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL ERROR: " . $conn->error);
    }
    $stmt->bind_param("ii", $selected_period_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // --- ✅ Fetch and group tardiness data + calculate totals ---
    while ($row = $result->fetch_assoc()) {
        $empName = $row['employee_name'];

        if (!isset($groupedData[$empName])) {
            $groupedData[$empName] = [];
            $totals[$empName] = [
                'total_lates' => 0,
                'total_minutes' => 0
            ];
        }

        $groupedData[$empName][] = $row;
        $totals[$empName]['total_lates']++;
        $totals[$empName]['total_minutes'] += (int)$row['tardy_minutes'];
    }
}
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
            <h3 class="mb-4">Tardiness Report</h3>
        </div>

        <!-- 🔽 Payroll Period Dropdown -->
        <form method="GET" class="mb-4">
        <div class="row g-2">
            <div class="col-md-4">
                <select name="period_id" class="form-select" required>
                    <option value="">-- Select Payroll Period --</option>
                    <?php foreach ($periods as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($selected_period_id == $p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['period_code']) ?> 
                            (<?= $p['start_date'] ?> to <?= $p['end_date'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Generate</button>
            </div>
        </div>
        </form>

        <!-- 📊 Tardiness Table -->
        <?php if ($selected_period_id && count($groupedData) > 0): ?>
            <?php foreach ($groupedData as $employeeName => $records): ?>
                <h5 class="mt-4 mb-2 fw-bold"><?= htmlspecialchars($employeeName) ?></h5>
                <table class="table table-bordered table-striped mb-4">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Work Day</th>
                            <th>Scheduled In</th>
                            <th>Actual In</th>
                            <th>Tardy Minutes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['log_date']) ?></td>
                                <td><?= htmlspecialchars($row['work_day']) ?></td>
                                <td><?= htmlspecialchars($row['scheduled_time_in']) ?></td>
                                <td><?= htmlspecialchars($row['actual_time_in']) ?></td>
                                <td><?= htmlspecialchars($row['tardy_minutes']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- ✅ Totals row -->
                <?php
                    $totalMinutes = $totals[$employeeName]['total_minutes'];
                    $hours = floor($totalMinutes / 60);
                    $minutes = $totalMinutes % 60;
                ?>
                <div class="alert alert-secondary">
                    <strong>Total Times Late:</strong> <?= $totals[$employeeName]['total_lates'] ?> |
                    <strong>Total Tardy:</strong> 
                    <?= $hours ?> hr<?= $hours != 1 ? 's' : '' ?> 
                    <?= $minutes ?> min<?= $minutes != 1 ? 's' : '' ?> 
                    (<?= $totalMinutes ?> mins)
                </div>
            <?php endforeach; ?>
        <?php elseif ($selected_period_id): ?>
            <div class="alert alert-info">No tardiness records found for this period.</div>
        <?php endif; ?>
    </main>        
    <?php include __DIR__ . '/layout/FOOTER'; ?>
</div>

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
