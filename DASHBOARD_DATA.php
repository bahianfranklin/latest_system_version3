<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Leave Balance
$leave_sql = "SELECT mandatory, vacation_leave, sick_leave FROM leave_credits WHERE user_id = ?";
$stmt = $conn->prepare($leave_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$leave = $stmt->get_result()->fetch_assoc();

// Birthdays today
$today = date("Y-m-d");
$bday_sql = "SELECT name, birthday FROM users WHERE DATE_FORMAT(birthday, '%m-%d') = DATE_FORMAT(?, '%m-%d')";
$stmt = $conn->prepare($bday_sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$birthdays = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Holidays next 7 days
$holiday_sql = "SELECT * FROM holidays WHERE date >= ? ORDER BY date ASC LIMIT 5";
$stmt = $conn->prepare($holiday_sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$holidays = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Work schedule
$sched_sql = "SELECT * FROM employee_schedules WHERE user_id = ?";
$stmt = $conn->prepare($sched_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$schedule = $stmt->get_result()->fetch_assoc();

// Payroll period
$period_sql = "SELECT * FROM payroll_periods ORDER BY end_date DESC LIMIT 1";
$period = $conn->query($period_sql)->fetch_assoc();

// Check if user is an approver
$approver_check = $conn->prepare("SELECT 1 FROM approver_assignments WHERE user_id = ? LIMIT 1");
$approver_check->bind_param("i", $user_id);
$approver_check->execute();
$approver_check->store_result();
$is_approver = $approver_check->num_rows > 0;

// Pending leave count for this approver
$stmt = $conn->prepare("
    SELECT COUNT(*) AS pending_count
    FROM leave_requests AS lr
    INNER JOIN users AS u ON lr.user_id = u.id
    INNER JOIN work_details AS wd ON u.id = wd.user_id
    INNER JOIN departments AS d ON d.department = wd.department
    INNER JOIN approver_assignments AS aa ON aa.department_id = d.id
    WHERE aa.user_id = ?
      AND lr.status = 'Pending'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$pending_leaves = (int)$result['pending_count'];

// Pending overtime requests count (for approver)
$sqlOvertimePendingCount = "
    SELECT COUNT(*) AS pending_count
    FROM overtime AS ot
    INNER JOIN users AS u ON ot.applied_by = u.id
    INNER JOIN work_details AS wd ON u.id = wd.user_id
    INNER JOIN departments AS d ON wd.department = d.department
    INNER JOIN approver_assignments AS aa ON aa.department_id = d.id
    WHERE aa.user_id = ?
      AND ot.status = 'Pending'
";
$stmtCnt = $conn->prepare($sqlOvertimePendingCount);
$stmtCnt->bind_param("i", $user_id);
$stmtCnt->execute();
$resCnt = $stmtCnt->get_result();
$rowCnt = $resCnt->fetch_assoc();
$overtimePendingCount = (int)$rowCnt['pending_count'];

// Pending official business requests count (for approver)
$officialBusinessPendingCountSql = "
    SELECT COUNT(*) AS pending_count
    FROM official_business AS ob
    INNER JOIN users AS u ON ob.applied_by = u.id
    INNER JOIN work_details AS wd ON u.id = wd.user_id
    INNER JOIN departments AS d ON d.department = wd.department
    INNER JOIN approver_assignments AS aa ON aa.department_id = d.id
    WHERE aa.user_id = ?
      AND ob.status = 'Pending'
";
$stmtOB = $conn->prepare($officialBusinessPendingCountSql);
$stmtOB->bind_param("i", $user_id);
$stmtOB->execute();
$resOB = $stmtOB->get_result();
$rowOB = $resOB->fetch_assoc();
$officialBusinessPendingCount = (int)$rowOB['pending_count'];

// Pending change schedule requests count (for approver)
$changeSchedulePendingCountSql = "
    SELECT COUNT(*) AS pending_count
    FROM change_schedule AS cs
    INNER JOIN users AS u ON cs.applied_by = u.id
    INNER JOIN work_details AS wd ON u.id = wd.user_id
    INNER JOIN departments AS d ON d.department = wd.department
    INNER JOIN approver_assignments AS aa ON aa.department_id = d.id
    WHERE aa.user_id = ?
      AND cs.status = 'Pending'
";
$stmtCS = $conn->prepare($changeSchedulePendingCountSql);
$stmtCS->bind_param("i", $user_id);
$stmtCS->execute();
$resCS = $stmtCS->get_result();
$rowCS = $resCS->fetch_assoc();
$changeSchedulePendingCount = (int)$rowCS['pending_count'];

// Pending failure clock requests count (for approver)
$failureClockPendingCountSql = "
    SELECT COUNT(*) AS pending_count
    FROM failure_clock AS fc
    INNER JOIN users AS u ON fc.applied_by = u.id
    INNER JOIN work_details AS wd ON u.id = wd.user_id
    INNER JOIN departments AS d ON d.department = wd.department
    INNER JOIN approver_assignments AS aa ON aa.department_id = d.id
    WHERE aa.user_id = ?
      AND fc.status = 'Pending'
";

$stmtFC = $conn->prepare($failureClockPendingCountSql);
$stmtFC->bind_param("i", $user_id);
$stmtFC->execute();
$resFC = $stmtFC->get_result();
$rowFC = $resFC->fetch_assoc();
$failureClockPendingCount = (int)$rowFC['pending_count'];

// Pending clock alteration requests count (for approver)
$clockAlterationPendingCountSql = "
    SELECT COUNT(*) AS pending_count
    FROM clock_alteration AS ca
    INNER JOIN users AS u ON ca.applied_by = u.id
    INNER JOIN work_details AS wd ON u.id = wd.user_id
    INNER JOIN departments AS d ON d.department = wd.department
    INNER JOIN approver_assignments AS aa ON aa.department_id = d.id
    WHERE aa.user_id = ?
      AND ca.status = 'Pending'
";
$stmtCA = $conn->prepare($clockAlterationPendingCountSql);
$stmtCA->bind_param("i", $user_id);
$stmtCA->execute();
$resCA = $stmtCA->get_result();
$rowCA = $resCA->fetch_assoc();
$clockAlterationPendingCount = (int)$rowCA['pending_count'];

// Pending work restday requests count (for approver)
$workRestdayPendingCountSql = "
    SELECT COUNT(*) AS pending_count
    FROM work_restday AS wr
    INNER JOIN users AS u ON wr.applied_by = u.id
    INNER JOIN work_details AS wd ON u.id = wd.user_id
    INNER JOIN departments AS d ON d.department = wd.department
    INNER JOIN approver_assignments AS aa ON aa.department_id = d.id
    WHERE aa.user_id = ?
      AND wr.status = 'Pending'
";
$stmtWR = $conn->prepare($workRestdayPendingCountSql);
$stmtWR->bind_param("i", $user_id);
$stmtWR->execute();
$resWR = $stmtWR->get_result();
$rowWR = $resWR->fetch_assoc();
$workRestdayPendingCount = (int)$rowWR['pending_count'];

// Prepare pending counts only if user is approver
$pending = null;

if ($is_approver) {
    $pending = [
        'leaves' => $pending_leaves,
        'overtime' => $overtimePendingCount, // fixed this line, was previously set to SQL string
        'official_business' => $officialBusinessPendingCount,
        'change_schedule' => $changeSchedulePendingCount,
        'failure_clock' => $failureClockPendingCount,
        'clock_alteration' => $clockAlterationPendingCount,
        'work_restday' => $workRestdayPendingCount
    ];
}

// Final JSON response
echo json_encode([
    'leave' => $leave,
    'birthdays' => $birthdays,
    'holidays' => $holidays,
    'schedule' => $schedule,
    'period' => $period,
    'pending' => $pending
]);
