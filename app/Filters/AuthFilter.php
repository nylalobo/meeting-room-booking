<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    /**
     * Verify that the user is authenticated before executing the controller.
     *
     * @param array|null $arguments
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = service('session');

        $isLoggedIn = $session->get('isLoggedIn') === true;
        $userId     = $session->get('user_id');

        if (!$isLoggedIn || empty($userId)) {
            $path = trim($request->getUri()->getPath(), '/');

            // Detect API requests, AJAX requests, or requests requesting JSON
            if (
                str_starts_with($path, 'api') ||
                str_starts_with($path, 'booking-participants') ||
                str_starts_with($path, 'dashboard/stats') ||
                str_starts_with($path, 'roles') ||
                $request->isAJAX() ||
                str_contains((string) $request->getHeaderLine('Accept'), 'application/json')
            ) {
                return service('response')
                    ->setStatusCode(401)
                    ->setJSON([
                        'status'  => 'error',
                        'message' => 'Unauthorized. Authentication required.',
                    ]);
            }

            return redirect()->to('/login');
        }
    }

    /**
     * After filter hook.
     *
     * @param array|null $arguments
     * @return void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed after request execution
    }
}
