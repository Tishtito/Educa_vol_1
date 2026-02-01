<?php

declare(strict_types=1);

namespace App\Controllers;

use Medoo\Medoo;

class ProfileController
{
    private Medoo $db;

    public function __construct(Medoo $db)
    {
        $this->db = $db;
    }

    /**
     * Get teacher profile information
     * Returns teacher name, assigned class, and total student count
     */
    public function getProfile(): void
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

        try {
            $user_id = $_SESSION['id'] ?? null;
            $class_assigned = $_SESSION['class_assigned'] ?? null;

            if (!$user_id || !$class_assigned) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID or class not found in session']);
                return;
            }

            // Fetch teacher information
            $teacher = $this->db->select('class_teachers', ['name'], ['id' => $user_id]);
            
            if (!$teacher) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Teacher not found']);
                return;
            }

            // Handle Medoo return format - select with specific columns returns the value directly
            $teacherName = is_array($teacher) ? ($teacher['name'] ?? 'Teacher') : $teacher;

            // Count total students in the assigned class
            $studentCount = $this->db->count('students', ['class' => $class_assigned]);

            // Format class title
            $classTitle = ucwords(str_replace('_', ' ', $class_assigned));

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'teacher' => [
                    'name' => $teacherName,
                    'role' => 'Class Teacher'
                ],
                'class' => [
                    'assigned' => $class_assigned,
                    'title' => $classTitle,
                    'totalStudents' => $studentCount
                ]
            ]);
        } catch (\Exception $e) {
            error_log('Error in getProfile: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error fetching profile']);
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
