<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class PointsTableController
{
    private Medoo $db;

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    /**
     * Get points table for the current exam and class
     * Returns students with their grades for all subjects
     */
    public function getPointsTable(): void
    {
        $this->startSession();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Check if user is authenticated
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $exam_id = $_SESSION['exam_id'] ?? null;
        $class_assigned = $_SESSION['class_assigned'] ?? null;

        // Validate parameters
        if (!$exam_id || !$class_assigned) {
            echo json_encode(['success' => false, 'message' => 'Exam or class not selected']);
            return;
        }

        try {
            // Fetch students and their marks for the selected exam
            $sql = "
                SELECT 
                    students.student_id AS student_id,
                    students.name AS Name,
                    COALESCE(exam_results.Math, 0) AS Math,
                    COALESCE(exam_results.`LS/SP`, 0) AS `LS/SP`,
                    COALESCE(exam_results.RDG, 0) AS RDG,
                    COALESCE(exam_results.GRM, 0) AS GRM,
                    COALESCE(exam_results.WRI, 0) AS WRI,
                    COALESCE(exam_results.`KUS/KUZ`, 0) AS `KUS/KUZ`,
                    COALESCE(exam_results.KUS, 0) AS KUS,
                    COALESCE(exam_results.LUG, 0) AS LUG,
                    COALESCE(exam_results.KUA, 0) AS KUA,
                    COALESCE(exam_results.Enviromental, 0) AS Enviromental,
                    COALESCE(exam_results.Creative, 0) AS Creative,
                    COALESCE(exam_results.Religious, 0) AS Religious
                FROM 
                    students
                LEFT JOIN 
                    exam_results 
                ON 
                    students.student_id = exam_results.student_id AND exam_results.exam_id = ?
                WHERE 
                    students.class = ?
                ORDER BY students.name ASC
            ";

            $stmt = $this->db->pdo->prepare($sql);
            $stmt->execute([$exam_id, $class_assigned]);
            $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Calculate grades for each student's marks
            $subjects = ['Math', 'LS/SP', 'RDG', 'GRM', 'WRI', 'KUS/KUZ', 'KUS', 'LUG', 'KUA', 'Enviromental', 'Creative', 'Religious'];
            $gradeCounts = []; // Track count of each grade abbreviation

            foreach ($students as &$student) {
                foreach ($subjects as $subject) {
                    $marks = $student[$subject];
                    $gradeInfo = $this->calculateGrade($marks);
                    $student['Grade_' . $subject] = $gradeInfo['grade'];
                    $student['Ab_' . $subject] = $gradeInfo['ab'];
                    
                    // Track grade abbreviation counts
                    $ab = $gradeInfo['ab'];
                    if ($ab !== '-' && $ab !== 'N/A') {
                        if (!isset($gradeCounts[$ab])) {
                            $gradeCounts[$ab] = 0;
                        }
                        $gradeCounts[$ab]++;
                    }
                }
            }

            // Sort grade counts by abbreviation
            ksort($gradeCounts);

            echo json_encode([
                'success' => true,
                'students' => $students,
                'subjects' => $subjects,
                'gradeCounts' => $gradeCounts
            ]);
        } catch (\Exception $e) {
            error_log('Error in getPointsTable: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching points table']);
        }
    }

    /**
     * Get exam details
     */
    public function getExamDetails(): void
    {
        $this->startSession();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $exam_id = $_SESSION['exam_id'] ?? null;
        $class_assigned = $_SESSION['class_assigned'] ?? null;

        if (!$exam_id || !$class_assigned) {
            echo json_encode(['success' => false, 'message' => 'Exam or class not selected']);
            return;
        }

        try {
            // Fetch exam name
            $sql = "SELECT exam_name FROM exams WHERE exam_id = ?";
            $stmt = $this->db->pdo->prepare($sql);
            $stmt->execute([$exam_id]);
            $exam = $stmt->fetch(\PDO::FETCH_ASSOC);

            $examName = $exam['exam_name'] ?? 'Unknown Exam';

            // Format class title
            $classTitle = ucwords(str_replace('_', ' ', $class_assigned));

            echo json_encode([
                'success' => true,
                'examName' => $examName,
                'classTitle' => $classTitle
            ]);
        } catch (\Exception $e) {
            error_log('Error in getExamDetails: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching exam details']);
        }
    }

    /**
     * Calculate grade based on marks
     * Looks up the grade and abbreviation from point_boundaries table
     */
    private function calculateGrade($marks)
    {
        if ($marks === null || $marks === '' || $marks === 0) {
            return ['grade' => '-', 'ab' => '-']; // No marks provided
        }

        try {
            $sql = "SELECT grade, ab FROM point_boundaries WHERE ? BETWEEN min_marks AND max_marks LIMIT 1";
            $stmt = $this->db->pdo->prepare($sql);
            $stmt->execute([$marks]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($result) {
                return ['grade' => $result['grade'], 'ab' => $result['ab']];
            }
            return ['grade' => 'N/A', 'ab' => 'N/A'];
        } catch (\Exception $e) {
            error_log('Error calculating grade: ' . $e->getMessage());
            return ['grade' => 'N/A', 'ab' => 'N/A'];
        }
    }

    /**
     * Start session if not already started
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
