-- Database name: lower_classes

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,          -- Store hashed passwords
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime,
    deleted_at datetime
);

CREATE TABLE  examiners (
    examiner_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    class_assigned VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,          -- Store hashed passwords
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    updated_at datetime,
    deleted_at datetime
);

CREATE TABLE  class_teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    class_assigned VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,          -- Store hashed passwords
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    updated_at datetime,
    deleted_at datetime
);


CREATE TABLE exams (
    exam_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_name VARCHAR(255) NOT NULL,
    exam_type ENUM('Weekly-Test', 'Opener', 'Mid-Term', 'End-Term'),
    term ENUM('Term 1', 'Term 2', 'Term 3'),
    academic_year YEAR NOT NULL,
    status ENUM('Scheduled', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime,
    deleted_at datetime
);

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    pno VARCHAR(20) UNIQUE,
    class VARCHAR(50) NOT NULL,
    status ENUM('Active', 'Finished', 'Graduated') DEFAULT 'Active',
    finished_at DATETIME NULL,
    created_at datetime,
    updated_at datetime,
    deleted_at datetime
);

CREATE TABLE student_classes (
    student_class_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class VARCHAR(50) NOT NULL,
    academic_year YEAR NOT NULL,
    created_at datetime,
    updated_at datetime,
    deleted_at datetime,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
);

CREATE TABLE exam_results (
    result_id INT AUTO_INCREMENT PRIMARY KEY NOT NULL,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    student_class_id INT NOT NULL,
    Math INT,
    English INT,
    LS/SP INT,
    GRM INT,
    WRI INT,
    RDG INT,
    Kiswahili INT,
    KUS/KUZ INT,
    KUS INT,
    LUG INT,
    KUA INT,    
    Enviromental INT,
    Creative INT,
    Religious INT,
    total_marks INT,
    position INT,
    stream_position INT,
    created_at datetime,
    updated_at datetime,
    deleted_at datetime,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (student_class_id) REFERENCES student_classes(student_class_id) ON DELETE CASCADE
);

CREATE TABLE examiners (
    examiner_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,          -- Store hashed passwords
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
    updated_at datetime,
    deleted_at datetime
);

CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

INSERT INTO subjects(name)
VALUES
('Math'),
('English'),
('Kiswahili'),
('creative'),
('enviromental'),
('religious'),

CREATE TABLE classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL UNIQUE,
    grade TINYINT NOT NULL,
    year YEAR
);

CREATE TABLE class_teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    class_assigned VARCHAR(255) NOT NULL,
    created_at datetime,
    updated_at datetime,
    deleted_at datetime
);

CREATE TABLE exam_mean_scores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    class VARCHAR(50) NOT NULL,
    English FLOAT DEFAULT NULL,
    Math FLOAT DEFAULT NULL,
    Kiswahili FLOAT DEFAULT NULL,
    Creative FLOAT DEFAULT NULL,
    Enviromental FLOAT DEFAULT NULL,
    Religious FLOAT DEFAULT NULL,
    LS/SP FLOAT DEFAULT NULL,
    GRM FLOAT DEFAULT NULL,
    WRI FLOAT DEFAULT NULL,
    RDG FLOAT DEFAULT NULL,
    KUS/KUZ FLOAT DEFAULT NULL,
    KUS FLOAT DEFAULT NULL,
    LUG FLOAT DEFAULT NULL,
    KUA FLOAT DEFAULT NULL,
    Integrated FLOAT DEFAULT NULL,
    total_mean FLOAT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime,
    deleted_at datetime,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE
);

CREATE TABLE marks_out_of (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    subject VARCHAR(50) NOT NULL,
    marks_out_of INT NOT NULL,
    UNIQUE KEY unique_exam_subject (exam_id, subject),
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE
);

CREATE TABLE point_boundaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(50) NOT NULL,
    grade CHAR(2) NOT NULL,
    min_marks INT NOT NULL,
    max_marks INT NOT NULL,
    pl VARCHAR(100) NOT NULL,
    ab VARCHAR(10) NOT NULL,
    UNIQUE KEY unique_subject_grade (subject, grade)
);

