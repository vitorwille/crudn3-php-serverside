<?php declare(strict_types=1);

namespace App\Http;

use App\Support\HttpException;

final class Request
{
    /**
     * @param array<string, mixed>  $query
     * @param array<string, mixed>  $body
     * @param array<string, string> $headers
     */
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $headers,
    ) {}

    /**
     * query params (ex: /api/componentes/{id}).
     *
     * @var array<string, string>
     */
    public array $params = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $user = null;

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);
        $path = rtrim($path, '/');

        if ($path === '') {
            $path = '/';
        }

        $headers = [];

        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (is_string($name) && is_string($value)) {
                    $headers[strtolower($name)] = $value;
                }
            }
        }

        $authorization = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? null;

        if ($authorization !== null && !isset($headers['authorization'])) {
            $headers['authorization'] = $authorization;
        }

        $rawBody = file_get_contents('php://input');
        $body = [];

        if ($rawBody !== false && trim($rawBody) !== '') {
            $decoded = json_decode($rawBody, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new HttpException(400, 'O body desta requisição não é um JSON válido.');
            }

            if (!is_array($decoded)) {
                throw new HttpException(400, 'O body desta requisição deve ser em formato JSON.');
            }

            $body = $decoded;
        }

        return new self($method, $path, $_GET, $body, $headers);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->body);
    }

    public function queryParam(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('authorization');

        if ($header === null) {
            return null;
        }

        if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches) !== 1) {
            return null;
        }

        return trim($matches[1]);
    }
}
