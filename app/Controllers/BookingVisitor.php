<?php

namespace App\Controllers;

use App\Models\AuditLog as AuditLogModel;
use App\Models\Booking as BookingModel;
use App\Models\BookingParticipant as BookingParticipantModel;
use App\Models\BookingVisitor as BookingVisitorModel;
use App\Models\Department as DepartmentModel;
use App\Models\User as UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class BookingVisitor extends BaseController
{
    protected BookingVisitorModel $bookingVisitorModel;
    protected BookingModel $bookingModel;
    protected BookingParticipantModel $bookingParticipantModel;
    protected UserModel $userModel;
    protected DepartmentModel $departmentModel;
    protected AuditLogModel $auditLogModel;

    public function __construct()
    {
        $this->bookingVisitorModel     = new BookingVisitorModel();
        $this->bookingModel            = new BookingModel();
        $this->bookingParticipantModel = new BookingParticipantModel();
        $this->userModel               = new UserModel();
        $this->departmentModel         = new DepartmentModel();
        $this->auditLogModel           = new AuditLogModel();
    }

    /**
     * List all visitors registered for a booking.
     *
     * GET /api/bookings/{booking_id}/visitors
     */
    public function index(int $bookingId): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $booking = $this->bookingModel->find($bookingId);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Booking not found.',
            ]);
        }

        $auth = $this->resolveVisitorAuthorization($currentUserId, $booking);
        if (!$auth['can_view']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to view visitors for this booking.',
            ]);
        }

        $visitors = $this->bookingVisitorModel->getVisitorsForBooking($bookingId, true);

        // Enrich with formatted durations
        $serverNow = time();
        $canManage = (bool) $auth['can_manage'];
        $enriched = array_map(function ($v) use ($booking, $serverNow, $canManage) {
            return $this->formatVisitorRow($v, $booking, $serverNow, $canManage);
        }, $visitors);

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'booking_id'    => $bookingId,
                'can_manage'    => $auth['can_manage'],
                'total_count'   => count($enriched),
                'visitors'      => $enriched,
            ],
        ]);
    }

    /**
     * Get details for a single visitor (IDOR-safe: scoped to booking_id and visitor_id).
     *
     * GET /api/bookings/{booking_id}/visitors/{visitor_id}
     */
    public function show(int $bookingId, int $visitorId): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $booking = $this->bookingModel->find($bookingId);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Booking not found.',
            ]);
        }

        $auth = $this->resolveVisitorAuthorization($currentUserId, $booking);
        if (!$auth['can_view']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to view visitors for this booking.',
            ]);
        }

        $visitor = $this->bookingVisitorModel->getVisitor($bookingId, $visitorId);
        if ($visitor === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Visitor not found for this booking.',
            ]);
        }

        $formatted = $this->formatVisitorRow($visitor, $booking, time(), (bool) $auth['can_manage']);
        $data = $formatted;
        $data['visitor'] = $formatted;

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * Register a new external visitor for a meeting.
     *
     * POST /api/bookings/{booking_id}/visitors
     */
    public function create(int $bookingId): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $booking = $this->bookingModel->find($bookingId);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Booking not found.',
            ]);
        }

        $auth = $this->resolveVisitorAuthorization($currentUserId, $booking);
        if (!$auth['can_manage']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to register visitors for this booking.',
            ]);
        }

        if ($booking['status'] === 'cancelled') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Cannot add visitors to a cancelled booking.',
            ]);
        }

        $raw = $this->request->getJSON(true) ?? $this->request->getPost() ?? [];

        $fullName = trim((string) ($raw['full_name'] ?? $raw['name'] ?? ''));
        $rawEmail = trim((string) ($raw['email'] ?? ''));
        $normEmail = strtolower($rawEmail);
        $phone = trim((string) ($raw['phone'] ?? ''));
        $company = trim((string) ($raw['company'] ?? ''));
        $notes = trim((string) ($raw['notes'] ?? ''));

        // Validation
        $errors = [];
        if ($fullName === '') {
            $errors['full_name'] = 'Visitor full name is required.';
        } elseif (mb_strlen($fullName) < 2) {
            $errors['full_name'] = 'Visitor full name must be at least 2 characters.';
        } elseif (mb_strlen($fullName) > 150) {
            $errors['full_name'] = 'Visitor full name cannot exceed 150 characters.';
        }

        if ($rawEmail === '') {
            $errors['email'] = 'Visitor email address is required.';
        } elseif (!filter_var($normEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please provide a valid email address.';
        } elseif (mb_strlen($normEmail) > 150) {
            $errors['email'] = 'Visitor email cannot exceed 150 characters.';
        }

        if ($phone !== '' && mb_strlen($phone) > 30) {
            $errors['phone'] = 'Phone number cannot exceed 30 characters.';
        }

        if ($company !== '' && mb_strlen($company) > 150) {
            $errors['company'] = 'Company name cannot exceed 150 characters.';
        }

        if ($notes !== '' && mb_strlen($notes) > 1000) {
            $errors['notes'] = 'Notes cannot exceed 1000 characters.';
        }

        if (!empty($errors)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ]);
        }

        // Duplicate check (occurrence-specific)
        $duplicate = $this->bookingVisitorModel->findDuplicate($bookingId, $normEmail);
        if ($duplicate !== null) {
            return $this->response->setStatusCode(409)->setJSON([
                'status'  => 'error',
                'message' => 'A visitor with this email is already registered for this meeting.',
            ]);
        }

        $visitorData = [
            'booking_id'      => $bookingId,
            'full_name'       => $fullName,
            'email'           => $normEmail,
            'phone'           => $phone !== '' ? $phone : null,
            'company'         => $company !== '' ? $company : null,
            'notes'           => $notes !== '' ? $notes : null,
            'status'          => 'expected',
            'check_in_time'   => null,
            'check_out_time'  => null,
            'check_in_method' => 'manual',
        ];

        try {
            $visitorId = $this->bookingVisitorModel->insert($visitorData);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), '1062')) {
                return $this->response->setStatusCode(409)->setJSON([
                    'status'  => 'error',
                    'message' => 'A visitor with this email is already registered for this meeting.',
                ]);
            }
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to register visitor.',
            ]);
        }

        if (!$visitorId) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to register visitor.',
                'errors'  => $this->bookingVisitorModel->errors(),
            ]);
        }

        $created = $this->bookingVisitorModel->find($visitorId);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'booking_visitor_added',
            'table_name' => 'booking_visitors',
            'record_id'  => (int) $visitorId,
            'old_values' => null,
            'new_values' => json_encode([
                'booking_id' => $bookingId,
                'full_name'  => $fullName,
                'email'      => $normEmail,
                'company'    => $company,
                'status'     => 'expected',
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $formatted = $this->formatVisitorRow($created, $booking, time());
        $data = $formatted;
        $data['visitor'] = $formatted;

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'success',
            'message' => 'Visitor registered successfully.',
            'data'    => $data,
        ]);
    }

    /**
     * Update visitor details.
     *
     * PUT /api/bookings/{booking_id}/visitors/{visitor_id}
     */
    public function update(int $bookingId, int $visitorId): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $booking = $this->bookingModel->find($bookingId);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Booking not found.',
            ]);
        }

        $auth = $this->resolveVisitorAuthorization($currentUserId, $booking);
        if (!$auth['can_manage']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to modify visitors for this booking.',
            ]);
        }

        $visitor = $this->bookingVisitorModel->getVisitor($bookingId, $visitorId);
        if ($visitor === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Visitor not found for this booking.',
            ]);
        }

        $raw = $this->request->getJSON(true) ?? $this->request->getRawInput() ?? [];

        // Disallow direct manipulation of protected lifecycle fields
        unset($raw['id'], $raw['booking_id'], $raw['status'], $raw['check_in_time'], $raw['check_out_time'], $raw['check_in_method']);

        $updateData = [];
        $errors = [];

        if (array_key_exists('full_name', $raw) || array_key_exists('name', $raw)) {
            $nameVal = trim((string) ($raw['full_name'] ?? $raw['name'] ?? ''));
            if ($nameVal === '') {
                $errors['full_name'] = 'Visitor full name is required.';
            } elseif (mb_strlen($nameVal) < 2) {
                $errors['full_name'] = 'Visitor full name must be at least 2 characters.';
            } elseif (mb_strlen($nameVal) > 150) {
                $errors['full_name'] = 'Visitor full name cannot exceed 150 characters.';
            } else {
                $updateData['full_name'] = $nameVal;
            }
        }

        if (array_key_exists('email', $raw)) {
            $rawEmail = trim((string) $raw['email']);
            $normEmail = strtolower($rawEmail);
            if ($normEmail === '') {
                $errors['email'] = 'Visitor email address is required.';
            } elseif (!filter_var($normEmail, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Please provide a valid email address.';
            } elseif (mb_strlen($normEmail) > 150) {
                $errors['email'] = 'Visitor email cannot exceed 150 characters.';
            } else {
                // Duplicate check against other visitors of this booking
                $dup = $this->bookingVisitorModel->findDuplicate($bookingId, $normEmail, $visitorId);
                if ($dup !== null) {
                    return $this->response->setStatusCode(409)->setJSON([
                        'status'  => 'error',
                        'message' => 'Another visitor with this email is already registered for this meeting.',
                    ]);
                }
                $updateData['email'] = $normEmail;
            }
        }

        if (array_key_exists('phone', $raw)) {
            $phoneVal = trim((string) $raw['phone']);
            if ($phoneVal !== '' && mb_strlen($phoneVal) > 30) {
                $errors['phone'] = 'Phone number cannot exceed 30 characters.';
            } else {
                $updateData['phone'] = $phoneVal !== '' ? $phoneVal : null;
            }
        }

        if (array_key_exists('company', $raw)) {
            $compVal = trim((string) $raw['company']);
            if ($compVal !== '' && mb_strlen($compVal) > 150) {
                $errors['company'] = 'Company name cannot exceed 150 characters.';
            } else {
                $updateData['company'] = $compVal !== '' ? $compVal : null;
            }
        }

        if (array_key_exists('notes', $raw)) {
            $notesVal = trim((string) $raw['notes']);
            if ($notesVal !== '' && mb_strlen($notesVal) > 1000) {
                $errors['notes'] = 'Notes cannot exceed 1000 characters.';
            } else {
                $updateData['notes'] = $notesVal !== '' ? $notesVal : null;
            }
        }

        if (!empty($errors)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Validation failed.',
                'errors'  => $errors,
            ]);
        }

        if (empty($updateData)) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'No changes to update.',
                'data'    => $this->formatVisitorRow($visitor, $booking, time()),
            ]);
        }

        $oldValues = array_intersect_key($visitor, $updateData);

        try {
            $updateSuccess = $this->bookingVisitorModel->update($visitorId, $updateData);
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), '1062')) {
                return $this->response->setStatusCode(409)->setJSON([
                    'status'  => 'error',
                    'message' => 'Another visitor with this email is already registered for this meeting.',
                ]);
            }
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to update visitor.',
            ]);
        }

        if (!$updateSuccess) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to update visitor.',
                'errors'  => $this->bookingVisitorModel->errors(),
            ]);
        }

        $updated = $this->bookingVisitorModel->find($visitorId);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'booking_visitor_updated',
            'table_name' => 'booking_visitors',
            'record_id'  => $visitorId,
            'old_values' => json_encode($oldValues),
            'new_values' => json_encode($updateData),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $formatted = $this->formatVisitorRow($updated, $booking, time());
        $data = $formatted;
        $data['visitor'] = $formatted;

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Visitor updated successfully.',
            'data'    => $data,
        ]);
    }

    /**
     * Remove / cancel a visitor from a booking.
     *
     * DELETE /api/bookings/{booking_id}/visitors/{visitor_id}
     */
    public function delete(int $bookingId, int $visitorId): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $booking = $this->bookingModel->find($bookingId);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Booking not found.',
            ]);
        }

        $auth = $this->resolveVisitorAuthorization($currentUserId, $booking);
        if (!$auth['can_manage']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to remove visitors from this booking.',
            ]);
        }

        $visitor = $this->bookingVisitorModel->getVisitor($bookingId, $visitorId);
        if ($visitor === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Visitor not found for this booking.',
            ]);
        }

        if ($visitor['status'] === 'cancelled') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Visitor is already cancelled.',
            ]);
        }

        if ($visitor['status'] !== 'expected') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Cannot cancel a visitor who has already checked in or checked out.',
            ]);
        }

        // Safe lifecycle cancellation: mark as cancelled to preserve historical check-in audit
        $updateSuccess = $this->bookingVisitorModel->update($visitorId, [
            'status' => 'cancelled',
        ]);

        if (!$updateSuccess) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to remove visitor.',
            ]);
        }

        $cancelled = $this->bookingVisitorModel->find($visitorId);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'booking_visitor_removed',
            'table_name' => 'booking_visitors',
            'record_id'  => $visitorId,
            'old_values' => json_encode(['status' => $visitor['status']]),
            'new_values' => json_encode(['status' => 'cancelled']),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $formatted = $this->formatVisitorRow($cancelled, $booking, time());
        $data = $formatted;
        $data['visitor'] = $formatted;

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Visitor removed from booking successfully.',
            'data'    => $data,
        ]);
    }

    /**
     * Check in an external visitor.
     *
     * POST /api/bookings/{booking_id}/visitors/{visitor_id}/check-in
     */
    public function checkIn(int $bookingId, int $visitorId): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $booking = $this->bookingModel->find($bookingId);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Booking not found.',
            ]);
        }

        $auth = $this->resolveVisitorAuthorization($currentUserId, $booking);
        if (!$auth['can_manage']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to check in visitors for this booking.',
            ]);
        }

        $visitor = $this->bookingVisitorModel->getVisitor($bookingId, $visitorId);
        if ($visitor === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Visitor not found for this booking.',
            ]);
        }

        // Booking status check
        if ($booking['status'] !== 'approved') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => "Cannot check in visitor for a booking with status '{$booking['status']}'. Only approved bookings can be checked into.",
            ]);
        }

        // Time window check: start_time - 15 mins through end_time
        $now = time();
        $startTime = strtotime($booking['start_time']);
        $endTime   = strtotime($booking['end_time']);
        $checkinWindowStart = $startTime - (15 * 60);

        if ($now < $checkinWindowStart) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Check-in is not yet open. Check-in opens 15 minutes before the meeting start time.',
            ]);
        }

        if ($now > $endTime) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Check-in has closed. This meeting has already ended.',
            ]);
        }

        // State check
        if ($visitor['status'] === 'cancelled') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Cannot check in a cancelled visitor.',
            ]);
        }

        if ($visitor['status'] === 'checked_in') {
            return $this->response->setStatusCode(409)->setJSON([
                'status'  => 'error',
                'message' => 'Visitor is already checked in to this meeting.',
                'data'    => $this->formatVisitorRow($visitor, $booking, $now),
            ]);
        }

        if ($visitor['status'] === 'checked_out') {
            return $this->response->setStatusCode(409)->setJSON([
                'status'  => 'error',
                'message' => 'Visitor has already completed attendance and checked out.',
                'data'    => $this->formatVisitorRow($visitor, $booking, $now),
            ]);
        }

        $raw = $this->request->getJSON(true) ?? $this->request->getPost() ?? [];
        $method = 'manual';
        if (!empty($raw['check_in_method']) && in_array($raw['check_in_method'], ['manual', 'reception'], true)) {
            $method = $raw['check_in_method'];
        }

        $serverNow = date('Y-m-d H:i:s');
        $updateSuccess = $this->bookingVisitorModel->update($visitorId, [
            'status'          => 'checked_in',
            'check_in_time'   => $serverNow,
            'check_in_method' => $method,
        ]);

        if (!$updateSuccess) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to record visitor check-in.',
                'errors'  => $this->bookingVisitorModel->errors(),
            ]);
        }

        $updated = $this->bookingVisitorModel->find($visitorId);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'booking_visitor_checked_in',
            'table_name' => 'booking_visitors',
            'record_id'  => $visitorId,
            'old_values' => json_encode([
                'status'        => $visitor['status'],
                'check_in_time' => $visitor['check_in_time'],
            ]),
            'new_values' => json_encode([
                'status'          => 'checked_in',
                'check_in_time'   => $serverNow,
                'check_in_method' => $method,
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => $serverNow,
        ]);

        $formatted = $this->formatVisitorRow($updated, $booking, $now);
        $data = $formatted;
        $data['visitor'] = $formatted;

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Visitor checked in successfully.',
            'data'    => $data,
        ]);
    }

    /**
     * Check out an active external visitor.
     *
     * POST /api/bookings/{booking_id}/visitors/{visitor_id}/check-out
     */
    public function checkOut(int $bookingId, int $visitorId): ResponseInterface
    {
        $session = service('session');
        $currentUserId = (int) $session->get('user_id');
        if (empty($currentUserId)) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        $booking = $this->bookingModel->find($bookingId);
        if ($booking === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Booking not found.',
            ]);
        }

        $auth = $this->resolveVisitorAuthorization($currentUserId, $booking);
        if (!$auth['can_manage']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. You do not have permission to check out visitors for this booking.',
            ]);
        }

        $visitor = $this->bookingVisitorModel->getVisitor($bookingId, $visitorId);
        if ($visitor === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Visitor not found for this booking.',
            ]);
        }

        if ($visitor['status'] === 'checked_out') {
            return $this->response->setStatusCode(409)->setJSON([
                'status'  => 'error',
                'message' => 'Visitor is already checked out.',
                'data'    => $this->formatVisitorRow($visitor, $booking, time()),
            ]);
        }

        if ($visitor['status'] !== 'checked_in') {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Cannot check out visitor who is not currently checked in.',
            ]);
        }

        $serverNow = date('Y-m-d H:i:s');
        $updateSuccess = $this->bookingVisitorModel->update($visitorId, [
            'status'         => 'checked_out',
            'check_out_time' => $serverNow,
        ]);

        if (!$updateSuccess) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to record visitor check-out.',
                'errors'  => $this->bookingVisitorModel->errors(),
            ]);
        }

        $updated = $this->bookingVisitorModel->find($visitorId);

        // Audit log
        $this->auditLogModel->insert([
            'user_id'    => $currentUserId,
            'action'     => 'booking_visitor_checked_out',
            'table_name' => 'booking_visitors',
            'record_id'  => $visitorId,
            'old_values' => json_encode([
                'status'         => $visitor['status'],
                'check_out_time' => $visitor['check_out_time'],
            ]),
            'new_values' => json_encode([
                'status'         => 'checked_out',
                'check_out_time' => $serverNow,
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => (string) $this->request->getUserAgent(),
            'created_at' => $serverNow,
        ]);

        $formatted = $this->formatVisitorRow($updated, $booking, time());
        $data = $formatted;
        $data['visitor'] = $formatted;

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Visitor checked out successfully.',
            'data'    => $data,
        ]);
    }

    /**
     * Resolve permissions for viewing and managing visitors for a booking.
     *
     * @param int $userId
     * @param array $booking
     * @return array{can_view: bool, can_manage: bool}
     */
    protected function resolveVisitorAuthorization(int $userId, array $booking): array
    {
        if (empty($userId)) {
            return ['can_view' => false, 'can_manage' => false];
        }

        $organizerId = (int) $booking['user_id'];
        $organizer = $this->userModel->find($organizerId);

        $currentUserRecord = $this->userModel->find($userId);
        $currentUserRoleName = (string) (service('session')->get('role_name') ?? '');
        if (empty($currentUserRoleName) && !empty($currentUserRecord['role_id'])) {
            $db = \Config\Database::connect();
            $roleRow = $db->table('roles')->where('id', $currentUserRecord['role_id'])->get()->getRowArray();
            if ($roleRow) {
                $currentUserRoleName = $roleRow['name'];
            }
        }

        $currentUserDeptId = !empty($currentUserRecord['department_id']) ? (int) $currentUserRecord['department_id'] : null;
        $organizerDeptId   = !empty($organizer['department_id']) ? (int) $organizer['department_id'] : null;

        // 1. Full management: Organizer, Admin, Facilities Manager
        if ($userId === $organizerId || in_array($currentUserRoleName, ['Admin', 'Facilities Manager'], true)) {
            return ['can_view' => true, 'can_manage' => true];
        }

        // 2. Full management: Department Manager of the same department
        if ($currentUserRoleName === 'Manager') {
            if ($currentUserDeptId !== null && $organizerDeptId !== null && $currentUserDeptId === $organizerDeptId) {
                return ['can_view' => true, 'can_manage' => true];
            }
        }

        // 3. Read-only: Invited booking participant (response_status != declined)
        $participant = $this->bookingParticipantModel
            ->where('booking_id', (int) $booking['id'])
            ->where('user_id', $userId)
            ->first();

        if ($participant !== null && ($participant['response_status'] ?? '') !== 'declined') {
            return ['can_view' => true, 'can_manage' => false];
        }

        // 4. Denied: Unrelated employee, declined participant, foreign department manager
        return ['can_view' => false, 'can_manage' => false];
    }

    /**
     * Format a visitor row with computed durations and late detection.
     *
     * @param array $v
     * @param array $booking
     * @param int $nowTs
     * @return array
     */
    protected function formatVisitorRow(array $v, array $booking, int $nowTs, bool $canManage = true): array
    {
        $checkInTime  = $v['check_in_time'];
        $checkOutTime = $v['check_out_time'];
        $status       = $v['status'];

        $isLate = false;
        if (!empty($checkInTime) && !empty($booking['start_time'])) {
            $isLate = (strtotime($checkInTime) > strtotime($booking['start_time']));
        }

        $durationInfo = $this->computeDuration($checkInTime, $checkOutTime, $status, $nowTs);

        return [
            'id'                 => (int) $v['id'],
            'booking_id'         => (int) $v['booking_id'],
            'full_name'          => $v['full_name'],
            'email'              => $v['email'],
            'phone'              => $canManage ? $v['phone'] : null,
            'company'            => $v['company'],
            'notes'              => $canManage ? $v['notes'] : null,
            'status'             => $v['status'],
            'check_in_time'      => $v['check_in_time'],
            'check_out_time'     => $v['check_out_time'],
            'check_in_method'    => $v['check_in_method'],
            'is_late'            => $isLate,
            'duration_minutes'   => $durationInfo['minutes'],
            'duration_formatted' => $durationInfo['formatted'],
            'created_at'         => $v['created_at'],
            'updated_at'         => $v['updated_at'],
        ];
    }

    /**
     * Compute duration in minutes and human string.
     */
    protected function computeDuration(?string $checkInTime, ?string $checkOutTime, string $status, int $nowTs): array
    {
        if (empty($checkInTime) || $status === 'expected' || $status === 'cancelled') {
            return ['minutes' => null, 'formatted' => null];
        }

        $inTs = strtotime($checkInTime);
        if ($inTs === false) {
            return ['minutes' => null, 'formatted' => null];
        }

        if (!empty($checkOutTime)) {
            $outTs = strtotime($checkOutTime);
            $diffSec = max(0, ($outTs !== false ? $outTs - $inTs : 0));
            $minutes = (int) round($diffSec / 60);

            return [
                'minutes'   => $minutes,
                'formatted' => $this->formatMinutesHuman($minutes),
            ];
        }

        if ($status === 'checked_in') {
            $diffSec = max(0, $nowTs - $inTs);
            $minutes = (int) round($diffSec / 60);

            return [
                'minutes'   => $minutes,
                'formatted' => 'In progress (' . $this->formatMinutesHuman($minutes) . ')',
            ];
        }

        return ['minutes' => 0, 'formatted' => '0 mins'];
    }

    /**
     * Format minutes into human-readable string.
     */
    protected function formatMinutesHuman(int $minutes): string
    {
        if ($minutes < 0) {
            $minutes = 0;
        }
        if ($minutes < 60) {
            return $minutes . ' mins';
        }

        $hours = intdiv($minutes, 60);
        $remMinutes = $minutes % 60;
        $hrStr = $hours === 1 ? '1 hr' : "{$hours} hrs";

        if ($remMinutes === 0) {
            return $hrStr;
        }

        return "{$hrStr} {$remMinutes} mins";
    }
}
