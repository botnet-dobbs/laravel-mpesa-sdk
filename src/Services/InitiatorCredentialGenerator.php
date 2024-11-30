<?php

namespace Botnetdobbs\Mpesa\Services;

use Illuminate\Cache\CacheManager;
use RuntimeException;

class InitiatorCredentialGenerator
{
    private string $environment;
    private string $initiatorPassword;
    private string $certificatePath;

    public function __construct(private CacheManager $cache)
    {
        $this->environment = (string) config('mpesa.environment'); // @phpstan-ignore-line
        $this->initiatorPassword = (string) config('mpesa.initiator_password'); // @phpstan-ignore-line
        $this->certificatePath = (string) config('mpesa.certificate_path'); // @phpstan-ignore-line
    }

    public function generate(): string
    {
        $cacheKey = "mpesa_initiator_credential_{$this->environment}_{$this->initiatorPassword}";

        return $this->cache->remember($cacheKey, now()->addHours(24), function () {
            if (!$this->initiatorPassword) {
                throw new \InvalidArgumentException('Mpesa initiator password not configured.');
            }

            if (!$this->certificatePath || !file_exists($this->certificatePath)) {
                throw new RuntimeException('Mpesa certificate not found.');
            }

            /** @var string $cert */
            $cert = file_get_contents($this->certificatePath);
            $pubKeyId = openssl_pkey_get_public($cert);

            if (!$pubKeyId) {
                throw new RuntimeException('Invalid Mpesa certificate');
            }

            openssl_public_encrypt($this->initiatorPassword, $encrypted, $pubKeyId, OPENSSL_PKCS1_PADDING);
            return base64_encode($encrypted);
        });
    }
}
