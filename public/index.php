<?php

require __DIR__ . './../vendor/autoload.php';

use App\Infrastructure\Database\User\MySQLUserRepository;
use App\Infrastructure\Database\User\RedisUserRepository;
use App\Infrastructure\ServiceContainer;
use App\Infrastructure\Router;
use App\UI\Controllers\Auth\LoginController;
use App\Domain\Repositories\UserRepositoryInterface;
use App\UI\Controllers\HomeController;
use App\UI\Controllers\ParkingSpot\ParkingSpotController;
use App\Domain\Repositories\ParkingSpotRepositoryInterface;
use App\Infrastructure\Database\ParkingSpot\MySQLParkingSpotRepository;
use App\Infrastructure\Database\ParkingSpot\RedisParkingSpotRepository;

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

$router = $serviceContainer->get(Router::class);

$router->add('/', 'GET', [HomeController::class, 'welcome']);
$router->add('/login', 'POST', [LoginController::class, 'login']);
$router->add('/spots', 'GET', [ParkingSpotController::class, 'index']);

$router->dispatch($_SERVER['REQUEST_URI']);