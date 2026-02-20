<?php
require 'db.php';

/* ============================
   ADD PHILHEALTH CONTRIBUTION
============================ */
if (isset($_POST['add_philhealth'])) {

    $class_code = $_POST['class_code'];
    $salary_min = floatval($_POST['salary_min']);
    $salary_max = ($_POST['salary_max'] !== '') ? floatval($_POST['salary_max']) : null;

    // Auto calculate shares
    $employee_share = round($salary_min * 0.025, 2);
    $employer_share = round($salary_min * 0.025, 2);
    $total_philhealth = round($employee_share + $employer_share, 2);

    $sql = "INSERT INTO philhealth_contributions
        (class_code, monthly_salary_min, monthly_salary_max, employee_share, employer_share, total_philhealth)
        VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sdddds",
        $class_code,
        $salary_min,
        $salary_max,
        $employee_share,
        $employer_share,
        $total_philhealth
    );

    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* ============================
   UPDATE PHILHEALTH CONTRIBUTION
============================ */
if (isset($_POST['update_philhealth'])) {

    $id = intval($_POST['id']);
    $class_code = $_POST['class_code'];
    $salary_min = floatval($_POST['salary_min']);
    $salary_max = ($_POST['salary_max'] !== '') ? floatval($_POST['salary_max']) : null;

    // Auto calculate shares
    $employee_share = round($salary_min * 0.025, 2);
    $employer_share = round($salary_min * 0.025, 2);
    $total_philhealth = round($employee_share + $employer_share, 2);

    $sql = "UPDATE philhealth_contributions SET
        class_code=?,
        monthly_salary_min=?,
        monthly_salary_max=?,
        employee_share=?,
        employer_share=?,
        total_philhealth=?
        WHERE id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sdddddi",
        $class_code,
        $salary_min,
        $salary_max,
        $employee_share,
        $employer_share,
        $total_philhealth,
        $id
    );

    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* ============================
   DELETE PHILHEALTH CONTRIBUTION
============================ */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {

    $stmt = $conn->prepare("DELETE FROM philhealth_contributions WHERE id=?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4">
        <div class="container">
            <br>
            <h3>PhilHealth Contribution Maintenance</h3>
            <br>

            <div class="text-end">
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
                    ➕ Add PhilHealth Contribution
                </button>
            </div>

            <!-- TABLE -->
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Salary Range</th>
                        <th>Employee Share</th>
                        <th>Employer Share</th>
                        <th>Total PhilHealth</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $result = $conn->query("SELECT * FROM philhealth_contributions ORDER BY monthly_salary_min ASC");
                while ($row = $result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= $row['class_code'] ?></td>
                        <td>₱<?= $row['monthly_salary_min'] ?> - <?= $row['monthly_salary_max'] ?? 'Above' ?></td>
                        <td>₱<?= $row['employee_share'] ?></td>
                        <td>₱<?= $row['employer_share'] ?></td>
                        <td>₱<?= $row['total_philhealth'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                data-id="<?= $row['id'] ?>"
                                data-class="<?= $row['class_code'] ?>"
                                data-min="<?= $row['monthly_salary_min'] ?>"
                                data-max="<?= $row['monthly_salary_max'] ?>">
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
            <div class="modal fade" id="addModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <form method="POST" class="modal-content">
                        <input type="hidden" name="add_philhealth" value="1">

                        <div class="modal-header">
                            <h5>Add PhilHealth Contribution</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body row g-2">
                            <div class="col-md-6">
                                <label>Class Code</label>
                                <input type="text" name="class_code" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label>Salary Min</label>
                                <input type="number" step="0.01" name="salary_min" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label>Salary Max</label>
                                <input type="number" step="0.01" name="salary_max" class="form-control">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- EDIT MODAL -->
            <div class="modal fade" id="editModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <form method="POST" class="modal-content">
                        <input type="hidden" name="update_philhealth" value="1">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="modal-header">
                            <h5>Edit PhilHealth Contribution</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body row g-2">
                            <div class="col-md-6">
                                <label>Class Code</label>
                                <input type="text" name="class_code" id="edit_class" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label>Salary Min</label>
                                <input type="number" step="0.01" name="salary_min" id="edit_min" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label>Salary Max</label>
                                <input type="number" step="0.01" name="salary_max" id="edit_max" class="form-control">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Update</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
        <?php include __DIR__ . '/layout/FOOTER.php'; ?>
    </main>
</div>



<script>
document.getElementById('editModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    document.getElementById('edit_id').value  = button.dataset.id;
    document.getElementById('edit_class').value = button.dataset.class;
    document.getElementById('edit_min').value   = button.dataset.min;
    document.getElementById('edit_max').value   = button.dataset.max ?? '';
});
</script>