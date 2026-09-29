<?php

namespace App\Controllers;

use App\Models\User as UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class User extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * List users with server-side pagination, search, and filtering.
     */
    public function index(): ResponseInterface
    {
        if ($this->request->getGet('format') === 'select2') {
            return $this->select2();
        }

        $all = $this->request->getGet('all') === '1' || $this->request->getGet('all') === 'true' || $this->request->getGet('paginate') === '0';
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(100, max(1, (int) ($this->request->getGet('per_page') ?? $this->request->getGet('limit') ?? 10)));
        $search = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('term') ?? $this->request->getGet('q') ?? ''));
        $roleId = $this->request->getGet('role_id');
        $deptId = $this->request->getGet('department_id');
        $status = $this->request->getGet('status');

        $builder = $this->userModel->builder();
        $builder->select('id, department_id, role_id, first_name, last_name, email, phone, is_active, created_at, updated_at');

        if ($search !== '') {
            $builder->groupStart()
                ->like('first_name', $search)
                ->orLike('last_name', $search)
                ->orLike('email', $search)
                ->orLike('phone', $search)
                ->groupEnd();
        }

        if ($roleId !== null && $roleId !== '') {
            $builder->where('role_id', (int) $roleId);
        }

        if ($deptId !== null && $deptId !== '') {
            $builder->where('department_id', (int) $deptId);
        }

        if ($status !== null && $status !== '') {
            $builder->where('is_active', (int) $status);
        }

        $total = (clone $builder)->countAllResults();

        if ($all) {
            $users = $builder->orderBy('id', 'ASC')->get()->getResultArray();
            return $this->response->setJSON([
                'status'      => 'success',
                'data'        => $users,
                'page'        => 1,
                'per_page'    => $total > 0 ? $total : 1,
                'total'       => $total,
                'total_pages' => 1,
            ]);
        }

        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $offset = ($page - 1) * $perPage;

        $users = $builder->orderBy('id', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        return $this->response->setJSON([
            'status'      => 'success',
            'data'        => $users,
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => $totalPages,
        ]);
    }


    /**
     * Select2 AJAX data source for users.
     * Supports search across first name, last name, and email with server-side pagination.
     */
    public function select2(): ResponseInterface
    {
        $session = service('session');
        if (!$session->get('isLoggedIn')) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'     => 'error',
                'message'    => 'Unauthorized. Please log in.',
                'results'    => [],
                'pagination' => ['more' => false],
            ]);
        }

        $id = $this->request->getGet('id');
        if ($id !== null && is_numeric($id) && (int) $id > 0) {
            $user = $this->userModel
                ->select('id, first_name, last_name, email, is_active')
                ->find((int) $id);

            if ($user) {
                $fullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                $text = $fullName !== '' ? $fullName : ($user['email'] ?? 'User #' . $user['id']);
                return $this->response->setJSON([
                    'results' => [[
                        'id'    => (int) $user['id'],
                        'text'  => $text,
                        'email' => $user['email'] ?? '',
                    ]],
                    'pagination' => ['more' => false],
                ]);
            }
            return $this->response->setJSON([
                'results'    => [],
                'pagination' => ['more' => false],
            ]);
        }

        $search = trim((string) ($this->request->getGet('search') ?? $this->request->getGet('term') ?? $this->request->getGet('q') ?? ''));
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $perPage = min(50, max(5, (int) ($this->request->getGet('per_page') ?? $this->request->getGet('limit') ?? 20)));
        $includeId = $this->request->getGet('include_id');
        $includeIdInt = ($includeId !== null && is_numeric($includeId)) ? (int) $includeId : null;

        $builder = $this->userModel->builder();
        $builder->select('id, first_name, last_name, email, is_active');

        if ($includeIdInt !== null) {
            $builder->groupStart()
                ->where('is_active', 1)
                ->orWhere('id', $includeIdInt)
                ->groupEnd();
        } else {
            $builder->where('is_active', 1);
        }

        if ($search !== '') {
            $builder->groupStart()
                ->like('first_name', $search)
                ->orLike('last_name', $search)
                ->orLike('email', $search)
                ->orLike("CONCAT(first_name, ' ', last_name)", $search)
                ->groupEnd();
        }

        $totalCount = (clone $builder)->countAllResults();

        $offset = ($page - 1) * $perPage;
        $users = $builder->orderBy('first_name', 'ASC')
            ->orderBy('last_name', 'ASC')
            ->limit($perPage, $offset)
            ->get()
            ->getResultArray();

        $results = [];
        foreach ($users as $u) {
            $fullName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
            $text = $fullName !== '' ? $fullName : ($u['email'] ?? 'User #' . $u['id']);
            $results[] = [
                'id'    => (int) $u['id'],
                'text'  => $text,
                'email' => $u['email'] ?? '',
            ];
        }

        $hasMore = ($offset + count($results)) < $totalCount;

        return $this->response->setJSON([
            'results'    => $results,
            'pagination' => [
                'more' => $hasMore,
            ],
        ]);
    }

    /**
     * Get a single user by ID.
     */
    public function show(int $id): ResponseInterface
    {
        $user = $this->userModel
            ->select('id, department_id, role_id, first_name, last_name, email, phone, is_active, created_at, updated_at')
            ->find($id);

        if ($user === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'User not found.',
                ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $user,
        ]);
    }

    /**
     * Create a new user.
     */
    public function create(): ResponseInterface
    {
        $data = $this->request->getJSON(true) ?? [];

        $rules = [
            'first_name'    => 'required|max_length[100]',
            'last_name'     => 'required|max_length[100]',
            'email'         => 'required|valid_email|max_length[255]|is_unique[users.email]',
            'password'      => 'required|min_length[6]',
            'department_id' => 'permit_empty|is_natural_no_zero',
            'role_id'       => 'permit_empty|is_natural_no_zero',
            'phone'         => 'permit_empty|max_length[30]',
            'is_active'     => 'permit_empty|in_list[0,1]',
        ];

        $messages = [
            'first_name' => [
                'required'   => 'First name is required.',
                'max_length' => 'First name cannot exceed 100 characters.',
            ],
            'last_name' => [
                'required'   => 'Last name is required.',
                'max_length' => 'Last name cannot exceed 100 characters.',
            ],
            'email' => [
                'required'    => 'Email address is required.',
                'valid_email' => 'Please provide a valid email address.',
                'is_unique'   => 'This email address is already registered.',
            ],
            'password' => [
                'required'   => 'Password is required.',
                'min_length' => 'Password must be at least 6 characters.',
            ],
            'phone' => [
                'max_length' => 'Phone number cannot exceed 30 characters.',
            ],
        ];

        if (!$this->validateData($data, $rules, $messages)) {
            $errors = $this->validator->getErrors();
            $statusCode = isset($errors['email']) && str_contains($errors['email'], 'already registered') ? 409 : 422;

            return $this->response
                ->setStatusCode($statusCode)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $errors,
                ]);
        }

        $userData = [
            'first_name'    => trim($data['first_name']),
            'last_name'     => trim($data['last_name']),
            'email'         => trim($data['email']),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'department_id' => !empty($data['department_id']) ? (int) $data['department_id'] : null,
            'role_id'       => !empty($data['role_id']) ? (int) $data['role_id'] : null,
            'phone'         => !empty($data['phone']) ? trim($data['phone']) : null,
            'is_active'     => isset($data['is_active']) ? (int) $data['is_active'] : 1,
        ];

        try {
            $userId = $this->userModel->insert($userData, true);

            if (!$userId) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'status' => 'error',
                        'errors' => $this->userModel->errors(),
                    ]);
            }
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') || $e->getCode() === 1062) {
                return $this->response
                    ->setStatusCode(409)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'A user with this email address already exists.',
                    ]);
            }

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to create user.',
                ]);
        }

        $user = $this->userModel
            ->select('id, department_id, role_id, first_name, last_name, email, phone, is_active, created_at, updated_at')
            ->find($userId);

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status'  => 'success',
                'message' => 'User created successfully.',
                'data'    => $user,
            ]);
    }

    /**
     * Update an existing user.
     */
    public function update(int $id): ResponseInterface
    {
        $existingUser = $this->userModel->find($id);

        if ($existingUser === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'User not found.',
                ]);
        }

        $data = $this->request->getJSON(true) ?? [];

        $isSameEmail = isset($data['email']) && trim((string) $data['email']) === $existingUser['email'];

        $emailRule = $isSameEmail
            ? 'required|valid_email|max_length[255]'
            : "required|valid_email|max_length[255]|is_unique[users.email,id,{$id}]";

        $rules = [
            'first_name'    => 'required|max_length[100]',
            'last_name'     => 'required|max_length[100]',
            'email'         => $emailRule,
            'password'      => 'permit_empty|min_length[6]',
            'department_id' => 'permit_empty|is_natural_no_zero',
            'role_id'       => 'permit_empty|is_natural_no_zero',
            'phone'         => 'permit_empty|max_length[30]',
            'is_active'     => 'permit_empty|in_list[0,1]',
        ];

        $messages = [
            'first_name' => [
                'required'   => 'First name is required.',
                'max_length' => 'First name cannot exceed 100 characters.',
            ],
            'last_name' => [
                'required'   => 'Last name is required.',
                'max_length' => 'Last name cannot exceed 100 characters.',
            ],
            'email' => [
                'required'    => 'Email address is required.',
                'valid_email' => 'Please provide a valid email address.',
                'is_unique'   => 'This email address is already registered.',
            ],
            'password' => [
                'min_length' => 'Password must be at least 6 characters.',
            ],
            'phone' => [
                'max_length' => 'Phone number cannot exceed 30 characters.',
            ],
        ];

        if (!$this->validateData($data, $rules, $messages)) {
            $errors = $this->validator->getErrors();
            $statusCode = isset($errors['email']) && str_contains($errors['email'], 'already registered') ? 409 : 422;

            return $this->response
                ->setStatusCode($statusCode)
                ->setJSON([
                    'status' => 'error',
                    'errors' => $errors,
                ]);
        }

        $userData = [
            'id'            => $id,
            'first_name'    => trim($data['first_name']),
            'last_name'     => trim($data['last_name']),
            'email'         => trim($data['email']),
            'department_id' => !empty($data['department_id']) ? (int) $data['department_id'] : null,
            'role_id'       => !empty($data['role_id']) ? (int) $data['role_id'] : null,
            'phone'         => !empty($data['phone']) ? trim($data['phone']) : null,
            'is_active'     => isset($data['is_active']) ? (int) $data['is_active'] : $existingUser['is_active'],
            'password_hash' => $existingUser['password_hash'],
        ];

        if (!empty($data['password'])) {
            $userData['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $this->userModel->setValidationRule('email', $emailRule);

        try {
            if (!$this->userModel->update($id, $userData)) {
                return $this->response
                    ->setStatusCode(422)
                    ->setJSON([
                        'status' => 'error',
                        'errors' => $this->userModel->errors(),
                    ]);
            }
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry') || $e->getCode() === 1062) {
                return $this->response
                    ->setStatusCode(409)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'A user with this email address already exists.',
                    ]);
            }

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to update user.',
                ]);
        }

        $updatedUser = $this->userModel
            ->select('id, department_id, role_id, first_name, last_name, email, phone, is_active, created_at, updated_at')
            ->find($id);

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'status'  => 'success',
                'message' => 'User updated successfully.',
                'data'    => $updatedUser,
            ]);
    }

    /**
     * Delete an existing user.
     */
    public function delete(int $id): ResponseInterface
    {
        $user = $this->userModel->find($id);

        if ($user === null) {
            return $this->response
                ->setStatusCode(404)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'User not found.',
                ]);
        }

        try {
            if (!$this->userModel->delete($id)) {
                return $this->response
                    ->setStatusCode(500)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Failed to delete user.',
                    ]);
            }
        } catch (\Throwable $e) {
            if (str_contains($e->getMessage(), '1451') || str_contains($e->getMessage(), 'foreign key constraint fails')) {
                return $this->response
                    ->setStatusCode(409)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'This user cannot be deleted because they are associated with existing meetings, bookings, or participants.',
                    ]);
            }

            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Failed to delete user.',
                ]);
        }

        return $this->response
            ->setStatusCode(200)
            ->setJSON([
                'status'  => 'success',
                'message' => 'User deleted successfully.',
            ]);
    }
}