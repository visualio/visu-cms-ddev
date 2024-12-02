<?php

namespace App\Services;

use Nette\Utils\FileSystem;
use Nette\Utils\Html;
use Nette\Utils\Json;

class ViteAssets
{
	public function __construct(
		private readonly int    $port,
		private readonly string $wwwDir,
		private readonly bool   $isDockerized
	)
	{
	}

	private function isDevServerRunning(string $module, string $entry): bool
	{
		if (isset($_SERVER['DDEV_HOSTNAME'])) {
			$protocol = 'http';
			$host = 'localhost';
		} else {
			$protocol = 'http';
			$host = 'localhost';
		}
		$url = $protocol . '://' . $host . ':' . $this->port . '/dev/' . $module . '/' . $entry;
		$result = @file_get_contents($url);
		return $result !== false;
	}

	public function printFrontTags(string $entrypoint): void
	{
		$this->printTags('front', $entrypoint);
	}

	public function printAdminTags(string $entrypoint): void
	{
		$this->printTags('admin', $entrypoint);
	}

	private function printTags(string $module, string $entry): void
	{
		$scripts = [];
		$styles = [];
		$baseUrl = "/dist/$module/";
		$manifestFile = $this->wwwDir . "/dist/" . $module . '/manifest.json';

		if ($this->isDevServerRunning($module, $entry)) {
			$baseUrl = 'http://localhost:' . $this->port . '/dev/' . $module . '/';
			$scripts = ['@vite/client', $entry];
		} else {
			if (file_exists($manifestFile)) {
				$manifest = Json::decode(FileSystem::read($manifestFile), Json::FORCE_ARRAY);
				$scripts = [$manifest[$entry]['file']];
				$styles = $manifest[$entry]['css'] ?? [];
			} else {
				trigger_error('Missing manifest file: ' . $manifestFile, E_USER_WARNING);
			}
		}

		foreach ($styles as $path) {
			echo Html::el('link')->rel('stylesheet')->href($baseUrl . $path);
		}

		foreach ($scripts as $path) {
			echo Html::el('script')->type('module')->src($baseUrl . $path);
		}
	}
}