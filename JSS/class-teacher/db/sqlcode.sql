-- Database name: jss

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,          -- Store hashed passwords
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    updated_at datetime,
    deleted_at datetime
);

CREATE TABLE  examiner(
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
    exam_type ENUM ('Opener', 'Mid-term', 'End-Term'),
    term ENUM ('Term 1', 'Term 2', 'Term 3'),	
    academic_year YEAR NOT NULL,  -- Added for clarity
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    updated_at datetime,
    deleted_at datetime
);

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    pno. VARCHAR(20) UNIQUE,
    class VARCHAR(50) NOT NULL,
    status ENUM('Active', 'Finished', 'Graduated') DEFAULT 'Active',
    finished_at YEAR,
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
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT,
    student_id INT,
    student_class_id INT NOT NULL,
    Math INT,
    English INT,
    Kiswahili INT,
    Technical INT,
    Agriculture INT,
    Creative INT,
    Religious INT,
    SST INT,
    Science INT,
    total_marks INT,
    position INT,
    stream_position INT,
    created_at datetime,
    updated_at datetime,
    deleted_at datetime,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
    FOREIGN KEY (student_class_id) REFERENCES student_classes(student_class_id) ON DELETE CASCADE
);

CREATE TABLE examiners (
    examiner_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,          -- Store hashed passwords
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

CREATE TABLE examiner_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    examiner_id INT NOT NULL,
    subject_id INT NOT NULL,
    FOREIGN KEY (examiner_id) REFERENCES examiners(examiner_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    UNIQUE (examiner_id, subject_id)         -- Ensure no duplicate assignments
);

CREATE TABLE classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL UNIQUE,
    year YEAR
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    updated_at datetime,
    deleted_at datetime,
    grade INT(11)
);

CREATE TABLE examiner_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    examiner_id INT NOT NULL,
    class_id INT NOT NULL,
    assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at datetime,
    updated_at datetime,
    deleted_at datetime,
    FOREIGN KEY (examiner_id) REFERENCES examiners(examiner_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE
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
    Technical FLOAT DEFAULT NULL,
    Creative FLOAT DEFAULT NULL,
    Science FLOAT DEFAULT NULL,
    Agriculture FLOAT DEFAULT NULL,
    SST FLOAT DEFAULT NULL,
    Religious FLOAT DEFAULT NULL,
    total_mean FLOAT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE
);

INSERT INTO subjects(name)
VALUES
('Math'),
('English'),
('Kiswahili'),
('creative arts'),
('pre-technical'),
('agriculture'),
('religious'),
('social studies'),
('intergrade science');

CREATE TABLE marks_out_of (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    subject VARCHAR(50) NOT NULL,
    marks_out_of INT NOT NULL,
    UNIQUE KEY unique_exam_subject (exam_id, subject),
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE
);
