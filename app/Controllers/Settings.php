<?php

namespace App\Controllers;

use App\Models\Setting;
use CodeIgniter\HTTP\ResponseInterface;

class Settings extends BaseController
{
    protected Setting $settingModel;

    public function __construct()
    {
        $this->settingModel = new Setting();
    }

    /**
     * Authoritative Admin check.
     */
    protected function isAdminUser(): bool
    {
        $session = service('session');
        $userId = (int) ($session->get('user_id') ?? 0);
        if ($userId <= 0) {
            return false;
        }

        $roleName = (string) ($session->get('role') ?? '');
        $roleId = (int) ($session->get('role_id') ?? 0);

        if (strcasecmp($roleName, 'Admin') === 0 || $roleId === 1) {
            return true;
        }

        // Authoritative DB verification fallback
        $db = \Config\Database::connect();
        $user = $db->table('users')
                   ->select('users.id, roles.name as role_name')
                   ->join('roles', 'roles.id = users.role_id', 'left')
                   ->where('users.id', $userId)
                   ->get()
                   ->getRowArray();

        if ($user && strcasecmp((string) ($user['role_name'] ?? ''), 'Admin') === 0) {
            $session->set('role', 'Admin');
            return true;
        }

        return false;
    }

    /**
     * Check if incoming request expects JSON.
     */
    protected function isJsonRequest(): bool
    {
        if ($this->request->isAJAX()) {
            return true;
        }

        $acceptHeader = $this->request->getHeaderLine('Accept');
        if (str_contains($acceptHeader, 'application/json')) {
            return true;
        }

        $contentType = $this->request->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve non-sensitive email configuration from Config\Email (.env backed).
     */
    protected function getEmailSettings(): array
    {
        $emailConfig = config(\Config\Email::class);

        $smtpUser = (string) ($emailConfig->SMTPUser ?? '');
        $smtpPass = (string) ($emailConfig->SMTPPass ?? '');

        // Mask email username safely if configured
        $userStatus = 'Not configured';
        if ($smtpUser !== '') {
            $parts = explode('@', $smtpUser);
            if (count($parts) === 2 && strlen($parts[0]) > 2) {
                $masked = substr($parts[0], 0, 2) . '***@' . $parts[1];
                $userStatus = "Configured ({$masked})";
            } else {
                $userStatus = 'Configured';
            }
        }

        return [
            'smtp_host'        => (string) ($emailConfig->SMTPHost ?? 'smtp-relay.brevo.com'),
            'smtp_port'        => (int) ($emailConfig->SMTPPort ?? 587),
            'smtp_crypto'      => strtoupper((string) ($emailConfig->SMTPCrypto ?? 'tls')),
            'from_email'       => (string) ($emailConfig->fromEmail ?? 'notifications@meetspace.local'),
            'from_name'        => (string) ($emailConfig->fromName ?? 'MeetSpace Enterprise Suite'),
            'smtp_user_status' => $userStatus,
            'smtp_pass_status' => ($smtpPass !== '') ? 'Configured & Protected' : 'Not configured',
        ];
    }

    /**
     * Retrieve system diagnostics and environment parameters.
     */
    protected function getSystemInfo(): array
    {
        $db = \Config\Database::connect();
        $dbDriver = $db->DBDriver ?? 'MySQLi';
        $dbPort = $db->port ?? 3308;
        $dbName = $db->database ?? 'demerg_meeting_db';

        $dbStatus = 'Connected';
        $dbVersion = 'MySQL 8.4';
        try {
            $versionRow = $db->query('SELECT VERSION() as v')->getRowArray();
            if (!empty($versionRow['v'])) {
                $dbVersion = 'MySQL ' . $versionRow['v'];
            }
        } catch (\Throwable $e) {
            $dbStatus = 'Connection error: ' . $e->getMessage();
        }

        return [
            'application' => 'MeetSpace Enterprise Suite',
            'framework'   => 'CodeIgniter ' . \CodeIgniter\CodeIgniter::CI_VERSION,
            'environment' => ucfirst(defined('ENVIRONMENT') ? ENVIRONMENT : 'development'),
            'php_version' => PHP_VERSION,
            'database'    => "{$dbDriver} - {$dbVersion} (Port {$dbPort}, DB: {$dbName})",
            'db_status'   => $dbStatus,
            'server_time' => date('Y-m-d H:i:s T'),
        ];
    }

    /**
     * Serve Settings Page (HTML) or Settings Data (JSON).
     *
     * GET /settings
     * GET /admin/settings
     * GET /api/settings
     */
    public function index(): ResponseInterface|string
    {
        $session = service('session');
        $userId = (int) ($session->get('user_id') ?? 0);

        // HTML Browser Request
        if (!$this->isJsonRequest()) {
            if ($userId <= 0) {
                return redirect()->to('/login');
            }

            if (!$this->isAdminUser()) {
                $session->setFlashdata('error', 'Access denied. Administrator privileges required to access Settings.');
                return redirect()->to('/');
            }

            $settings = $this->settingModel->getAllKeyValue();
            $email = $this->getEmailSettings();
            $system = $this->getSystemInfo();

            return view('settings/index', [
                'title'     => 'Settings & Configuration',
                'settings'  => $settings,
                'email'     => $email,
                'system'    => $system,
                'timezones' => [
                    'Asia/Kolkata'        => 'Asia/Kolkata (IST +05:30)',
                    'UTC'                 => 'UTC (Coordinated Universal Time)',
                    'America/New_York'    => 'America/New_York (EST/EDT -05:00/-04:00)',
                    'America/Chicago'     => 'America/Chicago (CST/CDT -06:00/-05:00)',
                    'America/Los_Angeles' => 'America/Los_Angeles (PST/PDT -08:00/-07:00)',
                    'Europe/London'       => 'Europe/London (GMT/BST +00:00/+01:00)',
                    'Europe/Berlin'       => 'Europe/Berlin (CET/CEST +01:00/+02:00)',
                    'Asia/Dubai'          => 'Asia/Dubai (GST +04:00)',
                    'Asia/Singapore'      => 'Asia/Singapore (SGT +08:00)',
                    'Asia/Tokyo'          => 'Asia/Tokyo (JST +09:00)',
                    'Australia/Sydney'    => 'Australia/Sydney (AEST/AEDT +10:00/+11:00)',
                ],
            ]);
        }

        // JSON Request
        if ($userId <= 0) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        if (!$this->isAdminUser()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. Administrator privileges required.',
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => [
                'settings' => $this->settingModel->getAllKeyValue(),
                'email'    => $this->getEmailSettings(),
                'system'   => $this->getSystemInfo(),
            ],
        ]);
    }

