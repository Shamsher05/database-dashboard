<?php
/**
 * export.php
 * ----------
 * Handles file exports.
 *   export.php?type=csv  -> downloads database_records.csv
 *   export.php?type=pdf  -> downloads database_records.pdf
 *
 * Always reads fresh data straight from MySQL — same source as the table.
 */

require_once __DIR__ . '/config/database.php';

$type = $_GET['type'] ?? '';

if (!in_array($type, ['csv', 'pdf'], true)) {
    http_response_code(400);
    die('Invalid export type.');
}

// Fetch all records fresh from the database
$stmt = $pdo->query('SELECT name, email, phone, age, address FROM records ORDER BY id ASC');
$records = $stmt->fetchAll();

// ==========================================================
// CSV EXPORT
// ==========================================================
if ($type === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="database_records.csv"');

    $output = fopen('php://output', 'w');

    // Header row
    fputcsv($output, ['Serial No.', 'Name', 'Email', 'Phone', 'Age', 'Address']);

    $serial = 1;
    foreach ($records as $row) {
        fputcsv($output, [
            $serial++,
            $row['name'],
            $row['email'],
            $row['phone'],
            $row['age'],
            $row['address'],
        ]);
    }

    fclose($output);
    exit;
}

// ==========================================================
// PDF EXPORT (requires Dompdf via Composer)
// ==========================================================
if ($type === 'pdf') {

    $autoload = __DIR__ . '/vendor/autoload.php';
    if (!file_exists($autoload)) {
        http_response_code(500);
        die('Dompdf is not installed. Run "composer require dompdf/dompdf" in the project folder.');
    }
    require_once $autoload;

    $rowsHtml = '';
    $serial = 1;
    foreach ($records as $row) {
        $rowsHtml .= '<tr>'
            . '<td>' . htmlspecialchars($serial++) . '</td>'
            . '<td>' . htmlspecialchars($row['name']) . '</td>'
            . '<td>' . htmlspecialchars($row['email']) . '</td>'
            . '<td>' . htmlspecialchars($row['phone']) . '</td>'
            . '<td>' . htmlspecialchars($row['age']) . '</td>'
            . '<td>' . htmlspecialchars($row['address']) . '</td>'
            . '</tr>';
    }

    if (empty($records)) {
        $rowsHtml = '<tr><td colspan="6" style="text-align:center;">No records found.</td></tr>';
    }

    $html = '
    <html>
    <head>
        <style>
            body { font-family: DejaVu Sans, sans-serif; color: #222; }
            h1 { font-size: 20px; margin-bottom: 0; color: #2c3e50; }
            h2 { font-size: 14px; margin-top: 4px; color: #555; font-weight: normal; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { border: 1px solid #ccc; padding: 6px 8px; font-size: 11px; text-align: left; }
            th { background-color: #2c3e50; color: #fff; }
            tr:nth-child(even) { background-color: #f7f7f7; }
        </style>
    </head>
    <body>
        <h1>Welcome in Our Database</h1>
        <h2>Database Records Report</h2>
        <table>
            <thead>
                <tr>
                    <th>Serial No.</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Age</th>
                    <th>Address</th>
                </tr>
            </thead>
            <tbody>' . $rowsHtml . '</tbody>
        </table>
    </body>
    </html>';

    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($html);
    $dompdf->render();

    // Multi-page happens automatically for long tables in Dompdf
    $dompdf->stream('database_records.pdf', ['Attachment' => true]);
    exit;
}