<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PHPMailerService;
use App\Services\EmailTemplate;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour la classe App\Services\PHPMailerService.
 */
class PHPMailerServiceTest extends TestCase
{
    public function testSendCallsMailerMethodsCorrectly(): void
    {
        // Création d'un mock partiel de PHPMailer pour simuler l'envoi
        $mockMailer = $this->getMockBuilder(PHPMailer::class)
            ->onlyMethods(['send'])
            ->getMock();

        $mockMailer->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $service = new PHPMailerService(new EmailTemplate(), $mockMailer);

        $result = $service->send([
            'fullName' => 'Thomas Robert',
            'email' => 'thomas@example.com',
            'message' => 'Test message',
        ]);

        $this->assertTrue($result);
        $this->assertNull($service->getLastError());
    }

    public function testSendFailsGracefullyWhenMailerErrors(): void
    {
        $mockMailer = $this->getMockBuilder(PHPMailer::class)
            ->onlyMethods(['send'])
            ->getMock();

        $mockMailer->ErrorInfo = 'SMTP connect() failed.';
        $mockMailer->expects($this->once())
            ->method('send')
            ->willReturn(false);

        $service = new PHPMailerService(new EmailTemplate(), $mockMailer);

        $result = $service->send([
            'fullName' => 'Thomas Robert',
            'email' => 'thomas@example.com',
            'message' => 'Test message',
        ]);

        $this->assertFalse($result);
        $this->assertSame('SMTP connect() failed.', $service->getLastError());
    }
}
