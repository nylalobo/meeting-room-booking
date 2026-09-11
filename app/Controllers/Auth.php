<?php

namespace App\Controllers;

use App\Models\Department as DepartmentModel;
use App\Models\Role as RoleModel;
use App\Models\User as UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class Auth extends BaseController
{
    protected UserModel $userModel;
    protected RoleModel $roleModel;
    protected DepartmentModel $departmentModel;

    public function __construct()
    {
        $this->userModel       = new UserModel();
        $this->roleModel       = new RoleModel();
        $this->departmentModel = new DepartmentModel();
    }

    /**
     * Render the login page.
     * If user is already authenticated, redirect to dashboard.
     */
    public function login(): string|ResponseInterface
    {
        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            return redirect()->to('/');
        }

        return view('auth/login', [
            'title'   => 'Sign In',
            'error'   => $this->session->getFlashdata('error'),
            'success' => $this->session->getFlashdata('success'),
        ]);
    }

    /**
     * Process login form submission.
     */
    public function attemptLogin(): ResponseInterface
    {
        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            return redirect()->to('/');
        }

        $email    = trim((string) ($this->request->getPost('email') ?? ''));
        $password = (string) ($this->request->getPost('password') ?? '');

        // Support JSON request payloads if called via API
        if (empty($email) && empty($password) && $this->request->is('json')) {
            $json     = $this->request->getJSON(true) ?? [];
            $email    = trim((string) ($json['email'] ?? ''));
            $password = (string) ($json['password'] ?? '');
        }

        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required',
        ];

        $messages = [
            'email' => [
                'required'    => 'Email address is required.',
                'valid_email' => 'Please provide a valid email address.',
            ],
            'password' => [
                'required' => 'Password is required.',
            ],
        ];

        $data = [
            'email'    => $email,
            'password' => $password,
        ];

        if (!$this->validateData($data, $rules, $messages)) {
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'status' => 'error',
                        'errors' => $this->validator->getErrors(),
                    ]);
            }

            return redirect()->to('/login')
                ->withInput()
                ->with('error', 'Please enter a valid email and password.');
        }

        // Generic authentication failure message (avoids user enumeration)
        $genericError = 'Invalid email or password.';

        // Look up user by email
        $user = $this->userModel
            ->where('email', $email)
            ->first();

        if ($user === null) {
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(401)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => $genericError,
                    ]);
            }

            return redirect()->to('/login')
                ->withInput()
                ->with('error', $genericError);
        }

        // Check active status
        if (empty($user['is_active']) || (int) $user['is_active'] !== 1) {
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(401)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => $genericError,
                    ]);
            }

            return redirect()->to('/login')
                ->withInput()
                ->with('error', $genericError);
        }

        // Verify password against stored bcrypt hash
        if (!password_verify($password, $user['password_hash'])) {
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(401)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => $genericError,
                    ]);
            }

            return redirect()->to('/login')
                ->withInput()
                ->with('error', $genericError);
        }

        // Check email verification status
        if (empty($user['email_verified_at'])) {
            $unverifiedError = 'Please verify your email address before signing in. Check your inbox for the verification link.';

            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(403)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => $unverifiedError,
                    ]);
            }

            return redirect()->to('/login')
                ->withInput()
                ->with('error', $unverifiedError);
        }

        // Regenerate session ID and destroy the old session to prevent session fixation
        $this->session->regenerate(true);

        // Fetch role name from Role model
        $roleName = 'User';
        if (!empty($user['role_id'])) {
            $role = $this->roleModel->find($user['role_id']);
            if ($role && !empty($role['name'])) {
                $roleName = $role['name'];
            }
        }

        // Store only safe identity information in session
        $this->session->set([
            'user_id'       => (int) $user['id'],
            'email'         => $user['email'],
            'first_name'    => $user['first_name'],
            'last_name'     => $user['last_name'],
            'role_id'       => !empty($user['role_id']) ? (int) $user['role_id'] : null,
            'role_name'     => $roleName,
            'department_id' => !empty($user['department_id']) ? (int) $user['department_id'] : null,
            'isLoggedIn'    => true,
        ]);

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Logged in successfully.',
                'data'    => [
                    'user_id'       => (int) $user['id'],
                    'email'         => $user['email'],
                    'first_name'    => $user['first_name'],
                    'last_name'     => $user['last_name'],
                    'role_id'       => !empty($user['role_id']) ? (int) $user['role_id'] : null,
                    'role_name'     => $roleName,
                    'department_id' => !empty($user['department_id']) ? (int) $user['department_id'] : null,
                ],
            ]);
        }

        return redirect()->to('/');
    }

    /**
     * Render registration page.
     */
    public function register(): string|ResponseInterface
    {
        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            return redirect()->to('/');
        }

        $allowedRoles = $this->roleModel
            ->whereIn('name', UserModel::PUBLIC_REGISTRATION_ROLE_NAMES)
            ->orderBy('name', 'ASC')
            ->findAll();

        $departments = $this->departmentModel
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('auth/register', [
            'title'        => 'Create Account',
            'allowedRoles' => $allowedRoles,
            'departments'  => $departments,
            'error'        => $this->session->getFlashdata('error'),
            'errors'       => $this->session->getFlashdata('errors') ?? [],
        ]);
    }

    /**
     * Process registration submission.
     */
    public function attemptRegister(): ResponseInterface
    {
        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            return redirect()->to('/');
        }

        $data = [
            'first_name'       => trim((string) ($this->request->getPost('first_name') ?? '')),
            'last_name'        => trim((string) ($this->request->getPost('last_name') ?? '')),
            'email'            => trim((string) ($this->request->getPost('email') ?? '')),
            'phone'            => trim((string) ($this->request->getPost('phone') ?? '')),
            'role_id'          => $this->request->getPost('role_id'),
            'department_id'    => $this->request->getPost('department_id'),
            'password'         => (string) ($this->request->getPost('password') ?? ''),
            'password_confirm' => (string) ($this->request->getPost('password_confirm') ?? ''),
        ];

        // Support JSON request payloads
        if (empty($data['email']) && empty($data['password']) && $this->request->is('json')) {
            $json = $this->request->getJSON(true) ?? [];
            $data = [
                'first_name'       => trim((string) ($json['first_name'] ?? '')),
                'last_name'        => trim((string) ($json['last_name'] ?? '')),
                'email'            => trim((string) ($json['email'] ?? '')),
                'phone'            => trim((string) ($json['phone'] ?? '')),
                'role_id'          => $json['role_id'] ?? null,
                'department_id'    => $json['department_id'] ?? null,
                'password'         => (string) ($json['password'] ?? ''),
                'password_confirm' => (string) ($json['password_confirm'] ?? ''),
            ];
        }

        // Clean up empty phone / department strings for validation
        if ($data['phone'] === '') {
            $data['phone'] = null;
        }
        if ($data['department_id'] === '' || $data['department_id'] === '0') {
            $data['department_id'] = null;
        }

        if (!$this->validateData($data, $this->userModel->registrationRules, $this->userModel->registrationMessages)) {
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'status' => 'error',
                        'errors' => $this->validator->getErrors(),
                    ]);
            }

            return redirect()->to('/register')
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Server-side security check: strictly enforce public registration role whitelist
        $submittedRoleId = (int) $data['role_id'];
        $safeRoles = $this->roleModel
            ->whereIn('name', UserModel::PUBLIC_REGISTRATION_ROLE_NAMES)
            ->findAll();
        $safeRoleIds = array_map(static fn($r) => (int) $r['id'], $safeRoles);

        if (!in_array($submittedRoleId, $safeRoleIds, true)) {
            $roleError = ['role_id' => 'The selected role is not authorized for public registration.'];
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'status' => 'error',
                        'errors' => $roleError,
                    ]);
            }

            return redirect()->to('/register')
                ->withInput()
                ->with('errors', $roleError);
        }

        // Validate department existence if provided
        $departmentId = null;
        if (!empty($data['department_id'])) {
            $deptCheck = $this->departmentModel->find((int) $data['department_id']);
            if ($deptCheck === null) {
                $deptError = ['department_id' => 'The selected department does not exist.'];
                if ($this->isJsonRequest()) {
                    return $this->response
                        ->setStatusCode(422)
                        ->setJSON([
                            'status' => 'error',
                            'errors' => $deptError,
                        ]);
                }

                return redirect()->to('/register')
                    ->withInput()
                    ->with('errors', $deptError);
            }
            $departmentId = (int) $data['department_id'];
        }

        $phone       = !empty($data['phone']) ? $data['phone'] : null;
        $email       = strtolower($data['email']);
        $firstName   = $data['first_name'];
        $lastName    = $data['last_name'];
        $password    = $data['password'];

        $successNotice = 'Registration successful! Please check your email inbox to verify your account before signing in.';

        // Check for existing account (anti-enumeration: don't reveal existence)
        $existing = $this->userModel->where('email', $email)->first();

        if ($existing !== null) {
            // If the user exists but is unverified, regenerate verification token and resend
            if (empty($existing['email_verified_at'])) {
                $rawToken    = bin2hex(random_bytes(32));
                $hashedToken = hash('sha256', $rawToken);
                $expiresAt   = date('Y-m-d H:i:s', time() + (24 * 3600));

                $updateData = [
                    'email_verification_token'      => $hashedToken,
                    'email_verification_expires_at' => $expiresAt,
                    'role_id'                       => $submittedRoleId,
                ];
                if ($departmentId !== null) {
                    $updateData['department_id'] = $departmentId;
                }
                if ($phone !== null) {
                    $updateData['phone'] = $phone;
                }

                $this->userModel->update($existing['id'], $updateData);

                $this->sendVerificationEmail($existing['first_name'], $email, $rawToken);
            }

            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(201)
                    ->setJSON([
                        'status'  => 'success',
                        'message' => $successNotice,
                    ]);
            }

            return redirect()->to('/login')
                ->with('success', $successNotice);
        }

        // New unverified user registration
        $rawToken    = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $rawToken);
        $expiresAt   = date('Y-m-d H:i:s', time() + (24 * 3600));

        $newUserData = [
            'first_name'                    => $firstName,
            'last_name'                     => $lastName,
            'email'                         => $email,
            'password_hash'                 => password_hash($password, PASSWORD_DEFAULT),
            'department_id'                 => $departmentId,
            'role_id'                       => $submittedRoleId,
            'phone'                         => $phone,
            'is_active'                     => 1,
            'email_verified_at'             => null,
            'email_verification_token'      => $hashedToken,
            'email_verification_expires_at' => $expiresAt,
        ];

        try {
            $this->userModel->insert($newUserData, true);
        } catch (\Throwable $e) {
            log_message('error', 'Registration insert failed: ' . $e->getMessage());
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Failed to create account. Please try again later.',
                    ]);
            }

            return redirect()->to('/register')
                ->withInput()
                ->with('error', 'Failed to create account. Please try again later.');
        }

        // Send verification email
        $this->sendVerificationEmail($firstName, $email, $rawToken);

        if ($this->isJsonRequest()) {
            return $this->response
                ->setStatusCode(201)
                ->setJSON([
                    'status'  => 'success',
                    'message' => $successNotice,
                ]);
        }

        return redirect()->to('/login')
            ->with('success', $successNotice);
    }

    /**
     * Verify email via single-use token.
     */
    public function verifyEmail(string $token): string|ResponseInterface
    {
        $token = trim($token);

        // Basic validation of token format (64-character hex string)
        if (empty($token) || strlen($token) !== 64 || !ctype_xdigit($token)) {
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Invalid or malformed verification link.',
                    ]);
            }

            return $this->response
                ->setStatusCode(400)
                ->setBody(view('auth/verify_email_status', [
                    'title'   => 'Invalid Verification Link',
                    'status'  => 'invalid',
                    'message' => 'This email verification link is malformed or invalid.',
                ]));
        }

        $hashedToken = hash('sha256', $token);

        $user = $this->userModel
            ->where('email_verification_token', $hashedToken)
            ->first();

        if ($user === null) {
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Invalid or already-used verification link.',
                    ]);
            }

            return $this->response
                ->setStatusCode(400)
                ->setBody(view('auth/verify_email_status', [
                    'title'   => 'Invalid Verification Link',
                    'status'  => 'invalid',
                    'message' => 'This verification link is invalid, expired, or has already been used.',
                ]));
        }

        // Check expiration
        if (!empty($user['email_verification_expires_at']) && strtotime($user['email_verification_expires_at']) < time()) {
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(410)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Verification link has expired.',
                    ]);
            }

            return $this->response
                ->setStatusCode(410)
                ->setBody(view('auth/verify_email_status', [
                    'title'   => 'Link Expired',
                    'status'  => 'expired',
                    'message' => 'This verification link has expired. Verification links are valid for 24 hours.',
                ]));
        }

        // Mark email as verified and clear token (single-use enforcement)
        $this->userModel->update($user['id'], [
            'email_verified_at'             => date('Y-m-d H:i:s'),
            'email_verification_token'      => null,
            'email_verification_expires_at' => null,
        ]);

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Email verified successfully. You can now log in.',
            ]);
        }

        return view('auth/verify_email_status', [
            'title'   => 'Email Verified',
            'status'  => 'success',
            'message' => 'Your email address has been verified successfully! You can now sign in to your MeetSpace account.',
        ]);
    }

    /**
     * Render resend verification form.
     */
    public function resendVerificationForm(): string|ResponseInterface
    {
        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            return redirect()->to('/');
        }

        return view('auth/resend_verification', [
            'title'   => 'Resend Verification',
            'error'   => $this->session->getFlashdata('error'),
            'success' => $this->session->getFlashdata('success'),
        ]);
    }

    /**
     * Process resend verification submission.
     */
    public function resendVerification(): ResponseInterface
    {
        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            return redirect()->to('/');
        }

        $email = trim((string) ($this->request->getPost('email') ?? ''));

        if (empty($email) && $this->request->is('json')) {
            $json  = $this->request->getJSON(true) ?? [];
            $email = trim((string) ($json['email'] ?? ''));
        }

        $rules = [
            'email' => 'required|valid_email',
        ];
        $messages = [
            'email' => [
                'required'    => 'Email address is required.',
                'valid_email' => 'Please provide a valid email address.',
            ],
        ];

        if (!$this->validateData(['email' => $email], $rules, $messages)) {
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'status' => 'error',
                        'errors' => $this->validator->getErrors(),
                    ]);
            }

            return redirect()->to('/resend-verification')
                ->withInput()
                ->with('error', 'Please enter a valid email address.');
        }

        $genericNotice = 'If an unverified account exists for this email address, a fresh verification link has been sent. Please check your inbox.';

        $user = $this->userModel
            ->where('email', strtolower($email))
            ->first();

        if ($user !== null && empty($user['email_verified_at'])) {
            $rawToken    = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $rawToken);
            $expiresAt   = date('Y-m-d H:i:s', time() + (24 * 3600));

            $this->userModel->update($user['id'], [
                'email_verification_token'      => $hashedToken,
                'email_verification_expires_at' => $expiresAt,
            ]);

            $this->sendVerificationEmail($user['first_name'], $user['email'], $rawToken);
        }

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $genericNotice,
            ]);
        }

        return redirect()->to('/resend-verification')
            ->with('success', $genericNotice);
    }

    /**
     * Terminate the authenticated session.
     */
    public function logout(): ResponseInterface
    {
        $this->session->destroy();

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Logged out successfully.',
            ]);
        }

        return redirect()->to('/login');
    }

    /**
     * Send verification email via Gmail SMTP.
     */
    protected function sendVerificationEmail(string $firstName, string $toEmail, string $rawToken): bool
    {
        $verificationUrl = base_url('verify-email/' . $rawToken);

        $email = service('email');
        $email->setTo($toEmail);
        $email->setSubject('Verify your MeetSpace account');
        $email->setMessage(view('emails/verify_email', [
            'firstName'       => $firstName,
            'verificationUrl' => $verificationUrl,
            'expiresHours'    => 24,
        ]));

        try {
            return (bool) $email->send(false);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to send verification email to ' . $toEmail . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if the incoming request expects a JSON response.
     */
    protected function isJsonRequest(): bool
    {
        return $this->request->isAJAX()
            || str_starts_with($this->request->getUri()->getPath(), 'api/')
            || str_contains((string) $this->request->getHeaderLine('Accept'), 'application/json');
    }
}
