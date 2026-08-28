<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Config\Config;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour la classe App\Config\Config.
 */
class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::reset();
    }

    protected function tearDown(): void
    {
        Config::reset();
        parent::tearDown();
    }

    public function testGetReturnsDefaultValueWhenKeyNotFound(): void
    {
        $value = Config::get('non_existent_key', 'default_val');
        $this->assertSame('default_val', $value);
    }

    public function testSetAndGetWithDotNotation(): void
    {
        Config::set('mailer.smtp.host', 'smtp.test.com');
        Config::set('mailer.smtp.port', 587);

        $this->assertSame('smtp.test.com', Config::get('mailer.smtp.host'));
        $this->assertSame(587, Config::get('mailer.smtp.port'));
    }

    public function testEnvCasting(): void
    {
        $_ENV['TEST_BOOL_TRUE'] = 'true';
        $_ENV['TEST_BOOL_FALSE'] = 'false';
        $_ENV['TEST_NULL'] = 'null';
        $_ENV['TEST_STRING'] = 'Hello';

        $this->assertTrue(Config::env('TEST_BOOL_TRUE'));
        $this->assertFalse(Config::env('TEST_BOOL_FALSE'));
        $this->assertNull(Config::env('TEST_NULL'));
        $this->assertSame('Hello', Config::env('TEST_STRING'));

        unset($_ENV['TEST_BOOL_TRUE'], $_ENV['TEST_BOOL_FALSE'], $_ENV['TEST_NULL'], $_ENV['TEST_STRING']);
    }
}
