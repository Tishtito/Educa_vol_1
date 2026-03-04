<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class DashboardController
{
    private Medoo $db;

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    private function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
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

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function test(): void
    {
        $this->log('[DASHBOARD-TEST] Test logging endpoint called at ' . date('c'));
        $this->jsonResponse([
            'success' => true,
            'message' => 'Test endpoint working - check logs'
        ]);
    }

    public function getDashboard(): void
    {
        $this->startSession();
        
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            $this->log('[DASHBOARD] Unauthorized: loggedin flag not set or false');
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
            return;
        }

        try {
            $examinerId = $_SESSION['id'] ?? null;
            
            if (!$examinerId) {
                $this->log('[DASHBOARD] Error: Examiner ID not found in session');
                $this->jsonResponse(['success' => false, 'message' => 'Examiner ID not found in session'], 400);
                return;
            }

            // $this->log('[DASHBOARD] Fetching assignments for examiner_id=' . $examinerId);

            // Fetch assignments (subject_id and class_id pairs) 
            $assignmentRows = $this->db->select(
                'examiner_subject_classes',
                ['subject_id', 'class_id'],
                ['examiner_id' => $examinerId]
            ) ?? [];

            // $this->log('[DASHBOARD] Query executed. Found ' . count($assignmentRows) . ' assignment rows');

            if (empty($assignmentRows)) {
                // $this->log('[DASHBOARD] Warning: No assignments found for examiner_id=' . $examinerId);
                $this->jsonResponse(['success' => false, 'message' => 'No classes assigned. Visit Admin for assistance.'], 403);
                return;
            }

            // Enrich assignments with subject and class names
            $assignments = [];
            foreach ($assignmentRows as $row) {
                $subjectId = (int)($row['subject_id'] ?? 0);
                $classId = (int)($row['class_id'] ?? 0);

                if ($subjectId > 0) {
                    $subject = $this->db->get('subjects', 'name', ['subject_id' => $subjectId]);
                    $subjectName = $subject ?? 'Unknown Subject';
                } else {
                    $subjectName = 'Unknown Subject';
                }

                if ($classId > 0) {
                    $class = $this->db->get('classes', 'class_name', ['class_id' => $classId]);
                    $className = $class ?? 'Unknown Class';
                } else {
                    $className = 'Unknown Class';
                }

                $assignments[] = [
                    'subject_id' => $subjectId,
                    'class_id' => $classId,
                    'subject_name' => $subjectName,
                    'class_name' => $className,
                ];
            }

            // $this->log('[DASHBOARD] Enriched ' . count($assignments) . ' assignments with subject and class names');

            // Extract unique subjects and classes
            $subjectsMap = [];
            $classesMap = [];
            
            foreach ($assignments as $assignment) {
                $subjectId = (int)$assignment['subject_id'];
                $classId = (int)$assignment['class_id'];
                
                if (!isset($subjectsMap[$subjectId])) {
                    $subjectsMap[$subjectId] = [
                        'subject_id' => $subjectId,
                        'subject_name' => $assignment['subject_name']
                    ];
                }
                
                if (!isset($classesMap[$classId])) {
                    $classesMap[$classId] = [
                        'class_id' => $classId,
                        'class_name' => $assignment['class_name']
                    ];
                }
            }

            $subjects = array_values($subjectsMap);
            $classes = array_values($classesMap);

            // $this->log('[DASHBOARD] Successfully loaded ' . count($subjects) . ' subjects and ' . count($classes) . ' classes');

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'subjects' => $subjects,
                    'classes' => $classes,
                    'assignments' => $assignments
                ]
            ]);

        } catch (\Throwable $e) {
            $this->log('[DASHBOARD] Exception: ' . $e->getMessage());
            $this->log('[DASHBOARD] File: ' . $e->getFile() . ':' . $e->getLine());
            $this->log('[DASHBOARD] Trace: ' . $e->getTraceAsString());
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Failed to load dashboard',
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}