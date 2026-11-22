<?php declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class HttpException extends RuntimeException
{
    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        private readonly int $status,
        string $message,
        private readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}
