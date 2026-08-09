<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->get('departments', 'Department::index');
$routes->get('roles', 'Role::index');
$routes->get('users', 'User::index');
$routes->get('locations', 'Location::index');
$routes->get('rooms', 'Room::index');