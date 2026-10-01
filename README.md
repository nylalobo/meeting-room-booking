# MeetSpace Enterprise Suite

A centralized web-based meeting room booking and management system built to simplify room availability, reservations, approvals, participants, and administrative management.

## Overview

MeetSpace Enterprise Suite provides a centralized platform for organizations to manage meeting rooms, equipment, facilities, users, and bookings from a single application.

The system supports secure authentication, role-based access control, room availability management, booking workflows, approval processes, recurring meetings, participant management, and administrative configuration.

## Key Features

### Authentication & Security
- User registration and login
- Email OTP verification
- Secure password hashing
- Forgot password and email-based password reset
- Session-based authentication
- CSRF protection
- Server-side validation
- Role-based access control
- Authorization checks for protected operations
- Audit logging

### Meeting Room Management
- Location management
- Room management
- Room capacity and facility information
- Equipment management
- Room availability checking
- Double-booking prevention
- Booking conflict detection

### Booking Management
- Create, update and cancel bookings
- Booking approval workflow
- Recurring meetings
- Participant management
- Booking resources/equipment
- Calendar view
- Booking status management

### Administration
- User management
- User role management
- Department management
- Location management
- Room management
- Facilities management
- Equipment management
- Application settings
- Audit logs

### User Experience
- Responsive Bootstrap interface
- Light and dark themes
- AJAX-based interactions
- Select2 searchable dropdowns
- Server-side pagination
- Search and filtering
- Dashboard statistics
- Subtle UI animations

## Technology Stack

- **Backend:** PHP 8.x, CodeIgniter 4
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, Bootstrap 5
- **JavaScript:** JavaScript, AJAX
- **UI Components:** Select2
- **Email:** SMTP / Brevo
- **Testing:** CodeIgniter automated tests and browser-based testing

## Project Structure
meeting-room-booking/
├── app/            # Application source code
├── public/         # Public assets, CSS and JavaScript
├── tests/          # Automated tests
├── database/       # Database-related files
├── docs/           # Project documentation
├── writable/       # CodeIgniter runtime files
├── .env.example    # Environment configuration template
├── composer.json   # PHP dependencies
└── README.md       # Project documentation