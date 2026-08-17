<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');


$routes->get('roles', 'Role::index');
$routes->get('users', 'User::index');
$routes->get('locations', 'Location::index');
$routes->get('rooms', 'Room::index');
$routes->get('rooms/(:num)', 'Room::show/$1');
$routes->post('rooms', 'Room::create');
$routes->put('rooms/(:num)', 'Room::update/$1');
$routes->delete('rooms/(:num)', 'Room::delete/$1');
$routes->get('facilities', 'Facility::index');
$routes->get('bookings', 'Booking::index');
$routes->get('booking-participants', 'BookingParticipant::index');
$routes->get('catering-requests', 'CateringRequest::index');
$routes->get('audit-logs', 'AuditLog::index');
$routes->get('room-facilities', 'RoomFacility::index');
$routes->get('departments', 'Department::index');
$routes->get('departments/(:num)', 'Department::show/$1');
$routes->post('departments', 'Department::create');
$routes->put('departments/(:num)', 'Department::update/$1');
$routes->delete('departments/(:num)', 'Department::delete/$1');
