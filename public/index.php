<?php

require __DIR__ . './../vendor/autoload.php';

// Domain Layer (Interfaces & Models)
use App\Domain\Repositories\ParkingSpotRepositoryInterface;
use App\Domain\Repositories\ReservationRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;

// Infrastructure Layer (Database & Core Services)
use App\Infrastructure\Database\ParkingSpot\MySQLParkingSpotRepository;
use App\Infrastructure\Database\ParkingSpot\RedisParkingSpotRepository;
use App\Infrastructure\Database\Reservation\MySQLReservationRepository;
use App\Infrastructure\Database\User\MySQLUserRepository;
use App\Infrastructure\Database\User\RedisUserRepository;
use App\Infrastructure\Router;
use App\Infrastructure\ServiceContainer;

// UI Layer (Controllers)
use App\UI\Controllers\Auth\LoginController;
use App\UI\Controllers\HomeController;
use App\UI\Controllers\ParkingSpot\ParkingSpotController;
use App\UI\Controllers\Reservation\ReservationController;

$serviceContainer = new ServiceContainer();

$serviceContainer->bind(ServiceContainer::class, function() use ($serviceContainer) {
    return $serviceContainer;
});

$serviceContainer->bind(\PDO::class, function() {
    $host = getenv('MYSQL_HOST');
    $db = getenv('MYSQL_DB');
    $user = getenv('MYSQL_USER');
    $pw = getenv('MYSQL_PASSWORD');
    return new \PDO("mysql:host={$host};dbname={$db}", $user, $pw);
});

$serviceContainer->bind(\Redis::class, function() {
    $redisHost = getenv('REDIS_HOST');
    $redisPort = getenv('REDIS_PORT');

    $redis = new \Redis();
    $redis->connect($redisHost, $redisPort);

    return $redis;
});

$serviceContainer->bind(UserRepositoryInterface::class, function($serviceContainer) {
    $pdo = $serviceContainer->get(\PDO::class);
    $mysqlRepository = new MySQLUserRepository($pdo);

    $redis = $serviceContainer->get(\Redis::class);
    return new RedisUserRepository($redis, $mysqlRepository);
});

$serviceContainer->bind(ParkingSpotRepositoryInterface::class, function($serviceContainer) {
    $pdo = $serviceContainer->get(\PDO::class);
    $mysqlRepository = new MySQLParkingSpotRepository($pdo);
    
    $redis = $serviceContainer->get(\Redis::class);
    return new RedisParkingSpotRepository($mysqlRepository, $redis);
});

$serviceContainer->bind(ReservationRepositoryInterface::class, function($serviceContainer) {
    $pdo = $serviceContainer->get(\PDO::class);
    return new MySQLReservationRepository($pdo);
});

$router = $serviceContainer->get(Router::class);

$router->add('/api/', 'GET', [HomeController::class, 'welcome']);
$router->add('/api/login', 'POST', [LoginController::class, 'login']);
$router->add('/api/spots', 'GET', [ParkingSpotController::class, 'index']);
$router->add('/api/reservations', 'POST', [ReservationController::class, 'store']);
$router->add('/api/reservations', 'GET', [ReservationController::class, 'index']);
$router->add('/api/reservations/{id}/complete', 'PUT', [ReservationController::class, 'complete']);

$router->dispatch(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));