<?php

$envFile = __DIR__ . '/../.env';
$envExampleFile = __DIR__ . '/../.env.example';

if (!file_exists($envFile)) {
    copy($envExampleFile, $envFile);
    echo "Created .env file \n";
}

$secretKey = bin2hex(random_bytes(16));

$envContent = file_get_contents($envFile);

$envContent = preg_replace(
    '/^JWT_SECRET=.*$/m', 
    'JWT_SECRET=' . $secretKey, 
    $envContent
);

file_put_contents($envFile, $envContent);
echo "JWT_SECRET generated and saved to .env successfully!\n";