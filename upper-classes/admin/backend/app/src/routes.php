<?php

use FastRoute\RouteCollector;

return function (RouteCollector $r): void {
	$r->addRoute('POST', '/auth/login', 'AuthController@login');
	$r->addRoute('GET', '/auth/check', 'AuthController@check');
	$r->addRoute('GET', '/auth/logout', 'AuthController@logout');
};