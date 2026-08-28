<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Config\Config;
use App\Controllers\ContactController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Request;
use App\Services\MailerServiceInterface;
use App\Validation\Validator;
use PHPUnit\Framework\TestCase;

/**
 * Tests d'intégration pour le contrôleur principal ContactController.
 */
class ContactControllerTest extends TestCase
{
    private string $apiKey = 'test_secret_api_key_xyz';
    private MailerServiceInterface $mockMailer;
    private ContactController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('app.api_key', $this->apiKey);
        Config::set('app.allowed_origins', ['https://example.com']);
        Config::set('app.debug', false);

        $this->mockMailer = $this->createMock(MailerServiceInterface::class);

        $this->controller = new ContactController(
            new CorsMiddleware(['https://example.com']),
            new AuthMiddleware($this->apiKey),
            new Validator(),
            $this->mockMailer
        );
    }

    public function testRejectsNonPostRequests(): void
    {
        $request = new Request('GET', ['X-API-KEY' => $this->apiKey]);
        $response = $this->controller->handle($request);

        $this->assertSame(405, $response->getStatusCode());
        $this->assertFalse($response->getData()['success']);
    }

    public function testRejectsUnauthorizedRequest(): void
    {
        $request = new Request('POST', ['X-API-KEY' => 'bad_key'], ['fullName' => 'Test']);
        $response = $this->controller->handle($request);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testRejectsInvalidJsonFormat(): void
    {
        $request = new Request('POST', ['X-API-KEY' => $this->apiKey], null, '{invalid_json');
        $response = $this->controller->handle($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertStringContainsString('Format de données invalide', $response->getData()['error']);
    }

    public function testRejectsMissingRequiredFields(): void
    {
        $payload = [
            'fullName' => 'Jean Dupont',
            // 'email' manquant
            'phone' => '0102030405',
            'message' => 'Ceci est un message de test.',
        ];

        $request = new Request('POST', ['X-API-KEY' => $this->apiKey], $payload);
        $response = $this->controller->handle($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertArrayHasKey('email', $response->getData()['errors']);
    }

    public function testHoneypotBotSpamReturnsSuccessWithoutSendingEmail(): void
    {
        $payload = [
            'fullName' => 'Robot Spammer',
            'email' => 'bot@spammer.com',
            'phone' => '0102030405',
            'message' => 'Spam content here',
            '_gotcha' => 'http://spam-link.ru', // Piège honeypot
        ];

        // On vérifie que le service d'envoi d'email n'est JAMAIS appelé
        $this->mockMailer->expects($this->never())->method('send');

        $request = new Request('POST', ['X-API-KEY' => $this->apiKey], $payload);
        $response = $this->controller->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData()['success']);
    }

    public function testValidSubmissionSendsEmailAndReturnsSuccess(): void
    {
        $payload = [
            'fullName' => 'Claire Dupont',
            'email' => 'claire@example.com',
            'phone' => '+33 6 98 76 54 32',
            'subject' => 'Demande d\'informations',
            'message' => 'Bonjour, je souhaite des informations sur vos prestations.',
        ];

        // Le service d'envoi doit être appelé une fois et réussir
        $this->mockMailer->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $request = new Request('POST', [
            'X-API-KEY' => $this->apiKey,
            'Origin' => 'https://example.com'
        ], $payload);

        $response = $this->controller->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData()['success']);
        $this->assertSame('https://example.com', $response->getHeaders()['Access-Control-Allow-Origin'] ?? null);
    }

    public function testServerErrorWhenMailerFails(): void
    {
        $payload = [
            'fullName' => 'Claire Dupont',
            'email' => 'claire@example.com',
            'phone' => '+33 6 98 76 54 32',
            'message' => 'Bonjour, test échec envoi.',
        ];

        $this->mockMailer->expects($this->once())
            ->method('send')
            ->willReturn(false);

        $this->mockMailer->method('getLastError')
            ->willReturn('SMTP Connection refused');

        $request = new Request('POST', ['X-API-KEY' => $this->apiKey], $payload);
        $response = $this->controller->handle($request);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertFalse($response->getData()['success']);
        // En mode debug=false, l'erreur technique ne doit pas fuiter
        $this->assertStringNotContainsString('SMTP Connection refused', $response->getData()['error']);
    }
}
