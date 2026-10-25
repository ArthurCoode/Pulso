<?php

namespace App\Services;

final readonly class CheckResult
{
    public function __construct(
        public bool $isSuccessful,
        public ?int $statusCode = null,
        public ?int $responseTimeMs = null,
        public ?string $error = null,
    ) {}
}   