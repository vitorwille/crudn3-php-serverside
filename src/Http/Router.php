<?php declare(strict_types=1);

namespace App\Http;

use App\Support\HttpException;

final class Router
{
    /**
     * @var list<array{method: string, pattern: string, regex: string, handler: callable}>
     */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, callable $handler): void
    {
        $this->add('PUT', $pattern, $handler);
    }

    public function patch(string $pattern, callable $handler): void
    {
        $this->add('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    public function add(string $method, string $pattern, callable $handler): void
    {
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static fn(array $matches): string => '(?P<' . $matches[1] . '>[^/]+)',
            $pattern,
        );

        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): Response
    {
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $request->path, $matches) !== 1) {
                continue;
            }

            if ($route['method'] !== $request->method) {
                $allowedMethods[] = $route['method'];
                continue;
            }

            $params = [];

            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            $request->params = $params;

            return ($route['handler'])($request);
        }

        if ($allowedMethods !== []) {
            throw new HttpException(405, 'Método não permitido para esta rota.', [
                'metodos_permitidos' => array_values(array_unique($allowedMethods)),
            ]);
        }

        throw new HttpException(404, 'Rota não encontrada.');
    }
}
