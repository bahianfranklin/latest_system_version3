<?php
// Simulate POST data for testing generated ID without file upload
$_POST['contact_person'] = 'Jane Smith';
$_POST['contact_person_address'] = '123 Sample St';
$_POST['contact_person_number'] = '09171234567';

$title = 'ACME';
$name = 'JOHN DOE';
$department = 'SALES';
$employee_no = 'EMP001';
$year = '2025';

// Use the same logic as in GENERATE_ID-TEST.php
$contact_person = isset($_POST['contact_person']) ? strtoupper(trim($_POST['contact_person'])) : '';
$contact_person_address = isset($_POST['contact_person_address']) ? strtoupper(trim($_POST['contact_person_address'])) : '';
$contact_person_number = isset($_POST['contact_person_number']) ? trim($_POST['contact_person_number']) : '';

$parts = [$title, $name, $department, $employee_no, $year];
if ($contact_person !== '') { $parts[] = $contact_person; }
if ($contact_person_address !== '') { $parts[] = $contact_person_address; }
if ($contact_person_number !== '') { $parts[] = $contact_person_number; }

$generated_id = implode('-', $parts);
$generated_id = preg_replace('/\s+/', '', $generated_id);

echo "Generated ID: $generated_id\n";
