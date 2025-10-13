<?php
    require 'db.php';
    //require 'vendor/autoload.php';
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

    // Get export type
    $type = $_GET['type'] ?? 'csv';

    // Optional search filter
    $search = $_GET['search'] ?? '';

    // Build query
    $sql = "SELECT id, name, address, contact, email, username, role, status, profile_pic 
            FROM users";
    $conditions = [];
    $params = [];
    $types = '';

    if ($search !== '') {
        $conditions[] = "(name LIKE ? OR username LIKE ? OR email LIKE ? OR role LIKE ? OR status LIKE ?)";
        $like = "%$search%";
        $params = [$like, $like, $like, $like, $like];
        $types .= 'sssss';
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY id ASC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Convert result to array
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // Export handlers
    if ($type === 'csv') {
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=users.csv");
        $out = fopen("php://output", "w");
        fputcsv($out, ["ID", "Name", "Address", "Contact", "Email", "Username", "Role", "Status", "Profile Pic"]);
        foreach ($data as $row) {
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    if ($type === 'excel') {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(["ID", "Name", "Address", "Contact", "Email", "Username", "Role", "Status", "Profile Pic"], NULL, 'A1');
        $sheet->fromArray($data, NULL, 'A2');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="users.xlsx"');
        $writer = new Xlsx($spreadsheet);
        $writer->save("php://output");
        exit;
    }

    if ($type === 'pdf') {
        require 'fpdf/fpdf.php';
        $pdf = new FPDF('L','mm','A4'); // Landscape
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(0,10,'Users List',0,1,'C');
        $pdf->SetFont('Arial','B',9);

        // Header
        $pdf->Cell(10,8,'ID',1);
        $pdf->Cell(30,8,'Name',1);
        $pdf->Cell(40,8,'Address',1);
        $pdf->Cell(25,8,'Contact',1);
        $pdf->Cell(40,8,'Email',1);
        $pdf->Cell(30,8,'Username',1);
        $pdf->Cell(25,8,'Role',1);
        $pdf->Cell(20,8,'Status',1);
        $pdf->Cell(40,8,'Profile Pic',1);
        $pdf->Ln();

        $pdf->SetFont('Arial','',8);
        foreach ($data as $row) {
            $pdf->Cell(10,8,$row['id'],1);
            $pdf->Cell(30,8,$row['name'],1);
            $pdf->Cell(40,8,substr($row['address'],0,25),1);
            $pdf->Cell(25,8,$row['contact'],1);
            $pdf->Cell(40,8,substr($row['email'],0,25),1);
            $pdf->Cell(30,8,$row['username'],1);
            $pdf->Cell(25,8,$row['role'],1);
            $pdf->Cell(20,8,$row['status'],1);
            $pdf->Cell(40,8,$row['profile_pic'],1);
            $pdf->Ln();
        }

        $pdf->Output("D","users.pdf");
        exit;
    }

    echo "Invalid export type";
?>
