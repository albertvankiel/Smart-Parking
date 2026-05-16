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
    return new \PDO('mysql:host=db;dbname=smart_parking', 'root', 'root');
});

$serviceContainer->bind(\Redis::class, function() {
    $redis = new \Redis();
    $redis->connect('redis', 6379);

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

$router->add('/', 'GET', [HomeController::class, 'welcome']);
$router->add('/login', 'POST', [LoginController::class, 'login']);
$router->add('/spots', 'GET', [ParkingSpotController::class, 'index']);
$router->add('/reservations', 'POST', [ReservationController::class, 'store']);

$router->dispatch($_SERVER['REQUEST_URI']);