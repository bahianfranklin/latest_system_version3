<?php
require 'db.php';

// ✅ Handle Add Payroll Period
if (isset($_POST['add'])) {
  $stmt = $conn->prepare("INSERT INTO payroll_periods (period_code, start_date, end_date, cutoff, status) VALUES (?,?,?,?,?)");
  $stmt->bind_param("sssss", $_POST['period_code'], $_POST['start_date'], $_POST['end_date'], $_POST['cutoff'], $_POST['status']);
  $stmt->execute();
  // Redirect back to MAINTENANCE.php with payroll tab active
  header("Location: MAINTENANCE.php?tab=payroll");
  exit;
}

// ✅ Handle Update Payroll Period
if (isset($_POST['update'])) {
  $stmt = $conn->prepare("UPDATE payroll_periods SET period_code=?, start_date=?, end_date=?, cutoff=?, status=? WHERE id=?");
  $stmt->bind_param("sssssi", $_POST['period_code'], $_POST['start_date'], $_POST['end_date'], $_POST['cutoff'], $_POST['status'], $_POST['id']);
  $stmt->execute();
  header("Location: MAINTENANCE.php?tab=payroll");
  exit;
}

// ✅ Handle Delete Payroll Period
if (isset($_POST['delete'])) {
  $stmt = $conn->prepare("DELETE FROM payroll_periods WHERE id=?");
  $stmt->bind_param("i", $_POST['id']);
  $stmt->execute();
  header("Location: MAINTENANCE.php?tab=payroll");
  exit;
}

// ✅ Fetch Payroll Periods
$sql = "SELECT * FROM payroll_periods ORDER BY start_date DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payroll Period Maintenance</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">Payroll Period Maintenance</h5>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPayrollModal">
            + Add Payroll Period
        </button>
    </div>
    <!-- Payroll Period Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Period Code</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Cutoff</th>
                <th>Status</th>
                <th width="180">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?= $row['period_code'] ?></td>
                <td><?= $row['start_date'] ?></td>
                <td><?= $row['end_date'] ?></td>
                <td><?= $row['cutoff'] ?></td>
                <td><?= $row['status'] ?></td>
                <td>
                    <button class="btn btn-sm btn-warning" 
                        data-bs-toggle="modal" 
                        data-bs-target="#editPayrollModal<?= $row['id'] ?>">Edit</button>
                    <button class="btn btn-sm btn-danger" 
                        data-bs-toggle="modal" 
                        data-bs-target="#deletePayrollModal<?= $row['id'] ?>">Delete</button>
                </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editPayrollModal<?= $row['id'] ?>">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="post" action="payroll_periods.php">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Payroll Period</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <div class="mb-2">
                            <label>Period Code</label>
                            <input type="text" name="period_code" class="form-control" value="<?= $row['period_code'] ?>" required>
                        </div>
                        <div class="mb-2">
                            <label>Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?= $row['start_date'] ?>" required>
                        </div>
                        <div class="mb-2">
                            <label>End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?= $row['end_date'] ?>" required>
                        </div>
                        <div class="mb-2">
                            <label>Cutoff</label>
                            <select name="cutoff" class="form-control" required>
                                <option <?= $row['cutoff']=="1st Half"?"selected":"" ?>>1st Half</option>
                                <option <?= $row['cutoff']=="2nd Half"?"selected":"" ?>>2nd Half</option>
                                <option <?= $row['cutoff']=="Monthly"?"selected":"" ?>>Monthly</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label>Status</label>
                            <select name="status" class="form-control" required>
                                <option <?= $row['status']=="Open"?"selected":"" ?>>Open</option>
                                <option <?= $row['status']=="Closed"?"selected":"" ?>>Closed</option>
                                <option <?= $row['status']=="Locked"?"selected":"" ?>>Locked</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                      <button type="submit" name="update" class="btn btn-success">Save Changes</button>
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <!-- Delete Modal -->
            <div class="modal fade" id="deletePayrollModal<?= $row['id'] ?>">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="post" action="payroll_periods.php">
                    <div class="modal-header">
                      <h5 class="modal-title">Delete Payroll Period</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        Are you sure you want to delete <b><?= $row['period_code'] ?></b>?
                    </div>
                    <div class="modal-footer">
                      <button type="submit" name="delete" class="btn btn-danger">Delete</button>
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <?php } ?>
        </tbody>
    </table>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addPayrollModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="payroll_periods.php">
        <div class="modal-header">
          <h5 class="modal-title">Add Payroll Period</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="mb-2">
                <label>Period Code</label>
                <input type="text" name="period_code" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Start Date</label>
                <input type="date" name="start_date" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>End Date</label>
                <input type="date" name="end_date" class="form-control" required>
            </div>
            <div class="mb-2">
                <label>Cutoff</label>
                <select name="cutoff" class="form-control" required>
                    <option>1st Half</option>
                    <option>2nd Half</option>
                    <option>Monthly</option>
                </select>
            </div>
            <div class="mb-2">
                <label>Status</label>
                <select name="status" class="form-control" required>
                    <option>Open</option>
                    <option>Closed</option>
                    <option>Locked</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="add" class="btn btn-primary">Add</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Debug: Check for duplicate modal IDs
document.addEventListener('DOMContentLoaded', function() {
  const ids = {};
  document.querySelectorAll('.modal').forEach(function(modal) {
    const id = modal.id;
    if (ids[id]) {
      alert('Duplicate modal id found: ' + id);
    } else {
      ids[id] = true;
    }
  });
});
</script>
</body>
</html>
