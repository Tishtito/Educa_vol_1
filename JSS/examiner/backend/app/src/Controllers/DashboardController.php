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

    // GET /dashboard - get examiner's subjects and classes
    public function getDashboard(): void
    {
        $this->startSession();
        error_log('[DASHBOARD] Request started. Session status: ' . session_status());
        error_log('[DASHBOARD] Session data: ' . json_encode($_SESSION, JSON_UNESCAPED_SLASHES));
        
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
            error_log('[DASHBOARD] Unauthorized: loggedin flag not set');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            $examinerId = $_SESSION['id'] ?? null;
            error_log('[DASHBOARD] Extracted examiner ID: ' . ($examinerId ?? 'NULL'));
            
            if (!$examinerId) {
                error_log('[DASHBOARD] Error: Examiner ID not found in session');
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Examiner ID not found in session']);
                return;
            }

            // Get subjects assigned to examiner
            error_log('[DASHBOARD] Fetching subjects for examiner_id=' . $examinerId);
            $subjects = $this->db->select('examiner_subjects', ['[>]subjects' => ['subject_id' => 'subject_id']], 
                ['subjects.subject_id', 'subjects.name'],
                ['examiner_subjects.examiner_id' => $examinerId]
            );
            error_log('[DASHBOARD] Subjects query result: ' . json_encode($subjects ?? []));

            // Get classes assigned to examiner
            error_log('[DASHBOARD] Fetching classes for examiner_id=' . $examinerId);
            $classes = $this->db->select('examiner_classes', ['[>]classes' => ['class_id' => 'class_id']], 
                ['classes.class_id', 'classes.class_name'],
                ['examiner_classes.examiner_id' => $examinerId]
            );
            error_log('[DASHBOARD] Classes query result: ' . json_encode($classes ?? []));

            // Check if examiner has assigned classes
            if (empty($classes)) {
                error_log('[DASHBOARD] Warning: No classes assigned to examiner_id=' . $examinerId);
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'No classes assigned. Visit Admin for assistance.']);
                return;
            }

            header('Content-Type: application/json');
            error_log('[DASHBOARD] Success: Returning dashboard data for examiner_id=' . $examinerId);
            echo json_encode([
                'success' => true,
                'subjects' => $subjects ?: [],
                'classes' => $classes ?: []
            ]);
        } catch (\Exception $e) {
            error_log('[DASHBOARD] Exception: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
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
