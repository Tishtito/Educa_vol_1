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

    private function log(string $message): void
    {
        $logDir = __DIR__ . '/../../../logs';
        $logFile = $logDir . '/php_errors.log';
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $entry = sprintf("[%s] %s\n", date('c'), $message);
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }

    // GET /subjects - get all subjects
    public function getSubjects(): void
    {
        // error_log('[SubjectController::getSubjects] START - ' . date('Y-m-d H:i:s'));
        // error_log('[SubjectController::getSubjects] Session status: ' . session_status());
        
        $this->startSession();
        
        // error_log('[SubjectController::getSubjects] Session data: ' . json_encode($_SESSION));
        
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            $this->log('[SubjectController::getSubjects] UNAUTHORIZED - loggedin not set or false');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            $examinerId = $_SESSION['id'] ?? null;
            // error_log('[SubjectController::getSubjects] Examiner ID: ' . ($examinerId ?? 'NULL'));
            
            if (!$examinerId) {
                // error_log('[SubjectController::getSubjects] ERROR - No examiner ID in session');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Examiner ID not found']);
                return;
            }

            // Get subjects assigned to this examiner
            // error_log('[SubjectController::getSubjects] Querying subjects for examiner: ' . $examinerId);
            $subjects = $this->db->select('examiner_subject_classes', 
                ['[>]subjects' => ['subject_id' => 'subject_id']], 
                ['subjects.subject_id', 'subjects.name'],
                ['examiner_subject_classes.examiner_id' => $examinerId]
            );
            
            // error_log('[SubjectController::getSubjects] Found ' . count($subjects) . ' subjects');
            // error_log('[SubjectController::getSubjects] Response: ' . json_encode(['success' => true, 'subjects' => $subjects ?: []]));

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'subjects' => $subjects ?: []]);
        } catch (\Exception $e) {
            $this->log('[SubjectController::getSubjects] EXCEPTION: ' . $e->getMessage());
            $this->log('[SubjectController::getSubjects] Stack: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // GET /subjects/{subject_id}/students - get students for a specific subject and class
    public function getSubjectStudents(): void
    {
        // error_log('[SubjectController::getSubjectStudents] START - ' . date('Y-m-d H:i:s'));
        
        $this->startSession();
        // error_log('[SubjectController::getSubjectStudents] Session data: ' . json_encode($_SESSION));
        
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            $this->log('[SubjectController::getSubjectStudents] UNAUTHORIZED');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            $examinerId = $_SESSION['id'] ?? null;
            $examId = $_SESSION['exam_id'] ?? null;
            
            // error_log('[SubjectController::getSubjectStudents] Examiner ID: ' . ($examinerId ?? 'NULL'));
            // error_log('[SubjectController::getSubjectStudents] Exam ID: ' . ($examId ?? 'NULL'));

            if (!$examinerId) {
                $this->log('[SubjectController::getSubjectStudents] ERROR - No examiner ID');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Examiner ID not found']);
                return;
            }

            if (!$examId) {
                $this->log('[SubjectController::getSubjectStudents] ERROR - No exam ID selected');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No exam selected']);
                return;
            }

            // Parse request to get subject_id and class_id from query parameters
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $subjectId = $input['subject_id'] ?? $_GET['subject_id'] ?? null;
            $classId = $input['class_id'] ?? $_GET['class_id'] ?? null;
            
            // error_log('[SubjectController::getSubjectStudents] Subject ID: ' . ($subjectId ?? 'NULL') . ', Class ID: ' . ($classId ?? 'NULL'));

            if (!$subjectId || !$classId) {
                $this->log('[SubjectController::getSubjectStudents] ERROR - Missing subject or class ID');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Subject ID and Class ID required']);
                return;
            }

            // Verify examiner has access to this subject
            $hasAccess = $this->db->count('examiner_subject_classes', 
                [
                    'AND' => [
                        'examiner_id' => $examinerId,
                        'subject_id' => $subjectId
                    ]
                ]
            );

            // error_log('[SubjectController::getSubjectStudents] Access check: ' . ($hasAccess ? 'GRANTED' : 'DENIED'));
            
            if (!$hasAccess) {
                $this->log('[SubjectController::getSubjectStudents] FORBIDDEN - Examiner ' . $examinerId . ' no access to subject ' . $subjectId);
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied to this subject']);
                return;
            }

            // Get students in the class
            // error_log('[SubjectController::getSubjectStudents] Querying students for class: ' . $classId);
            
            // First get the class name from class_id
            $classInfo = $this->db->get('classes', 'class_name', ['class_id' => $classId]);
            // error_log('[SubjectController::getSubjectStudents] Class info: ' . json_encode($classInfo));
            
            if (!$classInfo) {
                $this->log('[SubjectController::getSubjectStudents] ERROR - Class not found');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Class not found']);
                return;
            }
            
            // Query students directly from students table by class name and active status
            $studentList = $this->db->select('students', ['student_id', 'name'], 
                [
                    'class' => $classInfo,
                    'status' => 'Active',
                    'ORDER' => ['name' => 'ASC']
                ]
            );
            
            // error_log('[SubjectController::getSubjectStudents] Found ' . count($studentList) . ' students');

            // Get student_class_id for each student and build the students array
            $students = [];
            if (!empty($studentList)) {
                foreach ($studentList as $student) {
                    $studentClass = $this->db->get('student_classes', ['student_class_id'], 
                        ['student_id' => intval($student['student_id'])]
                    );
                    
                    if ($studentClass) {
                        $students[] = [
                            'student_class_id' => $studentClass['student_class_id'],
                            'student_id' => $student['student_id'],
                            'name' => $student['name']
                        ];
                    }
                }
            }

            // Get subject name
            $subject = $this->db->get('subjects', '*', ['subject_id' => $subjectId]);
            // error_log('[SubjectController::getSubjectStudents] Subject: ' . json_encode($subject));

            // Get exam results for these students in this subject
            $subjectColumn = $subject['name'] ?? null;
            
            if ($subjectColumn) {
                // Get exam results
                // error_log('[SubjectController::getSubjectStudents] Querying exam results for exam: ' . $examId);
                $results = $this->db->select('exam_results', '*', 
                    ['exam_id' => $examId]
                );
                
                // error_log('[SubjectController::getSubjectStudents] Found ' . count($results) . ' results');

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

            // error_log('[SubjectController::getSubjectStudents] SUCCESS - Returning ' . count($students) . ' students');
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
            $this->log('[SubjectController::getSubjectStudents] EXCEPTION: ' . $e->getMessage());
            $this->log('[SubjectController::getSubjectStudents] Stack: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // POST /subjects/students/marks - update marks for a student in a subject
    public function updateMarks(): void
    {
        $this->startSession();
        
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            $this->log('[SubjectController::updateMarks] UNAUTHORIZED');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            $examinerId = $_SESSION['id'] ?? null;
            $examId = $_SESSION['exam_id'] ?? null;

            if (!$examinerId || !$examId) {
                $this->log('[SubjectController::updateMarks] ERROR - Missing examiner or exam ID');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required session data']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            $studentId = $input['student_id'] ?? null;
            $studentClassId = $input['student_class_id'] ?? null;
            $marks = $input['marks'] ?? null;
            $marksOutOf = $input['marks_out_of'] ?? null;
            $paperType = $input['paper_type'] ?? null;
            
            // Get subject from input (for component subjects) or subject_id
            $subject = $input['subject'] ?? null;
            $subjectId = $input['subject_id'] ?? null;

            if (!$studentId || !$studentClassId || $marks === null) {
                $this->log('[SubjectController::updateMarks] ERROR - Missing required fields');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required fields']);
                return;
            }

            // Handle component subjects (Paper1, Paper2)
            if ($subject && $paperType) {
                // Paper1 and Paper2 are stored as raw marks (not percentages)
                if ($subject === 'English') {
                    $columnName = $paperType === 'Paper1' ? 'Paper1English' : ($paperType === 'Paper2' ? 'Paper2English' : 'English');
                } elseif ($subject === 'Kiswahili') {
                    $columnName = $paperType === 'Paper1' ? 'Paper1Kiswahili' : ($paperType === 'Paper2' ? 'Paper2Kiswahili' : 'Kiswahili');
                } else {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid subject']);
                    return;
                }
                
                $marksToStore = $marks; // Store raw marks for Paper1 and Paper2
            } else {
                // Regular subject - convert to percentage
                if (!$subjectId) {
                    $this->log('[SubjectController::updateMarks] ERROR - Missing subject ID for regular subject');
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Subject ID required']);
                    return;
                }

                // Verify examiner has access to this subject
                $hasAccess = $this->db->count('examiner_subject_classes', 
                    [
                        'AND' => [
                            'examiner_id' => $examinerId,
                            'subject_id' => $subjectId
                        ]
                    ]
                );
                
                if (!$hasAccess) {
                    $this->log('[SubjectController::updateMarks] FORBIDDEN - No access to subject');
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Access denied']);
                    return;
                }

                $subjectObj = $this->db->get('subjects', '*', ['subject_id' => $subjectId]);
                $columnName = $subjectObj['name'] ?? null;

                if (!$columnName) {
                    $this->log('[SubjectController::updateMarks] ERROR - Invalid subject');
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Invalid subject']);
                    return;
                }
                
                // Convert marks to percentage for regular subjects (except English and Kiswahili which are already percentages)
                if ($columnName === 'English' || $columnName === 'Kiswahili') {
                    // These are percentage totals from Paper1+Paper2
                    $marksToStore = $marks;
                } else {
                    // Convert to percentage
                    if ($marksOutOf && $marksOutOf > 0) {
                        $percentageMarks = ($marks / $marksOutOf) * 100;
                        $marksToStore = $percentageMarks;
                    } else {
                        $marksToStore = $marks;
                    }
                }
            }

            // Check if exam result exists for this student
            $existingResult = $this->db->get('exam_results', '*', 
                [
                    'AND' => [
                        'student_id' => $studentId,
                        'exam_id' => $examId,
                        'student_class_id' => $studentClassId
                    ]
                ]
            );

            if ($existingResult) {
                // Update existing record
                $this->db->update('exam_results', 
                    [$columnName => $marksToStore],
                    [
                        'AND' => [
                            'student_id' => $studentId,
                            'exam_id' => $examId,
                            'student_class_id' => $studentClassId
                        ]
                    ]
                );
            } else {
                // Create new record
                $this->db->insert('exam_results', [
                    'student_id' => $studentId,
                    'exam_id' => $examId,
                    'student_class_id' => $studentClassId,
                    $columnName => $marksToStore
                ]);
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Marks updated successfully']);
        } catch (\Exception $e) {
            $this->log('[SubjectController::updateMarks] EXCEPTION: ' . $e->getMessage());
            $this->log('[SubjectController::updateMarks] Stack: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // GET /subjects/marks-out-of - get marks out of for component subjects
    public function getMarksOutOf(): void
    {
        $this->startSession();
        
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            $subject = $_GET['subject'] ?? null;
            
            if (!$subject) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Subject parameter required']);
                return;
            }

            if ($subject === 'English') {
                // Return Paper1 and Paper2 max marks for English
                $marksOutOf = [
                    'Paper1' => 50,
                    'Paper2' => 50
                ];
            } elseif ($subject === 'Kiswahili') {
                // Return Paper1 and Paper2 max marks for Kiswahili
                $marksOutOf = [
                    'Paper1' => 50,
                    'Paper2' => 50
                ];
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid subject for marks out of']);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'marks_out_of' => $marksOutOf]);
        } catch (\Exception $e) {
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