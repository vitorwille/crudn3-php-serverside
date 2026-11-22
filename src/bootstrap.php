<?php declare(strict_types=1);

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';
$root = dirname(__DIR__);

$dotenv = Dotenv::createImmutable($root);
$dotenv->safeLoad();

$env = static function (string $key, ?string $default = null): ?string {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return (string) $value;
};

$isDevMode = filter_var($env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOL);

$config = ORMSetup::createAttributeMetadataConfiguration(
    [$root . '/src/Entity'],
    $isDevMode,
);
$config->enableNativeLazyObjects(true);

$connection = DriverManager::getConnection([
    'driver' => 'pdo_mysql',
    'host' => $env('DB_HOST', '127.0.0.1'),
    'port' => (int) $env('DB_PORT', '3306'),
    'dbname' => $env('DB_DATABASE', 'n3_serverside'),
    'user' => $env('DB_USERNAME', 'root'),
    'password' => $env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
], $config);

$entityManager = new EntityManager($connection, $config);

$jwtSecret = (string) $env('JWT_SECRET', '');

if ($jwtSecret === '') {
    throw new RuntimeException('JWT_SECRET não configurado.');
}

return [
    'em' => $entityManager,
    'jwt_secret' => $jwtSecret,
    'jwt_ttl' => (int) $env('JWT_TTL', '3600'),
    'connection' => $connection,
    'env' => $env,
    'debug' => $isDevMode,
];
