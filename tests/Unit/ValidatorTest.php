<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\Validator;
use App\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour la classe App\Validation\Validator.
 */
class ValidatorTest extends TestCase
{
    private array $sampleRules;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sampleRules = [
            'fullName' => [
                'label' => 'Nom et prénom',
                'rules' => ['required', 'string', 'min:2', 'max:50'],
            ],
            'email' => [
                'label' => 'Email',
                'rules' => ['required', 'email'],
            ],
            'phone' => [
                'label' => 'Téléphone',
                'rules' => ['optional', 'phone'],
            ],
            'message' => [
                'label' => 'Message',
                'rules' => ['required', 'string', 'min:5'],
            ],
        ];
    }

    public function testValidDataPasses(): void
    {
        $validator = new Validator($this->sampleRules, true, '_gotcha');
        $validData = [
            'fullName' => 'Jean Dupont',
            'email' => 'jean.dupont@example.com',
            'phone' => '+33 6 12 34 56 78',
            'message' => 'Bonjour, ceci est un test.',
        ];

        $this->assertTrue($validator->validate($validData));
        $this->assertEmpty($validator->getErrors());
        $this->assertFalse($validator->isSpam());
        $this->assertSame('Jean Dupont', $validator->getValidatedData()['fullName']);
    }

    public function testMissingRequiredFieldFails(): void
    {
        $validator = new Validator($this->sampleRules, true, '_gotcha');
        $invalidData = [
            'fullName' => '',
            'email' => 'jean.dupont@example.com',
            'message' => 'Bonjour',
        ];

        $this->assertFalse($validator->validate($invalidData));
        $this->assertArrayHasKey('fullName', $validator->getErrors());
        $this->assertStringContainsString('obligatoire', $validator->getFirstError());
    }

    public function testInvalidEmailFormatFails(): void
    {
        $validator = new Validator($this->sampleRules, true, '_gotcha');
        $invalidData = [
            'fullName' => 'Jean Dupont',
            'email' => 'email-non-valide',
            'message' => 'Bonjour',
        ];

        $this->assertFalse($validator->validate($invalidData));
        $this->assertArrayHasKey('email', $validator->getErrors());
    }

    public function testStringMinLengthFails(): void
    {
        $validator = new Validator($this->sampleRules, true, '_gotcha');
        $invalidData = [
            'fullName' => 'J', // min est 2
            'email' => 'jean@example.com',
            'message' => 'Bonjour',
        ];

        $this->assertFalse($validator->validate($invalidData));
        $this->assertArrayHasKey('fullName', $validator->getErrors());
    }

    public function testDeveloperCanAddCustomDynamicFields(): void
    {
        $validator = new Validator($this->sampleRules, true, '_gotcha');
        
        // Ajout d'un champ personnalisé "company" obligatoire
        $validator->setFieldRule('company', 'Entreprise', ['required', 'string', 'min:3']);

        $dataWithoutCompany = [
            'fullName' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'message' => 'Bonjour monde',
        ];

        $this->assertFalse($validator->validate($dataWithoutCompany));
        $this->assertArrayHasKey('company', $validator->getErrors());

        $dataWithCompany = array_merge($dataWithoutCompany, ['company' => 'Google France']);
        $this->assertTrue($validator->validate($dataWithCompany));
    }

    public function testHoneypotDetectsBotSpam(): void
    {
        $validator = new Validator($this->sampleRules, true, '_gotcha');
        $spamData = [
            'fullName' => 'Bot Spammer',
            'email' => 'bot@spammer.com',
            'message' => 'Buy cheap pills',
            '_gotcha' => 'http://spam-link.com', // Champ piège rempli
        ];

        $this->assertTrue($validator->validate($spamData));
        $this->assertTrue($validator->isSpam());
    }

    public function testValidateOrFailThrowsExceptionOnInvalidData(): void
    {
        $this->expectException(ValidationException::class);

        $validator = new Validator($this->sampleRules, true, '_gotcha');
        $validator->validateOrFail(['fullName' => '']);
    }
}
