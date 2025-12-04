<?php

namespace Ratings\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    private $jwtsecret;

    public function __construct($secret)
    {
        $this->jwtsecret = $secret;
    }

    public function __invoke($request, $response, $next)
    {
        $auth = $request->getHeaderLine('Authorization');

        if (!$auth || strpos($auth, 'Bearer ') !== 0) {
            return $response->withStatus(401)->write('Missing token');
        }

        $token = substr($auth, 7);

        try {
            $decoded = JWT::decode($token, new Key($this->jwtsecret, 'HS256'));
            $request = $request->withAttribute('user', $decoded);
        } catch (\Exception $e) {
            return $response->withStatus(403)->write('Invalid token');
        }

        return $next($request, $response);
    }
}