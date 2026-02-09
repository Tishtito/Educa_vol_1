<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r): void {
	$r->addRoute('POST', '/auth/login', 'AuthController@login');
	$r->addRoute('GET', '/auth/check', 'AuthController@check');
	$r->addRoute('GET', '/auth/logout', 'AuthController@logout');
	$r->addRoute('POST', '/signup/register', 'SignUpController@register');

	$r->addRoute('GET', '/dashboard/summary', 'DashboardController@summary');
	$r->addRoute('GET', '/dashboard/top-exams', 'DashboardController@topExams');
	$r->addRoute('GET', '/dashboard/exams', 'DashboardController@exams');
	$r->addRoute('GET', '/dashboard/grades', 'DashboardController@grades');
	
	$r->addRoute('GET', '/analysis/exams', 'AnalysisController@exams');
	$r->addRoute('GET', '/analysis/grades', 'AnalysisController@grades');
	$r->addRoute('GET', '/reports/exams', 'ReportsController@exams');
	$r->addRoute('GET', '/reports/grades', 'ReportsController@grades');
	$r->addRoute('GET', '/reports/students', 'ReportsController@students');
	$r->addRoute('GET', '/reports/report-single', 'ReportsController@reportSingle');
	$r->addRoute('GET', '/reports/report-combined', 'ReportsController@reportCombined');
	$r->addRoute('GET', '/reports/download', 'ReportsController@download');

	$r->addRoute('GET', '/students/summary', 'StudentsController@summary');
	$r->addRoute('GET', '/students/active-classes', 'StudentsController@activeClasses');
	$r->addRoute('GET', '/students/active-by-class', 'StudentsController@activeByClass');
	$r->addRoute('GET', '/students/finished-years', 'StudentsController@finishedYears');
	$r->addRoute('GET', '/students/finished-by-year', 'StudentsController@finishedByYear');
	$r->addRoute('GET', '/students/detail', 'StudentsController@detail');
	$r->addRoute('GET', '/students/profile', 'StudentsController@profile');
	$r->addRoute('GET', '/students/results', 'StudentsController@results');
	$r->addRoute('GET', '/students/result-detail', 'StudentsController@resultDetail');

	$r->addRoute('GET', '/streams/list', 'StreamListController@list');

	$r->addRoute('GET', '/marks/list', 'MarkListController@list');

	$r->addRoute('GET', '/teachers', 'UserController@teachers');
	$r->addRoute('GET', '/examiners', 'UserController@examiners');
	$r->addRoute('GET', '/classes', 'UserController@classes');

	$r->addRoute('GET', '/subjects', 'ExaminerController@subjects');
	$r->addRoute('GET', '/examiners/detail', 'ExaminerController@detail');
	$r->addRoute('POST', '/examiners/update', 'ExaminerController@update');
	$r->addRoute('POST', '/examiners/create', 'ExaminerController@create');
	$r->addRoute('POST', '/examiners/delete', 'ExaminerController@delete');
	
	$r->addRoute('POST', '/teachers/create', 'UserController@createTeacher');
	$r->addRoute('POST', '/teachers/update', 'UserController@updateTeacher');
	$r->addRoute('POST', '/teachers/delete', 'UserController@deleteTeacher');
	
	$r->addRoute('GET', '/mss', 'MssController@list');

	$r->addRoute('GET', '/settings/point-boundaries', 'SettingsController@pointBoundaries');
	$r->addRoute('POST', '/settings/point-boundaries', 'SettingsController@updatePointBoundaries');
	$r->addRoute('POST', '/settings/exams', 'SettingsController@createExam');

	$r->addRoute('GET', '/settings/classes', 'ClassesController@list');
	$r->addRoute('POST', '/settings/classes', 'ClassesController@create');
	$r->addRoute('POST', '/settings/classes/delete', 'ClassesController@delete');
	$r->addRoute('POST', '/settings/classes/move-all', 'ClassMovementController@moveAll');
	$r->addRoute('POST', '/settings/classes/move-student', 'ClassMovementController@moveStudent');
	$r->addRoute('POST', '/settings/classes/graduate-all', 'ClassMovementController@graduateAll');

	$r->addRoute('GET', '/settings/exams', 'ExamsController@list');
	$r->addRoute('POST', '/settings/exams/delete', 'ExamsController@delete');
	$r->addRoute('POST', '/settings/exams/update', 'ExamsController@update');

	$r->addRoute('GET', '/settings/sms/results', 'SmsController@results');
	$r->addRoute('POST', '/settings/sms/send', 'SmsController@send');
};