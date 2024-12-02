<?php

declare(strict_types=1);

namespace App;

use Nette\Bootstrap\Configurator;

class Bootstrap
{
	public static function boot(): Configurator
	{
		$configurator = new Configurator;
		$configurator->setDebugMode(true); // enable for your remote IP
		$configurator->enableTracy(__DIR__ . '/../log');
		$configurator->setTimeZone('Europe/Prague');
		$configurator->setTempDirectory(__DIR__ . '/../temp');
		$configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->register();
		$configurator->addConfig(__DIR__ . '/config/main.neon');
		$configurator->addParameters([
             'rootDir' => realpath(__DIR__ . '/..'),
             'appDir' => __DIR__,
             'wwwDir' => realpath(__DIR__ . '/../www'),
         ]);
		return $configurator;
	}
}