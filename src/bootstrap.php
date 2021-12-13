<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

$app = AppFactory::create();

$twig = Twig::create(__DIR__);

$app->add(TwigMiddleware::create($app, $twig));

require __DIR__ . '/Actions.php';
