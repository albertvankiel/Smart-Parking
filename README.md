# Smart Parking System

Web application for managing parking structure

## Requirements
To run this application locally, you will need the following installed on your machine:
- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)
- [Composer](https://getcomposer.org/download/)

## Local Development Setup
This project uses Docker Compose to orchestrate the PHP application, Nginx web server, MySQL database, and Redis.

## Starting the application
Start the application with docker compose:
```bash
docker-compose up -d --build
```
This will spin up the necessary containers, as well as automatically run the database migrations and seeds.

Install necessary dependencies and set up auto-wiring with Composer.
```bash
composer install
```
This will also automatically create a .env file which contains a randomly generated secret token for JWT and set Xdebug to "off" by default.

## Debugging with Xdebug
To enable Xdebug, set the following value in the .env file:
```bash
XDEBUG=debug
```
Then re-run docker compose