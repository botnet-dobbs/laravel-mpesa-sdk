<?php

namespace Botnetdobbs\Mpesa\Tests\Unit;

use Botnetdobbs\Mpesa\Services\InitiatorCredentialGenerator;
use Botnetdobbs\Mpesa\Tests\TestCase;
use InvalidArgumentException;
use RuntimeException;

class InitiatorCredentialGeneratorTest extends TestCase
{
    private string $certificatePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->certificatePath = __DIR__ . '/test.cer';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->certificatePath)) {
            unlink($this->certificatePath);
        }

        parent::tearDown();
    }

    public function testItGeneratesSecurityCredential(): void
    {
        $config = [
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        $key = openssl_pkey_new($config);
        $csr = openssl_csr_new([], $key);
        $cert = openssl_csr_sign($csr, null, $key, 365);
        openssl_x509_export($cert, $certOut);
        file_put_contents($this->certificatePath, $certOut);

        config(['mpesa.certificate_path' => $this->certificatePath, 'mpesa.initiator_password' => 'test']);

        $generator = new InitiatorCredentialGenerator(app('cache'));
        $credential = $generator->generate();

        $this->assertNotEmpty($credential);
    }

    public function testThrowsExceptionForMissingCertificate(): void
    {
        config([
            'mpesa.certificate_path' => '/invalid/path',
            'mpesa.initiator_password' => 'test'
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Mpesa certificate not found');

        $generator = new InitiatorCredentialGenerator(app('cache'));
        $generator->generate();
    }

    public function testThrowsExceptionForMissingInitiatorPassword(): void
    {
        config([
            'mpesa.certificate_path' => $this->certificatePath,
            'mpesa.initiator_password' => ''
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Mpesa initiator password not configured');

        $generator = new InitiatorCredentialGenerator(app('cache'));
        $generator->generate();
    }

    public function testThrowsExceptionForInvalidCertificate(): void
    {
        file_put_contents($this->certificatePath, "INVALID CERTIFICATE");

        config([
            'mpesa.certificate_path' => $this->certificatePath,
            'mpesa.initiator_password' => 'test'
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid Mpesa certificate');

        $generator = new InitiatorCredentialGenerator(app('cache'));
        $generator->generate();
    }
}
