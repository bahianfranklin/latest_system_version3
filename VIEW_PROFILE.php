<?php
require 'db.php';
require 'autolock.php';

session_start();

// audit helper (defines logAction)
if (file_exists(__DIR__ . '/AUDIT.php')) {
  require_once __DIR__ . '/AUDIT.php';
}

$user_id = $_SESSION['user_id'] ?? 1;

// Debug: Check if user_id is set
if (empty($_SESSION['user_id'])) {
    // Optionally redirect to login or show a message
    // For now, we'll continue with user_id = 1 (default)
}

// ---------- USERS TABLE ----------
$userQuery = $conn->prepare("SELECT * FROM users WHERE id = ?");
if (!$userQuery) {
    die("User query failed: " . $conn->error);
}
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();

// Debug: If no user found, show error
if (!$user) {
    die("Error: User ID " . intval($user_id) . " not found in the database. Please check your session or log in again.");
}

// ---------- WORK DETAILS ----------
$workQuery = $conn->prepare("SELECT * FROM work_details WHERE user_id = ?");
if (!$workQuery) {
    die("Work details query failed: " . $conn->error);
}
$workQuery->bind_param("i", $user_id);
$workQuery->execute();
$workResult = $workQuery->get_result();
$work = $workResult->fetch_assoc();

// ---------- WORKING HOURS ----------
$workingQuery = $conn->prepare("SELECT * FROM employee_working_hours WHERE user_id = ?");
if (!$workingQuery) {
    die("Employee working query failed: " . $conn->error);
}
$workingQuery->bind_param("i", $user_id);
$workingQuery->execute();
$workingResult = $workingQuery->get_result();
$workingHours = [];
while ($row = $workingResult->fetch_assoc()) {
    $workingHours[] = $row;
}

// ---------- SCHEDULE ----------
$schedQuery = $conn->prepare("SELECT * FROM employee_schedules WHERE user_id = ?");
if (!$schedQuery) {
    die("Employee schedule query failed: " . $conn->error);
}
$schedQuery->bind_param("i", $user_id);
$schedQuery->execute();
$schedResult = $schedQuery->get_result();
$schedule = $schedResult->fetch_assoc();

