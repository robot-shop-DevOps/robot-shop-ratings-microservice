<?php

namespace Ratings\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;

class AuthMiddleware
{
    private string $jwtsecret;
    private LoggerInterface $logger;

    public function __construct(string $secret, LoggerInterface $logger)
    {
        $this->jwtsecret = $secret;
        $this->logger    = $logger;
    }

    public function __invoke($request, $response, $next)
    {
        $auth = $request->getHeaderLine('Authorization');

        if (!$auth || strpos($auth, 'Bearer ') !== 0) {
            $this->logger->warning('missing authorization token', [
                'service'    => 'ratings',
                'error_type' => 'AUTH_HEADER_MISSING',
            ]);

            return $response
                ->withStatus(401)
                ->write('Missing token');
        }

        $token = substr($auth, 7);

        try {
            $decoded = JWT::decode(
                $token,
                new Key($this->jwtsecret, 'HS256')
            );

            $request = $request->withAttribute('user', $decoded);

        } catch (\Exception $e) {
            $this->logger->warning('invalid or expired token', [
                'service'    => 'ratings',
                'error_type' => 'TOKEN_INVALID',
                'exception'  => get_class($e),
            ]);

            return $response
                ->withStatus(403)
                ->write('Invalid token');
        }

        return $next($request, $response);
    }
}