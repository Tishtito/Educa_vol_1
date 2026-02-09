<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r): void {
	$r->addRoute('POST', '/auth/login', 'AuthController@login');
	$r->addRoute('GET', '/auth/classes', 'AuthController@getClasses');
	$r->addRoute('GET', '/auth/check', 'AuthController@check');
	$r->addRoute('GET', '/auth/logout', 'AuthController@logout');
	$r->addRoute('POST', '/signup/register', 'SignUpController@register');

	$r->addRoute('GET', '/exams', 'ExamController@getExams');
	$r->addRoute('POST', '/exams/select', 'ExamController@selectExam');
	
	$r->addRoute('GET', '/dashboard', 'DashboardController@getDashboard');
	
	$r->addRoute('GET', '/profile', 'ProfileController@getProfile');
	
	$r->addRoute('GET', '/subjects', 'SubjectController@getSubjects');
	$r->addRoute('GET', '/subjects/students', 'SubjectController@getSubjectStudents');
	$r->addRoute('POST', '/subjects/students/marks', 'SubjectController@updateMarks');
};