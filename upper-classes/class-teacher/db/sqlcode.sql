CREATE TABLE exams (
    exam_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_name VARCHAR(255) NOT NULL,
    exam_type ENUM ('Opener', 'Mid-term', 'End-Term'),
    term ENUM ('Term 1', 'Term 2', 'Term 3'),
    date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    class VARCHAR(50) NOT NULL
);

CREATE TABLE exam_results (
    result_id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT,
    student_id INT,
    Math INT,
    English INT,
    Kiswahili INT,
    SciTech INT,
    AgricNutri INT,
    Creative INT,
    CRE INT,
    SST INT,
    total_marks INT,
    position INT,
    stream_position INT,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
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

INSERT INTO subjects(name)
VALUES
('Math'),
('English'),
('Kiswahili'),
('creative'),
('science and technology'),
('agriculture and nutrition'),
('social studies'),
('CRE');

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
    class_assigned VARCHAR(255) NOT NULL
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
    total_mean FLOAT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id) ON DELETE CASCADE
);
