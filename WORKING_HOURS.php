<?php
    require 'db.php';
    require 'audit.php';

    // Fetch all users + working hours
    $query = "
    SELECT 
        u.id AS user_id,
        u.name,
        w.department,
        w.position,
        e.id AS work_id,
        e.work_day,
        e.time_in,
        e.time_out
    FROM users u
    LEFT JOIN work_details w ON w.user_id = u.id
    LEFT JOIN employee_working_hours e ON u.id = e.user_id
    WHERE u.status = 'active'
    ORDER BY u.name, FIELD(e.work_day, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
    ";

    $result = $conn->query($query);

    // organize data by user
    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    $all_data = [];

    while ($row = $result->fetch_assoc()) {
        $uid = $row['user_id'];
        $all_data[$uid]['name'] = $row['name'];
        if ($row['work_day']) {
            $all_data[$uid]['work'][$row['work_day']] = [
                'id' => $row['work_id'],
                'time_in' => $row['time_in'],
                'time_out' => $row['time_out']
            ];
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Employee Working Hours</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light p-4">

        <div class="container">
            <h4 class="mb-4">Employee Working Hours</h4>

            <?php foreach ($all_data as $user_id => $data): ?>
                <h4 class="mt-4"><?= htmlspecialchars($data['name']) ?></h4>
                <table class="table table-bordered table-sm align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Day</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($days as $day): 
                        $work = $data['work'][$day] ?? null;
                        $time_in = $work['time_in'] ?? '';
                        $time_out = $work['time_out'] ?? '';
                        $work_id = $work['id'] ?? '';
                    ?>
                        <tr>
                            <td><?= $day ?></td>
                            <td><?= $time_in ?></td>
                            <td><?= $time_out ?></td>
                            <td class="text-center">
                                <?php if ($work_id): ?>
                                    <button class="btn btn-sm btn-primary editBtn"
                                        data-id="<?= $work_id ?>"
                                        data-day="<?= $day ?>"
                                        data-timein="<?= $time_in ?>"
                                        data-timeout="<?= $time_out ?>">
                                        Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger deleteBtn" data-id="<?= $work_id ?>">Delete</button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-success addBtn"
                                        data-userid="<?= $user_id ?>"
                                        data-day="<?= $day ?>">
                                        Add
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        </div>

        <!-- 🔹 Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <form id="editForm">
                <div class="modal-header">
                <h5 class="modal-title">Edit Working Hours</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <input type="hidden" name="id" id="edit_id">

                <div class="mb-3">
                    <label>Work Day</label>
                    <input type="text" id="edit_day" class="form-control" readonly>
                </div>

                <div class="mb-3">
                    <label>Time In</label>
                    <input type="time" name="time_in" id="edit_time_in" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Time Out</label>
                    <input type="time" name="time_out" id="edit_time_out" class="form-control" required>
                </div>
                </div>
                <div class="modal-footer">
                <button type="submit" class="btn btn-success">Save Changes</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
            </div>
        </div>
        </div>

        <!-- 🔹 Delete Modal -->
        <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
            <form id="deleteForm">
                <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <input type="hidden" name="id" id="delete_id">
                <p>Are you sure you want to delete this record?</p>
                </div>
                <div class="modal-footer">
                <button type="submit" class="btn btn-danger">Delete</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
            </div>
        </div>
        </div>

        <!-- 🔹 Add Modal -->
        <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
            <form id="addForm">
                <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Add Working Hours</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                <input type="hidden" name="user_id" id="add_user_id">
                <input type="hidden" name="work_day" id="add_work_day">

                <div class="mb-3">
                    <label>Work Day</label>
                    <input type="text" id="add_day_display" class="form-control" readonly>
                </div>

                <div class="mb-3">
                    <label>Time In</label>
                    <input type="time" name="time_in" id="add_time_in" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Time Out</label>
                    <input type="time" name="time_out" id="add_time_out" class="form-control" required>
                </div>
                </div>
                <div class="modal-footer">
                <button type="submit" class="btn btn-success">Save</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
            </div>
        </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

        <script>
        // 🔹 Edit button click
        $(document).on('click', '.editBtn', function() {
            $('#edit_id').val($(this).data('id'));
            $('#edit_day').val($(this).data('day'));
            $('#edit_time_in').val($(this).data('timein'));
            $('#edit_time_out').val($(this).data('timeout'));
            new bootstrap.Modal(document.getElementById('editModal')).show();
        });

        // 🔹 Delete button click
        $(document).on('click', '.deleteBtn', function() {
            $('#delete_id').val($(this).data('id'));
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        });

        // 🔹 Save edits via AJAX
        $('#editForm').on('submit', function(e) {
            e.preventDefault();
            $.post('UPDATE_WORKING_HOURS.php', $(this).serialize(), function(response) {
                alert(response);
                location.reload();
            });
        });

        // 🔹 Delete record via AJAX
        $('#deleteForm').on('submit', function(e) {
            e.preventDefault();
            $.post('DELETE_WORKING_HOURS.php', $(this).serialize(), function(response) {
                alert(response);
                location.reload();
            });
        });
        </script>

        <script>
            // 🔹 Add button click
            $(document).on('click', '.addBtn', function() {
                let userId = $(this).data('userid');
                let day = $(this).data('day');
                $('#add_user_id').val(userId);
                $('#add_work_day').val(day);
                $('#add_day_display').val(day);
                new bootstrap.Modal(document.getElementById('addModal')).show();
            });

            // 🔹 Submit Add Form via AJAX
            $('#addForm').on('submit', function(e) {
                e.preventDefault();
                $.post('ADD_WORKING_HOURS.php', $(this).serialize(), function(response) {
                    alert(response);
                    location.reload();
                });
            });
        </script>
        
    </body>
</html>
