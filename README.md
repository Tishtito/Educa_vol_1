=======
📚 Student Management & Examination System
📌 Project Overview

This project is a web-based Student Management and Examination System built with PHP and MySQL.
It is designed to help schools manage students, track academic progress, and generate reports for active, finished, and graduated students.

The system provides:

Student lifecycle management

Exam results tracking

Detailed performance reporting

Secure role-based access

🎯 Key Objectives

Manage students by status (Active, Finished, Graduated)

Record and display exam results

Provide year-based and class-based reports

Allow administrators to view detailed subject breakdowns

Maintain clean and auditable academic records

🏗️ System Architecture
🔹 Backend

PHP (Procedural + Prepared Statements)

MySQL Database

Secure session-based authentication

🔹 Frontend

HTML5, CSS3

Boxicons UI icons

Responsive dashboard layout

🗄️ Database Structure
students Table

Stores student bio and academic status.

student_id (PK)
name
class
status (Active | Finished | Graduated)
finished_at
created_at
updated_at
deleted_at

exam_results Table

Stores exam performance per student per exam.

result_id (PK)
exam_id (FK)
student_id (FK)
student_class_id (FK)
subject scores (Math, English, Kiswahili, etc.)
total_marks
position
stream_position
created_at
updated_at
deleted_at

🚀 Core Features
1️⃣ Authentication

Secure login system

Session-based access control

Unauthorized users are redirected to login

2️⃣ Active Students Module

Pages:

students_active.php

students_active_by_class.php

Features:

Displays all classes with active students

View active students grouped by class

Quick access to student profiles

3️⃣ Finished Students Module

Pages:

students_finished.php

students_finished_by_year.php

Features:

Displays years students completed

Uses dedicated finished_at column

Year-based academic reporting

Clean historical records

4️⃣ Student Exam Results

Pages:

student_all_results.php

student_exam_breakdown.php

Features:

View all exams done by a student

Detailed subject-by-subject breakdown

Total marks, positions, and stream rankings

Per-exam drill-down analysis

5️⃣ Reports & Analytics

Yearly completion reports

Class-based student grouping

Expandable to charts, exports, and trends

🔐 Security Measures

Prepared SQL statements (prevents SQL injection)

Input validation for all GET/POST parameters

Session authentication on all protected pages

Soft deletes using deleted_at
======

## How edit the database to impliment the graduating students function to the exsisting system in production
## HOw to fix the students graduate function 
✅ STEP 1: Create student_classes table
    '''
        CREATE TABLE student_classes (
            student_class_id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            class VARCHAR(50) NOT NULL,
            academic_year YEAR NOT NULL,
            created_at DATETIME,
            updated_at DATETIME,
            deleted_at DATETIME,
            FOREIGN KEY (student_id)
                REFERENCES students(student_id)
                ON DELETE CASCADE
        );
    '''

✅STEP 2: Populate student_classes from students
    '''
        ALTER TABLE students
        ADD status ENUM('Active', 'Finished', 'Graduated') DEFAULT 'Active',
        ADD finished_at DATETIME NULL,
        ADD created_at datetime,
        ADD updated_at datetime,
        ADD deleted_at datetime;
    '''

    '''
        INSERT INTO student_classes (
            student_id,
            class,
            academic_year,
            created_at
        )
        SELECT
            s.student_id,
            s.class,
            2025,
            NOW()
        FROM students s
        WHERE s.status = 'Active';
    '''

✅ STEP 3: Add student_class_id to exam_results
    '''
        ALTER TABLE exam_results
        ADD student_class_id INT NULL;
    '''

✅ STEP 4: Populate exam_results.student_class_id
    '''
        UPDATE exam_results er
        JOIN student_classes sc
        ON er.student_id = sc.student_id
        AND sc.academic_year = 2025
        SET er.student_class_id = sc.student_class_id
        WHERE er.student_class_id IS NULL;
    '''

✅ STEP 5: Verify data integrity
    '''
        SELECT *
        FROM exam_results
        WHERE student_class_id IS NULL;
    '''

✅ STEP 6: Enforce NOT NULL and Foreign Key
    '''
        ALTER TABLE exam_results
        MODIFY student_class_id INT NOT NULL;

        ALTER TABLE exam_results
        ADD CONSTRAINT fk_student_class_id
        FOREIGN KEY (student_class_id)
        REFERENCES student_classes(student_class_id)
        ON DELETE CASCADE;
    '''

