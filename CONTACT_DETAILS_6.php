<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("CONFIG_2.php");   // API_KEY and COMPANY_KEY
include("WEBLIB.php");

$weblib = new WebLib();

// VIEW: Get all contacts
$urlView = "https://api.mandbox.com/apitest/v1/contact.php?action=view";

$params = [
    "record_id" => "",
    "limit"     => 50,
    "page"      => 1
];

$weblib->requestURL($urlView, $params);
$dataList = $weblib->resultData(); // array of contacts
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contacts CRUD</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">

<div class="container">
    <h2>Contacts List</h2>
    <div class="row mb-3">
        <div class="col-12 text-end">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                Add Contact
            </button>
        </div>
    </div>


    <?php if(!empty($dataList)): ?>
        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Address</th>
                    <th>Contact No</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach($dataList as $data): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($data->fullname ?? '') ?></td>
                        <td><?= htmlspecialchars($data->address ?? '') ?></td>
                        <td><?= htmlspecialchars($data->contact_no ?? '') ?></td>
                        <td>
                            <button class="btn btn-primary btn-sm editBtn" 
                                    data-id="<?= $data->id ?>" 
                                    data-fullname="<?= htmlspecialchars($data->fullname) ?>" 
                                    data-address="<?= htmlspecialchars($data->address) ?>" 
                                    data-contact="<?= htmlspecialchars($data->contact_no) ?>"
                                    data-bs-toggle="modal" data-bs-target="#editModal">Edit</button>
                            <button class="btn btn-danger btn-sm deleteBtn" data-id="<?= $data->id ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No records found.</p>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="addForm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label>Full Name</label>
                    <input type="text" name="fullname" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Address</label>
                    <input type="text" name="address" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Contact No</label>
                    <input type="text" name="contact_no" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success">Add</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editForm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Contact</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="record_id" id="edit_id">
                <div class="mb-3">
                    <label>Full Name</label>
                    <input type="text" name="fullname" id="edit_fullname" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Address</label>
                    <input type="text" name="address" id="edit_address" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Contact No</label>
                    <input type="text" name="contact_no" id="edit_contact" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function(){

    // Open edit modal and populate values
    $('.editBtn').click(function(){
        $('#edit_id').val($(this).data('id'));
        $('#edit_fullname').val($(this).data('fullname'));
        $('#edit_address').val($(this).data('address'));
        $('#edit_contact').val($(this).data('contact'));
    });

    // Handle Add form submit
    $('#addForm').submit(function(e){
        e.preventDefault();
        $.post('CRUD.php?action=add', $(this).serialize(), function(response){
            alert(response);
            location.reload();
        });
    });

    // Handle Edit form submit
    $('#editForm').submit(function(e){
        e.preventDefault();
        $.post('CRUD.php?action=edit', $(this).serialize(), function(response){
            alert(response);
            location.reload();
        });
    });

    // Handle Delete
    $('.deleteBtn').click(function(){
        if(confirm('Are you sure to delete this contact?')){
            $.post('CRUD.php?action=delete', {record_id: $(this).data('id')}, function(response){
                alert(response);
                location.reload();
            });
        }
    });

});
</script>
</body>
</html>