INSERT INTO point_boundaries (subject, grade, min_marks, max_marks, pl, ab) VALUES
('Math', '4', 0, 100, 'EXCEEDING EXPECTATIONS', 'EE-4'),
('Math', '3', 0, 100, 'MEETING EXPECTATIONS', 'ME-3'),
('Math', '2', 0, 100, 'APPROCHING EXPECTATIONS', 'AE-2'),
('Math', '1', 0, 100, 'BELOW EXPECTATIONS', 'BE-1'),
('LS/SP', '4', 0, 100, 'EXCEEDING EXPECTATIONS', 'EE-4'),
('LS/SP', '3', 0, 100, 'MEETING EXPECTATIONS', 'ME-3'),
('LS/SP', '2', 0, 100, 'APPROCHING EXPECTATIONS', 'AE-2'),
('LS/SP', '1', 0, 100, 'BELOW EXPECTATIONS', 'BE-1'),
('RDG', '4', 0, 100, 'EXCEEDING EXPECTATIONS', 'EE-4'),
('RDG', '3', 0, 100, 'MEETING EXPECTATIONS', 'ME-3'),
('RDG', '2', 0, 100, 'APPROCHING EXPECTATIONS', 'AE-2'),
('RDG', '1', 0, 100, 'BELOW EXPECTATIONS', 'BE-1'),
('GRM', '4', 0, 100, 'EXCEEDING EXPECTATIONS', 'EE-4'),
('GRM', '3', 0, 100, 'MEETING EXPECTATIONS', 'ME-3'),
('GRM', '2', 0, 100, 'APPROCHING EXPECTATIONS', 'AE-2'),
('GRM', '1', 0, 100, 'BELOW EXPECTATIONS', 'BE-1'),
('WRI', '4', 0, 100, 'EXCEEDING EXPECTATIONS', 'EE-4'),
('WRI', '3', 0, 100, 'MEETING EXPECTATIONS', 'ME-3'),
('WRI', '2', 0, 100, 'APPROCHING EXPECTATIONS', 'AE-2'),
('WRI', '1', 0, 100, 'BELOW EXPECTATIONS', 'BE-1'),
('KUS/KUZ', '4', 0, 100, 'EXCEEDING EXPECTATIONS', 'EE-4'),
('KUS/KUZ', '3', 0, 100, 'MEETING EXPECTATIONS', 'ME-3'),
('KUS/KUZ', '2', 0, 100, 'APPROCHING EXPECTATIONS', 'AE-2'),
('KUS/KUZ', '1', 0, 100, 'BELOW EXPECTATIONS', 'BE-1'),
('KUS', '4', 0, 100, 'EXCEEDING EXPECTATIONS', 'EE-4'),
('KUS', '3', 0, 100, 'MEETING EXPECTATIONS', 'ME-3'),
('KUS', '2', 0, 100, 'APPROCHING EXPECTATIONS', 'AE-2'),
('KUS', '1', 0, 100, 'BELOW EXPECTATIONS', 'BE-1'),
('LUG', '4', 0, 100, 'EXCEEDING EXPECTATIONS', 'EE-4'),
('LUG', '3', 0, 100, 'MEETING EXPECTATIONS', 'ME-3'),
('LUG', '2', 0, 100, 'APPROCHING EXPECTATIONS', 'AE-2'),
('LUG', '1', 0, 100, 'BELOW EXPECTATIONS', 'BE-1'),
('KUA', '4', 0, 100, 'EXCEEDING EXPECTATIONS', 'EE-4'),
('KUA', '3', 0, 100, 'MEETING EXPECTATIONS', 'ME-3'),
('KUA', '2', 0, 100, 'APPROCHING EXPECTATIONS', 'AE-2'),
('KUA', '1', 0, 100, 'BELOW EXPECTATIONS', 'BE-1'),
('Enviromental', '4', 0, 100, 'EXCEEDING EXPECTATIONS', 'EE-4'),
('Enviromental', '3', 0, 100, 'MEETING EXPECTATIONS', 'ME-3'),
('Enviromental', '2', 0, 100, 'APPROCHING EXPECTATIONS', 'AE-2'),
('Enviromental', '1', 0, 100, 'BELOW EXPECTATIONS', 'BE-1'),
('Creative', '4', 0, 100, 'EXCEEDING EXPECTATIONS', 'EE-4'),
('Creative', '3', 0, 100, 'MEETING EXPECTATIONS', 'ME-3'),
('Creative', '2', 0, 100, 'APPROCHING EXPECTATIONS', 'AE-2'),
('Creative', '1', 0, 100, 'BELOW EXPECTATIONS', 'BE-1'),
('Religious', '4', 0, 100, 'EXCEEDING EXPECTATIONS', 'EE-4'),
('Religious', '3', 0, 100, 'MEETING EXPECTATIONS', 'ME-3'),
('Religious', '2', 0, 100, 'APPROCHING EXPECTATIONS', 'AE-2'),
('Religious', '1', 0, 100, 'BELOW EXPECTATIONS', 'BE-1');