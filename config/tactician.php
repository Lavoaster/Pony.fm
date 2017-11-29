<?php


return [
    'middleware' => [
        League\Tactician\Logger\LoggerMiddleware::class,
//        Lavoaster\LaravelTactician\Middleware\ValidateCommandMiddleware::class,
        Lavoaster\LaravelTactician\Middleware\QueueCommandMiddleware::class,
        League\Tactician\Plugins\LockingMiddleware::class,
//        League\Tactician\Doctrine\ORM\TransactionMiddleware::class,
        League\Tactician\Handler\CommandHandlerMiddleware::class,
    ],

    'locator' => Lavoaster\LaravelTactician\Locators\ContainerLocator::class,

    'mapping' => [
        //
    ],
];
