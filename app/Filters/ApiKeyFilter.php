<?php

namespace App\Filters;

use App\Libraries\ApiKeyService;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiKeyFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $authorization = $request->getHeaderLine('Authorization');
        if (! preg_match('/^Bearer\s+(.+)$/i', trim($authorization), $matches)) {
            return service('response')->setStatusCode(401)->setJSON(['error' => 'Bearer API key required.']);
        }

        $service = new ApiKeyService();
        $key = $service->resolve(trim($matches[1]));
        if (! $key) return service('response')->setStatusCode(401)->setJSON(['error' => 'API key is invalid or revoked.']);

        $requiredScope = $arguments[0] ?? null;
        $scopes = json_decode($key['scopes'], true) ?: [];
        if ($requiredScope && ! in_array($requiredScope, $scopes, true)) {
            return service('response')->setStatusCode(403)->setJSON(['error' => 'API key does not include this scope.']);
        }

        ApiKeyService::setCurrent($key);
        db_connect()->table('api_keys')->where('id', $key['id'])->update(['last_used_at' => date('Y-m-d H:i:s')]);
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
