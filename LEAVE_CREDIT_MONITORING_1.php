<?php
session_start();
require 'db.php';
require 'audit.php'; // ⭐ add this
require 'autolock.php';

if (!isset($_SESSION['user'])) {
    header("Location: LOGIN.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$currentYear = date('Y');
$years = [$currentYear, $currentYear + 1];
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;

// ⭐⭐⭐ AUDIT TRAIL — logs once per page view ⭐⭐⭐
logAction(
    $conn,
    $user_id,
    "LEAVE CREDIT MONITORING",
    "Viewed leave credit monitoring report for year $selectedYear"
);

// ---------------------------------------------------
// 1️⃣ Define leave types
// ---------------------------------------------------
$leaveTypes = [
    'Mandatory' => 0,
    'Vacation' => 0,
    'Sick' => 0
];

// ---------------------------------------------------
// 2️⃣ BASE CREDITS from leave_credits
// ---------------------------------------------------
$baseCredits = [
    'Mandatory' => 0,
    'Vacation' => 0,
    'Sick' => 0
];

$baseCreditQuery = $conn->prepare("
    SELECT mandatory, vacation_leave, sick_leave
    FROM leave_credits
    WHERE user_id = ? AND year = ?
");
$baseCreditQuery->bind_param("ii", $user_id, $selectedYear);
$baseCreditQuery->execute();
$baseCreditResult = $baseCreditQuery->get_result();

if ($row = $baseCreditResult->fetch_assoc()) {
    $baseCredits['Mandatory'] = (float)$row['mandatory'];
    $baseCredits['Vacation'] = (float)$row['vacation_leave'];
    $baseCredits['Sick'] = (float)$row['sick_leave'];
}

// ---------------------------------------------------
// 3️⃣ TOTAL CREDITS from leave_credit_logs
// ---------------------------------------------------
$logCredits = [
    'Mandatory' => 0,
    'Vacation' => 0,
    'Sick' => 0
];

$creditQuery = $conn->prepare("
    SELECT leave_type, SUM(new_value - old_value) as total
    FROM leave_credit_logs
    WHERE user_id = ? AND year = ?
    GROUP BY leave_type
");
$creditQuery->bind_param("ii", $user_id, $selectedYear);
$creditQuery->execute();
$creditResult = $creditQuery->get_result();

while ($row = $creditResult->fetch_assoc()) {
    $type = trim($row['leave_type']);
    if (isset($logCredits[$type])) {
        $logCredits[$type] += (float)$row['total'];
    }
}

// ---------------------------------------------------
// 4️⃣ Normalize leave type names for requests
// ---------------------------------------------------
$typeMap = [
    'Mandatory Leave' => 'Mandatory',
    'Mandatory' => 'Mandatory',
    'Vacation Leave' => 'Vacation',
    'Vacation' => 'Vacation',
    'Sick Leave' => 'Sick',
    'Sick' => 'Sick'
];

// ---------------------------------------------------
// 5️⃣ CREDIT USED & PENDING from leave_requests
// ---------------------------------------------------
$used = ['Mandatory' => 0, 'Vacation' => 0, 'Sick' => 0];
$pending = ['Mandatory' => 0, 'Vacation' => 0, 'Sick' => 0];

$usageQuery = $conn->prepare("
    SELECT leave_type, status, SUM(credit_value) as total
    FROM leave_requests
    WHERE user_id = ?
      AND YEAR(date_from) = ?
    GROUP BY leave_type, status
");
$usageQuery->bind_param("ii", $user_id, $selectedYear);
$usageQuery->execute();
$usageResult = $usageQuery->get_result();

while ($row = $usageResult->fetch_assoc()) {
    $mappedType = $typeMap[$row['leave_type']] ?? $row['leave_type'];
    $value = (float)$row['total'];
    if (isset($used[$mappedType])) {
        if ($row['status'] === 'Approved') {
            $used[$mappedType] += $value;
        } elseif ($row['status'] === 'Pending') {
            $pending[$mappedType] += $value;
        }
    }
}

// ---------------------------------------------------
// 6️⃣ ENDING & REMAINING CREDITS
// Ending Credit: pulled from leave_credits table (actual current balance)
// Remaining Credit: Ending Credit - Pending
// ---------------------------------------------------
$endingCredits = $baseCredits; // if you store actual balance in leave_credits table
$remainingCredits = [
    'Mandatory' => $endingCredits['Mandatory'] - $pending['Mandatory'],
    'Vacation' => $endingCredits['Vacation'] - $pending['Vacation'],
    'Sick' => $endingCredits['Sick'] - $pending['Sick']
];
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4">
        <br>
        <div class="container">
            <h3 class="mb-4">Leave Credit Monitoring</h3>
        </div>

        <!-- Year Selector -->
        <form method="get" class="mb-4">
            <label for="year" class="form-label">Select Year</label>
            <select name="year" id="year" class="form-select d-inline-block" style="width:auto;">
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year ?>" <?= $selectedYear == $year ? 'selected' : '' ?>>
                        <?= $year ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary ms-2">Generate</button>
        </form>

        <!-- Leave Credits Table -->
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Leave Type</th>
                    <th>Total Credits (Logs)</th>
                    <th>Credit Used</th>
                    <th>Pending</th>
                    <th>Ending Credits (from leave_credits)</th>
                    <th>Remaining Credits</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalLog = $totalUsed = $totalPending = $totalEnding = $totalRemaining = 0;
                foreach ($leaveTypes as $type => $_):
                    $logVal = $logCredits[$type] ?? 0;
                    $usedVal = $used[$type] ?? 0;
                    $pendingVal = $pending[$type] ?? 0;
                    $endingVal = $endingCredits[$type] ?? 0;
                    $remainingVal = $remainingCredits[$type] ?? 0;

                    $totalLog += $logVal;
                    $totalUsed += $usedVal;
                    $totalPending += $pendingVal;
                    $totalEnding += $endingVal;
                    $totalRemaining += $remainingVal;
                ?>
                <tr>
                    <td><?= htmlspecialchars($type) ?></td>
                    <td><?= number_format($logVal, 2) ?></td>
                    <td><?= number_format($usedVal, 2) ?></td>
                    <td><?= number_format($pendingVal, 2) ?></td>
                    <td><?= number_format($endingVal, 2) ?></td>
                    <td><?= number_format($remainingVal, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-secondary">
                <tr>
                    <th>Total</th>
                    <th><?= number_format($totalLog, 2) ?></th>
                    <th><?= number_format($totalUsed, 2) ?></th>
                    <th><?= number_format($totalPending, 2) ?></th>
                    <th><?= number_format($totalEnding, 2) ?></th>
                    <th><?= number_format($totalRemaining, 2) ?></th>
                </tr>
            </tfoot>
        </table>
    </main>

    <?php include __DIR__ . '/layout/FOOTER.php'; ?>
</div>

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
