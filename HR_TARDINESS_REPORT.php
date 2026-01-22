<?php
session_start();
require 'db.php';
require 'audit.php';
require 'autolock.php';

/* ==============================
   PAYROLL PERIODS
============================== */
$periods = [];
$periodQuery = $conn->query("
    SELECT id, period_code, start_date, end_date
    FROM payroll_periods
    ORDER BY start_date DESC
");
while ($row = $periodQuery->fetch_assoc()) {
    $periods[] = $row;
}

/* ==============================
   DEPARTMENTS
============================== */
$departments = [];
$deptQuery = $conn->query("
    SELECT id, department
    FROM departments
    ORDER BY department ASC
");
while ($row = $deptQuery->fetch_assoc()) {
    $departments[] = $row;
}

/* ==============================
   SELECTED FILTERS
============================== */
$selected_period_id     = isset($_GET['period_id']) ? intval($_GET['period_id']) : 0;
$selected_department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
$selected_user_id       = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

/* ==============================
   USERS (FILTERED BY DEPARTMENT)
============================== */
$users = [];

$userSql = "
    SELECT u.id, u.name
    FROM users u
    JOIN work_details wd ON wd.user_id = u.id
    JOIN departments d ON d.department = wd.department
    WHERE u.status = 'active'
";

$params = [];
$types  = "";

if ($selected_department_id > 0) {
    $userSql .= " AND d.id = ?";
    $params[] = $selected_department_id;
    $types   .= "i";
}

$userSql .= " ORDER BY u.name ASC";

$stmtUsers = $conn->prepare($userSql);
if (!empty($params)) {
    $stmtUsers->bind_param($types, ...$params);
}
$stmtUsers->execute();
$users = $stmtUsers->get_result()->fetch_all(MYSQLI_ASSOC);

/* ==============================
   TARDINESS DATA
============================== */
$groupedData = [];
$totals = [];

if ($selected_period_id > 0) {

    $user_id = $_SESSION['user']['id'];

    /* PERIOD INFO */
    $periodStmt = $conn->prepare("
        SELECT period_code, start_date, end_date
        FROM payroll_periods
        WHERE id = ?
    ");
    $periodStmt->bind_param("i", $selected_period_id);
    $periodStmt->execute();
    $period = $periodStmt->get_result()->fetch_assoc();

    logAction(
        $conn,
        $user_id,
        "TARDINESS REPORT",
        "Viewed tardiness report for {$period['period_code']}"
    );

    /* MAIN QUERY */
    $sql = "
        SELECT 
            u.id AS user_id,
            u.name AS employee_name,
            wd.department,
            wd.position,
            wd.branch,
            al.log_date,
            ewh.work_day,
            TIME_FORMAT(ewh.time_in, '%H:%i') AS scheduled_time_in,
            TIME_FORMAT(al.login_time, '%H:%i') AS actual_time_in,
            TIMESTAMPDIFF(MINUTE, ewh.time_in, TIME(al.login_time)) AS tardy_minutes
        FROM attendance_logs al
        INNER JOIN users u ON u.id = al.user_id
        INNER JOIN work_details wd ON wd.user_id = u.id
        INNER JOIN employee_working_hours ewh 
            ON ewh.user_id = al.user_id
            AND DAYNAME(al.log_date) = ewh.work_day
        INNER JOIN payroll_periods pp
            ON al.log_date BETWEEN pp.start_date AND pp.end_date
        INNER JOIN departments d ON d.department = wd.department
        WHERE
            pp.id = ?
            AND u.status = 'active'
            AND TIME(al.login_time) > ewh.time_in
    ";

    $params = [$selected_period_id];
    $types  = "i";

    if ($selected_department_id > 0) {
        $sql .= " AND d.id = ?";
        $params[] = $selected_department_id;
        $types   .= "i";
    }

    if ($selected_user_id > 0) {
        $sql .= " AND u.id = ?";
        $params[] = $selected_user_id;
        $types   .= "i";
    }

    $sql .= " ORDER BY u.name, al.log_date";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL ERROR: " . $conn->error);
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $emp = $row['employee_name'];

        if (!isset($groupedData[$emp])) {
            $groupedData[$emp] = [];
            $totals[$emp] = [
                'total_lates'   => 0,
                'total_minutes' => 0
            ];
        }

        $groupedData[$emp][] = $row;
        $totals[$emp]['total_lates']++;
        $totals[$emp]['total_minutes'] += (int)$row['tardy_minutes'];
    }
}
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
<main class="container-fluid px-4">

<h3 class="mt-4 mb-3">Tardiness Report (Multiple Employees)</h3>

<form method="GET" class="mb-4">
<div class="row g-2">

    <div class="col-md-3">
        <select name="period_id" class="form-select" required>
            <option value="">-- Payroll Period --</option>
            <?php foreach ($periods as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $selected_period_id == $p['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['period_code']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-3">
        <select name="department_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- All Departments --</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $selected_department_id == $d['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['department']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-3">
        <select name="user_id" class="form-select">
            <option value="">-- All Employees --</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $selected_user_id == $u['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-2">
        <button class="btn btn-primary w-100">Generate</button>
    </div>

</div>
</form>

<?php if ($selected_period_id && count($groupedData) > 0): ?>
<?php foreach ($groupedData as $employee => $records): ?>

<h5 class="fw-bold mt-4"><?= htmlspecialchars($employee) ?></h5>

<table class="table table-bordered table-striped">
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
<?php foreach ($records as $r): ?>
<tr>
    <td><?= $r['log_date'] ?></td>
    <td><?= $r['work_day'] ?></td>
    <td><?= $r['scheduled_time_in'] ?></td>
    <td><?= $r['actual_time_in'] ?></td>
    <td><?= $r['tardy_minutes'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php
$total = $totals[$employee]['total_minutes'];
$hrs = floor($total / 60);
$mins = $total % 60;
?>

<div class="alert alert-secondary">
<strong>Total Late:</strong> <?= $totals[$employee]['total_lates'] ?> |
<strong>Total Time:</strong> <?= $hrs ?> hrs <?= $mins ?> mins (<?= $total ?> mins)
</div>

<?php endforeach; ?>
<?php elseif ($selected_period_id): ?>
<div class="alert alert-info">No tardiness records found.</div>
<?php endif; ?>

</main>
<?php include __DIR__ . '/layout/FOOTER'; ?>
</div>
