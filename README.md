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

