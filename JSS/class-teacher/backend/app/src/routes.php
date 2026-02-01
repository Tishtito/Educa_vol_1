<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r): void {
	$r->addRoute('POST', '/auth/login', 'AuthController@login');
	$r->addRoute('GET', '/auth/check', 'AuthController@check');
	$r->addRoute('GET', '/auth/logout', 'AuthController@logout');
	
	$r->addRoute('GET', '/profile', 'ProfileController@getProfile');

	$r->addRoute('GET', '/dashboard', 'DashboardController@index');

	$r->addRoute('GET', '/students', 'StudentController@getStudents');
	$r->addRoute('POST', '/students/create', 'StudentController@createStudent');
	$r->addRoute('POST', '/students/delete', 'StudentController@deleteStudent');
	$r->addRoute('POST', '/students/update-class', 'StudentController@updateClass');
	$r->addRoute('POST', '/students/graduate-all', 'StudentController@graduateAll');
	$r->addRoute('GET', '/classes', 'StudentController@getClasses');
	$r->addRoute('GET', '/student-profile', 'StudentProfileController@getStudentProfile');

	$r->addRoute('GET', '/exams', 'ExamController@getExams');
	$r->addRoute('POST', '/exams/select', 'ExamController@selectExam');

	$r->addRoute('GET', '/marklist', 'MarklistController@getMarkList');
	$r->addRoute('GET', '/marklist/exam-details', 'MarklistController@getExamDetails');

	$r->addRoute('GET', '/points-table', 'PointsTableController@getPointsTable');
	$r->addRoute('GET', '/points-table/exam-details', 'PointsTableController@getExamDetails');

	$r->addRoute('GET', '/subjects', 'SubjectsController@getSubjects');
	$r->addRoute('GET', '/subjects/marks', 'SubjectsController@getSubjectMarks');
};