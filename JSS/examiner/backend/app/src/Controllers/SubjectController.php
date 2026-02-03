<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class SubjectController
{
    private Medoo $db;

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    // GET /subjects - get all subjects
    public function getSubjects(): void
    {
        error_log('[SubjectController::getSubjects] START - ' . date('Y-m-d H:i:s'));
        error_log('[SubjectController::getSubjects] Session status: ' . session_status());
        
        $this->startSession();
        
        error_log('[SubjectController::getSubjects] Session data: ' . json_encode($_SESSION));
        
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            error_log('[SubjectController::getSubjects] UNAUTHORIZED - loggedin not set or false');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            $examinerId = $_SESSION['id'] ?? null;
            error_log('[SubjectController::getSubjects] Examiner ID: ' . ($examinerId ?? 'NULL'));
            
            if (!$examinerId) {
                error_log('[SubjectController::getSubjects] ERROR - No examiner ID in session');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Examiner ID not found']);
                return;
            }

            // Get subjects assigned to this examiner
            error_log('[SubjectController::getSubjects] Querying subjects for examiner: ' . $examinerId);
            $subjects = $this->db->select('examiner_subjects', 
                ['[>]subjects' => ['subject_id' => 'subject_id']], 
                ['subjects.subject_id', 'subjects.name'],
                ['examiner_subjects.examiner_id' => $examinerId]
            );
            
            error_log('[SubjectController::getSubjects] Found ' . count($subjects) . ' subjects');
            error_log('[SubjectController::getSubjects] Response: ' . json_encode(['success' => true, 'subjects' => $subjects ?: []]));

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'subjects' => $subjects ?: []]);
        } catch (\Exception $e) {
            error_log('[SubjectController::getSubjects] EXCEPTION: ' . $e->getMessage());
            error_log('[SubjectController::getSubjects] Stack: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // GET /subjects/{subject_id}/students - get students for a specific subject and class
    public function getSubjectStudents(): void
    {
        error_log('[SubjectController::getSubjectStudents] START - ' . date('Y-m-d H:i:s'));
        
        $this->startSession();
        error_log('[SubjectController::getSubjectStudents] Session data: ' . json_encode($_SESSION));
        
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            error_log('[SubjectController::getSubjectStudents] UNAUTHORIZED');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            $examinerId = $_SESSION['id'] ?? null;
            $examId = $_SESSION['exam_id'] ?? null;
            
            error_log('[SubjectController::getSubjectStudents] Examiner ID: ' . ($examinerId ?? 'NULL'));
            error_log('[SubjectController::getSubjectStudents] Exam ID: ' . ($examId ?? 'NULL'));

            if (!$examinerId) {
                error_log('[SubjectController::getSubjectStudents] ERROR - No examiner ID');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Examiner ID not found']);
                return;
            }

            if (!$examId) {
                error_log('[SubjectController::getSubjectStudents] ERROR - No exam ID selected');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No exam selected']);
                return;
            }

            // Parse request to get subject_id and class_id from query parameters
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $subjectId = $input['subject_id'] ?? $_GET['subject_id'] ?? null;
            $classId = $input['class_id'] ?? $_GET['class_id'] ?? null;
            
            error_log('[SubjectController::getSubjectStudents] Subject ID: ' . ($subjectId ?? 'NULL') . ', Class ID: ' . ($classId ?? 'NULL'));

            if (!$subjectId || !$classId) {
                error_log('[SubjectController::getSubjectStudents] ERROR - Missing subject or class ID');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Subject ID and Class ID required']);
                return;
            }

            // Verify examiner has access to this subject
            $hasAccess = $this->db->count('examiner_subjects', 
                [
                    'AND' => [
                        'examiner_id' => $examinerId,
                        'subject_id' => $subjectId
                    ]
                ]
            );

            error_log('[SubjectController::getSubjectStudents] Access check: ' . ($hasAccess ? 'GRANTED' : 'DENIED'));
            
            if (!$hasAccess) {
                error_log('[SubjectController::getSubjectStudents] FORBIDDEN - Examiner ' . $examinerId . ' no access to subject ' . $subjectId);
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied to this subject']);
                return;
            }

            // Get students in the class
            error_log('[SubjectController::getSubjectStudents] Querying students for class: ' . $classId);
            
            // First get the class name from class_id
            $classInfo = $this->db->get('classes', 'class_name', ['class_id' => $classId]);
            error_log('[SubjectController::getSubjectStudents] Class info: ' . json_encode($classInfo));
            
            if (!$classInfo) {
                error_log('[SubjectController::getSubjectStudents] ERROR - Class not found');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Class not found']);
                return;
            }
            
            // Query students by class name
            $students = $this->db->select('students', '*', 
                [
                    'class' => $classInfo,
                    'ORDER' => ['name' => 'ASC']
                ]
            );
            
            error_log('[SubjectController::getSubjectStudents] Found ' . count($students) . ' students');

            // Get subject name
            $subject = $this->db->get('subjects', '*', ['subject_id' => $subjectId]);
            error_log('[SubjectController::getSubjectStudents] Subject: ' . json_encode($subject));

            // Get exam results for these students in this subject
            $subjectColumn = $subject['name'] ?? null;
            
            if ($subjectColumn) {
                // Get exam results
                error_log('[SubjectController::getSubjectStudents] Querying exam results for exam: ' . $examId);
                $results = $this->db->select('exam_results', '*', 
                    ['exam_id' => $examId]
                );
                
                error_log('[SubjectController::getSubjectStudents] Found ' . count($results) . ' results');

                // Merge results with students
                $resultsMap = [];
                foreach ($results as $result) {
                    $resultsMap[$result['student_id']] = $result[$subjectColumn] ?? null;
                }

                // Add marks to student records
                foreach ($students as &$student) {
                    $student['marks'] = $resultsMap[$student['student_id']] ?? null;
                }
            }

            error_log('[SubjectController::getSubjectStudents] SUCCESS - Returning ' . count($students) . ' students');
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'subject_id' => $subjectId,
                'subject_name' => $subject['name'] ?? 'Unknown',
                'exam_id' => $examId,
                'class_id' => $classId,
                'students' => $students ?: []
            ]);
        } catch (\Exception $e) {
            error_log('[SubjectController::getSubjectStudents] EXCEPTION: ' . $e->getMessage());
            error_log('[SubjectController::getSubjectStudents] Stack: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // POST /subjects/students/marks - update marks for a student in a subject
    public function updateMarks(): void
    {
        error_log('[SubjectController::updateMarks] START - ' . date('Y-m-d H:i:s'));
        
        $this->startSession();
        error_log('[SubjectController::updateMarks] Session data: ' . json_encode($_SESSION));
        
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            error_log('[SubjectController::updateMarks] UNAUTHORIZED');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            $examinerId = $_SESSION['id'] ?? null;
            $examId = $_SESSION['exam_id'] ?? null;
            
            error_log('[SubjectController::updateMarks] Examiner ID: ' . ($examinerId ?? 'NULL') . ', Exam ID: ' . ($examId ?? 'NULL'));

            if (!$examinerId || !$examId) {
                error_log('[SubjectController::updateMarks] ERROR - Missing examiner or exam ID');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required session data']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $studentId = $input['student_id'] ?? null;
            $subjectId = $input['subject_id'] ?? null;
            $marks = $input['marks'] ?? null;
            
            error_log('[SubjectController::updateMarks] Student ID: ' . ($studentId ?? 'NULL') . ', Subject ID: ' . ($subjectId ?? 'NULL') . ', Marks: ' . ($marks ?? 'NULL'));

            if (!$studentId || !$subjectId || $marks === null) {
                error_log('[SubjectController::updateMarks] ERROR - Missing required fields');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                return;
            }

            // Verify examiner has access to this subject
            $hasAccess = $this->db->count('examiner_subjects', 
                [
                    'AND' => [
                        'examiner_id' => $examinerId,
                        'subject_id' => $subjectId
                    ]
                ]
            );

            error_log('[SubjectController::updateMarks] Access check: ' . ($hasAccess ? 'GRANTED' : 'DENIED'));
            
            if (!$hasAccess) {
                error_log('[SubjectController::updateMarks] FORBIDDEN - No access to subject');
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }

            // Get subject name to determine which column to update
            $subject = $this->db->get('subjects', '*', ['subject_id' => $subjectId]);
            $subjectColumn = $subject['name'] ?? null;
            
            error_log('[SubjectController::updateMarks] Subject column: ' . ($subjectColumn ?? 'NULL'));

            if (!$subjectColumn) {
                error_log('[SubjectController::updateMarks] ERROR - Invalid subject');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid subject']);
                return;
            }

            // Check if exam result exists for this student
            $existingResult = $this->db->get('exam_results', '*', 
                [
                    'AND' => [
                        'student_id' => $studentId,
                        'exam_id' => $examId
                    ]
                ]
            );

            error_log('[SubjectController::updateMarks] Existing record: ' . ($existingResult ? 'YES' : 'NO'));

            if ($existingResult) {
                // Update existing record
                error_log('[SubjectController::updateMarks] Updating record for student ' . $studentId);
                $this->db->update('exam_results', 
                    [$subjectColumn => $marks],
                    [
                        'AND' => [
                            'student_id' => $studentId,
                            'exam_id' => $examId
                        ]
                    ]
                );
            } else {
                // Create new record
                error_log('[SubjectController::updateMarks] Creating new record for student ' . $studentId);
                $this->db->insert('exam_results', [
                    'student_id' => $studentId,
                    'exam_id' => $examId,
                    $subjectColumn => $marks
                ]);
            }

            error_log('[SubjectController::updateMarks] SUCCESS - Marks updated');
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Marks updated successfully']);
        } catch (\Exception $e) {
            error_log('[SubjectController::updateMarks] EXCEPTION: ' . $e->getMessage());
            error_log('[SubjectController::updateMarks] Stack: ' . $e->getTraceAsString());
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