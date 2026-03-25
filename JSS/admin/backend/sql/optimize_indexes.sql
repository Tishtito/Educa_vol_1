-- Suggested indexes to speed up common dashboard/admin queries.
-- Review and apply in your production DB during low traffic.

CREATE INDEX idx_exams_date_created ON exams (date_created);
CREATE INDEX idx_exams_type_date ON exams (exam_type, date_created);

CREATE INDEX idx_exam_results_exam_id ON exam_results (exam_id);
CREATE INDEX idx_exam_results_exam_student ON exam_results (exam_id, student_id);
CREATE INDEX idx_exam_results_student_deleted_created ON exam_results (student_id, deleted_at, created_at);
CREATE INDEX idx_exam_results_result_deleted ON exam_results (result_id, deleted_at);

CREATE INDEX idx_students_status_deleted_class ON students (status, deleted_at, class);
CREATE INDEX idx_students_status_updated ON students (status, updated_at);
CREATE INDEX idx_students_class ON students (class);

CREATE INDEX idx_exam_mean_scores_exam_class ON exam_mean_scores (exam_id, class);
CREATE INDEX idx_class_teachers_assigned ON class_teachers (class_assigned);

CREATE INDEX idx_examiner_subjects_examiner ON examiner_subjects (examiner_id);
CREATE INDEX idx_examiner_subjects_subject ON examiner_subjects (subject_id);
