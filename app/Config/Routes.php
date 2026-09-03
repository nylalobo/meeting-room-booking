<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */


/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

$routes->get('/', 'Home::index');
$routes->get('dashboard/stats', 'Dashboard::stats');


/*
|--------------------------------------------------------------------------
| Basic API Resources
|--------------------------------------------------------------------------
*/

$routes->get('roles', 'Role::index');

$routes->get('users', 'User::index');


/*
|--------------------------------------------------------------------------
| Locations
|--------------------------------------------------------------------------
|
| /locations            -> Locations frontend page
| /api/locations        -> List locations
| /api/locations/{id}   -> Get one location
| /api/locations        -> Create location
| /api/locations/{id}   -> Update location
| /api/locations/{id}   -> Delete location
|
*/

$routes->get('locations', 'Home::locations');

$routes->get('api/locations', 'Location::index');
$routes->get('api/locations/(:num)', 'Location::show/$1');
$routes->post('api/locations', 'Location::create');
$routes->put('api/locations/(:num)', 'Location::update/$1');
$routes->delete('api/locations/(:num)', 'Location::delete/$1');


/*
|--------------------------------------------------------------------------
| Rooms
|--------------------------------------------------------------------------
|
| /rooms       -> Rooms frontend page
| /api/rooms   -> Rooms API
|
*/

$routes->get('rooms', 'Home::rooms');

$routes->get('api/rooms', 'Room::index');
$routes->get('api/rooms/(:num)', 'Room::show/$1');
$routes->post('api/rooms', 'Room::create');
$routes->put('api/rooms/(:num)', 'Room::update/$1');
$routes->delete('api/rooms/(:num)', 'Room::delete/$1');


/*
|--------------------------------------------------------------------------
| Facilities
|--------------------------------------------------------------------------
*/

$routes->get('facilities', 'Facility::index');
$routes->get('facilities/(:num)', 'Facility::show/$1');
$routes->post('facilities', 'Facility::create');
$routes->put('facilities/(:num)', 'Facility::update/$1');
$routes->delete('facilities/(:num)', 'Facility::delete/$1');


/*
|--------------------------------------------------------------------------
| Bookings
|--------------------------------------------------------------------------
|
| /bookings       -> Bookings frontend page
| /api/bookings   -> Bookings API
|
*/

$routes->get('bookings', 'Home::bookings');

$routes->get('api/bookings', 'Booking::apiIndex');
$routes->get('bookings/(:num)', 'Booking::show/$1');
$routes->post('bookings', 'Booking::create');
$routes->put('bookings/(:num)', 'Booking::update/$1');
$routes->delete('bookings/(:num)', 'Booking::delete/$1');


/*
|--------------------------------------------------------------------------
| Booking Participants
|--------------------------------------------------------------------------
|
| /participants                         -> Participants frontend page
| /booking-participants                 -> List booking participants
| /booking-participants/{bid}/{uid}     -> Get one booking participant
| /booking-participants                 -> Create booking participant
| /booking-participants/{bid}/{uid}     -> Update booking participant
| /booking-participants/{bid}/{uid}     -> Delete booking participant
|
*/

$routes->get('participants', 'Home::participants');

$routes->get('booking-participants', 'BookingParticipant::index');

$routes->get(
    'booking-participants/(:num)/(:num)',
    'BookingParticipant::show/$1/$2'
);

$routes->post(
    'booking-participants',
    'BookingParticipant::create'
);

$routes->put(
    'booking-participants/(:num)/(:num)',
    'BookingParticipant::update/$1/$2'
);

$routes->delete(
    'booking-participants/(:num)/(:num)',
    'BookingParticipant::delete/$1/$2'
);


/*
|--------------------------------------------------------------------------
| Catering Requests
|--------------------------------------------------------------------------
*/

$routes->get(
    'catering-requests',
    'CateringRequest::index'
);


/*
|--------------------------------------------------------------------------
| Audit Logs
|--------------------------------------------------------------------------
*/

$routes->get(
    'audit-logs',
    'AuditLog::index'
);


/*
|--------------------------------------------------------------------------
| Room Facilities
|--------------------------------------------------------------------------
*/

$routes->get(
    'room-facilities',
    'RoomFacility::index'
);


/*
|--------------------------------------------------------------------------
| Departments
|--------------------------------------------------------------------------
*/

$routes->get(
    'departments',
    'Department::index'
);

$routes->get(
    'departments/(:num)',
    'Department::show/$1'
);

$routes->post(
    'departments',
    'Department::create'
);

$routes->put(
    'departments/(:num)',
    'Department::update/$1'
);

$routes->delete(
    'departments/(:num)',
    'Department::delete/$1'
);