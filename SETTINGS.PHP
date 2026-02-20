<?php
require 'db.php';
session_start();

/* 1. Fetch existing settings */
$q = $conn->query("SELECT * FROM system_settings WHERE id = 1");
$settings = $q->fetch_assoc();

/* If table empty, create default row */
if (!$settings) {
    $conn->query("
        INSERT INTO system_settings 
        (id, system_title, system_footer, system_logo, company_name)
        VALUES 
        (1, 'My System', '© 2025 My System', '', 'My Company')
    ");

    $q = $conn->query("SELECT * FROM system_settings WHERE id = 1");
    $settings = $q->fetch_assoc();
}

$SYSTEM_TITLE     = $settings['system_title'] ?? '';
$SYSTEM_FOOTER    = $settings['system_footer'] ?? '';
$SYSTEM_LOGO      = $settings['system_logo'] ?? '';
$COMPANY_NAME     = $settings['company_name'] ?? '';
$ADDRESS          = $settings['address'] ?? '';
$TELEPHONE_NUMBER = $settings['telephone_number'] ?? '';

/* 2. Update settings when form is submitted */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $logo_name = $SYSTEM_LOGO;

    if (!empty($_FILES['system_logo']['name'])) {
        $logo_name = time() . "_" . $_FILES['system_logo']['name'];
        move_uploaded_file($_FILES['system_logo']['tmp_name'], "uploads/logos/" . $logo_name);
    }

    $stmt = $conn->prepare("
        UPDATE system_settings 
        SET system_title = ?, 
            company_name = ?, 
            system_footer = ?, 
            system_logo = ?,
            address = ?,
            telephone_number = ?
        WHERE id = 1
    ");

    $stmt->bind_param(
        "ssssss",
        $_POST['system_title'],
        $_POST['company_name'],
        $_POST['system_footer'],
        $logo_name,
        $_POST['address'],
        $_POST['telephone_number']
    );

    $stmt->execute();

    header("Location: settings.php?success=1");
    exit;
}
?>

<?php include __DIR__ . '/layout/HEADER'; ?>
<?php include __DIR__ . '/layout/NAVIGATION'; ?>

<div id="layoutSidenav_content">
    <main>
        <div class="container-fluid px-4">
            <h2 class="mt-4">System Settings</h2>
            <br>
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    Settings updated successfully!
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    Update System Settings
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">

                        <div class="mb-3">
                            <label class="form-label">System Title</label>
                            <input type="text" name="system_title" class="form-control"
                                value="<?= htmlspecialchars($SYSTEM_TITLE) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company_name" class="form-control"
                                value="<?= htmlspecialchars($COMPANY_NAME) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Company Address</label>
                            <textarea name="address" class="form-control"
                                rows="3"><?= htmlspecialchars($ADDRESS) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Telephone Number</label>
                            <input type="text" name="telephone_number" class="form-control"
                                value="<?= htmlspecialchars($TELEPHONE_NUMBER) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">System Footer</label>
                            <textarea name="system_footer" class="form-control" rows="3"
                                required><?= htmlspecialchars($SYSTEM_FOOTER) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">System Logo</label>
                            <input type="file" name="system_logo" class="form-control">
                        </div>

                        <?php if (!empty($SYSTEM_LOGO)): ?>
                            <div class="mb-3">
                                <label>Current Logo:</label><br>
                                <img src="uploads/logos/<?= htmlspecialchars($SYSTEM_LOGO) ?>" height="80"
                                    class="img-thumbnail">
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-success">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>

        <?php include __DIR__ . '/layout/FOOTER.php'; ?>
    </main>
</div>