    /**
     * Update application settings (Admin only).
     *
     * POST /api/settings
     * PUT  /api/settings
     */
    public function update(): ResponseInterface
    {
        $session = service('session');
        $userId = (int) ($session->get('user_id') ?? 0);

        if ($userId <= 0) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 'error',
                'message' => 'Unauthorized. Authentication required.',
            ]);
        }

        if (!$this->isAdminUser()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden. Administrator privileges required.',
            ]);
        }

        // Retrieve payload (JSON or Form POST)
        $input = $this->request->getJSON(true);
        if (empty($input)) {
            $input = $this->request->getPost();
        }

        $rules = [
            'app_name'                 => 'required|min_length[2]|max_length[100]',
            'app_timezone'             => 'required',
            'default_meeting_duration' => 'required|is_natural_no_zero|greater_than_equal_to[5]|less_than_equal_to[480]',
            'max_meeting_duration'     => 'required|is_natural_no_zero|greater_than_equal_to[15]|less_than_equal_to[1440]',
            'booking_buffer_time'      => 'required|is_natural|greater_than_equal_to[0]|less_than_equal_to[120]',
        ];

        $messages = [
            'app_name' => [
                'required'   => 'Application Name is required.',
                'min_length' => 'Application Name must be at least 2 characters.',
                'max_length' => 'Application Name cannot exceed 100 characters.',
            ],
            'app_timezone' => [
                'required' => 'Application Timezone is required.',
            ],
            'default_meeting_duration' => [
                'required'              => 'Default Meeting Duration is required.',
                'is_natural_no_zero'    => 'Default Meeting Duration must be a positive integer.',
                'greater_than_equal_to' => 'Default Meeting Duration must be at least 5 minutes.',
                'less_than_equal_to'    => 'Default Meeting Duration cannot exceed 480 minutes (8 hours).',
            ],
            'max_meeting_duration' => [
                'required'              => 'Maximum Meeting Duration is required.',
                'is_natural_no_zero'    => 'Maximum Meeting Duration must be a positive integer.',
                'greater_than_equal_to' => 'Maximum Meeting Duration must be at least 15 minutes.',
                'less_than_equal_to'    => 'Maximum Meeting Duration cannot exceed 1440 minutes (24 hours).',
            ],
            'booking_buffer_time' => [
                'required'              => 'Booking Buffer Time is required.',
                'is_natural'            => 'Booking Buffer Time must be 0 or greater.',
                'greater_than_equal_to' => 'Booking Buffer Time cannot be negative.',
                'less_than_equal_to'    => 'Booking Buffer Time cannot exceed 120 minutes (2 hours).',
            ],
        ];

        if (!$this->validateData($input, $rules, $messages)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Please correct the validation errors.',
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        // Logical validation: max_meeting_duration >= default_meeting_duration
        $defaultDur = (int) $input['default_meeting_duration'];
        $maxDur = (int) $input['max_meeting_duration'];
        if ($maxDur < $defaultDur) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Maximum meeting duration must be greater than or equal to the default duration.',
                'errors'  => [
                    'max_meeting_duration' => 'Maximum meeting duration must be greater than or equal to the default meeting duration (' . $defaultDur . ' mins).',
                ],
            ]);
        }

        // Timezone validation
        if (!in_array($input['app_timezone'], \DateTimeZone::listIdentifiers(), true)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'The selected timezone is invalid.',
                'errors'  => ['app_timezone' => 'Please select a valid IANA timezone identifier.'],
            ]);
        }

        // Prepare settings map
        $keysToUpdate = [
            'app_name'                 => trim((string) $input['app_name']),
            'app_timezone'             => (string) $input['app_timezone'],
            'default_meeting_duration' => (string) $defaultDur,
            'max_meeting_duration'     => (string) $maxDur,
            'booking_buffer_time'      => (string) ((int) $input['booking_buffer_time']),
            'allow_recurring_meetings' => !empty($input['allow_recurring_meetings']) ? '1' : '0',
            'require_booking_approval' => !empty($input['require_booking_approval']) ? '1' : '0',
            'allow_user_cancellation'  => !empty($input['allow_user_cancellation']) ? '1' : '0',
            'allow_user_rescheduling'  => !empty($input['allow_user_rescheduling']) ? '1' : '0',
        ];

        foreach ($keysToUpdate as $key => $val) {
            $this->settingModel->setSetting($key, $val);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Settings saved successfully.',
            'data'    => $this->settingModel->getAllKeyValue(),
        ]);
    }
}
