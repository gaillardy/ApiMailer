<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\CorsMiddleware;
use App\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le middleware CORS.
 */
class CorsMiddlewareTest extends TestCase
{
    public function testAllowedOriginIsAccepted(): void
    {
        $middleware = new CorsMiddleware(['http://localhost:3000', 'https://example.com/']);

        $this->assertTrue($middleware->isOriginAllowed('http://localhost:3000'));
        $this->assertTrue($middleware->isOriginAllowed('https://example.com'));
        $this->assertFalse($middleware->isOriginAllowed('https://evil-site.com'));
    }

    public function testPreflightOptionsReturns204WithCorsHeadersForAllowedOrigin(): void
    {
        $middleware = new CorsMiddleware(['https://example.com']);
        $request = new Request('OPTIONS', ['Origin' => 'https://example.com']);

        $response = $middleware->handle($request);

        $this->assertNotNull($response);
        $this->assertSame(204, $response->getStatusCode());
        $headers = $response->getHeaders();
        $this->assertSame('https://example.com', $headers['Access-Control-Allow-Origin'] ?? null);
    }

    public function testPreflightOptionsReturns403ForForbiddenOrigin(): void
    {
        $middleware = new CorsMiddleware(['https://example.com']);
        $request = new Request('OPTIONS', ['Origin' => 'https://unauthorized-site.com']);

        $response = $middleware->handle($request);

        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testEmptyOriginDirectBackendCallAllowed(): void
    {
        $middleware = new CorsMiddleware(['https://example.com']);
        $this->assertTrue($middleware->isOriginAllowed(''));
    }
}
