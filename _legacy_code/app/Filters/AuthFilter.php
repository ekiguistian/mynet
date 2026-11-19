<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = session('user');
        if (! is_array($user) || empty($user['id'])) {
            return redirect()->to('/');
        }

        $path = trim(service('uri')->getPath(), '/');
        if (! empty($user['force_password_change']) && ! in_array($path, ['auth/password', 'logout'], true)) {
            return redirect()->to('/auth/password');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
