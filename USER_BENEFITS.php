<?php
require 'db.php';
session_start();

/* ============================
   DELETE
============================ */
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM user_benefits WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    header("Location: user_benefits.php");
    exit;
}

/* ============================
   SAVE (ADD / UPDATE)
============================ */
if(isset($_POST['save'])){

    $id       = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $user_id  = intval($_POST['user_id']);
    $salary   = floatval($_POST['salary']);

    /* ===== SSS (sample 4.5% employee, 9.5% employer) ===== */
    $sss_employee = $salary * 0.045;
    $sss_employer = $salary * 0.095;

    /* ===== PhilHealth (5% split 50/50) ===== */
    $phil_total    = $salary * 0.05;
    $phil_employee = $phil_total / 2;
    $phil_employer = $phil_total / 2;

    /* ===== Pag-IBIG (2%) ===== */
    $pagibig_employee = $salary * 0.02;
    $pagibig_employer = $salary * 0.02;

    $total_deduction = $sss_employee + $phil_employee + $pagibig_employee;

    if($id == 0){
        $stmt = $conn->prepare("
            INSERT INTO user_benefits
            (user_id,salary_monthly,
            sss_employee,sss_employer,
            philhealth_employee,philhealth_employer,
            pagibig_employee,pagibig_employer,
            total_deduction)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");

        $stmt->bind_param("idddddddd",
            $user_id,
            $salary,
            $sss_employee,
            $sss_employer,
            $phil_employee,
            $phil_employer,
            $pagibig_employee,
            $pagibig_employer,
            $total_deduction
        );

        $stmt->execute();

    }else{
        $stmt = $conn->prepare("
            UPDATE user_benefits SET
            user_id=?,
            salary_monthly=?,
            sss_employee=?,
            sss_employer=?,
            philhealth_employee=?,
            philhealth_employer=?,
            pagibig_employee=?,
            pagibig_employer=?,
            total_deduction=?
            WHERE id=?
        ");

        $stmt->bind_param("iddddddddi",
            $user_id,
            $salary,
            $sss_employee,
            $sss_employer,
            $phil_employee,
            $phil_employer,
            $pagibig_employee,
            $pagibig_employer,
            $total_deduction,
            $id
        );

        $stmt->execute();
    }

    header("Location: user_benefits.php");
    exit;
}

/* ============================
   EDIT FETCH
============================ */
$edit = null;
if(isset($_GET['edit'])){
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM user_benefits WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>User Benefits</title>
<style>
table { border-collapse: collapse; width:100%; }
th, td { border:1px solid #ccc; padding:8px; text-align:center; }
form { margin-bottom:20px; }
input, select { padding:5px; }
button { padding:6px 12px; }
</style>
</head>
<body>

<h2>Employee Benefits Module</h2>

<!-- ================= FORM ================= -->
<form method="POST">

<input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">

<select name="user_id" required>
<option value="">Select Employee</option>
<?php
$users = $conn->query("SELECT id,name FROM users WHERE status='active'");
while($u = $users->fetch_assoc()){
    $selected = (isset($edit['user_id']) && $edit['user_id']==$u['id']) ? "selected" : "";
    echo "<option value='{$u['id']}' $selected>{$u['name']}</option>";
}
?>
</select>

<input type="number" step="0.01" name="salary"
value="<?= $edit['salary_monthly'] ?? '' ?>"
placeholder="Monthly Salary" required>

<button type="submit" name="save">
<?= isset($edit) ? "Update" : "Save" ?>
</button>

</form>

<!-- ================= TABLE ================= -->
<table>
<tr>
    <th>Employee</th>
    <th>Salary</th>
    <th>SSS</th>
    <th>PhilHealth</th>
    <th>Pag-IBIG</th>
    <th>Total Deduction</th>
    <th>Actions</th>
</tr>

<?php
$query = "
SELECT ub.*, u.name
FROM user_benefits ub
JOIN users u ON ub.user_id = u.id
ORDER BY ub.id DESC
";

$result = $conn->query($query);

while($row = $result->fetch_assoc()){
?>
<tr>
<td><?= $row['name'] ?></td>
<td><?= number_format($row['salary_monthly'],2) ?></td>
<td><?= number_format($row['sss_employee'],2) ?></td>
<td><?= number_format($row['philhealth_employee'],2) ?></td>
<td><?= number_format($row['pagibig_employee'],2) ?></td>
<td><strong><?= number_format($row['total_deduction'],2) ?></strong></td>
<td>
<a href="?edit=<?= $row['id'] ?>">Edit</a> |
<a href="?delete=<?= $row['id'] ?>"
onclick="return confirm('Delete this record?')">Delete</a>
</td>
</tr>
<?php } ?>

</table>

</body>
</html>