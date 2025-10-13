<?php
require 'CONFIG.php';

// ✅ Get params
$search = $_GET['search'] ?? '';
$page   = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage= $_GET['limit'] ?? 5;
$type   = $_GET['type'] ?? '';

// ✅ Fetch all records from API
$url = $baseUrl . "?action=view";
$result = requestData($url);
$result = preg_replace('/^[^\{]+/', '', $result);
$result = preg_replace('/[^\}]+$/', '', $result);
$dataArray = json_decode($result, true);

$records = $dataArray['data'] ?? [];

// ✅ Apply search filter
if (!empty($search)) {
    $records = array_filter($records, function($row) use ($search) {
        return stripos($row['fullname'], $search) !== false ||
               stripos($row['address'], $search) !== false ||
               stripos($row['contact_no'], $search) !== false;
    });
}

// ✅ Pagination
$totalRecords = count($records);
if ($perPage !== "all") {
    $perPage = intval($perPage);
    $offset = ($page - 1) * $perPage;
    $records = array_slice($records, $offset, $perPage);
}

// ✅ Export by type
if ($type === "csv") {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=contacts.csv");
    $out = fopen("php://output", "w");
    fputcsv($out, ["Name", "Address", "Contact"]);
    foreach ($records as $row) {
        fputcsv($out, [$row['fullname'], $row['address'], $row['contact_no']]);
    }
    fclose($out);
    exit;
}

if ($type === "excel") {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=contacts.xls");
    echo "<table border='1'>";
    echo "<tr><th>Name</th><th>Address</th><th>Contact</th></tr>";
    foreach ($records as $row) {
        echo "<tr>";
        echo "<td>".htmlspecialchars($row['fullname'])."</td>";
        echo "<td>".htmlspecialchars($row['address'])."</td>";
        echo "<td>".htmlspecialchars($row['contact_no'])."</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

if ($type === "pdf") {
    require_once __DIR__ . "/fpdf/fpdf.php";

    // ✅ Landscape mode
    $pdf = new FPDF("L", "mm", "A4");
    $pdf->AddPage();
    $pdf->SetFont("Arial", "B", 14);
    $pdf->Cell(0, 10, "Contacts List", 0, 1, "C");

    // ✅ Table header
    $pdf->SetFont("Arial", "B", 10);
    $pdf->Cell(90, 10, "Name", 1);     // wider
    $pdf->Cell(90, 10, "Address", 1);  // wider
    $pdf->Cell(90, 10, "Contact", 1);  // wider
    $pdf->Ln();

    // ✅ Table rows
    $pdf->SetFont("Arial", "", 10);
    foreach ($records as $row) {
        $pdf->Cell(90, 10, $row['fullname'], 1);
        $pdf->Cell(90, 10, $row['address'], 1);
        $pdf->Cell(90, 10, $row['contact_no'], 1);
        $pdf->Ln();
    }

    $pdf->Output("D", "contacts.pdf");
    exit;
}

