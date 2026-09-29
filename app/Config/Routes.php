<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */


/*
|--------------------------------------------------------------------------
| Authentication (Public Routes)
|--------------------------------------------------------------------------
*/

$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');
$routes->get('register', 'Auth::register');
$routes->post('register', 'Auth::attemptRegister');

// Registration Email OTP Verification
$routes->get('verify-email', 'Auth::verifyEmailForm');
$routes->post('verify-email', 'Auth::attemptVerifyEmail');
$routes->post('verify-email/resend', 'Auth::resendOtp');
$routes->get('verify-email/(:any)', 'Auth::verifyEmailLegacy/$1');
$routes->get('resend-verification', 'Auth::verifyEmailForm');
$routes->post('resend-verification', 'Auth::resendOtp');

// Password Reset / Forgot Password
$routes->get('forgot-password', 'Auth::forgotPassword');
$routes->post('forgot-password', 'Auth::attemptForgotPassword');
$routes->post('forgot-password/resend', 'Auth::resendPasswordResetOtp');
$routes->get('reset-password', 'Auth::resetPassword');
$routes->post('reset-password', 'Auth::attemptResetPassword');

$routes->match(['GET', 'POST'], 'logout', 'Auth::logout');
$routes->get('check-in/room/(:segment)', 'Room::checkInLanding/$1');


/*
|--------------------------------------------------------------------------
| Protected Application Routes (Requires Authentication)
|--------------------------------------------------------------------------
*/

