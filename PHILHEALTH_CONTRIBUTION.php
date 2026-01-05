<?php
require 'db.php';

/* ============================
   PHILHEALTH 2025 COMPUTATION
============================ */
function computePhilHealth($salary_min) {

    $rate = 0.05;

    if ($salary_min <= 10000) {
        // Fixed minimum
        $total = 500;
    }
    elseif ($salary_min >= 100000) {
        // Fixed maximum cap
        $total = 5000;
    }
    else {
        // Regular computation
        $total = $salary_min * $rate;
    }

    return [
        'rate'     => $rate,
        'total'    => $total,
        'employee' => $total / 2,
        'employer' => $total / 2
    ];
}

/* ============================
   ADD PHILHEALTH
============================ */
if (isset($_POST['add_philhealth'])) {

    $class_code = $_POST['class_code'] ?? null;
    $salary_min = floatval($_POST['salary_min']);
    $salary_max = ($_POST['salary_max'] !== '') ? floatval($_POST['salary_max']) : null;

    $c = computePhilHealth($salary_min);

    $stmt = $conn->prepare("
        INSERT INTO philhealth_contributions
        (class_code, salary_min, salary_max, premium_rate, total_premium, employee_share, employer_share)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sdddddd",
        $class_code,
        $salary_min,
        $salary_max,
        $c['rate'],
        $c['total'],
        $c['employee'],
        $c['employer']
    );

    $stmt->execute();
    header("Location: PHILHEALTH_CONTRIBUTION.php");
    exit;
}

/* ============================
   UPDATE PHILHEALTH
============================ */
if (isset($_POST['update_philhealth'])) {

    $id = intval($_POST['id']);
    $class_code = $_POST['class_code'] ?? null;
    $salary_min = floatval($_POST['salary_min']);
    $salary_max = ($_POST['salary_max'] !== '') ? floatval($_POST['salary_max']) : null;

    $c = computePhilHealth($salary_min);

    $stmt = $conn->prepare("
        UPDATE philhealth_contributions SET
            class_code=?,
            salary_min=?,
            salary_max=?,
            premium_rate=?,
            total_premium=?,
            employee_share=?,
            employer_share=?
        WHERE id=?
    ");

    $stmt->bind_param(
        "sddddddi",
        $class_code,
        $salary_min,
        $salary_max,
        $c['rate'],
        $c['total'],
        $c['employee'],
        $c['employer'],
        $id
    );

    $stmt->execute();
    header("Location: PHILHEALTH_CONTRIBUTION.php");
    exit;
}

/* ============================
   DELETE
============================ */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $stmt = $conn->prepare("DELETE FROM philhealth_contributions WHERE id=?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    header("Location: PHILHEALTH_CONTRIBUTION.php");
    exit;
}
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
<main class="container-fluid px-4">
<br>
<h3>PhilHealth Contribution Maintenance (2025)</h3>
<br>

<div class="text-end">
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
        ➕ Add PhilHealth Contribution
    </button>
</div>

<table class="table table-bordered table-striped">
<thead>
<tr>
    <th>Class</th>
    <th>Salary Range</th>
    <th>Rate</th>
    <th>Total Premium</th>
    <th>Employee</th>
    <th>Employer</th>
    <th>Action</th>
</tr>
</thead>
<tbody>
<?php
$result = $conn->query("SELECT * FROM philhealth_contributions ORDER BY salary_min ASC");
while ($row = $result->fetch_assoc()):
?>
<tr>
    <td><?= htmlspecialchars($row['class_code']) ?></td>
    <td>₱<?= number_format($row['salary_min'],2) ?> - <?= $row['salary_max'] !== null ? '₱' . number_format($row['salary_max'],2) : 'Above' ?></td>
    <td><?= $row['premium_rate'] * 100 ?>%</td>
    <td>₱<?= number_format($row['total_premium'],2) ?></td>
    <td>₱<?= number_format($row['employee_share'],2) ?></td>
    <td>₱<?= number_format($row['employer_share'],2) ?></td>
    <td>
        <button class="btn btn-sm btn-warning"
            data-bs-toggle="modal"
            data-bs-target="#editModal"
            data-id="<?= $row['id'] ?>"
            data-class="<?= htmlspecialchars($row['class_code']) ?>"
            data-min="<?= $row['salary_min'] ?>"
            data-max="<?= $row['salary_max'] ?>">
            ✏ Edit
        </button>

        <a href="?action=delete&id=<?= $row['id'] ?>"
           class="btn btn-sm btn-danger"
           onclick="return confirm('Delete this record?')">
           🗑 Delete
        </a>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal">
<div class="modal-dialog">
<form method="POST" class="modal-content">
<div class="modal-header">
    <h5>Add PhilHealth Contribution</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <input type="hidden" name="add_philhealth" value="1">

    <label>Class Code (Optional)</label>
    <input type="text" name="class_code" class="form-control mb-2">

    <label>Salary Min</label>
    <input type="number" step="0.01" name="salary_min" class="form-control mb-2" required>

    <label>Salary Max (Optional)</label>
    <input type="number" step="0.01" name="salary_max" class="form-control">
</div>

<div class="modal-footer">
    <button class="btn btn-success">Save</button>
</div>
</form>
</div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal">
<div class="modal-dialog">
<form method="POST" class="modal-content">
<input type="hidden" name="update_philhealth" value="1">
<input type="hidden" name="id" id="edit_id">

<div class="modal-header">
    <h5>Edit PhilHealth Contribution</h5>
    <button class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <label>Class Code (Optional)</label>
    <input type="text" name="class_code" id="edit_class" class="form-control mb-2">

    <label>Salary Min</label>
    <input type="number" step="0.01" name="salary_min" id="edit_min" class="form-control mb-2">

    <label>Salary Max (Optional)</label>
    <input type="number" step="0.01" name="salary_max" id="edit_max" class="form-control">
</div>

<div class="modal-footer">
    <button class="btn btn-success">Update</button>
</div>
</form>
</div>
</div>

</main>
</div>

<script>
document.getElementById('editModal').addEventListener('show.bs.modal', e => {
    let btn = e.relatedTarget;
    document.getElementById('edit_id').value    = btn.dataset.id;
    document.getElementById('edit_class').value = btn.dataset.class;
    document.getElementById('edit_min').value   = btn.dataset.min;
    document.getElementById('edit_max').value   = btn.dataset.max;
});
</script>
