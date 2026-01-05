<?php
require 'db.php';

/* ============================
   ADD SSS CONTRIBUTION
============================ */
if (isset($_POST['add_sss'])) {

    $class_code = $_POST['class_code'];

    $salary_min = floatval($_POST['salary_min']);
    $salary_max = ($_POST['salary_max'] !== '') ? floatval($_POST['salary_max']) : null;

    $msc = floatval($_POST['msc']);
    $employee = floatval($_POST['employee']);
    $employer = floatval($_POST['employer']);
    $ec = floatval($_POST['ec']);
    $total = $employee + $employer + $ec;

    $sql = "INSERT INTO sss_contributions
        (class_code, salary_min, salary_max, monthly_salary_credit,
         employee_share, employer_share, ec, total_contribution)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if ($salary_max === null) {
        $stmt->bind_param(
            "sddddddd",
            $class_code, $salary_min, $salary_max,
            $msc, $employee, $employer, $ec, $total
        );
    } else {
        $stmt->bind_param(
            "sddddddd",
            $class_code, $salary_min, $salary_max,
            $msc, $employee, $employer, $ec, $total
        );
    }

    $stmt->execute();
    header("Location: SSS_CONTRIBUTION.php");
    exit;
}

/* ============================
   UPDATE SSS CONTRIBUTION
============================ */
if (isset($_POST['update_sss'])) {

    $id = intval($_POST['id']);
    $class_code = $_POST['class_code'];

    $salary_min = floatval($_POST['salary_min']);
    $salary_max = ($_POST['salary_max'] !== '') ? floatval($_POST['salary_max']) : null;

    $msc = floatval($_POST['msc']);
    $employee = floatval($_POST['employee']);
    $employer = floatval($_POST['employer']);
    $ec = floatval($_POST['ec']);
    $total = $employee + $employer + $ec;

    $sql = "UPDATE sss_contributions SET
        class_code=?,
        salary_min=?,
        salary_max=?,
        monthly_salary_credit=?,
        employee_share=?,
        employer_share=?,
        ec=?,
        total_contribution=?
        WHERE id=?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "sdddddddi",
        $class_code,
        $salary_min,
        $salary_max,
        $msc,
        $employee,
        $employer,
        $ec,
        $total,
        $id
    );

    if (!$stmt->execute()) {
        die("UPDATE ERROR: " . $stmt->error);
    }

    header("Location: SSS_CONTRIBUTION.php");
    exit;
}

/* ============================
   DELETE
============================ */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $stmt = $conn->prepare("DELETE FROM sss_contributions WHERE id=?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();

    header("Location: SSS_CONTRIBUTION.php");
    exit;
}
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4">
        <div class="container"></div>
            <br>
            <h3>SSS Contribution Maintenance</h3>
            <br>
            <div class="text-end">
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
                    ➕ Add SSS Contribution
                </button>
            </div>

            <!-- ============================
                DISPLAY TABLE (AFTER LOGIC)
            ============================ -->

            <table class="table table-bordered table-striped">
            <thead>
            <tr>
            <th>Class</th>
            <th>Salary Range</th>
            <th>MSC</th>
            <th>Employee</th>
            <th>Employer</th>
            <th>EC</th>
            <th>Total</th>
            <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $result = $conn->query("SELECT * FROM sss_contributions ORDER BY salary_min ASC");
            while ($row = $result->fetch_assoc()):
            ?>
            <tr>
            <td><?= $row['class_code'] ?></td>
            <td>₱<?= $row['salary_min'] ?> - <?= $row['salary_max'] ?? 'Above' ?></td>
            <td>₱<?= $row['monthly_salary_credit'] ?></td>
            <td>₱<?= $row['employee_share'] ?></td>
            <td>₱<?= $row['employer_share'] ?></td>
            <td>₱<?= $row['ec'] ?></td>
            <td>₱<?= $row['total_contribution'] ?></td>
            <td>
                <button class="btn btn-sm btn-warning"
                data-bs-toggle="modal"
                data-bs-target="#editModal"
                data-id="<?= $row['id'] ?>"
                data-class="<?= $row['class_code'] ?>"
                data-min="<?= $row['salary_min'] ?>"
                data-max="<?= $row['salary_max'] ?>"
                data-msc="<?= $row['monthly_salary_credit'] ?>"
                data-emp="<?= $row['employee_share'] ?>"
                data-er="<?= $row['employer_share'] ?>"
                data-ec="<?= $row['ec'] ?>">
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


            <!-- Add Modal -->
            <div class="modal fade" id="addModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add SSS Contribution</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body row g-2">
                    <input type="hidden" name="add_sss" value="1">

                    <div class="col-md-4">
                    <label>Class Code</label>
                    <input type="text" name="class_code" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                    <label>Salary Min</label>
                    <input type="number" step="0.01" name="salary_min" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                    <label>Salary Max</label>
                    <input type="number" step="0.01" name="salary_max" class="form-control">
                    </div>

                    <div class="col-md-3">
                    <label>Monthly Salary Credit (MSC)</label>
                    <input type="number" step="0.01" name="msc" class="form-control" required>
                    </div>

                    <div class="col-md-3">
                    <label>Employee Share (5%)</label>
                    <input type="number" step="0.01" name="employee" class="form-control" required>
                    </div>

                    <div class="col-md-3">
                    <label>Employer Share (10%)</label>
                    <input type="number" step="0.01" name="employer" class="form-control" required>
                    </div>

                    <div class="col-md-3">
                    <label>Employees Compensation (EC)</label>
                    <input type="number" step="0.01" name="ec" class="form-control" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-success">Save</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
                </form>
            </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <form method="POST" class="modal-content">
                    <input type="hidden" name="update_sss" value="1">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="modal-header">
                        <h5>Edit SSS Contribution</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body row g-2">
                        <div class="col-md-4">
                            <label>Class Code</label>
                            <input type="text" name="class_code" id="edit_class" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label>Salary Min</label>
                            <input type="number" step="0.01" name="salary_min" id="edit_min" class="form-control">
                        </div>
                        
                        <div class="col-md-4">
                            <label>Salary Max</label>
                            <input type="number" step="0.01" name="salary_max" id="edit_max" class="form-control">
                        </div>
                        
                        <div class="col-md-3">
                        <label>Monthly Salary Credit (MSC)</label>
                        <input type="number" step="0.01" name="msc" id="edit_msc" class="form-control">
                        </div>

                        <div class="col-md-3">
                        <label>Employee Share (5%)</label>
                        <input type="number" step="0.01" name="employee" id="edit_emp" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label>Employer Share (10%)</label>
                            <input type="number" step="0.01" name="employer" id="edit_er" class="form-control">
                        </div>

                        <div class="col-md-3">
                        <label>Employees Compensation (EC)</label>
                        <input type="number" step="0.01" name="ec" id="edit_ec" class="form-control">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-success">Update</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
        <?php include __DIR__ . '/layout/FOOTER.php'; ?>
    </main>
</div>

<script>
    document.getElementById('editModal').addEventListener('show.bs.modal', function (e) {
        let btn = e.relatedTarget;
        document.getElementById('edit_id').value = btn.dataset.id;
        document.getElementById('edit_class').value = btn.dataset.class;
        document.getElementById('edit_min').value = btn.dataset.min;
        document.getElementById('edit_max').value = btn.dataset.max;
        document.getElementById('edit_msc').value = btn.dataset.msc;
        document.getElementById('edit_emp').value = btn.dataset.emp;
        document.getElementById('edit_er').value = btn.dataset.er;
        document.getElementById('edit_ec').value = btn.dataset.ec;
    });
</script>

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



