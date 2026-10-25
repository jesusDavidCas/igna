<?php

namespace App\Services\Security;

final readonly class TurnstileVerificationResult
{
    private function __construct(
        public bool $accepted,
        public bool $retryable,
        public string $reason,
    ) {}

    public static function accepted(): self
    {
        return new self(true, false, 'accepted');
    }

    public static function rejected(string $reason): self
    {
        return new self(false, false, $reason);
    }

    public static function unavailable(string $reason): self
    {
        return new self(false, true, $reason);
    }
}