// Fetch all requirements and user uploads for the logged-in user
$reqQuery = $conn->prepare("
    SELECT r.requirement_name, ur.status, ur.date_uploaded, ur.uploaded_by, ur.file_path
    FROM requirements r
    LEFT JOIN user_requirements ur 
    ON ur.requirement_id = r.id AND ur.user_id = ?
");
$reqQuery->bind_param("i", $user_id);
$reqQuery->execute();
$reqResult = $reqQuery->get_result();

// 🟢 FETCH SALARY INFO FOR LOGGED-IN USER
$salaryQuery = $conn->prepare("SELECT * FROM user_salary_rates WHERE user_id = ?");
$salaryQuery->bind_param("i", $user_id);
$salaryQuery->execute();
$salaryResult = $salaryQuery->get_result();
$salary = $salaryResult->fetch_assoc();

logAction($conn, $user_id, "VIEW PROFILE", "User view his full profile data");

?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
  <main>
    <div class="container-fluid px-4">
      <div class="container mt-4 p-4 bg-white rounded shadow">
        <div class="d-flex align-items-center">
          <?php
            $profilePath = (!empty($user['profile_pic']) && file_exists(__DIR__ . '/uploads/' . $user['profile_pic']))
              ? 'uploads/' . $user['profile_pic']
              : 'uploads/img_temp.png';
          ?>
          <img src="<?= htmlspecialchars($profilePath) ?>" 
              alt="Profile Picture" width="120" height="120" class="rounded-circle border">
          <div class="ms-4 flex-grow-1">
          <h3 class="fw-bold mb-2"><?= strtoupper(htmlspecialchars($user['name'])) ?></h3>

          <div class="row mb-1">
            <div class="col-md-4"><b>Branch:</b> <?= htmlspecialchars($work['branch'] ?? '') ?></div>
            <div class="col-md-4"><b>Level:</b> <?= htmlspecialchars($work['level_desc'] ?? '') ?></div>
          </div>

          <div class="row mb-1">
            <div class="col-md-4"><b>Employee No.:</b> <?= htmlspecialchars($work['employee_no'] ?? '') ?></div>
            <div class="col-md-4"><b>Department:</b> <?= htmlspecialchars($work['department'] ?? '') ?></div>
            <div class="col-md-4"><b>Date Hired:</b> <?= htmlspecialchars($work['date_hired'] ?? '') ?></div>
          </div>

          <div class="row mb-1">
            <div class="col-md-4"><b>Address:</b> <?= htmlspecialchars($user['address'] ?? '') ?></div>
            <div class="col-md-4"><b>Position:</b> <?= htmlspecialchars($work['position'] ?? '') ?></div>
            <div class="col-md-4"><b>Status:</b> <?= htmlspecialchars($work['status_desc'] ?? '') ?></div>
          </div>
        </div>
      </div>
        <hr>

        <div class="d-flex justify-content-end mt-2 gap-2">
          <button class="btn btn-secondary btn-sm" onclick="printAllData()">
            <i class="fas fa-print me-1"></i> Print All Data
          </button>
          
          <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
            <i class="fas fa-key me-1"></i> Change Password
          </button>
        </div>

        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
          <li class="nav-item">
            <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button">Personal Information</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" id="work-tab" data-bs-toggle="tab" data-bs-target="#work" type="button">Work Details</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule" type="button">Working Schedule</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" id="requirements-tab" data-bs-toggle="tab" data-bs-target="#requirements" type="button">201 Files</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" id="salary-tab" data-bs-toggle="tab" data-bs-target="#salary" type="button">Salary</button>
          </li>
        </ul>

      <div class="tab-content mt-3" id="profileTabsContent">

        <div class="tab-pane fade show active" id="personal" role="tabpanel">
          <table class="table table-bordered">
            <tr><th>Employee ID</th><td><?= htmlspecialchars($work['employee_no'] ?? '') ?></td></tr>
            <tr><th>Full Name</th><td><?= htmlspecialchars($user['full_name'] ?? $user['name'] ?? '') ?></td></tr>
            <tr><th>Gender</th><td><?= htmlspecialchars($user['gender'] ?? '') ?></td></tr>
            <tr><th>Civil Status</th><td><?= htmlspecialchars($user['civil_status'] ?? '') ?></td></tr>
            <tr><th>Nationality</th><td><?= htmlspecialchars($user['nationality'] ?? '') ?></td></tr>
            <tr><th>Religion</th><td><?= htmlspecialchars($user['religion'] ?? '') ?></td></tr>
            <tr><th>Date of Birth</th><td><?= htmlspecialchars($user['birthday'] ?? '') ?></td></tr>
            <tr><th>Place of Birth</th><td><?= htmlspecialchars($user['place_of_birth'] ?? '') ?></td></tr>
            <tr><th>Age</th><td><?= htmlspecialchars($user['age'] ?? '') ?></td></tr>
            <tr><th>Mobile No.</th><td><?= htmlspecialchars($user['mobile_no'] ?? '') ?></td></tr>
            <tr><th>Contact</th><td><?= htmlspecialchars($user['contact'] ?? '') ?></td></tr>
            <tr><th>Email Address</th><td><?= htmlspecialchars($user['email'] ?? '') ?></td></tr>
            <tr><th>Address</th><td><?= htmlspecialchars($user['address'] ?? '') ?></td></tr>
            <tr><th>Region</th><td><?= htmlspecialchars($user['region'] ?? '') ?></td></tr>
            <tr><th>Province</th><td><?= htmlspecialchars($user['province'] ?? '') ?></td></tr>
            <tr><th>City/Municipality</th><td><?= htmlspecialchars($user['city_municipality'] ?? '') ?></td></tr>
            <tr><th>Contact Person</th><td><?= htmlspecialchars($user['contact_person'] ?? '') ?></td></tr>
            <tr><th>Relationship</th><td><?= htmlspecialchars($user['contact_person_relationship'] ?? '') ?></td></tr>
            <tr><th>Contact Person Address</th><td><?= htmlspecialchars($user['contact_person_address'] ?? '') ?></td></tr>
            <tr><th>Contact Person Contact No.</th><td><?= htmlspecialchars($user['contact_person_contact'] ?? '') ?></td></tr>
            <tr><th>Mother's Name</th><td><?= htmlspecialchars($user['mother_name'] ?? '') ?></td></tr>
            <tr><th>Father's Name</th><td><?= htmlspecialchars($user['father_name'] ?? '') ?></td></tr>
            <tr><th>Status</th><td><?= htmlspecialchars(ucfirst($user['status'] ?? '')) ?></td></tr>
          </table>
        </div>

        <div class="tab-pane fade" id="work" role="tabpanel">
          <table class="table table-bordered">
            <tr><th>Bank Account No.</th><td><?= htmlspecialchars($work['bank_account_no'] ?? '') ?></td></tr>
            <tr><th>SSS No.</th><td><?= htmlspecialchars($work['sss_no'] ?? '') ?></td></tr>
            <tr><th>PhilHealth No.</th><td><?= htmlspecialchars($work['philhealth_no'] ?? '') ?></td></tr>
            <tr><th>PAG-IBIG No.</th><td><?= htmlspecialchars($work['pagibig_no'] ?? '') ?></td></tr>
            <tr><th>TIN No.</th><td><?= htmlspecialchars($work['tin_no'] ?? '') ?></td></tr>
            <tr><th>Date Hired</th><td><?= htmlspecialchars($work['date_hired'] ?? '') ?></td></tr>
            <tr><th>Regularization Date</th><td><?= htmlspecialchars($work['regularization'] ?? '') ?></td></tr>
            <tr><th>Branch</th><td><?= htmlspecialchars($work['branch'] ?? '') ?></td></tr>
            <tr><th>Department</th><td><?= htmlspecialchars($work['department'] ?? '') ?></td></tr>
            <tr><th>Position</th><td><?= htmlspecialchars($work['position'] ?? '') ?></td></tr>
            <tr><th>Level</th><td><?= htmlspecialchars($work['level_desc'] ?? '') ?></td></tr>
            <tr><th>Tax Category</th><td><?= htmlspecialchars($work['tax_category'] ?? '') ?></td></tr>
            <tr><th>Status</th><td><?= htmlspecialchars($work['status_desc'] ?? '') ?></td></tr>
            <tr><th>Leave Rule</th><td><?= htmlspecialchars($work['leave_rule'] ?? '') ?></td></tr>
          </table>
        </div>
        </br>
        <div class="tab-pane fade" id="schedule" role="tabpanel">
          <h6 class="fw-bold mt-3">Working Hours:</h6>
          <table class="table table-bordered text-center align-middle">
            <thead class="table-light">
              <tr>
                <th>Day</th>
                <th>Time</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($workingHours as $row): 
                $day = ucfirst($row['work_day']);
                $timeIn  = !empty($row['time_in'])  ? date("h:i A", strtotime($row['time_in'])) : 'N/A';
                $timeOut = !empty($row['time_out']) ? date("h:i A", strtotime($row['time_out'])) : 'N/A';

                // if both N/A → mark as restday
                if ($timeIn === 'N/A' && $timeOut === 'N/A') {
                    $timeBadge = "<span class='badge bg-secondary px-3 py-2'>Rest Day</span>";
                } else {
                    $timeBadge = "
                      <span class='badge bg-success px-3 py-2 me-2'>
                        <i class='bi bi-clock'></i> IN: {$timeIn}
                      </span>
                      <span class='badge bg-danger px-3 py-2'>
                        <i class='bi bi-clock-history'></i> OUT: {$timeOut}
                      </span>
                    ";
                }
              ?>
                <tr>
                  <th><?= $day ?></th>
                  <td><?= $timeBadge ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </br>
          <h6 class="mt-3 fw-bold">Schedule Type:</h6>
          <table class="table table-bordered text-center align-middle">
            <thead class="table-light">
              <tr>
                <th>Day</th>
                <th>Schedule</th>
              </tr>
            </thead>
            <tbody>
              <?php
            // Function that returns a badge based on schedule type
            function badgeSchedule($value) {
              // normalize: convert underscores/hyphens to spaces, trim and lowercase
              $raw = $value ?? '';
              $normalized = strtolower(trim(str_replace(['_', '-'], ' ', $raw)));

              // map known aliases to canonical labels
              $workFromHomeAliases = ['work from home', 'wfh', 'remote', 'work fromhome', 'workfromhome'];
              $restDayAliases = ['day off', 'rest day', 'rest_day', 'off'];

              if ($normalized === 'onsite') {
                $label = 'Onsite';
                $cls = 'bg-primary';
              } elseif (in_array($normalized, $workFromHomeAliases, true)) {
                $label = 'Work From Home';
                $cls = 'bg-success';
              } elseif (in_array($normalized, $restDayAliases, true)) {
                $label = 'Rest Day';
                $cls = 'bg-secondary';
              } elseif ($normalized === '') {
                $label = 'N/A';
                $cls = 'bg-light text-dark';
              } else {
                // fallback: prettify the normalized value (escape for safety)
                $label = ucwords($normalized);
                $cls = 'bg-dark';
              }

              return "<span class='badge {$cls} px-3 py-2'>" . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . "</span>";
            }
              ?>

              <tr><th>Monday</th><td><?= badgeSchedule($schedule['monday'] ?? '') ?></td></tr>
              <tr><th>Tuesday</th><td><?= badgeSchedule($schedule['tuesday'] ?? '') ?></td></tr>
              <tr><th>Wednesday</th><td><?= badgeSchedule($schedule['wednesday'] ?? '') ?></td></tr>
              <tr><th>Thursday</th><td><?= badgeSchedule($schedule['thursday'] ?? '') ?></td></tr>
              <tr><th>Friday</th><td><?= badgeSchedule($schedule['friday'] ?? '') ?></td></tr>
              <tr><th>Saturday</th><td><?= badgeSchedule($schedule['saturday'] ?? '') ?></td></tr>
              <tr><th>Sunday</th><td><?= badgeSchedule($schedule['sunday'] ?? '') ?></td></tr>
            </tbody>
          </table>
        </div>

        <div class="tab-pane fade" id="requirements" role="tabpanel">
          <h6 class="fw-bold mb-3 mt-3">Uploaded Requirements</h6>
          <table class="table table-bordered align-middle text-center">
            <thead class="table-light">
              <tr>
                <th>Requirement Name</th>
                <th>Status</th>
                <th>Date Uploaded</th>
                <th>Uploaded By</th>
                <th>File</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($row = $reqResult->fetch_assoc()): ?>
                <tr>
                  <td><?= htmlspecialchars($row['requirement_name']) ?></td>
                  <td><?= htmlspecialchars($row['status'] ?? 'Not Uploaded') ?></td>
                  <td><?= htmlspecialchars($row['date_uploaded'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($row['uploaded_by'] ?? '-') ?></td>
                  <td>
                    <?php if (!empty($row['file_path'])): ?>
                      <a href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn btn-sm btn-primary">
                        View File
                      </a>
                    <?php else: ?>
                      <span class="text-muted">No file</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>

        <div class="tab-pane fade" id="salary" role="tabpanel">
          <h6 class="fw-bold mb-3 mt-3">Salary Information</h6>

          <?php if ($salary): ?>
            <div class="table-responsive mt-3">
              <table class="table table-bordered">
                <tr><th>Monthly Rate:</th><td>₱<?= number_format($salary['monthly_rate'], 2) ?></td></tr>
                <tr><th>Periodic Rate:</th><td>₱<?= number_format($salary['periodic_rate'], 2) ?></td></tr>
                <tr><th>Daily Rate:</th><td>₱<?= number_format($salary['daily_rate'], 2) ?></td></tr>
                <tr><th>Hourly Rate:</th><td>₱<?= number_format($salary['hourly_rate'], 2) ?></td></tr>
              </table>
            </div>
          <?php else: ?>
            <p class="text-muted text-center mt-4">No salary information available.</p>
          <?php endif; ?>
        </div>
      </div> <!-- end tab-content -->
    </div> <!-- end container -->
  </main>
  <?php include __DIR__ . '/layout/FOOTER.php'; ?>
</div>
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> -->

<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"> -->
<!-- <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script> -->

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

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="changePasswordForm">
        <div class="modal-header bg-warning text-dark">
          <h5 class="modal-title" id="changePasswordModalLabel">
            <i class="fas fa-key me-2"></i>Change Password
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div id="passwordAlert"></div>
          <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i> Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById("changePasswordForm").addEventListener("submit", function(e) {
  e.preventDefault();

  const formData = new FormData(this);
  fetch("UPDATE_PASSWORD.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    const alertBox = document.getElementById("passwordAlert");
    if (data.status === "success") {
      alertBox.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
      this.reset();
      setTimeout(() => {
        const modal = bootstrap.Modal.getInstance(document.getElementById("changePasswordModal"));
        modal.hide();
      }, 1200);
    } else {
      alertBox.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
    }
  })
  .catch(() => {
    document.getElementById("passwordAlert").innerHTML = `<div class="alert alert-danger">Something went wrong.</div>`;
  });
});
</script>

<script>
function printAllData() {
    // Log audit trail via AJAX
    fetch("log_print_audit.php", {
        method: "POST"
    });

    // Trigger browser print
    window.print();
}
</script>

<style>
@media print {
  body * {
    visibility: hidden;
  }
  #layoutSidenav_content, #layoutSidenav_content * {
    visibility: visible;
  }
  #layoutSidenav_content {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
  .btn, .nav-tabs, .modal, .d-flex.justify-content-end {
    display: none !important;
  }
}
</style>