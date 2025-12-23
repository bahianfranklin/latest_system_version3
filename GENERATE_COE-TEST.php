<?php
require 'db.php';

/* ============================
   AJAX: Fetch employees
============================ */
if (isset($_GET['ajax']) && $_GET['ajax'] === 'employees') {
    header('Content-Type: application/json');

    // Ensure we have a valid numeric department id
    $department = (int)($_GET['department'] ?? 0);

    if ($department <= 0) {
        echo json_encode([]);
        exit;
    }
    
    $sql = "
        SELECT u.id, u.name
        FROM users u
        JOIN work_details w ON u.id = w.user_id
        WHERE w.department = ?
        ORDER BY u.name
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'prepare_failed', 'message' => $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $department);
    if (!$stmt->execute()) {
        echo json_encode(['error' => 'execute_failed', 'message' => $stmt->error]);
        exit;
    }

    $res = $stmt->get_result();

    $data = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
    }

    echo json_encode($data);
    exit;
}

/* ============================
   GENERATE COE
============================ */
$coeData = null;
$logoPath = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id = (int)$_POST['user_id'];
    $title   = $_POST['title'];

    // Logo upload
    if (!empty($_FILES['logo']['name'])) {
        $logoPath = 'assets/logos/' . time() . '_' . $_FILES['logo']['name'];
        move_uploaded_file($_FILES['logo']['tmp_name'], $logoPath);
    }

    $sql = "
        SELECT u.name, u.address, u.contact,
               w.position, d.department AS department, w.date_hired
        FROM users u
        JOIN work_details w ON u.id = w.user_id
        LEFT JOIN departments d ON d.id = w.department
        WHERE u.id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $coeData = $stmt->get_result()->fetch_assoc();
}

/* ============================
   LOAD DEPARTMENTS
============================ */
$departments = $conn->query("SELECT id, department FROM departments ORDER BY department");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Certificate of Employment</title>
    <style>
        body { font-family: Arial; }
        select, input, button { width: 100%; padding: 8px; margin-bottom: 10px; }
        .container { width: 800px; margin: auto; }

        .cert {
            border: 2px solid #000;
            padding: 40px;
            margin-top: 30px;
            font-family: "Times New Roman";
        }
        .center { text-align: center; }

        @media print {
            form, button { display: none; }
        }
    </style>
</head>
<body>

<div class="container">

<h2>Generate Certificate of Employment</h2>

<form method="POST" enctype="multipart/form-data">

    <input type="text" name="title" placeholder="Certificate Title" required>

    <input type="file" name="logo" accept="image/*">

    <select id="department" name="department_id" required>
        <option value="">Select Department</option>
        <?php while ($d = $departments->fetch_assoc()): ?>
            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['department']) ?></option>
        <?php endwhile; ?>
    </select>

    <select name="user_id" id="employee" required>
        <option value="">Select Employee</option>
    </select>

    <button type="submit">Generate Certificate</button>
</form>

<?php if ($coeData): ?>
    <button onclick="window.print()">PRINT</button>

    <!-- FRONT PAGE -->
    <div class="cert">
        <?php if ($logoPath): ?>
            <div class="center">
                <img src="<?= $logoPath ?>" height="80">
            </div>
        <?php endif; ?>

        <h2 class="center"><?= strtoupper($title) ?></h2>

        <p>
            This is to certify that <b><?= $coeData['name'] ?></b> is presently
            employed with the company under the <b><?= $coeData['department'] ?></b>
            department as <b><?= $coeData['position'] ?></b> since
            <b><?= date('F d, Y', strtotime($coeData['date_hired'])) ?></b>.
        </p>

        <p>
            This certificate is issued upon the request of the employee
            for whatever legal purpose it may serve.
        </p>

        <br><br>
        <p>Issued this <?= date('F d, Y') ?>.</p>

        <br><br>
        <p>
            ___________________________<br>
            HR Manager
        </p>
    </div>

    <!-- BACK PAGE -->
    <div class="cert">
        <h3 class="center">Employee Information</h3>
        <p><b>Name:</b> <?= $coeData['name'] ?></p>
        <p><b>Address:</b> <?= $coeData['address'] ?></p>
        <p><b>Contact:</b> <?= $coeData['contact'] ?></p>
        <p><b>Department:</b> <?= $coeData['department'] ?></p>
        <p><b>Position:</b> <?= $coeData['position'] ?></p>
    </div>
<?php endif; ?>

</div>

<script>
document.getElementById('department').addEventListener('change', function () {
    const url = `GENERATE_COE-TEST.php?ajax=employees&department=${encodeURIComponent(this.value)}`;
    console.log('Fetching employees from', url);
    fetch(url)
        .then(res => res.text().then(text => {
            if (!res.ok) {
                throw new Error(`HTTP ${res.status} - ${text}`);
            }
            try {
                return JSON.parse(text);
            } catch (e) {
                throw new Error('Invalid JSON response: ' + text);
            }
        }))
        .then(data => {
            if (data && data.error) {
                console.error('Employee fetch error:', data);
                alert('Error fetching employees: ' + (data.message || data.error));
                return;
            }

            let emp = document.getElementById('employee');
            emp.innerHTML = '<option value="">Select Employee</option>';
            data.forEach(e => {
                emp.innerHTML += `<option value="${e.id}">${e.name}</option>`;
            });
        })
        .catch(err => {
            console.error('Employee fetch failed:', err);
            alert('Error fetching employees: ' + err.message);
        });
});
</script>

</body>
</html>
