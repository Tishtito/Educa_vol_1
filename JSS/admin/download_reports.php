<?php
require_once "db/database.php";
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get class and exam from URL
$class = isset($_GET['grade']) ? $_GET['grade'] : '';
$exam_id = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

if (empty($class) || $exam_id <= 0) {
    die("Invalid class or exam selected.");
}

// Fetch students and other data (keep your existing code)

// Configure Dompdf with better settings
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('defaultFont', 'Helvetica');
$options->set('tempDir', sys_get_temp_dir());
$options->set('fontCache', sys_get_temp_dir());
$options->set('logOutputFile', sys_get_temp_dir() . '/dompdf.log');

// Read the CSS file
$css = file_get_contents('css/report.css');

// Start building the combined HTML
$combinedHtml = '<!DOCTYPE html><html><head>
    <style>'.$css.'</style>
    <style>
        .report-page { 
            page-break-after: always; 
            margin-bottom: 2cm;
        }
        .report-page:last-child { page-break-after: avoid; }
        @page { margin: 2cm; }
    </style>
</head><body>';

// Generate each student's report
foreach ($students as $index => $student) {
    ob_start();
    $_GET['student_id'] = $student['student_id'];
    $_GET['exam_id'] = $exam_id;
    
    // Temporarily override the image path to use absolute path
    $originalServer = $_SERVER;
    $_SERVER['DOCUMENT_ROOT'] = __DIR__;
    
    include 'one_exam_report.php';
    $reportHtml = ob_get_clean();
    
    // Restore original server vars
    $_SERVER = $originalServer;
    
    $combinedHtml .= '<div class="report-page">'.$reportHtml.'</div>';
}

$combinedHtml .= '</body></html>';

// Generate the final PDF
$dompdf = new Dompdf($options);
$dompdf->loadHtml($combinedHtml);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Set the PDF filename
$filename = "Class_Reports_Grade_".str_replace(' ', '_', $class)."_Exam_".$exam_id.".pdf";

// Output the PDF
header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Length: " . strlen($dompdf->output()));

echo $dompdf->output();
exit;
?>