<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\EmailTemplate;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour la classe App\Services\EmailTemplate.
 */
class EmailTemplateTest extends TestCase
{
    private EmailTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->template = new EmailTemplate([
            'fullName' => ['label' => 'Nom complet'],
            'email' => ['label' => 'Email de contact'],
            'message' => ['label' => 'Message'],
        ]);
    }

    public function testRenderHtmlEscapesMaliciousXssInput(): void
    {
        $maliciousData = [
            'fullName' => '<script>alert("XSS")</script>',
            'email' => 'hacker@test.com',
            'message' => '<img src="x" onerror="alert(1)"> Ceci est <b>important</b> & "test".',
        ];

        $html = $this->template->renderHtml($maliciousData);

        // Vérification qu'aucune balise brute dangereuse n'est présente
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src="x"', $html);
        $this->assertStringNotContainsString('<b>important</b>', $html);

        // Vérification de la présence des entités échappées
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $html);
        $this->assertStringContainsString('&lt;img src=&quot;x&quot;', $html);
        $this->assertStringContainsString('&amp; &quot;test&quot;.', $html);
    }

    public function testBuildSubjectSanitizesCrlfHeaderInjection(): void
    {
        $injectedData = [
            'fullName' => "Hacker\r\nBcc: victim@example.com\r\nSubject: Injected",
        ];

        $subject = $this->template->buildSubject($injectedData);

        $this->assertStringNotContainsString("\r", $subject);
        $this->assertStringNotContainsString("\n", $subject);
        $this->assertStringContainsString('Hacker  Bcc: victim@example.com  Subject: Injected', $subject);
    }

    public function testRenderPlainTextContainsAllFields(): void
    {
        $data = [
            'fullName' => 'Alice Martin',
            'email' => 'alice@example.com',
            'custom_budget' => '5000 €',
            'message' => 'Demande de devis pour un projet.',
        ];

        $plainText = $this->template->renderPlainText($data);

        $this->assertStringContainsString('Alice Martin', $plainText);
        $this->assertStringContainsString('alice@example.com', $plainText);
        $this->assertStringContainsString('Custom budget : 5000 €', $plainText);
        $this->assertStringContainsString('Demande de devis pour un projet.', $plainText);
    }
}
