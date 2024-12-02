<?php

echo "Welcome to the Visualio App setup!\n\n";

// Dotazy na vstupy s výchozími hodnotami
$projectName = readline("Enter project name [default: visu-app]: ");
$projectName = $projectName ?: 'visu-app';

$phpVersion = readline("PHP version [default: 8.2]: ");
$phpVersion = $phpVersion ?: '8.2';

$nodeVersion = readline("Node.js version [default: 16]: ");
$nodeVersion = $nodeVersion ?: '16';

$httpPort = readline("HTTP port [default: 8080]: ");
$httpPort = $httpPort ?: '8080';

$httpsPort = readline("HTTPS port [default: 8444]: ");
$httpsPort = $httpsPort ?: '8444';

$vitePort = readline("Vite port [default: 3333]: ");
$vitePort = $vitePort ?: '3333';

// Vytvoření obsahu .env
$envContent = <<<EOL
PROJECT_NAME=$projectName
PHP_VERSION=$phpVersion
NODE_VERSION=$nodeVersion
HTTP_PORT=$httpPort
HTTPS_PORT=$httpsPort
VITE_PORT=$vitePort
EOL;

file_put_contents('.env', $envContent);
echo "\n.env file created with the following content:\n";
echo $envContent . "\n";

if (!shell_exec("command -v npm")) {
	echo "\nError: npm is not installed or not in PATH. Please install Node.js before proceeding.\n";
	exit(1);
}

echo "\nRunning build script...\n";
$buildOutput = shell_exec("npm run ddev:build 2>&1");

if ($buildOutput === null) {
	echo "\nError: Failed to execute build script. Please check your npm setup.\n";
	exit(1);
}

echo "\nBuild script output:\n";
echo $buildOutput;

echo "\nBuild completed successfully. You're ready to go!\n";