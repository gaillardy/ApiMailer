<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\AuthMiddleware;
use App\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le middleware d'authentification AuthMiddleware.
 */
class AuthMiddlewareTest extends TestCase
{
    private string $secretKey = 'super_secret_api_key_test_value';

    public function testMissingApiKeyReturns401(): void
    {
        $middleware = new AuthMiddleware($this->secretKey);
        $request = new Request('POST', [], ['fullName' => 'Test']);

        $response = $middleware->handle($request);

        $this->assertNotNull($response);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testInvalidApiKeyReturns401(): void
    {
        $middleware = new AuthMiddleware($this->secretKey);
        $request = new Request('POST', ['X-API-KEY' => 'wrong_key'], ['fullName' => 'Test']);

        $response = $middleware->handle($request);

        $this->assertNotNull($response);
        $this->assertSame(401, $response->getStatusCode());
    }

    public function testValidApiKeyViaHeaderXApiKeyPasses(): void
    {
        $middleware = new AuthMiddleware($this->secretKey);
        $request = new Request('POST', ['X-API-KEY' => $this->secretKey], ['fullName' => 'Test']);

        $response = $middleware->handle($request);

        $this->assertNull($response);
    }

    public function testValidApiKeyViaBearerTokenPasses(): void
    {
        $middleware = new AuthMiddleware($this->secretKey);
        $request = new Request('POST', ['Authorization' => 'Bearer ' . $this->secretKey], ['fullName' => 'Test']);

        $response = $middleware->handle($request);

        $this->assertNull($response);
    }

    public function testUnconfiguredServerKeyReturns500(): void
    {
        $middleware = new AuthMiddleware(''); // Clé non configurée
        $request = new Request('POST', ['X-API-KEY' => 'some_key']);

        $response = $middleware->handle($request);

        $this->assertNotNull($response);
        $this->assertSame(500, $response->getStatusCode());
    }
}
