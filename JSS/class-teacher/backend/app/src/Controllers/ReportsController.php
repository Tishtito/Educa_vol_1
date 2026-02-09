<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class ReportsController
{
    private Medoo $db;

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    // GET /reports/class-history - get exam results for all students (current and past) who were in the class
    public function getClassResultsHistory(): void
    {
        // error_log('[ReportsController::getClassResultsHistory] START - ' . date('Y-m-d H:i:s'));
        
        $this->startSession();
        // error_log('[ReportsController::getClassResultsHistory] Session data: ' . json_encode($_SESSION));
        
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            error_log('[ReportsController::getClassResultsHistory] UNAUTHORIZED');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            $examinerId = $_SESSION['id'] ?? null;
            $classAssigned = $_SESSION['class_assigned'] ?? null;
            
            // error_log('[ReportsController::getClassResultsHistory] Examiner ID: ' . ($examinerId ?? 'NULL'));
            // error_log('[ReportsController::getClassResultsHistory] Class Assigned: ' . ($classAssigned ?? 'NULL'));

            if (!$examinerId) {
                error_log('[ReportsController::getClassResultsHistory] ERROR - No examiner ID');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Examiner ID not found']);
                return;
            }

            if (!$classAssigned) {
                error_log('[ReportsController::getClassResultsHistory] ERROR - No class assigned');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No class assigned to this examiner']);
                return;
            }

            // Parse request to get optional filters
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $academicYear = $input['academic_year'] ?? $_GET['academic_year'] ?? null;
            $examId = $input['exam_id'] ?? $_GET['exam_id'] ?? null;

            // Build base query conditions
            $conditions = [
                'AND' => [
                    'student_classes.class' => $classAssigned
                ]
            ];

            // Add optional filters
            if ($academicYear) {
                $conditions['AND']['exams.academic_year'] = $academicYear;
            }

            if ($examId) {
                $conditions['AND']['exam_results.exam_id'] = $examId;
            }

            // error_log('[ReportsController::getClassResultsHistory] Query conditions: ' . json_encode($conditions));

            // Get exam results for all students who were in this class (including past students)
            $results = $this->db->select(
                'exam_results',
                [
                    '[>]exams' => ['exam_id' => 'exam_id'],
                    '[>]student_classes' => ['student_class_id' => 'student_class_id'],
                    '[>]students' => ['student_id' => 'student_id']
                ],
                [
                    'exam_results.result_id',
                    'students.student_id',
                    'students.name',
                    'students.status',
                    'student_classes.class',
                    'student_classes.academic_year',
                    'exams.exam_id',
                    'exams.exam_name',
                    'exams.term',
                    'exams.academic_year (exam_year)',
                    'exam_results.Math',
                    'exam_results.English',
                    'exam_results.Kiswahili',
                    'exam_results.Technical',
                    'exam_results.Agriculture',
                    'exam_results.Creative',
                    'exam_results.Religious',
                    'exam_results.SST',
                    'exam_results.Science',
                    'exam_results.total_marks',
                    'exam_results.position',
                    'exam_results.stream_position'
                ],
                $conditions,
                [
                    'ORDER' => [
                        'student_classes.academic_year' => 'DESC',
                        'exam_results.exam_id' => 'DESC',
                        'students.name' => 'ASC'
                    ]
                ]
            );

            // error_log('[ReportsController::getClassResultsHistory] Found ' . count($results) . ' results');

            // Group results by academic year and exam for better organization
            $groupedResults = [];
            if (!empty($results)) {
                foreach ($results as $result) {
                    $year = $result['academic_year'];
                    $examName = $result['exam_name'];
                    
                    if (!isset($groupedResults[$year])) {
                        $groupedResults[$year] = [];
                    }
                    if (!isset($groupedResults[$year][$examName])) {
                        $groupedResults[$year][$examName] = [];
                    }
                    
                    $groupedResults[$year][$examName][] = $result;
                }
            }

            // error_log('[ReportsController::getClassResultsHistory] SUCCESS - Returning results');
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'class_assigned' => $classAssigned,
                'academic_year_filter' => $academicYear,
                'exam_id_filter' => $examId,
                'total_records' => count($results),
                'results' => $results ?: [],
                'grouped_results' => $groupedResults ?: []
            ]);
        } catch (\Exception $e) {
            error_log('[ReportsController::getClassResultsHistory] EXCEPTION: ' . $e->getMessage());
            error_log('[ReportsController::getClassResultsHistory] Stack: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // GET /reports/student-progress - get all exam results for a specific student across all exams
    public function getStudentProgress(): void
    {
        // error_log('[ReportsController::getStudentProgress] START - ' . date('Y-m-d H:i:s'));
        
        $this->startSession();
        
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            error_log('[ReportsController::getStudentProgress] UNAUTHORIZED');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            $examinerId = $_SESSION['id'] ?? null;
            
            if (!$examinerId) {
                error_log('[ReportsController::getStudentProgress] ERROR - No examiner ID');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Examiner ID not found']);
                return;
            }

            // Parse request to get student_id
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $studentId = $input['student_id'] ?? $_GET['student_id'] ?? null;

            if (!$studentId) {
                error_log('[ReportsController::getStudentProgress] ERROR - No student ID provided');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Student ID required']);
                return;
            }

            // error_log('[ReportsController::getStudentProgress] Student ID: ' . $studentId);

            // Get all exam results for this student
            $results = $this->db->select(
                'exam_results',
                [
                    '[>]exams' => ['exam_id' => 'exam_id'],
                    '[>]student_classes' => ['student_class_id' => 'student_class_id'],
                    '[>]students' => ['student_id' => 'student_id']
                ],
                [
                    'exam_results.result_id',
                    'students.student_id',
                    'students.name',
                    'student_classes.class',
                    'student_classes.academic_year',
                    'exams.exam_id',
                    'exams.exam_name',
                    'exams.term',
                    'exams.academic_year (exam_year)',
                    'exam_results.Math',
                    'exam_results.English',
                    'exam_results.Kiswahili',
                    'exam_results.Technical',
                    'exam_results.Agriculture',
                    'exam_results.Creative',
                    'exam_results.Religious',
                    'exam_results.SST',
                    'exam_results.Science',
                    'exam_results.total_marks',
                    'exam_results.position'
                ],
                [
                    'exam_results.student_id' => $studentId
                ],
                [
                    'ORDER' => [
                        'student_classes.academic_year' => 'DESC',
                        'exams.exam_id' => 'DESC'
                    ]
                ]
            );

            // error_log('[ReportsController::getStudentProgress] Found ' . count($results) . ' exam results');

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'student_id' => $studentId,
                'total_exams' => count($results),
                'results' => $results ?: []
            ]);
        } catch (\Exception $e) {
            error_log('[ReportsController::getStudentProgress] EXCEPTION: ' . $e->getMessage());
            error_log('[ReportsController::getStudentProgress] Stack: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // GET /reports/class-summary - get summary statistics for the class across all exams
    public function getClassSummary(): void
    {
        // error_log('[ReportsController::getClassSummary] START - ' . date('Y-m-d H:i:s'));
        
        $this->startSession();
        
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            error_log('[ReportsController::getClassSummary] UNAUTHORIZED');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            $examinerId = $_SESSION['id'] ?? null;
            $classAssigned = $_SESSION['class_assigned'] ?? null;
            
            if (!$examinerId || !$classAssigned) {
                error_log('[ReportsController::getClassSummary] ERROR - Missing examiner or class info');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Examiner or class information not found']);
                return;
            }

            // Parse request to get academic year filter
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $academicYear = $input['academic_year'] ?? $_GET['academic_year'] ?? null;

            $conditions = [
                'student_classes.class' => $classAssigned
            ];

            if ($academicYear) {
                $conditions['student_classes.academic_year'] = $academicYear;
            }

            // Get summary statistics
            $summary = $this->db->select(
                'exam_results',
                [
                    '[>]student_classes' => ['student_class_id' => 'student_class_id'],
                    '[>]exams' => ['exam_id' => 'exam_id']
                ],
                [
                    'exams.exam_name',
                    'student_classes.academic_year',
                    '[>]Math' => ['avg' => 'AVG(exam_results.Math)'],
                    '[>]English' => ['avg' => 'AVG(exam_results.English)'],
                    '[>]Kiswahili' => ['avg' => 'AVG(exam_results.Kiswahili)'],
                    '[>]Technical' => ['avg' => 'AVG(exam_results.Technical)'],
                    '[>]Agriculture' => ['avg' => 'AVG(exam_results.Agriculture)'],
                    '[>]Creative' => ['avg' => 'AVG(exam_results.Creative)'],
                    '[>]Religious' => ['avg' => 'AVG(exam_results.Religious)'],
                    '[>]SST' => ['avg' => 'AVG(exam_results.SST)'],
                    '[>]Science' => ['avg' => 'AVG(exam_results.Science)'],
                    'total_students' => ['count' => 'COUNT(exam_results.result_id)']
                ],
                $conditions,
                [
                    'GROUP' => [
                        'exams.exam_name',
                        'student_classes.academic_year'
                    ],
                    'ORDER' => [
                        'student_classes.academic_year' => 'DESC',
                        'exams.exam_name' => 'ASC'
                    ]
                ]
            );

            // error_log('[ReportsController::getClassSummary] Found ' . count($summary) . ' summary records');

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'class_assigned' => $classAssigned,
                'academic_year_filter' => $academicYear,
                'summary' => $summary ?: []
            ]);
        } catch (\Exception $e) {
            error_log('[ReportsController::getClassSummary] EXCEPTION: ' . $e->getMessage());
            error_log('[ReportsController::getClassSummary] Stack: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
