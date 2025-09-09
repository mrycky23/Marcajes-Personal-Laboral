<?php
use Psr\Container\ContainerInterface;

return [
    PDO::class => function (ContainerInterface $c) {
        $settings = require __DIR__ . '/settings.php';
        $db = $settings['db'];
        $pdo = new PDO(
            "{$db['driver']}:host={$db['host']};dbname={$db['database']};charset={$db['charset']}",
            $db['username'],
            $db['password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    },
];
