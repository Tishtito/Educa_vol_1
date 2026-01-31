<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r): void {
	$r->addRoute('POST', '/auth/login', 'AuthController@login');
	$r->addRoute('GET', '/auth/check', 'AuthController@check');
	$r->addRoute('GET', '/auth/logout', 'AuthController@logout');

	$r->addRoute('GET', '/dashboard/summary', 'DashboardController@summary');
	$r->addRoute('GET', '/dashboard/top-exams', 'DashboardController@topExams');
	$r->addRoute('GET', '/dashboard/exams', 'DashboardController@exams');
	$r->addRoute('GET', '/dashboard/grades', 'DashboardController@grades');
	
	$r->addRoute('GET', '/analysis/exams', 'AnalysisController@exams');
	$r->addRoute('GET', '/analysis/grades', 'AnalysisController@grades');
	$r->addRoute('GET', '/streams/list', 'StreamListController@list');
	$r->addRoute('GET', '/marks/list', 'MarkListController@list');
	
	$r->addRoute('GET', '/mss', 'MssController@list');
};
