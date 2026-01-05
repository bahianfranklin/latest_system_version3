<?php
require 'db.php';

/* ============================
   ADD PAG-IBIG CONTRIBUTION
============================ */
if (isset($_POST['add_pagibig'])) {

    $class_code = $_POST['class_code'];

    $salary_min = floatval($_POST['salary_min']);
    $salary_max = ($_POST['salary_max'] !== '') ? floatval($_POST['salary_max']) : null;

    $employee_rate = floatval($_POST['employee_rate']);
    $employer_rate = floatval($_POST['employer_rate']);
    $salary_cap = ($_POST['salary_cap'] !== '') ? floatval($_POST['salary_cap']) : null;

    $sql = "INSERT INTO pagibig_contributions
        (class_code, salary_min, salary_max, employee_rate, employer_rate, salary_cap)
        VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sdddds",
        $class_code,
        $salary_min,
        $salary_max,
        $employee_rate,
        $employer_rate,
        $salary_cap
    );

    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* ============================
   UPDATE PAG-IBIG CONTRIBUTION
============================ */
if (isset($_POST['update_pagibig'])) {

    $id = intval($_POST['id']);
    $class_code = $_POST['class_code'];

    $salary_min = floatval($_POST['salary_min']);
    $salary_max = ($_POST['salary_max'] !== '') ? floatval($_POST['salary_max']) : null;

    $employee_rate = floatval($_POST['employee_rate']);
    $employer_rate = floatval($_POST['employer_rate']);
    $salary_cap = ($_POST['salary_cap'] !== '') ? floatval($_POST['salary_cap']) : null;

    $sql = "UPDATE pagibig_contributions SET
        class_code=?,
        salary_min=?,
        salary_max=?,
        employee_rate=?,
        employer_rate=?,
        salary_cap=?
        WHERE id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sddddsi",
        $class_code,
        $salary_min,
        $salary_max,
        $employee_rate,
        $employer_rate,
        $salary_cap,
        $id
    );

    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* ============================
   DELETE PAG-IBIG CONTRIBUTION
============================ */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {

    $stmt = $conn->prepare("DELETE FROM pagibig_contributions WHERE id=?");
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
        <div class="container"></div>
            <br>
            <h3>Pag-IBIG Contribution Maintenance</h3>
            <br>

            <div class="text-end">
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
                    ➕ Add Pag-IBIG Contribution
                </button>
            </div>

            <!-- ============================
                TABLE
            ============================ -->
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Salary Range</th>
                        <th>Employee %</th>
                        <th>Employer %</th>
                        <th>Salary Cap</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $result = $conn->query("SELECT * FROM pagibig_contributions ORDER BY salary_min ASC");
                while ($row = $result->fetch_assoc()):
                ?>
                    <tr>
                        <td><?= $row['class_code'] ?></td>
                        <td>₱<?= $row['salary_min'] ?> - <?= $row['salary_max'] ?? 'Above' ?></td>
                        <td><?= $row['employee_rate'] ?>%</td>
                        <td><?= $row['employer_rate'] ?>%</td>
                        <td>₱<?= $row['salary_cap'] ?? 'No Cap' ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                data-id="<?= $row['id'] ?>"
                                data-class="<?= $row['class_code'] ?>"
                                data-min="<?= $row['salary_min'] ?>"
                                data-max="<?= $row['salary_max'] ?>"
                                data-emp="<?= $row['employee_rate'] ?>"
                                data-er="<?= $row['employer_rate'] ?>"
                                data-cap="<?= $row['salary_cap'] ?>">
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

            <!-- ============================
                ADD MODAL
            ============================ -->
            <div class="modal fade" id="addModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <form method="POST" class="modal-content">
                        <input type="hidden" name="add_pagibig" value="1">

                        <div class="modal-header">
                            <h5>Add Pag-IBIG Contribution</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body row g-2">
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

                            <div class="col-md-4">
                                <label>Employee Rate (%)</label>
                                <input type="number" step="0.01" name="employee_rate" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label>Employer Rate (%)</label>
                                <input type="number" step="0.01" name="employer_rate" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label>Salary Cap</label>
                                <input type="number" step="0.01" name="salary_cap" class="form-control">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" class="btn btn-success">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ============================
                EDIT MODAL
            ============================ -->
            <div class="modal fade" id="editModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <form method="POST" class="modal-content">
                        <input type="hidden" name="update_pagibig" value="1">
                        <input type="hidden" name="id" id="edit_id">

                        <div class="modal-header">
                            <h5>Edit Pag-IBIG Contribution</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body row g-2">
                            <div class="col-md-4">
                                <label>Class Code</label>
                                <input type="text" name="class_code" id="edit_class" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label>Salary Min</label>
                                <input type="number" step="0.01" name="salary_min" id="edit_min" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label>Salary Max</label>
                                <input type="number" step="0.01" name="salary_max" id="edit_max" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label>Employee Rate (%)</label>
                                <input type="number" step="0.01" name="employee_rate" id="edit_emp" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label>Employer Rate (%)</label>
                                <input type="number" step="0.01" name="employer_rate" id="edit_er" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label>Salary Cap</label>
                                <input type="number" step="0.01" name="salary_cap" id="edit_cap" class="form-control">
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

        document.getElementById('edit_id').value    = button.dataset.id;
        document.getElementById('edit_class').value = button.dataset.class;
        document.getElementById('edit_min').value   = button.dataset.min;
        document.getElementById('edit_max').value   = button.dataset.max ?? '';
        document.getElementById('edit_emp').value   = button.dataset.emp;
        document.getElementById('edit_er').value    = button.dataset.er;
        document.getElementById('edit_cap').value   = button.dataset.cap ?? '';
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
