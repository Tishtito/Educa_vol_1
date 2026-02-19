<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class MarklistController
{
    private Medoo $db;

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    /**
     * Get mark list for the teacher's class and selected exam
     */
    public function getMarkList(): void
    {
        $this->startSession();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // Check authentication
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $classAssigned = $_SESSION['class_assigned'] ?? null;
        $examId = $_SESSION['exam_id'] ?? null;

        if (!$classAssigned || !$examId) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Missing class or exam']);
            return;
        }

        try {
            // Calculate and store total marks
            $this->calculateTotalMarks($examId);

            // Calculate and store ranks
            $this->calculateRanks($examId);

            // Fetch student marks with performance levels
            $pdo = $this->db->pdo;
            $sql = "
                SELECT 
                    s.student_id, 
                    s.name AS Name, 
                    er.Math, 
                    (SELECT ab FROM point_boundaries WHERE er.Math BETWEEN min_marks AND max_marks LIMIT 1) AS PL_Math,
                    er.`LS/SP`, 
                    (SELECT ab FROM point_boundaries WHERE er.`LS/SP` BETWEEN min_marks AND max_marks LIMIT 1) AS PL_LSSP,
                    er.RDG, 
                    (SELECT ab FROM point_boundaries WHERE er.RDG BETWEEN min_marks AND max_marks LIMIT 1) AS PL_RDG,
                    er.GRM, 
                    (SELECT ab FROM point_boundaries WHERE er.GRM BETWEEN min_marks AND max_marks LIMIT 1) AS PL_GRM,
                    er.WRI, 
                    (SELECT ab FROM point_boundaries WHERE er.WRI BETWEEN min_marks AND max_marks LIMIT 1) AS PL_WRI,
                    er.`KUS/KUZ`, 
                    (SELECT ab FROM point_boundaries WHERE er.`KUS/KUZ` BETWEEN min_marks AND max_marks LIMIT 1) AS PL_KUSKUZ,
                    er.KUS, 
                    (SELECT ab FROM point_boundaries WHERE er.KUS BETWEEN min_marks AND max_marks LIMIT 1) AS PL_KUS,
                    er.LUG, 
                    (SELECT ab FROM point_boundaries WHERE er.LUG BETWEEN min_marks AND max_marks LIMIT 1) AS PL_LUG,
                    er.KUA, 
                    (SELECT ab FROM point_boundaries WHERE er.KUA BETWEEN min_marks AND max_marks LIMIT 1) AS PL_KUA,
                    er.Enviromental, 
                    (SELECT ab FROM point_boundaries WHERE er.Enviromental BETWEEN min_marks AND max_marks LIMIT 1) AS PL_Enviromental,
                    er.Creative, 
                    (SELECT ab FROM point_boundaries WHERE er.Creative BETWEEN min_marks AND max_marks LIMIT 1) AS PL_Creative,
                    er.Religious, 
                    (SELECT ab FROM point_boundaries WHERE er.Religious BETWEEN min_marks AND max_marks LIMIT 1) AS PL_Religious,
                    er.total_marks,
                    er.position
                FROM 
                    students s
                LEFT JOIN 
                    exam_results er ON s.student_id = er.student_id AND er.exam_id = ?
                WHERE 
                    s.class = ?
                ORDER BY 
                    COALESCE(er.total_marks, 0) DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$examId, $classAssigned]);
            $students = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Calculate mean scores
            $subjects = ['Math', 'LS/SP', 'RDG', 'GRM', 'WRI', 'KUS/KUZ', 'KUS', 'LUG', 'KUA', 'Enviromental', 'Creative', 'Religious'];
            $subjectTotals = [];
            $subjectCounts = [];
            $totalScore = 0;
            $totalStudents = 0;

            foreach ($subjects as $subject) {
                $subjectTotals[$subject] = 0;
                $subjectCounts[$subject] = 0;
            }

            foreach ($students as $row) {
                foreach ($subjects as $subject) {
                    if ($row[$subject] !== null && $row[$subject] !== '') {
                        $subjectTotals[$subject] += (int)$row[$subject];
                        $subjectCounts[$subject]++;
                    }
                }
                if ($row['total_marks'] > 0) {
                    $totalScore += (int)$row['total_marks'];
                    $totalStudents++;
                }
            }

            $subjectMeans = [];
            foreach ($subjects as $subject) {
                $count = $subjectCounts[$subject];
                $subjectMeans[$subject] = $count > 0 ? round($subjectTotals[$subject] / $count, 2) : 0;
            }
            $totalMean = ($totalStudents > 0) ? round($totalScore / $totalStudents, 2) : 0;

            // Fetch previous mean scores
            $prevMeans = $this->getPreviousMeans($classAssigned, $examId);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'students' => $students,
                'subjects' => $subjects,
                'subjectMeans' => $subjectMeans,
                'totalMean' => $totalMean,
                'previousMeans' => $prevMeans,
                'totalStudents' => $totalStudents
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error loading mark list: ' . $e->getMessage()
            ]);
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
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $examId = $_SESSION['exam_id'] ?? null;
        $classAssigned = $_SESSION['class_assigned'] ?? null;

        if (!$examId || !$classAssigned) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Missing exam or class']);
            return;
        }

        try {
            $exam = $this->db->select('exams', '*', ['exam_id' => $examId]);
            $examName = $exam[0]['exam_name'] ?? 'Unknown Exam';

            // Format class title
            $classTitle = ucwords(str_replace('_', ' ', $classAssigned));

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'examName' => $examName,
                'classTitle' => $classTitle
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching exam details: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate total marks for students
     */
    private function calculateTotalMarks(int $examId): void
    {
        $pdo = $this->db->pdo;
        
        $sql = "
            UPDATE exam_results
            SET total_marks = (
                COALESCE(Math, 0) + COALESCE(`LS/SP`, 0) + COALESCE(RDG, 0) + COALESCE(GRM, 0) + COALESCE(WRI, 0) + COALESCE(`KUS/KUZ`, 0) + COALESCE(KUS, 0) + COALESCE(LUG, 0) + COALESCE(KUA, 0) +
                COALESCE(Enviromental, 0) + COALESCE(Creative, 0) + COALESCE(Religious, 0)
            )
            WHERE exam_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$examId]);
    }

    /**
     * Calculate ranks for students
     */
    private function calculateRanks(int $examId): void
    {
        $pdo = $this->db->pdo;

        // Reset rank counter
        $pdo->exec("SET @rank = 0");

        // Update ranks
        $sql = "
            UPDATE exam_results
            SET position = (
                SELECT @rank := @rank + 1
                FROM (
                    SELECT student_id FROM exam_results 
                    WHERE exam_id = ? 
                    ORDER BY total_marks DESC
                ) AS ranked
                WHERE ranked.student_id = exam_results.student_id
            )
            WHERE exam_id = ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$examId, $examId]);
    }

    /**
     * Get previous mean scores for comparison
     */
    private function getPreviousMeans(string $classAssigned, int $examId): array
    {
        try {
            $result = $this->db->select('exam_mean_scores', '*', [
                'class' => $classAssigned,
                'exam_id[<]' => $examId,
                'ORDER' => ['exam_id' => 'DESC'],
                'LIMIT' => 1
            ]);

            if ($result && count($result) > 0) {
                return $result[0];
            }
        } catch (\Exception $e) {
            // If table doesn't exist or other error, return empty
        }

        return [];
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
