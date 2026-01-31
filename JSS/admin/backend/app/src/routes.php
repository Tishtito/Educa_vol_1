<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r): void {
	$r->addRoute('POST', '/auth/login', 'AuthController@login');
	$r->addRoute('GET', '/auth/check', 'AuthController@check');
	$r->addRoute('GET', '/dashboard/summary', 'DashboardController@summary');
	$r->addRoute('GET', '/dashboard/top-exams', 'DashboardController@topExams');
	$r->addRoute('GET', '/dashboard/exams', 'DashboardController@exams');
};
