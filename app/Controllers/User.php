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
     * List all users.
     */
    public function index(): ResponseInterface
    {
        $users = $this->userModel
            ->select('id, department_id, role_id, first_name, last_name, email, phone, is_active, created_at, updated_at')
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $users,
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