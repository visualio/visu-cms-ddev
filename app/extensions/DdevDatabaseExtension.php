<?php

namespace App\Extensions;

use Nette\DI\CompilerExtension;
use Nette\Database\Connection;
use Nette\Database\Explorer;
use Nette\Database\Structure;
use Nette\Database\Conventions\DiscoveredConventions;

class DdevDatabaseExtension extends CompilerExtension
{
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();

		$isDdev = isset($_SERVER['DDEV_VERSION']);

		if ($isDdev) {
			$dbName = $_SERVER['DDEV_PROJECT'];
			$connection = $builder->addDefinition($this->prefix('connection'))
				->setFactory(Connection::class, [
					'dsn' => "mysql:host=db;dbname=$dbName;port=3306",
					'user' => 'db',
					'password' => 'db',
				]);

			$builder->addDefinition($this->prefix('explorer'))
				->setFactory(Explorer::class, [
					$connection,
					$builder->addDefinition($this->prefix('structure'))
						->setFactory(Structure::class, [$connection]),
					$builder->addDefinition($this->prefix('conventions'))
						->setFactory(DiscoveredConventions::class),
				]);
		}
	}
}