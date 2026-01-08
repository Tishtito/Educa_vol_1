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
