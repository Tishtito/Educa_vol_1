CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,          -- Store hashed passwords
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    updated_at datetime,
    deleted_at datetime
);

CREATE TABLE  users(
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
    exam_type ENUM('Weekly-Test', 'Opener', 'Mid-term', 'End-Term'),
    term ENUM('Term 1', 'Term 2', 'Term 3'),
    academic_year YEAR NOT NULL,  -- Added for clarity
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    updated_at datetime,
    deleted_at datetime
);

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
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
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT,
    student_id INT,
    student_class_id INT NOT NULL,
    Math INT,
    English INT,
    Kiswahili INT,
    SciTech INT,
    AgricNutri INT,
    Creative INT,
    CRE INT,
    SST INT,
    Integrated_science INT,
    CA_SST_CRE INT,
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
('science and technology'),
('agriculture and nutrition'),
('social studies'),
('CRE'),
('Integrated Science'),
('CA, SST, CRE');

CREATE TABLE examiner_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    examiner_id INT NOT NULL,
    subject_id INT NOT NULL,
    created_at datetime,
    updated_at datetime,
    deleted_at datetime,
    FOREIGN KEY (examiner_id) REFERENCES examiners(examiner_id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id) ON DELETE CASCADE,
    UNIQUE (examiner_id, subject_id)         -- Ensure no duplicate assignments
);

CREATE TABLE classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(50) NOT NULL UNIQUE,
    grade TINYINT NOT NULL,
    year YEAR
);

CREATE TABLE examiner_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    examiner_id INT NOT NULL,
    class_id INT NOT NULL,
    assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP,
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
    Creative FLOAT DEFAULT NULL,
    SciTech FLOAT DEFAULT NULL,
    AgricNutri FLOAT DEFAULT NULL,
    SST FLOAT DEFAULT NULL,
    CRE FLOAT DEFAULT NULL,
    Integrated_science FLOAT DEFAULT NULL,
    CA_SST_CRE FLOAT DEFAULT NULL,
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
