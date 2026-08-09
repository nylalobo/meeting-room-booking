<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->get('departments', 'Department::index');
$routes->get('roles', 'Role::index');
$routes->get('users', 'User::index');
$routes->get('locations', 'Location::index');
$routes->get('rooms', 'Room::index');
$routes->get('facilities', 'Facility::index');
$routes->get('bookings', 'Booking::index');
$routes->get('booking-participants', 'BookingParticipant::index');
$routes->get('catering-requests', 'CateringRequest::index');