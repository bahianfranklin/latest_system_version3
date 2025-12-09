<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';

// Fetch system settings (only ONE row expected)
$settings = [
    'system_title' => 'Company Name',
    'system_footer' => 'Authorized Signature',
    'system_logo' => 'default_logo.png'
];

$settingsQuery = $conn->query("SELECT * FROM system_settings ORDER BY id DESC LIMIT 1");
if ($settingsQuery && $settingsQuery->num_rows > 0) {
    $settings = $settingsQuery->fetch_assoc();
}

// Logo path
$systemLogo = !empty($settings['system_logo'])
    ? 'uploads/system/' . $settings['system_logo']
    : 'uploads/system/default_logo.png';


// Fetch all users for dropdown
$usersQuery = $conn->query("SELECT id, name, employee_no FROM users ORDER BY name ASC");
$users = ($usersQuery && $usersQuery->num_rows > 0) ? $usersQuery->fetch_all(MYSQLI_ASSOC) : [];

// Fetch all departments for dropdown
$deptQuery = $conn->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department ASC");
$departments = ($deptQuery && $deptQuery->num_rows > 0) ? $deptQuery->fetch_all(MYSQLI_ASSOC) : [];

// Default ID
$id = null;
$data = null;
$profile_pic = "uploads/default.png";

// Check if user selected via form or URL
if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
    $id = intval($_POST['user_id']);
} elseif (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
} elseif (isset($_SESSION['user_id'])) {
    $id = intval($_SESSION['user_id']);
}

// Fetch user data if ID is available
if ($id) {
    $query = $conn->prepare("
        SELECT u.*, w.*
        FROM users u
        LEFT JOIN work_details w ON w.user_id = u.id
        WHERE u.id = ?
    ");
    $query->bind_param("i", $id);
    $query->execute();
    $result = $query->get_result();
    $data = $result->fetch_assoc();
    
    if ($data) {
        $profile_pic = (!empty($data['profile_pic'])) ? "uploads/" . $data['profile_pic'] : "uploads/default.png";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Employee ID</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        
        .container-main {
            display: flex;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .selection-panel {
            flex: 1;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .id-card-panel {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .selection-panel h4 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .id-card {
            width: 350px;
            height: 550px;
            padding: 20px;
            border: 3px solid #000;
            border-radius: 12px;
            font-family: Arial, sans-serif;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 10px;
            background-color: #007bff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
        }
        
        .header h3 {
            margin: 5px 0;
            font-size: 20px;
            font-weight: bold;
        }
        
        .header p {
            margin: 3px 0;
            font-size: 13px;
            color: #666;
        }
        
        .photo {
            text-align: center;
            margin: 10px 0;
        }
        
        .photo img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 2px solid #007bff;
            object-fit: cover;
        }
        
        .info {
            font-size: 12px;
            margin: 10px 0;
        }
        
        .info p {
            margin: 5px 0;
            line-height: 1.3;
        }
        
        .info strong {
            display: inline-block;
            width: 80px;
        }
        
        .footer {
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 10px;
            margin-top: 15px;
            font-size: 11px;
        }
        
        .btn-print {
            margin-top: 15px;
        }

        .logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 10px;
            background-color: #007bff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
        }
        
        @media print {
            .selection-panel, .btn-print {
                display: none;
            }
            
            .container-main {
                gap: 0;
            }
            
            .id-card-panel {
                flex: none;
            }
        }
    </style>
</head>
<body>

<div class="container-main">
    <!-- Selection Panel -->
    <div class="selection-panel">
        <h4>📋 Generate Employee ID Card</h4>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="department" class="form-label">Select Department:</label>
                <select class="form-select" id="department" name="department" onchange="filterUsersByDept()">
                    <option value="">-- All Departments --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= htmlspecialchars($dept['department']) ?>">
                            <?= htmlspecialchars($dept['department']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="user_id" class="form-label">Select User:</label>
                <select class="form-select" id="user_id" name="user_id" onchange="this.form.submit()">
                    <option value="">-- Select Employee --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>" <?= ($id == $user['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['employee_no']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="button" class="btn btn-primary btn-print" onclick="window.print()">
                🖨️ Print ID Card
            </button>
        </form>
    </div>
    
    <!-- ID Card Display -->
    <div class="id-card-panel">
        <?php if ($data): ?>
        <div class="id-card">
            <div class="header">
                <img src="<?= htmlspecialchars($systemLogo) ?>" class="system-logo" alt="System Logo">
                <h3><?= htmlspecialchars($settings['system_title']) ?></h3>
                <p>Employee Identification Card</p>
            </div>
            <div class="photo">
                <img src="<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture">
            </div>

            <div class="info">
                <p><strong>Name:</strong> <?= htmlspecialchars($data['name'] ?? '') ?></p>
                <p><strong>Employee No:</strong> <?= htmlspecialchars($data['employee_no'] ?? '') ?></p>
                <p><strong>Position:</strong> <?= htmlspecialchars($data['position'] ?? '') ?></p>
                <p><strong>Department:</strong> <?= htmlspecialchars($data['department'] ?? '') ?></p>
                <?php if (!empty($data['date_hired'])): ?>
                    <p><strong>Date Hired:</strong> <?= date('M d, Y', strtotime($data['date_hired'])) ?></p>
                <?php endif; ?>
            </div>

            <div class="footer">
                <p>Authorized Signature</p>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info" style="width: 350px;">
            <strong>Select an employee from the list to generate their ID card.</strong>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function filterUsersByDept() {
    const deptSelect = document.getElementById('department');
    const userSelect = document.getElementById('user_id');
    const selectedDept = deptSelect.value;
    
    // Get all options
    const allOptions = Array.from(userSelect.querySelectorAll('option')).slice(1); // Skip first empty option
    
    // Show/hide based on department
    userSelect.querySelectorAll('option:not(:first-child)').forEach(opt => {
        opt.style.display = selectedDept ? 'block' : 'block';
    });
    
    // Reset selection
    userSelect.value = '';
}
</script>

</body>
</html>
