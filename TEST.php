<?php
require 'db.php';

/* =========================
   AJAX: Fetch single employee
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
            u.profile_pic AS photo,
            u.contact_person,
            u.contact_person_address,
            u.contact_person_contact,
            w.employee_no,
            w.department,
            w.position
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
        u.profile_pic AS photo,
        w.employee_no,
        w.department,
        w.position
    FROM users u
    LEFT JOIN work_details w ON w.user_id = u.id    WHERE u.status = 'active'
    ORDER BY u.name
")->fetchAll();
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
.id-card-container { 
    display:flex; 
    gap:10px; 
    margin-top:20px; 
    flex-wrap:wrap; 
    justify-content:center; 

}
.id-card { 
    width:204px; 
    height:324px; 
    border:1px solid #000; 
    border-radius:8px; 
    padding:6px; 
    font-family:Arial,sans-serif; 
    box-sizing:border-box; 
    overflow:hidden; 
}

.id-logo { 
    width:60px; 
    height:auto; 
    display:block; 
    margin:5px auto; 
    object-fit:contain; 
    border-radius:4px; 
}

.front { 
    background:#f4f4f4; 
    display:flex; 
    flex-direction:column; 
    justify-content:center; 
    align-items:center; 
    text-align:center; 
    padding:6px; 
}

.back { 
    background:#fff; 
    font-size:9px; 
    display:flex; 
    flex-direction:column; 
    justify-content:flex-start; 
    padding:6px; 
}

.id-card p { 
    margin:3px 0; 
    line-height:1.15; 
    font-size:9px; 
}

.id-card h6.id-title { 
    margin:5px 0 5px; 
    font-size:13px; 
    letter-spacing:0.5px; 
}

.id-card p.section-title { 
    margin-bottom:5px; 
    font-size:12px; 
    text-align:left; 
}

.id-card p.rule { 
    margin:3px 0; 
    font-size:10px; 
    color:#333; 
}

.id-card .empno { 
    margin-top:8px; 
}

.emp-photo { width:72px; height:72px; object-fit:cover; border-radius:50%; display:block; margin:6px auto; border:2px solid #fff; box-shadow:0 0 2px rgba(0,0,0,0.15); }

@media print {
    body * {
        visibility: hidden;
    }

    #preview, #preview * {
        visibility: visible;
    }

    #preview {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        display: flex;
        justify-content: center;
        gap: 10mm;
    }

    .id-card {
        width: 54mm;
        height: 86mm;
        border: 1px solid #000;
        page-break-inside: avoid;
    }

    .signature {
        margin-top: 5px;
        text-align: center;
    }

    .signature img {
        width: 80px;
        height: auto;
    }

    .signature p {
        font-size: 10px;
        margin-top: 3px;
    }
}

</style>

<div id="layoutSidenav_content">
    <main class="container-fluid px-4"></main>
        <div class="container">
            <br>
            <h3>Employee ID Generator</h3>

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
                    <label>Employee No</label>
                    <input type="text" id="emp_no" class="form-control" readonly>
                </div>

                <div class="col-md-3">
                    <label>Contact Person</label>
                    <input type="text" id="emp_contact_person" class="form-control" readonly>
                </div>

                <div class="col-md-3">
                    <label>Contact Address</label>
                    <input type="text" id="emp_contact_address" class="form-control" readonly>
                </div>

                <div class="col-md-3">
                    <label>Contact Number</label>
                    <input type="text" id="emp_contact_number" class="form-control" readonly>
                </div>

                <div class="col-md-3">
                    <label>Company Name</label>
                    <input type="text" id="id_title" class="form-control" placeholder="Company Name">
                </div>

                <div class="col-md-3">
                    <label>ID Logo</label>
                    <input type="file" id="logo_input" class="form-control" accept="image/*">
                </div>

                <div class="col-md-3">
                    <label>Company Address</label>
                    <input type="text" id="company_address" class="form-control" placeholder="Company Address">
                </div>

                <div class="col-md-3">
                    <label>Telephone No.</label>
                    <input type="text" id="company_phone" class="form-control" placeholder="Telephone Number">
                </div>

                <div class="col-md-3">
                    <label>Authorized Signature</label>
                    <input type="file" id="signature_input" class="form-control" accept="image/*">
                </div>


                <div class="col-md-3 d-flex align-items-end">
                    <button id="generate_btn" class="btn btn-primary w-100" onclick="generateID()" disabled>
                        Generate ID
                    </button>
                </div>

                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-success w-100" onclick="printID()" disabled id="print_btn">
                        Print ID
                    </button>
                </div>
            </div>

            <div id="preview" class="id-card-container" style="display:none;"></div>
        </div>

        <script>
            const EMPLOYEES = <?= json_encode($emps) ?>;
            const DEPARTMENTS = <?= json_encode($depts) ?>;
            let photoDataURL = ''; // profile pic from users.profile_pic (updated on selection)


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
                        o.textContent = `${e.name} (${e.employee_no})`;
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
                emp_no.value = '';
                generate_btn.disabled = true;
                preview.style.display = 'none';
                preview.innerHTML = '';
            }

            function onEmployeeChange() {
                const id = employee_select.value;
                if (!id) return clearFields();

                // fast-path: if we already have the photo in the client EMPLOYEES list, use it immediately
                const local = EMPLOYEES.find(e => String(e.id) === String(id));
                if (local && local.photo) {
                    // Use relative path so it works under subdirectory (e.g., /phpfile/LATEST_SYSTEM)
                    photoDataURL = `uploads/${local.photo}`;
                } else {
                    photoDataURL = '';
                }

                fetch(`?id=${id}`)
                    .then(r => r.json())
                    .then(d => {
                        if (!d.id) return clearFields();
                        emp_name.value = d.name;
                        emp_position.value = d.position;
                        emp_no.value = d.employee_no;
                        emp_contact_person.value = d.contact_person || '';
                        emp_contact_address.value = d.contact_person_address || '';
                        emp_contact_number.value = d.contact_person_contact || '';
                        // profile picture (server wins if present) — use relative path
                        photoDataURL = d.photo ? `uploads/${d.photo}` : photoDataURL;
                        generate_btn.disabled = false; 
                    });
            }

            function generateID() {
                preview.style.display = 'flex';

                const logoHTML = logoDataURL
                    ? `<img src="${logoDataURL}" class="id-logo">`
                    : '';

                const photoHTML = photoDataURL
                    ? `<img src="${photoDataURL}" class="emp-photo">`
                    : '';

                const signatureHTML = signatureDataURL
                    ? `
                        <div class="signature">
                            <img src="${signatureDataURL}">
                            <p><strong>Authorized Signature</strong></p>
                        </div>
                    `
                    : '';

                preview.innerHTML = `
                    <div class="id-card front">
                        ${logoHTML}
                        <h6 class="id-title"><b>${escape(id_title.value)}</b></h6>
                        <p>${escape(company_address.value)}</p>
                        <p>Tel: ${escape(company_phone.value)}</p>
                        ${photoHTML}
                        <p class="name"><strong>${escape(emp_name.value)}</strong></p>
                        <p class="position">${escape(emp_position.value)}</p>
                        <p class="empno"><strong>${escape(emp_no.value)}</strong></p>
                    </div>

                    <div class="id-card back">
                        <p class="section-title"><strong>EMPLOYEE DETAILS</strong></p>
                        <p>${escape(emp_name.value)}</p>
                        <p>${escape(emp_no.value)}</p>
                        <p>${escape(emp_contact_person.value)}</p>
                        <p>${escape(emp_contact_address.value)}</p>
                        <p>${escape(emp_contact_number.value)}</p>

                        <hr>
                        <p class="title-strong"><strong>${escape(id_title.value)}</strong></p>
                        <p class="rule">1. This card is the property of the company and must be returned upon termination.</p>
                        <p class="rule">2. For identification purposes only.</p>
                        <p class="rule">3. Report loss immediately to HR.</p>
                        ${signatureHTML}
                    </div>
                `;

                document.getElementById('print_btn').disabled = false;
            }

            function escape(s) {
                return String(s).replace(/[&<>"']/g, m =>
                    ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])
                );
            }

            function printID() {
                window.print();
            }

            document.addEventListener('DOMContentLoaded', () => {
                populateDepartments();
                populateEmployees();
            });

            let logoDataURL = '';
            document.getElementById('logo_input').addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = e => {
                    logoDataURL = e.target.result;
                };
                reader.readAsDataURL(file);
            });

            // Signature upload
            let signatureDataURL = '';

            document.getElementById('signature_input').addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;

                const reader = new FileReader();
                reader.onload = e => {
                    signatureDataURL = e.target.result;
                };
                reader.readAsDataURL(file);
            });

        </script>
        <?php include __DIR__ . '/layout/FOOTER.php'; ?>
    </main>
</div>
