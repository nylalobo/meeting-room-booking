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
$routes->get('facilities/(:num)', 'Facility::show/$1');
$routes->post('facilities', 'Facility::create');
$routes->put('facilities/(:num)', 'Facility::update/$1');
$routes->delete('facilities/(:num)', 'Facility::delete/$1');
$routes->get('bookings', 'Booking::index');
$routes->get('bookings/(:num)', 'Booking::show/$1');
$routes->post('bookings', 'Booking::create');
$routes->put('bookings/(:num)', 'Booking::update/$1');
$routes->delete('bookings/(:num)', 'Booking::delete/$1');
$routes->get('booking-participants', 'BookingParticipant::index');
$routes->get('booking-participants/(:num)/(:num)', 'BookingParticipant::show/$1/$2');
$routes->post('booking-participants', 'BookingParticipant::create');
$routes->put('booking-participants/(:num)/(:num)', 'BookingParticipant::update/$1/$2');
$routes->delete('booking-participants/(:num)/(:num)', 'BookingParticipant::delete/$1/$2');
$routes->get('catering-requests', 'CateringRequest::index');
$routes->get('audit-logs', 'AuditLog::index');
$routes->get('room-facilities', 'RoomFacility::index');
$routes->get('departments', 'Department::index');
$routes->get('departments/(:num)', 'Department::show/$1');
$routes->post('departments', 'Department::create');
$routes->put('departments/(:num)', 'Department::update/$1');
$routes->delete('departments/(:num)', 'Department::delete/$1');
