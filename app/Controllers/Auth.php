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
     * Validate and sanitize a post-login return URL.
     * Ensures only safe, relative, internal application paths are accepted.
     */
    private function getSafeReturnUrl(): ?string
    {
        $req = $this->request ?? service('request');
        $raw = (string) (
            $req->getPost('return_url')
            ?? $req->getGet('return_url')
            ?? $req->getPost('return')
            ?? $req->getGet('return')
            ?? $req->getPost('redirect')
            ?? $req->getGet('redirect')
            ?? ''
        );

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        // Must start with exactly one forward slash and not protocol-relative (// or /\)
        if (!str_starts_with($trimmed, '/') || str_starts_with($trimmed, '//') || str_starts_with($trimmed, '/\\')) {
            return null;
        }

        // Must not contain scheme delimiters (://, :) or backslashes
        if (str_contains($trimmed, '://') || str_contains($trimmed, ':') || str_contains($trimmed, '\\')) {
            return null;
        }

        // Must not contain control characters or newlines
        if (preg_match('/[\x00-\x1F\x7F]/', $trimmed)) {
            return null;
        }

        // Prevent redirect loops to login, register, or logout
        $pathOnly = parse_url($trimmed, PHP_URL_PATH) ?? '';
        $normalizedPath = trim($pathOnly, '/');
        if (in_array($normalizedPath, ['login', 'register', 'logout', 'resend-verification'], true)) {
            return null;
        }

        // Validate safe URI path characters
        if (!preg_match('@^/[a-zA-Z0-9_\-\.~/%?&=#]*$@', $trimmed)) {
            return null;
        }

        return $trimmed;
    }

    /**
     * Render the login page.
     * If user is already authenticated, redirect to dashboard or safe return URL.
     */
    public function login(): string|ResponseInterface
    {
        $returnUrl = $this->getSafeReturnUrl();

        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            if ($returnUrl !== null) {
                return redirect()->to($returnUrl);
            }
            return redirect()->to('/');
        }

        return view('auth/login', [
            'title'     => 'Sign In',
            'error'     => $this->session->getFlashdata('error'),
            'success'   => $this->session->getFlashdata('success'),
            'returnUrl' => $returnUrl,
        ]);
    }

    /**
     * Process login form submission.
     */
    public function attemptLogin(): ResponseInterface
    {
        $returnUrl = $this->getSafeReturnUrl();
        $loginUrl  = '/login' . ($returnUrl !== null ? '?return=' . rawurlencode($returnUrl) : '');

        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            if ($returnUrl !== null) {
                return redirect()->to($returnUrl);
            }
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

            return redirect()->to($loginUrl)
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

            return redirect()->to($loginUrl)
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

            return redirect()->to($loginUrl)
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

            return redirect()->to($loginUrl)
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

            return redirect()->to($loginUrl)
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
                    'return_url'    => $returnUrl,
                ],
            ]);
        }

        if ($returnUrl !== null) {
            return redirect()->to($returnUrl);
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

        $successNotice = 'Registration successful! A 6-digit verification code has been sent to your email.';

        // Check for existing account (anti-enumeration: don't reveal existence)
        $existing = $this->userModel->where('email', $email)->first();

        if ($existing !== null) {
            // If user exists and is unverified, send a fresh OTP (with cooldown protection)
            if (empty($existing['email_verified_at'])) {
                $sentAt  = !empty($existing['email_verification_otp_sent_at']) ? strtotime($existing['email_verification_otp_sent_at']) : 0;
                $elapsed = time() - $sentAt;

                if ($elapsed >= 60) {
                    $otp         = $this->generateOtp();
                    $hashedOtp   = hash('sha256', $otp);
                    $expiresAt   = date('Y-m-d H:i:s', time() + 600); // 10 minutes

                    $updateData = [
                        'email_verification_otp_hash'       => $hashedOtp,
                        'email_verification_otp_expires_at' => $expiresAt,
                        'email_verification_otp_attempts'   => 0,
                        'email_verification_otp_sent_at'    => date('Y-m-d H:i:s'),
                        'role_id'                           => $submittedRoleId,
                    ];
                    if ($departmentId !== null) {
                        $updateData['department_id'] = $departmentId;
                    }
                    if ($phone !== null) {
                        $updateData['phone'] = $phone;
                    }

                    $this->userModel->update($existing['id'], $updateData);
                    $this->sendVerificationEmail($existing['first_name'], $email, $otp);
                }
            }

            $this->session->set('verification_email', $email);

            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(201)
                    ->setJSON([
                        'status'  => 'success',
                        'message' => $successNotice,
                        'email'   => $email,
                    ]);
            }

            return redirect()->to('/verify-email')
                ->with('success', $successNotice);
        }

        // New unverified user registration
        $otp         = $this->generateOtp();
        $hashedOtp   = hash('sha256', $otp);
        $expiresAt   = date('Y-m-d H:i:s', time() + 600); // 10 minutes

        $newUserData = [
            'first_name'                        => $firstName,
            'last_name'                         => $lastName,
            'email'                             => $email,
            'password_hash'                     => password_hash($password, PASSWORD_DEFAULT),
            'department_id'                     => $departmentId,
            'role_id'                           => $submittedRoleId,
            'phone'                             => $phone,
            'is_active'                         => 1,
            'email_verified_at'                 => null,
            'email_verification_otp_hash'       => $hashedOtp,
            'email_verification_otp_expires_at' => $expiresAt,
            'email_verification_otp_attempts'   => 0,
            'email_verification_otp_sent_at'    => date('Y-m-d H:i:s'),
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

        // Send verification email with 6-digit OTP
        $this->sendVerificationEmail($firstName, $email, $otp);

        $this->session->set('verification_email', $email);

        if ($this->isJsonRequest()) {
            return $this->response
                ->setStatusCode(201)
                ->setJSON([
                    'status'  => 'success',
                    'message' => $successNotice,
                    'email'   => $email,
                ]);
        }

        return redirect()->to('/verify-email')
            ->with('success', $successNotice);
    }

    /**
     * Render the Email OTP Verification page.
     */
    public function verifyEmailForm(): string|ResponseInterface
    {
        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            return redirect()->to('/');
        }

        $email = (string) (
            $this->request->getGet('email')
            ?? $this->session->get('verification_email')
            ?? old('email')
            ?? ''
        );

        $cooldownRemaining = 0;
        if (!empty($email)) {
            $user = $this->userModel->where('email', strtolower($email))->first();
            if ($user && !empty($user['email_verification_otp_sent_at'])) {
                $elapsed = time() - strtotime($user['email_verification_otp_sent_at']);
                if ($elapsed < 60) {
                    $cooldownRemaining = 60 - $elapsed;
                }
            }
        }

        return view('auth/verify_email', [
            'title'             => 'Verify Email',
            'email'             => $email,
            'cooldownRemaining' => $cooldownRemaining,
            'error'             => $this->session->getFlashdata('error'),
            'success'           => $this->session->getFlashdata('success'),
        ]);
    }

    /**
     * Process 6-digit Email OTP Verification submission.
     */
    public function attemptVerifyEmail(): ResponseInterface
    {
        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            return redirect()->to('/');
        }

        $email = trim((string) ($this->request->getPost('email') ?? ''));
        $otp   = trim((string) ($this->request->getPost('otp') ?? ''));

        if (empty($email) && empty($otp) && $this->request->is('json')) {
            $json  = $this->request->getJSON(true) ?? [];
            $email = trim((string) ($json['email'] ?? ''));
            $otp   = trim((string) ($json['otp'] ?? ''));
        }

        // Clean OTP to strictly numeric characters
        $otp = preg_replace('/[^0-9]/', '', $otp);

        $rules = [
            'email' => 'required|valid_email',
            'otp'   => 'required|min_length[6]|max_length[6]|regex_match[/^[0-9]{6}$/]',
        ];
        $messages = [
            'email' => [
                'required'    => 'Email address is required.',
                'valid_email' => 'Please provide a valid email address.',
            ],
            'otp' => [
                'required'    => 'Verification code is required.',
                'min_length'  => 'Verification code must be exactly 6 digits.',
                'max_length'  => 'Verification code must be exactly 6 digits.',
                'regex_match' => 'Verification code must contain only numbers.',
            ],
        ];

        if (!$this->validateData(['email' => $email, 'otp' => $otp], $rules, $messages)) {
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'status' => 'error',
                        'errors' => $this->validator->getErrors(),
                    ]);
            }

            return redirect()->to('/verify-email?email=' . rawurlencode($email))
                ->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $user = $this->userModel->where('email', strtolower($email))->first();

        // User not found
        if ($user === null) {
            $genericError = 'Invalid verification code or email address.';
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON(['status' => 'error', 'message' => $genericError]);
            }

            return redirect()->to('/verify-email?email=' . rawurlencode($email))
                ->withInput()
                ->with('error', $genericError);
        }

        // Account is already verified
        if (!empty($user['email_verified_at'])) {
            $alreadyVerifiedNotice = 'Your email address is already verified. Please sign in.';
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(200)
                    ->setJSON(['status' => 'success', 'message' => $alreadyVerifiedNotice]);
            }

            return redirect()->to('/login')
                ->with('success', $alreadyVerifiedNotice);
        }

        // Check failed attempts limit (max 5)
        $attempts = (int) ($user['email_verification_otp_attempts'] ?? 0);
        if ($attempts >= 5) {
            // Invalidate OTP hash
            $this->userModel->update($user['id'], [
                'email_verification_otp_hash'       => null,
                'email_verification_otp_expires_at' => null,
            ]);

            $lockoutError = 'Maximum verification attempts exceeded. Please request a new verification code.';
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(429)
                    ->setJSON(['status' => 'error', 'message' => $lockoutError]);
            }

            return redirect()->to('/verify-email?email=' . rawurlencode($email))
                ->withInput()
                ->with('error', $lockoutError);
        }

        // Check expiry (10 minutes)
        if (
            empty($user['email_verification_otp_expires_at'])
            || strtotime($user['email_verification_otp_expires_at']) < time()
        ) {
            $expiredError = 'Verification code has expired. Please request a new code.';
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(410)
                    ->setJSON(['status' => 'error', 'message' => $expiredError]);
            }

            return redirect()->to('/verify-email?email=' . rawurlencode($email))
                ->withInput()
                ->with('error', $expiredError);
        }

        // Verify SHA-256 hash using timing-safe comparison
        $submittedHash = hash('sha256', $otp);
        if (
            empty($user['email_verification_otp_hash'])
            || !hash_equals((string) $user['email_verification_otp_hash'], $submittedHash)
        ) {
            $newAttempts = $attempts + 1;
            $updateData  = ['email_verification_otp_attempts' => $newAttempts];
            if ($newAttempts >= 5) {
                $updateData['email_verification_otp_hash'] = null;
            }
            $this->userModel->update($user['id'], $updateData);

            $remaining = max(0, 5 - $newAttempts);
            $mismatchError = 'Invalid verification code. ' . ($remaining > 0 ? "{$remaining} attempt(s) remaining." : 'Please request a new code.');

            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON(['status' => 'error', 'message' => $mismatchError]);
            }

            return redirect()->to('/verify-email?email=' . rawurlencode($email))
                ->withInput()
                ->with('error', $mismatchError);
        }

        // Verification successful: mark verified and invalidate single-use OTP
        $this->userModel->update($user['id'], [
            'email_verified_at'                 => date('Y-m-d H:i:s'),
            'email_verification_otp_hash'       => null,
            'email_verification_otp_expires_at' => null,
            'email_verification_otp_attempts'   => 0,
            'email_verification_otp_sent_at'    => null,
        ]);

        $this->session->remove('verification_email');

        $verifiedSuccess = 'Email verified successfully! You can now sign in to your MeetSpace account.';

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $verifiedSuccess,
            ]);
        }

        return redirect()->to('/login')
            ->with('success', $verifiedSuccess);
    }

    /**
     * Resend registration email verification OTP with 60-second cooldown.
     */
    public function resendOtp(): ResponseInterface
    {
        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            return redirect()->to('/');
        }

        $email = trim((string) ($this->request->getPost('email') ?? ''));

        if (empty($email) && $this->request->is('json')) {
            $json  = $this->request->getJSON(true) ?? [];
            $email = trim((string) ($json['email'] ?? ''));
        }

        $rules = ['email' => 'required|valid_email'];
        if (!$this->validateData(['email' => $email], $rules)) {
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON(['status' => 'error', 'message' => 'Please provide a valid email address.']);
            }

            return redirect()->to('/verify-email?email=' . rawurlencode($email))
                ->with('error', 'Please provide a valid email address.');
        }

        $genericNotice = 'If an unverified account exists for this email address, a new verification code has been sent. Please check your inbox.';

        $user = $this->userModel->where('email', strtolower($email))->first();

        if ($user !== null && empty($user['email_verified_at'])) {
            // Check 60-second cooldown
            $sentAt  = !empty($user['email_verification_otp_sent_at']) ? strtotime($user['email_verification_otp_sent_at']) : 0;
            $elapsed = time() - $sentAt;

            if ($elapsed < 60) {
                $wait = 60 - $elapsed;
                $cooldownMsg = "Please wait {$wait} seconds before requesting another code.";

                if ($this->isJsonRequest()) {
                    return $this->response
                        ->setStatusCode(429)
                        ->setJSON(['status' => 'error', 'message' => $cooldownMsg]);
                }

                return redirect()->to('/verify-email?email=' . rawurlencode($email))
                    ->with('error', $cooldownMsg);
            }

            // Generate new OTP, hash, and set 10-minute expiry
            $otp       = $this->generateOtp();
            $hashedOtp = hash('sha256', $otp);
            $expiresAt = date('Y-m-d H:i:s', time() + 600);

            $this->userModel->update($user['id'], [
                'email_verification_otp_hash'       => $hashedOtp,
                'email_verification_otp_expires_at' => $expiresAt,
                'email_verification_otp_attempts'   => 0,
                'email_verification_otp_sent_at'    => date('Y-m-d H:i:s'),
            ]);

            $this->sendVerificationEmail($user['first_name'], $user['email'], $otp);
        }

        $this->session->set('verification_email', $email);

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $genericNotice,
            ]);
        }

        return redirect()->to('/verify-email?email=' . rawurlencode($email))
            ->with('success', $genericNotice);
    }

    /**
     * Legacy token URL handler: redirects gracefully to OTP verification page.
     */
    public function verifyEmailLegacy(string $token): ResponseInterface
    {
        return redirect()->to('/verify-email')
            ->with('error', 'Email verification now uses a 6-digit code. Please enter your verification code below.');
    }

    /**
     * Render the Forgot Password page.
     */
    public function forgotPassword(): string|ResponseInterface
    {
        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            return redirect()->to('/');
        }

        return view('auth/forgot_password', [
            'title'   => 'Forgot Password',
            'error'   => $this->session->getFlashdata('error'),
            'success' => $this->session->getFlashdata('success'),
        ]);
    }

    /**
     * Process Forgot Password submission: generate recovery OTP and send email.
     */
    public function attemptForgotPassword(): ResponseInterface
    {
        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            return redirect()->to('/');
        }

        $email = trim((string) ($this->request->getPost('email') ?? ''));

        if (empty($email) && $this->request->is('json')) {
            $json  = $this->request->getJSON(true) ?? [];
            $email = trim((string) ($json['email'] ?? ''));
        }

        $rules = ['email' => 'required|valid_email'];
        if (!$this->validateData(['email' => $email], $rules)) {
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON(['status' => 'error', 'message' => 'Please provide a valid email address.']);
            }

            return redirect()->to('/forgot-password')
                ->withInput()
                ->with('error', 'Please provide a valid email address.');
        }

        // Generic anti-enumeration response
        $genericNotice = 'If an account exists for that email, a password recovery code has been sent. Please check your inbox.';

        $user = $this->userModel->where('email', strtolower($email))->first();

        if ($user !== null && (int) ($user['is_active'] ?? 1) === 1) {
            $sentAt  = !empty($user['password_reset_otp_sent_at']) ? strtotime($user['password_reset_otp_sent_at']) : 0;
            $elapsed = time() - $sentAt;

            // Only generate new recovery code if cooldown has passed
            if ($elapsed >= 60) {
                $otp       = $this->generateOtp();
                $hashedOtp = hash('sha256', $otp);
                $expiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes

                $this->userModel->update($user['id'], [
                    'password_reset_otp_hash'       => $hashedOtp,
                    'password_reset_otp_expires_at' => $expiresAt,
                    'password_reset_otp_attempts'   => 0,
                    'password_reset_otp_sent_at'    => date('Y-m-d H:i:s'),
                ]);

                $this->sendPasswordResetEmail($user['first_name'], $user['email'], $otp);
            }
        }

        $this->session->set('reset_email', $email);

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $genericNotice,
                'email'   => $email,
            ]);
        }

        return redirect()->to('/reset-password?email=' . rawurlencode($email))
            ->with('success', $genericNotice);
    }

    /**
     * Render the Reset Password page.
     */
    public function resetPassword(): string|ResponseInterface
    {
        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            return redirect()->to('/');
        }

        $email = (string) (
            $this->request->getGet('email')
            ?? $this->session->get('reset_email')
            ?? old('email')
            ?? ''
        );

        $cooldownRemaining = 0;
        if (!empty($email)) {
            $user = $this->userModel->where('email', strtolower($email))->first();
            if ($user && !empty($user['password_reset_otp_sent_at'])) {
                $elapsed = time() - strtotime($user['password_reset_otp_sent_at']);
                if ($elapsed < 60) {
                    $cooldownRemaining = 60 - $elapsed;
                }
            }
        }

        return view('auth/reset_password', [
            'title'             => 'Reset Password',
            'email'             => $email,
            'cooldownRemaining' => $cooldownRemaining,
            'error'             => $this->session->getFlashdata('error'),
            'errors'            => $this->session->getFlashdata('errors') ?? [],
            'success'           => $this->session->getFlashdata('success'),
        ]);
    }

    /**
     * Process Reset Password submission: validate recovery OTP and update password.
     */
    public function attemptResetPassword(): ResponseInterface
    {
        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            return redirect()->to('/');
        }

        $data = [
            'email'            => trim((string) ($this->request->getPost('email') ?? '')),
            'otp'              => trim((string) ($this->request->getPost('otp') ?? '')),
            'password'         => (string) ($this->request->getPost('password') ?? ''),
            'password_confirm' => (string) ($this->request->getPost('password_confirm') ?? ''),
        ];

        if (empty($data['email']) && empty($data['otp']) && $this->request->is('json')) {
            $json = $this->request->getJSON(true) ?? [];
            $data = [
                'email'            => trim((string) ($json['email'] ?? '')),
                'otp'              => trim((string) ($json['otp'] ?? '')),
                'password'         => (string) ($json['password'] ?? ''),
                'password_confirm' => (string) ($json['password_confirm'] ?? ''),
            ];
        }

        $data['otp'] = preg_replace('/[^0-9]/', '', $data['otp']);

        if (!$this->validateData($data, $this->userModel->passwordResetRules, $this->userModel->passwordResetMessages)) {
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'status' => 'error',
                        'errors' => $this->validator->getErrors(),
                    ]);
            }

            return redirect()->to('/reset-password?email=' . rawurlencode($data['email']))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $user = $this->userModel->where('email', strtolower($data['email']))->first();

        if ($user === null) {
            $genericError = 'Invalid recovery code or email address.';
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON(['status' => 'error', 'message' => $genericError]);
            }

            return redirect()->to('/reset-password?email=' . rawurlencode($data['email']))
                ->withInput()
                ->with('error', $genericError);
        }

        // Check failed attempts limit (max 5)
        $attempts = (int) ($user['password_reset_otp_attempts'] ?? 0);
        if ($attempts >= 5) {
            $this->userModel->update($user['id'], [
                'password_reset_otp_hash'       => null,
                'password_reset_otp_expires_at' => null,
            ]);

            $lockoutError = 'Maximum recovery attempts exceeded. Please request a new recovery code.';
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(429)
                    ->setJSON(['status' => 'error', 'message' => $lockoutError]);
            }

            return redirect()->to('/reset-password?email=' . rawurlencode($data['email']))
                ->withInput()
                ->with('error', $lockoutError);
        }

        // Check expiry (10 minutes)
        if (
            empty($user['password_reset_otp_expires_at'])
            || strtotime($user['password_reset_otp_expires_at']) < time()
        ) {
            $expiredError = 'Recovery code has expired. Please request a new code.';
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(410)
                    ->setJSON(['status' => 'error', 'message' => $expiredError]);
            }

            return redirect()->to('/reset-password?email=' . rawurlencode($data['email']))
                ->withInput()
                ->with('error', $expiredError);
        }

        // Verify SHA-256 hash
        $submittedHash = hash('sha256', $data['otp']);
        if (
            empty($user['password_reset_otp_hash'])
            || !hash_equals((string) $user['password_reset_otp_hash'], $submittedHash)
        ) {
            $newAttempts = $attempts + 1;
            $updateData  = ['password_reset_otp_attempts' => $newAttempts];
            if ($newAttempts >= 5) {
                $updateData['password_reset_otp_hash'] = null;
            }
            $this->userModel->update($user['id'], $updateData);

            $remaining = max(0, 5 - $newAttempts);
            $mismatchError = 'Invalid recovery code. ' . ($remaining > 0 ? "{$remaining} attempt(s) remaining." : 'Please request a new code.');

            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON(['status' => 'error', 'message' => $mismatchError]);
            }

            return redirect()->to('/reset-password?email=' . rawurlencode($data['email']))
                ->withInput()
                ->with('error', $mismatchError);
        }

        // Reset password: update hash, invalidate recovery OTP, preserve role, department, verified status
        $this->userModel->update($user['id'], [
            'password_hash'                 => password_hash($data['password'], PASSWORD_DEFAULT),
            'password_reset_otp_hash'       => null,
            'password_reset_otp_expires_at' => null,
            'password_reset_otp_attempts'   => 0,
            'password_reset_otp_sent_at'    => null,
        ]);

        $this->session->remove('reset_email');

        $resetSuccess = 'Your password has been reset successfully! You can now sign in with your new password.';

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $resetSuccess,
            ]);
        }

        return redirect()->to('/login')
            ->with('success', $resetSuccess);
    }

    /**
     * Resend password reset OTP with 60-second cooldown.
     */
    public function resendPasswordResetOtp(): ResponseInterface
    {
        if ($this->session->get('isLoggedIn') === true && !empty($this->session->get('user_id'))) {
            return redirect()->to('/');
        }

        $email = trim((string) ($this->request->getPost('email') ?? ''));

        if (empty($email) && $this->request->is('json')) {
            $json  = $this->request->getJSON(true) ?? [];
            $email = trim((string) ($json['email'] ?? ''));
        }

        $rules = ['email' => 'required|valid_email'];
        if (!$this->validateData(['email' => $email], $rules)) {
            if ($this->isJsonRequest()) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON(['status' => 'error', 'message' => 'Please provide a valid email address.']);
            }

            return redirect()->to('/reset-password?email=' . rawurlencode($email))
                ->with('error', 'Please provide a valid email address.');
        }

        $genericNotice = 'If an account exists for that email, a new password recovery code has been sent.';

        $user = $this->userModel->where('email', strtolower($email))->first();

        if ($user !== null && (int) ($user['is_active'] ?? 1) === 1) {
            $sentAt  = !empty($user['password_reset_otp_sent_at']) ? strtotime($user['password_reset_otp_sent_at']) : 0;
            $elapsed = time() - $sentAt;

            if ($elapsed < 60) {
                $wait = 60 - $elapsed;
                $cooldownMsg = "Please wait {$wait} seconds before requesting another recovery code.";

                if ($this->isJsonRequest()) {
                    return $this->response
                        ->setStatusCode(429)
                        ->setJSON(['status' => 'error', 'message' => $cooldownMsg]);
                }

                return redirect()->to('/reset-password?email=' . rawurlencode($email))
                    ->with('error', $cooldownMsg);
            }

            $otp       = $this->generateOtp();
            $hashedOtp = hash('sha256', $otp);
            $expiresAt = date('Y-m-d H:i:s', time() + 600);

            $this->userModel->update($user['id'], [
                'password_reset_otp_hash'       => $hashedOtp,
                'password_reset_otp_expires_at' => $expiresAt,
                'password_reset_otp_attempts'   => 0,
                'password_reset_otp_sent_at'    => date('Y-m-d H:i:s'),
            ]);

            $this->sendPasswordResetEmail($user['first_name'], $user['email'], $otp);
        }

        $this->session->set('reset_email', $email);

        if ($this->isJsonRequest()) {
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => $genericNotice,
            ]);
        }

        return redirect()->to('/reset-password?email=' . rawurlencode($email))
            ->with('success', $genericNotice);
    }

    /**
     * Generate a cryptographically secure 6-digit numeric OTP.
     */
    protected function generateOtp(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
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
     * Send registration verification email with 6-digit OTP.
     */
    protected function sendVerificationEmail(string $firstName, string $toEmail, string $otp): bool
    {
        $email = service('email');
        $email->setTo($toEmail);
        $email->setSubject('Verify your MeetSpace account');
        $email->setMessage(view('emails/verify_email', [
            'firstName' => $firstName,
            'otp'       => $otp,
        ]));

        try {
            return (bool) $email->send(false);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to send verification email to ' . $toEmail . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send password reset recovery email with 6-digit OTP.
     */
    protected function sendPasswordResetEmail(string $firstName, string $toEmail, string $otp): bool
    {
        $email = service('email');
        $email->setTo($toEmail);
        $email->setSubject('Reset your MeetSpace password');
        $email->setMessage(view('emails/reset_password', [
            'firstName' => $firstName,
            'otp'       => $otp,
        ]));

        try {
            return (bool) $email->send(false);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to send password reset email to ' . $toEmail . ': ' . $e->getMessage());
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
