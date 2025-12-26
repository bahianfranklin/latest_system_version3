<?php
require 'db.php';

/* =========================
   AJAX: Fetch single employee (COE)
========================= */
if (isset($_GET['id'])) {
    header('Content-Type: application/json');

    $user_id = (int) $_GET['id'];
    if ($user_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ID']);
        exit;
    }

    $sql = "
        SELECT 
            u.id,
            u.name,
            u.address,
            u.contact,
            u.gender,
            w.position,
            w.department,
            w.date_hired
        FROM users u
        LEFT JOIN work_details w ON w.user_id = u.id
        WHERE u.id = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $emp = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($emp ?: []);
    exit;
}

/* =========================
   Load employees
========================= */
$emps = $pdo->query("
    SELECT 
        u.id,
        u.name,
        w.department,
        w.position,
        w.date_hired
    FROM users u
    LEFT JOIN work_details w ON w.user_id = u.id
    WHERE u.status = 'active'
    ORDER BY u.name
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Load departments
========================= */
$depts = $pdo->query("
    SELECT DISTINCT department 
    FROM work_details
    ORDER BY department
")->fetchAll(PDO::FETCH_COLUMN);
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<style>
/* ===== COE PAPER STYLE ===== */
#preview {
    max-width: 800px;
    margin: auto;
    padding: 40px;
    border: 1px solid #000;
    font-family: "Times New Roman", serif;
    font-size: 16px;
}

/* Centered content container used for both screen preview and print */
.coe-content {
    width: 800px;
    padding: 50px;
    box-sizing: border-box;
    margin: 0 auto;
}

/* ===== PRINT SETTINGS ===== */
@media print {
    body * {
        visibility: hidden;
    }

    #preview, #preview * {
        visibility: visible;
    }

    /* Make the preview area full page but center the certificate content */
    #preview {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        padding: 0;
        margin: 0;
        border: none;
        box-sizing: border-box;
        overflow: visible;
        font-size: 16px;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    /* Ensure the content itself is centered and avoids page breaks */
    #preview .coe-content {
        margin-top: 20px;
    }

    /* Remove any unwanted margins from paragraphs */
    #preview p {
        margin: 8px 0;
    }

    /* Optional: prevent page break inside */
    #preview h2, #preview .coe-content {
        page-break-inside: avoid;
    }
}
</style>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4">
        <div class="container">
            <br>
            <h3>Certificate of Employment Generator</h3>

            <div class="row g-2 mt-3">

                <div class="col-md-3">
                    <label>Department</label>
                    <select id="dept_select" class="form-select" onchange="filterEmployees()">
                        <option value="">All Departments</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Employee</label>
                    <select id="employee_select" class="form-select" onchange="onEmployeeChange()">
                        <option value="">-- Select Employee --</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label>Name</label>
                    <input type="text" id="emp_name" class="form-control" readonly>
                </div>

                <div class="col-md-3">
                    <label>Position</label>
                    <input type="text" id="emp_position" class="form-control" readonly>
                </div>

                <div class="col-md-3">
                    <label>Date Hired</label>
                    <input type="text" id="emp_date_hired" class="form-control" readonly>
                </div>

                <div class="col-md-3">
                    <label>Authorized Name</label>
                    <input type="text" id="auth_name" class="form-control" placeholder="e.g. Juan Dela Cruz">
                </div>

                <div class="col-md-3">
                    <label>Authorized Position</label>
                    <input type="text" id="auth_position" class="form-control" placeholder="HR Manager">
                </div>

                <div class="col-md-3">
                    <label>Authorized Signature</label>
                    <input type="file" id="auth_signature" class="form-control" accept="image/*">
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" onclick="generateCOE()" disabled id="generate_btn">
                        Generate COE
                    </button>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-success w-100" onclick="printCOE()" disabled id="print_btn">
                        Print COE
                    </button>
                </div>
            </div>

            <div id="preview" style="display:none; margin-top:30px;"></div>

            <script>
                const EMPLOYEES = <?= json_encode($emps) ?>;
                const DEPARTMENTS = <?= json_encode($depts) ?>;

                // current selected employee gender (string)
                let emp_gender = '';

                function populateDepartments() {
                    const sel = document.getElementById('dept_select');
                    DEPARTMENTS.forEach(d => {
                        const o = document.createElement('option');
                        o.value = d;
                        o.textContent = d;
                        sel.appendChild(o);
                    });
                }

                function populateEmployees(filter = '') {
                    const sel = document.getElementById('employee_select');
                    sel.innerHTML = '<option value="">-- Select Employee --</option>';

                    EMPLOYEES
                        .filter(e => !filter || e.department === filter)
                        .forEach(e => {
                            const o = document.createElement('option');
                            o.value = e.id;
                            o.textContent = e.name;
                            sel.appendChild(o);
                        });
                }

                function filterEmployees() {
                    populateEmployees(document.getElementById('dept_select').value);
                    clearFields();
                }

                function clearFields() {
                    emp_name.value = '';
                    emp_position.value = '';
                    emp_date_hired.value = '';
                    emp_gender = '';
                    generate_btn.disabled = true;
                    print_btn.disabled = true;
                    preview.style.display = 'none';
                    preview.innerHTML = '';
                }

                function onEmployeeChange() {
                    const id = employee_select.value;
                    if (!id) return clearFields();

                    fetch(`?id=${id}`)
                        .then(r => r.json())
                        .then(d => {
                            if (!d.id) return clearFields();

                            emp_name.value = d.name;
                            emp_position.value = d.position;
                            emp_date_hired.value = new Date(d.date_hired).toLocaleDateString();
                            emp_gender = (d.gender || '').toString(); // store gender string

                            generate_btn.disabled = false;
                        });
                }

                function generateCOE() {
                    const authName = auth_name.value;
                    const authPosition = auth_position.value;

                    preview.style.display = 'block';
                    const g = (emp_gender || '').toString().toLowerCase();
                    const pronoun = g === 'female' ? 'She' : (g === 'male' ? 'He' : 'They');

                    preview.innerHTML = `
                    <div class="coe-content">
                        <h2 style="text-align:center;">CERTIFICATE OF EMPLOYMENT</h2>

                        <p style="text-align:justify;">
                            This is to certify that <strong>${escape(emp_name.value)}</strong>
                            is currently employed with our company as a
                            <strong>${escape(emp_position.value)}</strong>.
                        </p>

                        <p style="text-align:justify;">
                            ${pronoun} has been employed since
                            <strong>${escape(emp_date_hired.value)}</strong>.
                        </p>

                        <p style="text-align:justify;">
                            Issued this ${new Date().toLocaleDateString()} for whatever legal
                            purpose it may serve.
                        </p>

                        <br><br><br>

                        <div style="text-align:left;">
                            ${signatureDataURL ? `<img src="${signatureDataURL}" style="height:60px;"><br>` : ''}
                            <strong>${escape(auth_name.value)}</strong><br>
                            ${escape(auth_position.value)}
                        </div>
                    </div>
                    `;
                    print_btn.disabled = false;
                }

                function printCOE() {
                    window.print();
                }

                function escape(s) {
                    return String(s).replace(/[&<>"']/g, m =>
                        ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])
                    );
                }

                document.addEventListener('DOMContentLoaded', () => {
                    populateDepartments();
                    populateEmployees();
                });

                let signatureDataURL = '';

                document.getElementById('auth_signature').addEventListener('change', function () {
                    const file = this.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = e => signatureDataURL = e.target.result;
                    reader.readAsDataURL(file);
                });
            </script>

        </div>
    </main>
    <?php include __DIR__ . '/layout/FOOTER.php'; ?>
</div>
