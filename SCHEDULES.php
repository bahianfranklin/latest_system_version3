<?php
require 'db.php';

// --- FETCH USERS WITH SCHEDULES ---
$sql = "SELECT u.id as user_id, u.name,
            es.id as schedule_id, es.monday, es.tuesday, es.wednesday, es.thursday, 
            es.friday, es.saturday, es.sunday
        FROM users u
        LEFT JOIN employee_schedules es ON es.user_id = u.id
        WHERE u.status='active'
        ORDER BY u.name";
$schedules = $conn->query($sql);
if (!$schedules) {
    die("Error fetching schedules: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Schedules</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .badge-onsite {
            background-color: #08e7a4ff !important;
            color: #000;
            font-weight: 600;
            padding: 0.35em 0.7em;
            border-radius: 15px;
            font-size: 0.85em;
        }
        .badge-work_from_home {
            background-color: #c0e00bff !important;
            color: #050505ff;
            font-weight: 600;
            padding: 0.35em 0.7em;
            border-radius: 15px;
            font-size: 0.85em;
        }
        .badge-rest_day {
            background-color: #7f8081ff !important;
            color: #000000ff;
            font-weight: 600;
            padding: 0.35em 0.7em;
            border-radius: 15px;
            font-size: 0.85em;
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid py-4">
    <h5 class="mb-4">Employee Work Schedules</h5>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle text-left">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th>
                    <th>Onsite</th><th>WFH</th><th>Rest</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $status_labels = [
                    'onsite' => 'ON-SITE',
                    'work_from_home' => 'WFH',
                    'rest_day' => 'REST'
                ];
                $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

                while($row = $schedules->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <?php
                        $counts = ['onsite'=>0, 'work_from_home'=>0, 'rest_day'=>0];
                        foreach($days as $day):
                            $status = $row[$day] ?? 'rest_day';
                            $counts[$status]++;
                        ?>
                            <td>
                                <span class="badge badge-<?= $status ?>">
                                    <?= $status_labels[$status] ?? strtoupper($status) ?>
                                </span>
                            </td>
                        <?php endforeach; ?>
                        <td><?= $counts['onsite'] ?></td>
                        <td><?= $counts['work_from_home'] ?></td>
                        <td><?= $counts['rest_day'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#schedule_editModal<?= $row['user_id'] ?>">Edit</button>
                            <?php if($row['schedule_id']): ?>
                                <a href="USER_MAINTENANCE.php?maintenanceTabs=schedules&delete_id=<?= $row['schedule_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this schedule?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="schedule_editModal<?= $row['user_id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form method="POST" action="">
                                    <input type="hidden" name="schedule_submit" value="1">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Schedule - <?= htmlspecialchars($row['name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body row g-3">
                                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                                        <?php foreach($days as $day): ?>
                                            <div class="col-md-3">
                                                <label class="form-label text-capitalize"><?= $day ?></label>
                                                <select class="form-select" name="<?= $day ?>">
                                                    <option value="onsite" <?= ($row[$day]=='onsite')?'selected':'' ?>>Onsite</option>
                                                    <option value="work_from_home" <?= ($row[$day]=='work_from_home')?'selected':'' ?>>WFH</option>
                                                    <option value="rest_day" <?= ($row[$day]=='rest_day' || !$row[$day])?'selected':'' ?>>Rest Day</option>
                                                </select>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-success">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
