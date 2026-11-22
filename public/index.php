<?php declare(strict_types=1);

use App\Controller\AuthController;
use App\Controller\CategoriaController;
use App\Controller\ComponenteController;
use App\Controller\EquipamentoController;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Support\HttpException;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$debug = false;

try {
    /** @var array{em: \Doctrine\ORM\EntityManagerInterface, jwt_secret: string, jwt_ttl: int, debug: bool} $container */
    $container = require __DIR__ . '/../src/bootstrap.php';

    $debug = (bool) ($container['debug'] ?? false);
    $em = $container['em'];
    $jwtSecret = $container['jwt_secret'];
    $jwtTtl = $container['jwt_ttl'];

    $authController = new AuthController($em, $jwtSecret, $jwtTtl);
    $categoriaController = new CategoriaController($em);
    $componenteController = new ComponenteController($em);
    $equipamentoController = new EquipamentoController($em);

    $router = new Router();

    $router->get('/', static fn(): Response => Response::json([
        'status' => 'ok',
        'auth' => 'JWT Bearer',
        'endpoints' => [
            'POST                 -> /api/auth/login',
            'GET                  -> /api/auth/me',
            'GET|POST             -> /api/categorias',
            'GET|PUT|PATCH|DELETE -> /api/categorias/{id}',
            'GET                  -> /api/categorias/{id}/componentes',
            'GET|POST             -> /api/componentes',
            'GET|PUT|PATCH|DELETE -> /api/componentes/{id}',
            'GET                  -> /api/componentes?categoria={id}',
            'GET                  -> /api/componentes?equipamento={id}',
            'GET|POST             -> /api/equipamentos',
            'GET|PUT|PATCH|DELETE -> /api/equipamentos/{id}',
            'GET                  -> /api/equipamentos/{id}/componentes',
        ],
    ]));

    $router->get('/api/health', static fn(): Response => Response::json([
        'status' => 'ok',
        'data' => gmdate(DATE_ATOM),
    ]));

    $router->post('/api/auth/login', [$authController, 'login']);
    $router->get('/api/auth/me', [$authController, 'me']);

    $router->get('/api/categorias', [$categoriaController, 'index']);
    $router->post('/api/categorias', [$categoriaController, 'store']);
    $router->get('/api/categorias/{id}', [$categoriaController, 'show']);
    $router->put('/api/categorias/{id}', [$categoriaController, 'update']);
    $router->patch('/api/categorias/{id}', [$categoriaController, 'update']);
    $router->delete('/api/categorias/{id}', [$categoriaController, 'destroy']);
    $router->get('/api/categorias/{id}/componentes', [$categoriaController, 'componentes']);

    $router->get('/api/componentes', [$componenteController, 'index']);
    $router->post('/api/componentes', [$componenteController, 'store']);
    $router->get('/api/componentes/{id}', [$componenteController, 'show']);
    $router->put('/api/componentes/{id}', [$componenteController, 'update']);
    $router->patch('/api/componentes/{id}', [$componenteController, 'update']);
    $router->delete('/api/componentes/{id}', [$componenteController, 'destroy']);

    $router->get('/api/equipamentos', [$equipamentoController, 'index']);
    $router->post('/api/equipamentos', [$equipamentoController, 'store']);
    $router->get('/api/equipamentos/{id}', [$equipamentoController, 'show']);
    $router->put('/api/equipamentos/{id}', [$equipamentoController, 'update']);
    $router->patch('/api/equipamentos/{id}', [$equipamentoController, 'update']);
    $router->delete('/api/equipamentos/{id}', [$equipamentoController, 'destroy']);
    $router->get('/api/equipamentos/{id}/componentes', [$equipamentoController, 'componentes']);

    $request = Request::fromGlobals();

    $rotasPublicas = ['/api/health', '/api/auth/login'];

    if (str_starts_with($request->path, '/api') && !in_array($request->path, $rotasPublicas, true)) {
        $token = $request->bearerToken();

        if ($token === null) {
            throw new HttpException(
                401,
                'Token de autenticação não informado. Envie o cabeçalho Authorization: Bearer <token>.',
            );
        }

        try {
            $request->user = (array) JWT::decode($token, new Key($jwtSecret, 'HS256'));
        } catch (ExpiredException) {
            throw new HttpException(401, 'Token expirado. Faça login novamente.');
        } catch (\UnexpectedValueException|\DomainException|\InvalidArgumentException) {
            throw new HttpException(401, 'Token inválido.');
        }
    }

    $router->dispatch($request)->send();
} catch (HttpException $exception) {
    jsonError($exception->getStatus(), $exception->getMessage(), $exception->getDetails());
} catch (ConstraintViolationException $exception) {
    jsonError(409, 'A operação viola uma restrição do banco de dados.', debugDetails($exception, $debug));
} catch (ConnectionException $exception) {
    jsonError(503, 'Não foi possível conectar-se ao banco de dados.', debugDetails($exception, $debug));
} catch (Throwable $exception) {
    error_log((string) $exception);
    jsonError(500, 'Erro interno no servidor.', debugDetails($exception, $debug));
}

/**
 * @param array<string, mixed> $details
 */
function jsonError(int $status, string $message, array $details = []): void
{
    $body = ['erro' => $message];

    if ($details !== []) {
        $body['detalhes'] = $details;
    }

    Response::json($body, $status)->send();
}

/**
 * @return array<string, mixed>
 */
function debugDetails(Throwable $exception, bool $debug): array
{
    if (!$debug) {
        return [];
    }

    return ['detalhe' => $exception->getMessage()];
}