$routes->group('', ['filter' => 'auth'], static function ($routes): void {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    $routes->get('/', 'Home::index');
    $routes->get('dashboard/stats', 'Dashboard::stats');


/*
|--------------------------------------------------------------------------
| Roles (User Roles Management)
|--------------------------------------------------------------------------
|
| /roles                 -> Roles frontend page / list
| /admin/roles           -> Roles frontend page (alias)
| /api/roles             -> List roles (JSON)
| /api/roles/{id}        -> Get one role (JSON)
| /api/roles             -> Create role (Admin only)
| /api/roles/{id}        -> Update role (Admin only)
| /api/roles/{id}        -> Delete role (Admin only)
|
*/

$routes->get('roles', 'Role::index');
$routes->get('admin/roles', 'Role::index');

$routes->get('api/roles', 'Role::index');
$routes->get('api/roles/(:num)', 'Role::show/$1');
$routes->post('api/roles', 'Role::create');
$routes->put('api/roles/(:num)', 'Role::update/$1');
$routes->delete('api/roles/(:num)', 'Role::delete/$1');

// Legacy routes for backward compatibility
$routes->post('roles', 'Role::create');
$routes->put('roles/(:num)', 'Role::update/$1');
$routes->delete('roles/(:num)', 'Role::delete/$1');


/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
|
| /users            -> Users frontend page
| /api/users        -> List users
| /api/users/{id}   -> Get one user
| /api/users        -> Create user
| /api/users/{id}   -> Update user
| /api/users/{id}   -> Delete user
|
*/

$routes->get('users', 'Home::users');

$routes->get('api/users', 'User::index');
$routes->get('api/users/(:num)', 'User::show/$1');
$routes->post('api/users', 'User::create');
$routes->put('api/users/(:num)', 'User::update/$1');
$routes->delete('api/users/(:num)', 'User::delete/$1');

// Legacy routes for backward compatibility
$routes->get('users/(:num)', 'User::show/$1');
$routes->post('users', 'User::create');
$routes->put('users/(:num)', 'User::update/$1');
$routes->delete('users/(:num)', 'User::delete/$1');


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

$routes->get('api/rooms/availability', 'Room::availability');
$routes->post('api/rooms/availability', 'Room::availability');
$routes->get('api/rooms/(:num)/availability', 'Room::roomAvailability/$1');
$routes->get('api/rooms/(:num)/current-booking', 'Room::currentBooking/$1');
$routes->get('api/rooms/(:num)/qr-code', 'Room::qrCode/$1');
$routes->get('api/rooms', 'Room::index');
$routes->get('api/rooms/(:num)', 'Room::show/$1');
$routes->post('api/rooms', 'Room::create');
$routes->put('api/rooms/(:num)', 'Room::update/$1');
$routes->delete('api/rooms/(:num)', 'Room::delete/$1');


/*
|--------------------------------------------------------------------------
| Facilities
|--------------------------------------------------------------------------
|
| /facilities            -> Facilities frontend page
| /api/facilities        -> List facilities
| /api/facilities/{id}   -> Get one facility
| /api/facilities        -> Create facility
| /api/facilities/{id}   -> Update facility
| /api/facilities/{id}   -> Delete facility
|
*/

$routes->get('facilities', 'Home::facilities');

$routes->get('api/facilities', 'Facility::index');
$routes->get('api/facilities/(:num)', 'Facility::show/$1');
$routes->post('api/facilities', 'Facility::create');
$routes->put('api/facilities/(:num)', 'Facility::update/$1');
$routes->delete('api/facilities/(:num)', 'Facility::delete/$1');

// Legacy routes for backward compatibility
$routes->get('facilities/(:num)', 'Facility::show/$1');
$routes->post('facilities', 'Facility::create');
$routes->put('facilities/(:num)', 'Facility::update/$1');
$routes->delete('facilities/(:num)', 'Facility::delete/$1');


/*
|--------------------------------------------------------------------------
| Equipment
|--------------------------------------------------------------------------
|
| /api/equipment/availability -> List catalog-available equipment
| /api/equipment              -> List equipment catalog
| /api/equipment/{id}         -> Get one equipment item
| /api/equipment              -> Create equipment (Admin/FacMgr)
| /api/equipment/{id}         -> Update equipment (Admin/FacMgr)
| /api/equipment/{id}         -> Retire equipment (Admin/FacMgr)
|
*/

$routes->get('equipment', 'Home::equipment');

$routes->get('api/equipment/availability', 'Equipment::availability');
$routes->get('api/equipment', 'Equipment::index');
$routes->get('api/equipment/(:num)', 'Equipment::show/$1');
$routes->post('api/equipment', 'Equipment::create');
$routes->put('api/equipment/(:num)', 'Equipment::update/$1');
$routes->delete('api/equipment/(:num)', 'Equipment::delete/$1');


/*
|--------------------------------------------------------------------------
| Bookings
|--------------------------------------------------------------------------
|
| /bookings            -> Bookings frontend page
| /api/bookings        -> List bookings (enriched)
| /api/bookings/{id}   -> Get one booking
| /api/bookings        -> Create booking
| /api/bookings/{id}   -> Update booking
| /api/bookings/{id}   -> Delete booking
|
*/

$routes->get('bookings', 'Home::bookings');

$routes->get('api/bookings/pending-approvals', 'Booking::pendingApprovals');
$routes->post('api/bookings/recurring-preview', 'Booking::recurringPreview');
$routes->get('api/bookings/calendar', 'Booking::calendar');
$routes->get('api/bookings/series/(:segment)', 'Booking::series/$1');
$routes->delete('api/bookings/series/(:segment)', 'Booking::deleteSeries/$1');
$routes->get('api/bookings', 'Booking::apiIndex');
$routes->get('api/bookings/(:num)', 'Booking::show/$1');
$routes->post('api/bookings', 'Booking::create');
$routes->put('api/bookings/(:num)', 'Booking::update/$1');
$routes->delete('api/bookings/(:num)', 'Booking::delete/$1');
$routes->post('api/bookings/(:num)/approve', 'Booking::approve/$1');
$routes->post('api/bookings/(:num)/reject', 'Booking::reject/$1');
$routes->post('api/bookings/(:num)/detach', 'Booking::detach/$1');
$routes->post('api/bookings/(:num)/check-in', 'Booking::checkIn/$1');
$routes->post('api/bookings/(:num)/check-out', 'Booking::checkOut/$1');
$routes->get('api/bookings/(:num)/check-ins', 'Booking::checkIns/$1');
$routes->get('api/bookings/(:num)/attendance', 'Booking::attendance/$1');
$routes->get('api/bookings/(:num)/visitors', 'BookingVisitor::index/$1');
$routes->post('api/bookings/(:num)/visitors', 'BookingVisitor::create/$1');
$routes->get('api/bookings/(:num)/visitors/(:num)', 'BookingVisitor::show/$1/$2');
$routes->put('api/bookings/(:num)/visitors/(:num)', 'BookingVisitor::update/$1/$2');
$routes->delete('api/bookings/(:num)/visitors/(:num)', 'BookingVisitor::delete/$1/$2');
$routes->post('api/bookings/(:num)/visitors/(:num)/check-in', 'BookingVisitor::checkIn/$1/$2');
$routes->post('api/bookings/(:num)/visitors/(:num)/check-out', 'BookingVisitor::checkOut/$1/$2');
$routes->get('api/bookings/(:num)/resources', 'BookingResource::index/$1');
$routes->post('api/bookings/(:num)/resources', 'BookingResource::create/$1');
$routes->get('api/bookings/(:num)/resources/(:num)', 'BookingResource::show/$1/$2');
$routes->delete('api/bookings/(:num)/resources/(:num)', 'BookingResource::delete/$1/$2');
$routes->post('api/bookings/(:num)/resources/(:num)/checkout', 'BookingResource::checkout/$1/$2');
$routes->post('api/bookings/(:num)/resources/(:num)/return', 'BookingResource::returnResource/$1/$2');
$routes->get('api/bookings/(:num)/catering', 'CateringRequest::bookingIndex/$1');
$routes->post('api/bookings/(:num)/catering', 'CateringRequest::create/$1');

// Legacy routes for backward compatibility
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

$routes->get('catering-requests', 'CateringRequest::index');
$routes->get('api/catering-requests/(:num)', 'CateringRequest::show/$1');
$routes->put('api/catering-requests/(:num)', 'CateringRequest::update/$1');
$routes->delete('api/catering-requests/(:num)', 'CateringRequest::delete/$1');
$routes->post('api/catering-requests/(:num)/approve', 'CateringRequest::approve/$1');
$routes->post('api/catering-requests/(:num)/reject', 'CateringRequest::reject/$1');
$routes->post('api/catering-requests/(:num)/complete', 'CateringRequest::complete/$1');


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
|
| /departments            -> Departments frontend page
| /api/departments        -> List departments
| /api/departments/{id}   -> Get one department
| /api/departments        -> Create department
| /api/departments/{id}   -> Update department
| /api/departments/{id}   -> Delete department
|
*/

$routes->get('departments', 'Home::departments');

$routes->get('api/departments', 'Department::index');
$routes->get('api/departments/(:num)', 'Department::show/$1');
$routes->post('api/departments', 'Department::create');
$routes->put('api/departments/(:num)', 'Department::update/$1');
$routes->delete('api/departments/(:num)', 'Department::delete/$1');

// Legacy routes for backward compatibility
$routes->get('departments/(:num)', 'Department::show/$1');
$routes->post('departments', 'Department::create');
$routes->put('departments/(:num)', 'Department::update/$1');
$routes->delete('departments/(:num)', 'Department::delete/$1');

});
