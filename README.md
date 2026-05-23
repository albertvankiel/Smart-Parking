# Smart Parking System

Web application for managing parking structure

## Features & Architecture Highlights

* **Real-Time WebSockets:** Features a dedicated Node.js/Socket.io microservice. When a reservation is created or released in PHP, an internal event is broadcasted to the WebSocket server, instantly updating the Vue UI for all connected users without requiring a page refresh.
* **Concurrency & Data Integrity:** Implements MySQL Pessimistic Locking (`SELECT ... FOR UPDATE`) within database transactions. This guarantees that two users attempting to book the exact same spot at the exact same millisecond will never result in a double-booking.
* **Micro-Frontend Architecture:** Embeds Vue 3 application into the provided legacy Vanilla JS routing structure using Vite, communicating via custom DOM events.
* **Automated Background Worker:** Includes a continuous PHP daemon (`worker.php`) running in its own Docker container. It periodically polls the database to automatically complete "stale" reservations once their end time passes, instantly freeing up the spot and broadcasting the release via WebSockets.
* **Domain-Driven Design (DDD):** The PHP backend is built from scratch without a heavy framework, utilizing the Repository Pattern, a custom Dependency Injection Container, Enums, and strict typing (PHP 8.2) to ensure a decoupled and maintainable codebase.
* **High-Performance Caching:** Utilizes the Decorator pattern and Redis Hashes to cache parking spot metadata, significantly reducing read load on the primary MySQL database.
* **Secure Authentication:** Stateless JWT-based authentication with explicit mitigations against timing attacks during the login flow.

## Requirements
To run this application locally, you will need the following installed on your machine:
- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)
- [Composer](https://getcomposer.org/download/)
- [NPM](https://docs.npmjs.com/downloading-and-installing-node-js-and-npm)

## Local Development Setup
This project uses Docker Compose to orchestrate the PHP application, Nginx web server, MySQL database, and Redis.

## Getting started
Install necessary dependencies and set up auto-wiring with Composer.
```bash
composer install
```
This will also automatically create a .env file which contains a randomly generated secret token for JWT and set Xdebug to "off" by default.

If you do not have PHP on your local machine you can run this part using Docker:
```bash
docker run --rm -v $(pwd):/app -w /app composer:2.7 sh -c "composer install && php bin/setup.php"
```

Build the frontend:
```bash
cd frontend && npm install && npm run build
```

## Starting the application
Start the application with docker compose:
```bash
docker-compose up -d --build
```
This will spin up the necessary containers, as well as automatically run the database migrations and seeds.

The values in the .env.example file should work with the services configured with Docker.

Navigate to [http://localhost:8080](http://localhost:8080) to use the application in your browser.

## Test Credentials
You can log in using the seeded test accounts:
- `driver1@parking.com` / `password123`
- `driver2@parking.com` / `password123`

## Debugging with Xdebug
To enable Xdebug, set the following value in the .env file:
```bash
XDEBUG_MODE=debug
```
Then restart the app container:
```bash
docker-compose restart app
```

## Background worker
The automated background worker (daemon) checks periodically for stale reservations and updates them where necessary. 
To check the logs, run the following command with docker compose:
```bash
docker-compose logs -f worker
```
It will output a log of the auto release cycle:
```
[2026-05-16 16:54:05] Auto-released Spot #3 (Reservation ID 24)
```
## Testing
Integration testing for simulating parallel requests can be done with PHPUnit. It must be run using docker-compose:
```bash
docker-compose exec app vendor/bin/phpunit tests/Integration/PessimisticLockTest.php
```

## Code quality
To verify the quality of the code, use PHPStan and PHPCS:
```bash
php ./vendor/bin/phpstan analyse ./src
php ./vendor/bin/phpcs ./src
```
