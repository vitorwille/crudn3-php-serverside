<?php

declare(strict_types=1);

namespace App\Http;

final class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly int $status,
        private readonly mixed $data = null,
        private readonly array $headers = [],
        private readonly bool $raw = false,
    ) {
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self($status, $data, [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($status, $html, [
            'Content-Type' => 'text/html; charset=utf-8',
        ], true);
    }

    public static function noContent(): self
    {
        return new self(204);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        if ($this->status === 204 || $this->data === null) {
            return;
        }

        if ($this->raw) {
            echo $this->data;

            return;
        }

        echo json_encode(
            $this->data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_PRETTY_PRINT,
        );
    }
}
