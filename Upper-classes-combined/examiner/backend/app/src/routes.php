<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r): void {
    // Auth routes
    $r->addRoute('POST', '/auth/login', 'AuthController@login');
    $r->addRoute('GET', '/auth/check', 'AuthController@check');
    $r->addRoute('GET', '/auth/logout', 'AuthController@logout');
    $r->addRoute('GET', '/auth/classes', 'AuthController@getClasses');
    $r->addRoute('POST', '/signup/register', 'SignUpController@register');

    // Dashboard route
    $r->addRoute('GET', '/dashboard', 'DashboardController@index');

    // Exam routes
    $r->addRoute('GET', '/exams', 'ExamController@getExams');
    $r->addRoute('POST', '/exams/select', 'ExamController@selectExam');

    // Subject routes
    $r->addRoute('GET', '/subjects/marks', 'SubjectController@getMarks');
    $r->addRoute('POST', '/subjects/marks/update', 'SubjectController@updateMarks');
    $r->addRoute('POST', '/subjects/marks-out-of', 'SubjectController@setMarksOutOf');

    // User routes
    $r->addRoute('GET', '/profile', 'ProfileController@getProfile');

    // Reports routes
};