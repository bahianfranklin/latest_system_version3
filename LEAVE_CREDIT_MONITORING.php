<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: LOGIN.php");
    exit();
}

$user_id = $_SESSION['user']['id'];
$currentYear = date('Y');
$years = [$currentYear, $currentYear + 1];
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;

// Get leave credits (Remaining from DB)
$creditQuery = $conn->prepare("
    SELECT leave_type, SUM(new_value - old_value) as total
    FROM leave_credit_logs
    WHERE user_id = ? AND year = ?
    GROUP BY leave_type
");
$creditQuery->bind_param("ii", $user_id, $selectedYear);
$creditQuery->execute();
$creditResult = $creditQuery->get_result();

$leaveTypes = [
    'Mandatory' => 0,
    'Vacation' => 0,
    'Sick' => 0
];

while ($row = $creditResult->fetch_assoc()) {
    $type = $row['leave_type'];
    if (isset($leaveTypes[$type])) {
        $leaveTypes[$type] = $row['total'];
    }
}

// Normalize leave_type names
$typeMap = [
    'Mandatory Leave' => 'Mandatory',
    'Mandatory' => 'Mandatory',
    'Vacation Leave' => 'Vacation',
    'Vacation' => 'Vacation',
    'Sick Leave' => 'Sick',
    'Sick' => 'Sick'
];

// Get leave usage
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

$used = [];
$pending = [];

while ($row = $usageResult->fetch_assoc()) {
    $mappedType = $typeMap[$row['leave_type']] ?? $row['leave_type'];
    if ($row['status'] === 'Approved') {
        $used[$mappedType] = $row['total'];
    } elseif ($row['status'] === 'Pending') {
        $pending[$mappedType] = $row['total'];
    }
}
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4">
        <br>
        <div class="container">
            <h3 class="mb-4">Leave Credit Monitoring</h3>
        </div>

        <form method="get" class="mb-4">
            <label for="year" class="form-label">Select Year</label>
            <select name="year" id="year" class="form-select" style="width:auto; display:inline-block;">
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year ?>" <?= $selectedYear == $year ? 'selected' : '' ?>>
                        <?= $year ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary ms-2">Generate</button>
        </form>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Leave Type</th>
                    <th>Total Credit (from Logs)</th>
                    <th>Credit Used</th>
                    <th>Pending</th>
                    <th>Ending Credits (Total - Used)</th>
                    <th>Remaining Credits (Total - (Used + Pending))</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leaveTypes as $type => $remainingFromDB): 
                    $usedVal = $used[$type] ?? 0;
                    $pendingVal = $pending[$type] ?? 0;

                    // ✅ Corrected formula
                    $totalCredit = $remainingFromDB;
                    $endingCredit = $totalCredit - $usedVal;
                    $remainingCredit = $totalCredit - ($usedVal + $pendingVal);
                ?>
                <tr>
                    <td><?= htmlspecialchars($type) ?></td>
                    <td><?= number_format($totalCredit, 2) ?></td>
                    <td><?= number_format($usedVal, 2) ?></td>
                    <td><?= number_format($pendingVal, 2) ?></td>
                    <td><?= number_format($endingCredit, 2) ?></td>
                    <td><?= number_format($remainingCredit, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <?php include __DIR__ . '/layout/FOOTER'; ?>
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
